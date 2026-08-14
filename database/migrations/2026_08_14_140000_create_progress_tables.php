<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('measured_at');
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->decimal('bmi', 5, 2)->nullable();
            $table->decimal('arm', 5, 2)->nullable();
            $table->decimal('forearm', 5, 2)->nullable();
            $table->decimal('chest', 5, 2)->nullable();
            $table->decimal('waist', 5, 2)->nullable();
            $table->decimal('abdomen', 5, 2)->nullable();
            $table->decimal('hip', 5, 2)->nullable();
            $table->decimal('thigh', 5, 2)->nullable();
            $table->decimal('calf', 5, 2)->nullable();
            $table->decimal('body_fat_percentage', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('student_id');
            $table->index('measured_at');
        });

        Schema::create('progress_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('photo_path');
            $table->date('taken_at');
            $table->string('visibility')->default('private');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('student_id');
            $table->index('category');
            $table->index('taken_at');
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('target', 10, 2);
            $table->decimal('current_value', 10, 2)->default(0);
            $table->string('unit')->nullable();
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('student_id');
            $table->index('status');
            $table->index('target_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goals');
        Schema::dropIfExists('progress_photos');
        Schema::dropIfExists('body_measurements');
    }
};
