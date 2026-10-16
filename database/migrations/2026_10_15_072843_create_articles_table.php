<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('featuredImage')->nullable();
            $table->string('status')->default('DRAFT');
            $table->integer('viewCount')->default(0);
            $table->boolean('isFeatured')->default(false);
            $table->boolean('isBreaking')->default(false);
            $table->boolean('isPinned')->default(false);
            $table->string('placement')->default('latest');
            $table->string('source')->nullable();
            $table->string('sourceUrl')->nullable();
            $table->timestamp('publishedAt')->nullable();
            $table->timestamp('scheduledAt')->nullable();
            
            // ارتباط با نویسنده و دسته‌بندی
            $table->string('authorId', 30);
            $table->foreign('authorId')->references('id')->on('users')->onDelete('cascade');
            
            $table->string('categoryId', 30)->nullable();
            $table->foreign('categoryId')->references('id')->on('categories')->onDelete('set null');
            
            // سئو
            $table->string('metaTitle')->nullable();
            $table->text('metaDesc')->nullable();
            $table->string('metaKeywords')->nullable();
            $table->string('region')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};