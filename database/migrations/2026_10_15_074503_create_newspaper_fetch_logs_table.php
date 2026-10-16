<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newspaper_fetch_logs', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            
            $table->string('newspaperId', 30)->nullable();
            $table->foreign('newspaperId')->references('id')->on('newspapers')->onDelete('cascade');
            
            $table->string('sourceId', 30)->nullable();
            $table->foreign('sourceId')->references('id')->on('newspaper_sources')->onDelete('cascade');
            
            $table->timestamp('startedAt')->useCurrent();
            $table->timestamp('finishedAt')->nullable();
            $table->string('status')->default('FAILED');
            $table->integer('httpStatus')->nullable();
            $table->text('errorMessage')->nullable();
            $table->string('discoveredImageUrl')->nullable();
            $table->string('discoveredDate')->nullable();
            $table->integer('responseTimeMs')->nullable();
            $table->timestamp('createdAt')->useCurrent();

            $table->index('newspaperId');
            $table->index('createdAt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newspaper_fetch_logs');
    }
};