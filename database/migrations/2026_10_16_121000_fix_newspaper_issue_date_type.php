<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('newspaper_issues', function (Blueprint $table) {
            $table->dateTime('date')->change();
        });
    }

    public function down(): void
    {
        Schema::table('newspaper_issues', function (Blueprint $table) {
            $table->timestamp('date')->change();
        });
    }
};
