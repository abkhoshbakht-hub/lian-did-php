<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_news', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('title');
            $table->string('link')->unique();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('source');
            $table->string('sourceName');
            $table->string('category')->nullable();
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, REJECTED
            $table->boolean('isBreaking')->default(false);
            $table->string('region')->nullable();
            $table->string('topic')->nullable();
            $table->timestamp('publishedAt')->nullable();
            $table->timestamp('fetchedAt')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_news');
    }
};