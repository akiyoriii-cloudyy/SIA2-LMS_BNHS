<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strands', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('teacher_subjects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['teacher_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subjects');
        Schema::dropIfExists('strands');
    }
};
