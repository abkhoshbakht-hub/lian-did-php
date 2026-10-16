<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('homepage_slots', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->string('slotKey')->unique();
            $table->string('label');
            $table->string('type')->default('EXTERNAL'); // EXTERNAL, CUSTOM
            
            // ارتباط با اخبار خارجی
            $table->string('externalNewsId', 30)->nullable();
            $table->foreign('externalNewsId')->references('id')->on('external_news')->onDelete('set null');
            
            $table->string('customTitle')->nullable();
            $table->text('customContent')->nullable();
            $table->string('customImage')->nullable();
            $table->string('customLink')->nullable();
            $table->string('category')->nullable();
            $table->boolean('isActive')->default(true);
            $table->integer('order')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homepage_slots');
    }
};