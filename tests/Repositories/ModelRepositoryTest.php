<?php

namespace Heritage\Tests\Repositories;

use Heritage\Config\Repository;
use Heritage\Container\Container;
use Heritage\Database\Capsule\Manager as DB;
use Heritage\Database\Eloquent\Collection;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\Eloquent\ModelNotFoundException;
use Heritage\Database\Eloquent\SoftDeletes;
use Heritage\Database\Schema\Blueprint;
use Heritage\Pagination\LengthAwarePaginator;
use Heritage\Parcels\Parcel;
use Heritage\Parcels\QueryParcel;
use Heritage\Repositories\ModelRepository;
use Heritage\Repositories\TranslationRepository;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

/**
 * Parcels in, models out: provided keys, create/update, null clearing, find and destroy.
 */
class ModelRepositoryTest extends TestCase
{
    protected DB $db;

    protected Container $previousContainer;

    /**
     * Boot an in-memory database with a soft-deletable notes table and a translatable topics table.
     */
    protected function setUp(): void
    {
        $this->previousContainer = Container::getInstance();

        $container = new Container;
        Container::setInstance($container);
        $container->instance('config', new Repository([
            'locale' => [
                'default_locale' => 'ar',
                'fallback_locale' => 'en',
                'available_locales' => ['ar' => ['native' => 'العربية'], 'en' => ['native' => 'English']],
            ],
        ]));

        $this->db = new DB($container);
        $this->db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->db->bootEloquent();
        $this->db->setAsGlobal();

        $this->db->schema()->create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $this->db->schema()->createTranslatable('topics', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('name')->translatable();
            $table->text('description')->nullable()->translatable();
            $table->timestamps();
        });
    }

    /**
     * Restore the global container and connection resolver.
     */
    protected function tearDown(): void
    {
        Model::unsetConnectionResolver();
        Container::setInstance($this->previousContainer);
    }

    public function test_parcel_records_the_keys_it_was_built_from(): void
    {
        $parcel = NoteParcel::fromArray(['title' => 'A', 'summary' => null, 'unknown' => 1]);

        $this->assertSame(['title', 'summary'], $parcel->provided());
        $this->assertSame(['title' => 'A', 'summary' => null], $parcel->toAttributes());
        $this->assertArrayNotHasKey('model', $parcel->toArray());
        $this->assertArrayNotHasKey('provided', $parcel->toArray());
    }

    public function test_a_parcel_instantiated_directly_drops_its_nulls(): void
    {
        $parcel = new NoteParcel(title: 'A');

        $this->assertNull($parcel->provided());
        $this->assertSame(['title' => 'A', 'force' => false], $parcel->toAttributes());
    }

    public function test_from_parcel_passes_on_only_the_provided_keys(): void
    {
        $source = NoteParcel::fromArray(['summary' => null]);

        $copy = NoteParcel::fromParcel($source, ['id' => 5]);

        $this->assertSame(['id', 'summary'], $copy->provided());
        $this->assertSame(['id' => 5, 'summary' => null], $copy->toAttributes());
    }

    public function test_from_parcel_carries_the_attached_model(): void
    {
        $note = new Note;

        $this->assertSame($note, NoteParcel::fromParcel(NoteParcel::fromArray(['title' => 'A'])->setModel($note))->getModel());
        $this->assertNull(NoteParcel::fromParcel(NoteParcel::fromArray(['title' => 'A']))->getModel());
    }

    public function test_save_creates_and_returns_the_fresh_model(): void
    {
        $note = (new NoteRepository)->save(NoteParcel::fromArray(['title' => 'First', 'summary' => 'Short']));

        $this->assertInstanceOf(Note::class, $note);
        $this->assertTrue($note->exists);
        $this->assertSame('First', $note->title);
        $this->assertNotNull($note->created_at);
    }

    public function test_update_clears_a_column_given_as_null_and_keeps_the_others(): void
    {
        $repository = new NoteRepository;
        $note = $repository->save(NoteParcel::fromArray(['title' => 'First', 'summary' => 'Short']));

        $cleared = $repository->save(NoteParcel::fromArray(['summary' => null])->setModel($note));

        $this->assertSame('First', $cleared->title);
        $this->assertNull($cleared->summary);

        $renamed = $repository->save(NoteParcel::fromArray(['title' => 'Second'])->setModel($cleared));

        $this->assertSame('Second', $renamed->title);
        $this->assertNull($renamed->summary);
    }

    public function test_find_all_and_find_or_fail_return_models(): void
    {
        $repository = new NoteRepository;
        $note = $repository->save(NoteParcel::fromArray(['title' => 'First']));

        $this->assertTrue($repository->find($note->id)->is($note));
        $this->assertNull($repository->find(999));
        $this->assertInstanceOf(Collection::class, $repository->all());
        $this->assertCount(1, $repository->all());

        $this->expectException(ModelNotFoundException::class);
        $repository->findOrFail(999);
    }

    public function test_destroy_by_parcel_model_or_key(): void
    {
        $repository = new NoteRepository;
        $a = $repository->save(NoteParcel::fromArray(['title' => 'A']));
        $b = $repository->save(NoteParcel::fromArray(['title' => 'B']));
        $c = $repository->save(NoteParcel::fromArray(['title' => 'C']));

        $this->assertTrue($repository->destroy(NoteParcel::fromArray(['id' => $a->id])));
        $this->assertTrue($repository->destroy($b));
        $this->assertTrue($repository->destroy($c->id));
        $this->assertFalse($repository->destroy(999));
        $this->assertSame(0, Note::count());
        $this->assertSame(3, Note::withTrashed()->count());
    }

    public function test_destroy_force_deletes_when_the_parcel_asks(): void
    {
        $repository = new NoteRepository;
        $note = $repository->save(NoteParcel::fromArray(['title' => 'A']));

        $repository->destroy(NoteParcel::fromArray(['force' => true])->setModel($note));

        $this->assertSame(0, Note::withTrashed()->count());
    }

    public function test_destroy_without_model_or_id_is_a_logic_error(): void
    {
        $this->expectException(LogicException::class);

        (new NoteRepository)->destroy(NoteParcel::fromArray([]));
    }

    public function test_translation_repository_upserts_and_clears_by_locale(): void
    {
        $topic = Topic::create(['slug' => 'faq', 'translations' => ['ar' => ['name' => 'أسئلة']]]);
        $repository = new TopicTranslationRepository;

        $en = $repository->save(TopicTranslationParcel::fromArray(['topic_id' => $topic->id, 'locale' => 'en', 'name' => 'FAQ', 'description' => 'Answers']));
        $same = $repository->save(TopicTranslationParcel::fromArray(['topic_id' => $topic->id, 'locale' => 'en', 'description' => null]));

        $this->assertSame($en->id, $same->id);
        $this->assertSame('FAQ', $same->name);
        $this->assertNull($same->description);
        $this->assertSame('en', $same->locale_code);
        $this->assertTrue($repository->findForLocale($topic->id, 'en')->is($same));

        $this->assertTrue($repository->destroyForLocale($topic->id, 'en'));
        $this->assertFalse($repository->destroyForLocale($topic->id, 'en'));
        $this->assertNull($repository->findForLocale($topic->id, 'en'));
    }

    public function test_translation_repository_rejects_an_unknown_locale(): void
    {
        $topic = Topic::create(['slug' => 'faq', 'translations' => ['ar' => ['name' => 'أسئلة']]]);

        $this->expectException(InvalidArgumentException::class);

        (new TopicTranslationRepository)->save(TopicTranslationParcel::fromArray(['topic_id' => $topic->id, 'locale' => 'xx', 'name' => 'X']));
    }

    public function test_browse_filters_searches_and_sorts_plain_columns(): void
    {
        Note::create(['title' => 'Alpha', 'summary' => 'first']);
        Note::create(['title' => 'Beta', 'summary' => 'second']);
        Note::create(['title' => 'Gamma', 'summary' => 'first again']);

        $parcel = new NoteQuery;
        $parcel->search = 'first';
        $parcel->sort = '-title';

        $this->assertSame(['Gamma', 'Alpha'], (new NoteRepository)->browse($parcel)->pluck('title')->all());
        $this->assertSame(['Beta'], (new NoteRepository)->browse(new NoteQuery(title: 'Beta'))->pluck('title')->all());
    }

    public function test_browse_paginates_when_per_page_is_set(): void
    {
        foreach (['A', 'B', 'C'] as $title) {
            Note::create(['title' => $title]);
        }

        $parcel = new NoteQuery;
        $parcel->sort = 'title';
        $parcel->page = 2;
        $parcel->per_page = 2;

        $page = (new NoteRepository)->browse($parcel);

        $this->assertInstanceOf(LengthAwarePaginator::class, $page);
        $this->assertSame(3, $page->total());
        $this->assertSame(['C'], $page->getCollection()->pluck('title')->all());
    }

    public function test_browse_searches_and_sorts_translated_columns_in_the_current_locale(): void
    {
        Topic::create(['slug' => 'one', 'translations' => ['ar' => ['name' => 'ياء'], 'en' => ['name' => 'Alpha']]]);
        Topic::create(['slug' => 'two', 'translations' => ['ar' => ['name' => 'ألف'], 'en' => ['name' => 'Beta']]]);

        $parcel = new TopicQuery;
        $parcel->sort = 'name';

        $this->assertSame(['two', 'one'], (new TopicRepository)->browse($parcel)->pluck('slug')->all());

        $parcel->search = 'ياء';

        $this->assertSame(['one'], (new TopicRepository)->browse($parcel)->pluck('slug')->all());
    }
}

