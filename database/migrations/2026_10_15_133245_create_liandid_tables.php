<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // ساخت جدول دسته‌بندی‌ها
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->string('id')->primary(); // Prisma uses CUID strings
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->string('icon')->nullable();
                $table->string('color')->nullable();
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        // ساخت جدول اخبار اختصاصی
        if (!Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('title');
                $table->string('slug')->unique();
                $table->string('subtitle')->nullable();
                $table->longText('content');
                $table->text('excerpt')->nullable();
                $table->string('featured_image')->nullable();
                $table->string('status')->default('DRAFT');
                $table->integer('view_count')->default(0);
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_breaking')->default(false);
                $table->boolean('is_pinned')->default(false);
                $table->string('placement')->default('latest');
                $table->string('source')->nullable();
                $table->string('source_url')->nullable();
                $table->timestamp('published_at')->nullable();
                $table->string('author_id')->nullable();
                $table->string('category_id')->nullable();
                $table->timestamps();
            });
        }

        // ساخت جدول اخبار خارجی (RSS)
        if (!Schema::hasTable('external_news')) {
            Schema::create('external_news', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('title');
                $table->string('link')->unique();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->string('source');
                $table->string('source_name');
                $table->string('category')->nullable();
                $table->string('status')->default('PENDING');
                $table->boolean('is_breaking')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->timestamp('fetched_at')->useCurrent();
                $table->timestamps();
            });
        }

        // ساخت جدول جایگاه‌های صفحه اصلی
        if (!Schema::hasTable('homepage_slots')) {
            Schema::create('homepage_slots', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->string('slot_key')->unique();
                $table->string('label');
                $table->string('type')->default('EXTERNAL');
                $table->string('external_news_id')->nullable();
                $table->string('custom_title')->nullable();
                $table->text('custom_content')->nullable();
                $table->string('custom_image')->nullable();
                $table->string('custom_link')->nullable();
                $table->string('category')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        // ساخت جدول نظرات
        if (!Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->text('content');
                $table->boolean('approved')->default(false);
                $table->string('user_id')->nullable();
                $table->string('article_id');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('homepage_slots');
        Schema::dropIfExists('external_news');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('categories');
    }
};