<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropUnique(['name']);
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropIndex(['name']);
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->unique('name');
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
