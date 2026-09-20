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

    public function testCreateWithTranslationCreatesTwoTablesAndSeparatesColumns()
    {
        Schema::createWithTranslation('posts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title')->translation();
            $table->text('content')->translation();
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

        Schema::dropWithTranslation('posts');
        $this->assertFalse(Schema::hasTable('posts'));
        $this->assertFalse(Schema::hasTable('post_translations'));
    }

    public function testCreateWithTranslationUsingTranslatableGroup()
    {
        Schema::createWithTranslation('articles', function (Blueprint $table) {
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

        Schema::dropIfExistsWithTranslation('articles');
        $this->assertFalse(Schema::hasTable('articles'));
        $this->assertFalse(Schema::hasTable('article_translations'));
    }

    public function testCreateWithTranslationWithCustomNames()
    {
        Schema::createWithTranslation(
            'categories',
            function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('name')->translatable();
            },
            translationCallback: null,
            translationTable: 'custom_cat_translations',
            foreignKey: 'category_fk'
        );

        $this->assertTrue(Schema::hasTable('categories'));
        $this->assertTrue(Schema::hasTable('custom_cat_translations'));

        $columns = Schema::getColumnListing('custom_cat_translations');
        $this->assertContains('category_fk', $columns);
        $this->assertContains('name', $columns);

        Schema::dropWithTranslation('categories', 'custom_cat_translations');
        $this->assertFalse(Schema::hasTable('categories'));
        $this->assertFalse(Schema::hasTable('custom_cat_translations'));
    }
}
