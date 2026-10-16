<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newspaper_issues', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            
            $table->string('newspaperId', 30);
            $table->foreign('newspaperId')->references('id')->on('newspapers')->onDelete('cascade');
            
            $table->timestamp('date');
            $table->string('persianDate');
            $table->string('issueNumber')->nullable();
            $table->string('title')->nullable();
            $table->string('originalUrl')->nullable();
            $table->string('imageUrl')->nullable();
            $table->string('thumbnailUrl')->nullable();
            
            $table->string('sourceId', 30)->nullable();
            $table->foreign('sourceId')->references('id')->on('newspaper_sources')->onDelete('set null');
            
            $table->string('imageHash')->nullable();
            $table->integer('confidence')->default(0);
            $table->string('status')->default('PENDING');
            $table->timestamp('publishedAt')->nullable();
            $table->timestamps();

            $table->unique(['newspaperId', 'date']);
            $table->index('date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newspaper_issues');
    }
};