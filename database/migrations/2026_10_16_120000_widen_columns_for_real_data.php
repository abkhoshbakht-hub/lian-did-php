<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_news', function (Blueprint $table) {
            $table->dropUnique('external_news_link_unique');
        });

        Schema::table('external_news', function (Blueprint $table) {
            $table->text('title')->change();
            $table->text('link')->nullable()->change();
            $table->text('image')->nullable()->change();
            $table->char('link_hash', 64)->nullable()->unique()->after('link');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->text('featuredImage')->nullable()->change();
        });

        Schema::table('newspaper_fetch_logs', function (Blueprint $table) {
            $table->text('discoveredImageUrl')->nullable()->change();
        });

        Schema::table('newspaper_issues', function (Blueprint $table) {
            $table->text('originalUrl')->nullable()->change();
            $table->text('imageUrl')->nullable()->change();
            $table->text('thumbnailUrl')->nullable()->change();
        });

        DB::table('external_news')->whereNotNull('link')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('external_news')
                    ->where('id', $row->id)
                    ->update(['link_hash' => hash('sha256', (string) $row->link)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('external_news', function (Blueprint $table) {
            $table->dropUnique('external_news_link_hash_unique');
            $table->dropColumn('link_hash');
            $table->string('title')->change();
            $table->string('link')->nullable()->unique()->change();
            $table->string('image')->nullable()->change();
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->string('featuredImage')->nullable()->change();
        });

        Schema::table('newspaper_fetch_logs', function (Blueprint $table) {
            $table->string('discoveredImageUrl')->nullable()->change();
        });

        Schema::table('newspaper_issues', function (Blueprint $table) {
            $table->string('originalUrl')->nullable()->change();
            $table->string('imageUrl')->nullable()->change();
            $table->string('thumbnailUrl')->nullable()->change();
        });
    }
};
