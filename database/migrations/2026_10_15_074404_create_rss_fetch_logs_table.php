<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rss_fetch_logs', function (Blueprint $table) {
            $table->string('id', 30)->primary();
            $table->timestamp('startedAt')->useCurrent();
            $table->timestamp('finishedAt')->nullable();
            $table->integer('durationMs')->nullable();
            $table->string('status')->default('RUNNING');
            $table->integer('totalSources')->default(0);
            $table->integer('successSources')->default(0);
            $table->integer('failedSources')->default(0);
            $table->integer('newItems')->default(0);
            $table->integer('updatedItems')->default(0);
            $table->text('errorSummary')->nullable();
            $table->string('triggerType')->default('manual');
            $table->timestamp('createdAt')->useCurrent();

            $table->index('createdAt');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rss_fetch_logs');
    }
};