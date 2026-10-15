<?php

namespace Heritage\Tests\Database;

use Heritage\Config\Repository;
use Heritage\Container\Container;
use Heritage\Contracts\Translation\LocaleResolver;
use Heritage\Database\Eloquent\RuntimeTranslation;
use Heritage\Translation\LocaleStorageConverter;
use Heritage\Database\Capsule\Manager as DB;
use Heritage\Database\Eloquent\Model;
use Heritage\Database\QueryException;
use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Carbon;
use Heritage\Translation\ConfigLocaleResolver;
use Heritage\Translation\Locales;
use PHPUnit\Framework\TestCase;

/**
 * Translations without the i18n artifact: ConfigLocaleResolver, codes stored in a "locale" column.
 */
class DatabaseEloquentTranslatableTest extends TestCase
{
    protected DB $db;

    protected Container $previousContainer;

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

        $this->db->schema()->createTranslatable('items', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('name')->translatable()->unique();
            $table->text('description')->nullable()->translatable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Model::unsetConnectionResolver();
        Container::setInstance($this->previousContainer);
    }

    public function test_resolver_defaults_to_config_without_i18n(): void
    {
        $this->assertInstanceOf(ConfigLocaleResolver::class, Locales::resolver());
        $this->assertSame(['ar', 'en'], Locales::resolver()->available());
        $this->assertNull(Locales::resolver()->toStorage('xx'));
    }

    public function test_translation_table_has_locale_code_and_its_own_timestamps(): void
    {
        $columns = $this->db->schema()->getColumnListing('item_translations');

        foreach (['id', 'item_id', 'locale', 'name', 'created_at', 'updated_at'] as $column) {
            $this->assertContains($column, $columns);
        }
        $this->assertNotContains('locale_id', $columns);
        $this->assertNotContains('name', $this->db->schema()->getColumnListing('items'));
    }

    public function test_translatable_group_and_drop_helpers(): void
    {
        $this->db->schema()->createTranslatable('notes', function (Blueprint $table) {
            $table->id();
            $table->translatable(function (Blueprint $table) {
                $table->string('title');
                $table->string('summary');
            });
        });

        $this->assertSame(['id'], $this->db->schema()->getColumnListing('notes'));
        $this->assertContains('summary', $this->db->schema()->getColumnListing('note_translations'));

        $this->db->schema()->dropIfExistsTranslatable('notes');
        $this->assertFalse($this->db->schema()->hasTable('note_translations'));

        $this->assertInstanceOf(ItemTranslationProbe::class, (new ItemWithProbe)->newTranslation());
    }

    public function test_unique_translated_column_is_unique_per_locale(): void
    {
        $table = $this->db->table('item_translations');
        $table->insert(['item_id' => 1, 'locale' => 'ar', 'name' => 'Same']);
        $table->insert(['item_id' => 2, 'locale' => 'en', 'name' => 'Same']);

        $this->expectException(QueryException::class);
        $table->insert(['item_id' => 3, 'locale' => 'ar', 'name' => 'Same']);
    }

    public function test_fill_merges_translations_and_null_deletes_a_locale(): void
    {
        $item = Item::create(['slug' => 'blue', 'translations' => ['ar' => ['name' => 'أزرق'], 'en' => ['name' => 'Blue']]]);

        $this->assertSame(['ar' => 'أزرق', 'en' => 'Blue'], $this->names($item));

        // Omitted locales are left untouched.
        Item::find($item->id)->update(['translations' => ['en' => ['name' => 'Navy']]]);
        $this->assertSame(['ar' => 'أزرق', 'en' => 'Navy'], $this->names($item));

        // A locale sent as null is deleted.
        Item::find($item->id)->update(['translations' => ['en' => null]]);
        $this->assertSame(['ar' => 'أزرق'], $this->names($item));
    }

    public function test_refresh_reads_the_translations_again(): void
    {
        $item = Item::create(['slug' => 'red', 'translations' => ['en' => ['name' => 'Red']]]);
        $this->assertSame('Red', $item->translate('en')->name);

        Item::find($item->id)->update(['translations' => ['en' => ['name' => 'Crimson']]]);

        $this->assertSame('Crimson', $item->refresh()->translate('en')->name);
    }

