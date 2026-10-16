<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_submissions', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('title');
            $table->text('content');
            $table->string('category');
            $table->string('mediaType')->default('PHOTO'); // PHOTO, VIDEO, TEXT
            $table->string('mediaUrl')->nullable();
            $table->string('fileName')->nullable();
            $table->string('senderName');
            $table->string('senderPhone')->nullable();
            $table->string('senderEmail')->nullable();
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, REJECTED
            $table->text('adminNote')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_submissions');
    }
};