class NoteQuery extends QueryParcel
{
    public function __construct(
        public ?string $title = null,
    ) {}

    public function searchable(): array
    {
        return ['title', 'summary'];
    }

    public function sortable(): array
    {
        return ['title'];
    }
}

class TopicQuery extends QueryParcel
{
    public function searchable(): array
    {
        return ['slug', 'name'];
    }

    public function sortable(): array
    {
        return ['name'];
    }
}

/**
 * @extends ModelRepository<Topic>
 */
class TopicRepository extends ModelRepository
{
    public function __construct()
    {
        parent::__construct(new Topic);
    }
}

class Note extends Model
{
    use SoftDeletes;

    protected $table = 'notes';

    protected $fillable = ['title', 'summary'];
}

class NoteParcel extends Parcel
{
    public function __construct(
        public ?int $id = null,
        public ?string $title = null,
        public ?string $summary = null,
        public bool $force = false,
    ) {}
}

/**
 * @extends ModelRepository<Note>
 */
class NoteRepository extends ModelRepository
{
    public function __construct()
    {
        parent::__construct(new Note);
    }
}

class Topic extends Model
{
    protected $table = 'topics';

    protected $fillable = ['slug'];

    protected array $translatable = ['name', 'description'];
}

class TopicTranslationParcel extends Parcel
{
    public function __construct(
        public ?int $topic_id = null,
        public ?string $locale = null,
        public ?string $name = null,
        public ?string $description = null,
    ) {}
}

class TopicTranslationRepository extends TranslationRepository
{
    public function __construct()
    {
        parent::__construct((new Topic)->newTranslation());
    }
}
