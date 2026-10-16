<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->text('content');
            $table->boolean('approved')->default(false);
            
            // ارتباط با کاربر و مقاله
            $table->string('userId', 30);
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            
            $table->string('articleId', 30);
            $table->foreign('articleId')->references('id')->on('articles')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};