    public function test_forget_translation_and_setting_it_again_cancels_the_deletion(): void
    {
        $item = Item::create(['slug' => 'g', 'translations' => ['ar' => ['name' => 'أخضر'], 'en' => ['name' => 'Green']]]);

        $item->forgetTranslation('en');
        $this->assertNull($item->translate('en'));
        $item->setTranslation('name', 'Lime', 'en');
        $item->save();

        $this->assertSame(['ar' => 'أخضر', 'en' => 'Lime'], $this->names($item));
    }

    public function test_runtime_translation_model_is_used_without_a_translation_class(): void
    {
        $item = Item::create(['slug' => 'rt', 'translations' => ['ar' => ['name' => 'ر']]]);

        $translation = Item::find($item->id)->translations->first();

        $this->assertInstanceOf(RuntimeTranslation::class, $translation);
        $this->assertSame('item_translations', $translation->getTable());
        $this->assertSame(Item::class, $translation->getParentModelClass());
        $this->assertSame($item->id, $translation->parent->id);
    }

    public function test_translations_to_array_is_keyed_by_locale_code(): void
    {
        $item = Item::create(['slug' => 'k', 'translations' => ['ar' => ['name' => 'ك'], 'en' => ['name' => 'K']]]);

        $payload = Item::find($item->id)->translationsToArray();

        $this->assertSame(['ar', 'en'], array_keys($payload));
        $this->assertSame('K', $payload['en']['name']);
        $this->assertSame('en', $payload['en']['locale']);
        $this->assertArrayHasKey('created_at', $payload['en']);
    }

    public function test_single_record_queries_do_not_eager_load_translations(): void
    {
        $item = Item::create(['slug' => 'one', 'translations' => ['ar' => ['name' => 'واحد']]]);

        $connection = $this->db->getConnection();
        $connection->enableQueryLog();
        $connection->flushQueryLog();

        $found = Item::find($item->id);
        $this->assertCount(1, $connection->getQueryLog());
        $this->assertFalse($found->relationLoaded('localizedTranslations'));

        $this->assertSame('واحد', $found->name);
    }

    public function test_create_translatable_rejects_unknown_options(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->db->schema()->createTranslatable('things', fn (Blueprint $table) => $table->id(), ['translationTable' => 'x']);
    }

    public function test_locale_storage_can_be_converted_both_ways(): void
    {
        $item = Item::create(['slug' => 'conv', 'translations' => ['ar' => ['name' => 'تحويل'], 'en' => ['name' => 'Convert']]]);

        $this->db->schema()->create('locales', function (Blueprint $table) {
            $table->id();
        });
        $this->db->table('locales')->insert([['id' => 1], ['id' => 2]]);

        $converter = new LocaleStorageConverter($this->db->getConnection(), new IdLocaleResolver);

        $this->assertSame(['item_translations'], $converter->translationTables());
        $this->assertSame(['ar' => 1, 'en' => 2], $converter->convert('item_translations', 'locale_id', dryRun: true)['mapping']);

        $converter->convert('item_translations', 'locale_id');
        $columns = $this->db->schema()->getColumnListing('item_translations');
        $this->assertContains('locale_id', $columns);
        $this->assertNotContains('locale', $columns);
        $this->assertSame(['1' => 'تحويل', '2' => 'Convert'], $this->db->table('item_translations')->orderBy('locale_id')->pluck('name', 'locale_id')->all());

        $converter->convert('item_translations', 'locale');
        $this->assertSame(['ar' => 'تحويل', 'en' => 'Convert'], $this->names($item));
        $this->assertNotContains('locale_id', $this->db->schema()->getColumnListing('item_translations'));

        // The unique (item, locale) index followed the column.
        $this->expectException(QueryException::class);
        $this->db->table('item_translations')->insert(['item_id' => $item->id, 'locale' => 'ar', 'name' => 'Duplicate']);
    }

    public function test_unknown_locales_and_empty_values_are_skipped(): void
    {
        $item = Item::create(['slug' => 'x', 'translations' => ['xx' => ['name' => 'Bad'], 'en' => ['name' => ''], 'ar' => ['name' => 'سين']]]);

        $this->assertSame(['ar' => 'سين'], $this->names($item));
    }

