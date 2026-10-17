<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gallery_albums')) {
            return;
        }

        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->string('category', 128)->nullable();
            $table->longText('description')->nullable();
            $table->string('coverImage', 500)->nullable();
            $table->string('color', 64)->default('from-blue-500 to-blue-700');
            $table->boolean('isActive')->default(true);
            $table->unsignedInteger('displayOrder')->default(0);
            $table->date('eventDate')->nullable();
            $table->timestamps();
        });

        Schema::create('gallery_items', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('albumId', 30);
            $table->enum('kind', ['image', 'video'])->default('image');
            $table->string('url', 1000);
            $table->string('thumbnail', 1000)->nullable();
            $table->string('title', 255)->nullable();
            $table->string('videoUrl', 1000)->nullable();
            $table->unsignedInteger('displayOrder')->default(0);
            $table->boolean('isActive')->default(true);
            $table->timestamps();

            $table->index('albumId');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_items');
        Schema::dropIfExists('gallery_albums');
    }
};