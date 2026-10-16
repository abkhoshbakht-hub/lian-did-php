<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('static_pages', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('featuredImage')->nullable();
            $table->string('authorName')->nullable();
            $table->string('authorImage')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('static_pages');
    }
};