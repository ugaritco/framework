<?php

namespace Heritage\Tests\Database;

use Heritage\Container\Container;
use Heritage\Database\Capsule\Manager as DB;
use Heritage\Database\Schema\Blueprint;
use Heritage\Support\Facades\Facade;
use Heritage\Support\Facades\Schema;
use PHPUnit\Framework\TestCase;

class DatabaseSchemaBuilderWithTranslationTest extends TestCase
{
    protected $db;

    protected function setUp(): void
    {
        $this->db = $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->setAsGlobal();

        $container = new Container;
        $container->instance('db', $db->getDatabaseManager());
        $container->bind('db.schema', function () use ($db) {
            return $db->getConnection()->getSchemaBuilder();
        });
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testCreateTranslatableCreatesTwoTablesAndSeparatesColumns()
    {
        Schema::createTranslatable('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title')->translatable();
            $table->text('content')->translatable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->assertTrue(Schema::hasTable('posts'));
        $this->assertTrue(Schema::hasTable('post_translations'));

        $mainColumns = Schema::getColumnListing('posts');
        $this->assertContains('id', $mainColumns);
        $this->assertContains('slug', $mainColumns);
        $this->assertContains('is_active', $mainColumns);
        $this->assertNotContains('title', $mainColumns);
        $this->assertNotContains('content', $mainColumns);

        $transColumns = Schema::getColumnListing('post_translations');
        $this->assertContains('id', $transColumns);
        $this->assertContains('post_id', $transColumns);
        $this->assertContains('locale', $transColumns);
        $this->assertContains('title', $transColumns);
        $this->assertContains('content', $transColumns);

        // Test inserting data into both tables
        $postId = $this->db->getConnection()->table('posts')->insertGetId([
            'slug' => 'first-post',
            'is_active' => 1,
        ]);

        $this->db->getConnection()->table('post_translations')->insert([
            ['post_id' => $postId, 'locale' => 'ar', 'title' => 'المقال الأول', 'content' => 'نص المقال'],
            ['post_id' => $postId, 'locale' => 'en', 'title' => 'First Post', 'content' => 'Post body'],
        ]);

        $this->assertSame(2, $this->db->getConnection()->table('post_translations')->where('post_id', $postId)->count());

        Schema::dropTranslatable('posts');
        $this->assertFalse(Schema::hasTable('posts'));
        $this->assertFalse(Schema::hasTable('post_translations'));
    }

    public function testCreateTranslatableUsingTranslatableGroup()
    {
        Schema::createTranslatable('articles', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->translatable(function (Blueprint $table) {
                $table->string('headline');
                $table->text('summary')->nullable();
            });
            $table->timestamps();
        });

        $this->assertTrue(Schema::hasTable('articles'));
        $this->assertTrue(Schema::hasTable('article_translations'));

        $this->assertNotContains('headline', Schema::getColumnListing('articles'));
        $this->assertContains('headline', Schema::getColumnListing('article_translations'));
        $this->assertContains('summary', Schema::getColumnListing('article_translations'));

        Schema::dropIfExistsTranslatable('articles');
        $this->assertFalse(Schema::hasTable('articles'));
        $this->assertFalse(Schema::hasTable('article_translations'));
    }

    public function testCreateTranslatableWithCustomNames()
    {
        Schema::createTranslatable(
            'categories',
            function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('name')->translatable();
            },
            ['table' => 'custom_cat_translations', 'foreign_key' => 'category_fk']
        );

        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasTable('custom_cat_translations'));

        $columns = Schema::getColumnListing('custom_cat_translations');
        $this->assertContains('category_fk', $columns);
        $this->assertContains('name', $columns);

        Schema::dropTranslatable('categories', 'custom_cat_translations');
        $this->assertFalse(Schema::hasTable('categories'));
        $this->assertFalse(Schema::hasTable('custom_cat_translations'));
    }

    /**
     * softDeletes()->withTranslations() keeps deleted_at on both tables; a unique translated column
     * ignores trashed rows (partial index), while one row per (parent, locale) stays enforced.
     */
    public function testCreateTranslatableWithSoftDeletedTranslations()
    {
        Schema::createTranslatable('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->translatable()->unique();
            $table->softDeletes()->withTranslations();
        }, ['locale_column' => 'locale', 'timestamps' => false]);

        $this->assertContains('deleted_at', Schema::getColumnListing('tags'));
        $this->assertContains('deleted_at', Schema::getColumnListing('tag_translations'));

        $connection = $this->db->getConnection();
        $first = $connection->table('tags')->insertGetId([]);
        $second = $connection->table('tags')->insertGetId([]);

        $connection->table('tag_translations')->insert(['tag_id' => $first, 'locale' => 'en', 'name' => 'Shared', 'deleted_at' => '2026-01-01 00:00:00']);
        $connection->table('tag_translations')->insert(['tag_id' => $second, 'locale' => 'en', 'name' => 'Shared']);

        $this->assertSame(2, $connection->table('tag_translations')->count());

        try {
            $connection->table('tag_translations')->insert(['tag_id' => $first, 'locale' => 'en', 'name' => 'Other']);
            $this->fail('A second row for the same parent and locale must be rejected.');
        } catch (\Heritage\Database\QueryException) {
            $this->addToAssertionCount(1);
        }

        try {
            $connection->table('tag_translations')->insert(['tag_id' => $first, 'locale' => 'ar', 'name' => 'Shared']);
            $connection->table('tag_translations')->insert(['tag_id' => $second, 'locale' => 'ar', 'name' => 'Shared']);
            $this->fail('A live duplicate name in one locale must be rejected.');
        } catch (\Heritage\Database\QueryException) {
            $this->addToAssertionCount(1);
        }

        Schema::dropIfExistsTranslatable('tags');
    }
}
