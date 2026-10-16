<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newspaper_sources', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            
            $table->string('newspaperId', 30);
            $table->foreign('newspaperId')->references('id')->on('newspapers')->onDelete('cascade');
            
            $table->string('name');
            $table->string('type')->default('official');
            $table->string('url')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('active')->default(true);
            $table->string('parserType')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamp('lastSuccessAt')->nullable();
            $table->timestamp('lastFailureAt')->nullable();
            $table->text('lastError')->nullable();
            $table->timestamps();

            $table->index('newspaperId');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newspaper_sources');
    }
};