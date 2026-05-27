<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropUnique(['name']);
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropIndex(['name']);
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->unique('name');
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
