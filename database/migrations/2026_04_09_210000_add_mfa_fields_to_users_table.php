<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'mfa_enabled')) {
                $table->boolean('mfa_enabled')->default(false)->after('password');
            }
            if (! Schema::hasColumn('users', 'mfa_secret')) {
                $table->text('mfa_secret')->nullable()->after('mfa_enabled');
            }
            if (! Schema::hasColumn('users', 'mfa_recovery_codes')) {
                $table->longText('mfa_recovery_codes')->nullable()->after('mfa_secret');
            }
            if (! Schema::hasColumn('users', 'mfa_confirmed_at')) {
                $table->timestamp('mfa_confirmed_at')->nullable()->after('mfa_recovery_codes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'mfa_confirmed_at')) {
                $table->dropColumn('mfa_confirmed_at');
            }
            if (Schema::hasColumn('users', 'mfa_recovery_codes')) {
                $table->dropColumn('mfa_recovery_codes');
            }
            if (Schema::hasColumn('users', 'mfa_secret')) {
                $table->dropColumn('mfa_secret');
            }
            if (Schema::hasColumn('users', 'mfa_enabled')) {
                $table->dropColumn('mfa_enabled');
            }
        });
    }
};