    public function test_empty_value_clears_an_optional_attribute_of_a_filled_locale(): void
    {
        $item = Item::create(['slug' => 'd', 'translations' => [
            'ar' => ['name' => 'دال', 'description' => 'وصف'],
            'en' => ['name' => 'D', 'description' => 'Text'],
        ]]);

        Item::find($item->id)->update(['translations' => [
            'ar' => ['name' => 'دال', 'description' => ''],
            'en' => ['name' => 'D', 'description' => null],
        ]]);

        $this->assertSame(
            ['ar' => null, 'en' => null],
            $this->db->table('item_translations')->where('item_id', $item->id)->orderBy('locale')->pluck('description', 'locale')->all()
        );

        // A locale whose values are all empty is left untouched.
        Item::find($item->id)->update(['translations' => ['en' => ['name' => '', 'description' => null]]]);
        $this->assertSame(['ar' => 'دال', 'en' => 'D'], $this->names($item));
    }

    public function test_reading_falls_back_to_fallback_locale_then_column(): void
    {
        $english = Item::create(['slug' => 'only-en', 'translations' => ['en' => ['name' => 'English only']]]);
        $none = Item::create(['slug' => 'no-names']);

        $this->assertSame('English only', Item::find($english->id)->name); // current is "ar"
        $this->assertSame('no-names', Item::find($none->id)->name);
    }

    public function test_listing_eager_loads_current_and_fallback_translations(): void
    {
        foreach (['a', 'b', 'c'] as $slug) {
            Item::create(['slug' => $slug, 'translations' => ['ar' => ['name' => "ع-{$slug}"], 'en' => ['name' => "E-{$slug}"]]]);
        }

        $connection = $this->db->getConnection();
        $connection->enableQueryLog();
        $connection->flushQueryLog();

        $names = Item::all()->map(fn (Item $item) => $item->name)->all();

        $this->assertSame(['ع-a', 'ع-b', 'ع-c'], $names);
        $this->assertCount(2, $connection->getQueryLog());
    }

    public function test_translation_change_touches_the_parent_once(): void
    {
        $item = Item::create(['slug' => 'touch', 'translations' => ['ar' => ['name' => 'قديم']]]);
        $this->db->table('items')->where('id', $item->id)->update(['updated_at' => '2020-01-01 00:00:00']);

        Item::find($item->id)->update(['translations' => ['ar' => ['name' => 'جديد']]]);

        $this->assertNotSame('2020-01-01 00:00:00', $this->db->table('items')->value('updated_at'));
        $this->assertNotNull($this->db->table('item_translations')->value('created_at'));
    }

    public function test_direct_translation_edit_touches_the_parent(): void
    {
        $item = Item::create(['slug' => 'direct', 'translations' => ['ar' => ['name' => 'قديم']]]);
        $this->db->table('items')->where('id', $item->id)->update(['updated_at' => '2020-01-01 00:00:00']);

        $translation = $item->translations()->first();
        $translation->name = 'جديد';
        $translation->save();

        $this->assertNotSame('2020-01-01 00:00:00', $this->db->table('items')->value('updated_at'));
    }

    protected function names(Item $item): array
    {
        return $this->db->table('item_translations')
            ->where('item_id', $item->id)
            ->orderBy('locale')
            ->pluck('name', 'locale')
            ->all();
    }
}

/**
 * A locale_id resolver (like the i18n artifact's) mapping ar => 1, en => 2.
 */
class IdLocaleResolver implements LocaleResolver
{
    protected array $ids = ['ar' => 1, 'en' => 2];

    public function current(): string
    {
        return 'ar';
    }

    public function default(): string
    {
        return 'ar';
    }

    public function fallback(): string
    {
        return 'en';
    }

    public function available(): array
    {
        return array_keys($this->ids);
    }

    public function isAvailable(string $code): bool
    {
        return isset($this->ids[$code]);
    }

    public function storageColumn(): string
    {
        return 'locale_id';
    }

    public function storageTable(): ?string
    {
        return 'locales';
    }

    public function toStorage(string $code): string|int|null
    {
        return $this->ids[$code] ?? null;
    }

    public function fromStorage(string|int $value): ?string
    {
        return array_search((int) $value, $this->ids, true) ?: null;
    }

    public function label(string $code): string
    {
        return strtoupper($code);
    }
}

class ItemWithProbe extends Model
{
    protected array $translatable = ['name'];

    protected string $translationModel = ItemTranslationProbe::class;
}

class ItemTranslationProbe extends \Heritage\Database\Eloquent\ModelTranslation
{
    protected $table = 'item_translations';
}

class Item extends Model
{
    protected $table = 'items';

    protected $fillable = ['slug'];

    protected array $translatable = ['name', 'description'];

    protected array $translatableFallback = ['name' => 'slug'];
}
