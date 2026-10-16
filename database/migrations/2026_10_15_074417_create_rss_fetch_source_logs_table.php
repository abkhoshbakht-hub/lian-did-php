<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rss_fetch_source_logs', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            
            $table->string('fetchLogId', 30);
            $table->foreign('fetchLogId')->references('id')->on('rss_fetch_logs')->onDelete('cascade');
            
            $table->string('source');
            $table->string('status')->default('PENDING');
            $table->timestamp('startedAt')->useCurrent();
            $table->timestamp('finishedAt')->nullable();
            $table->integer('durationMs')->nullable();
            $table->integer('fetchedCount')->default(0);
            $table->integer('newCount')->default(0);
            $table->text('error')->nullable();
            $table->integer('retryCount')->default(0);

            $table->index('fetchLogId');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rss_fetch_source_logs');
    }
};