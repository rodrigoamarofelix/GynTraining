<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('student_id');
            $table->index('trainer_id');
            $table->index('status');
        });

        Schema::create('workout_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('order')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('workout_plan_id');
            $table->index(['workout_plan_id', 'order']);
        });

        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_day_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(1);
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('execution_time')->nullable();
            $table->unsignedSmallInteger('rest_time')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('workout_day_id');
            $table->index('exercise_id');
            $table->index(['workout_day_id', 'order']);
        });

        Schema::create('workout_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('set_number');
            $table->unsignedSmallInteger('repetitions')->nullable();
            $table->decimal('load', 8, 2)->nullable();
            $table->unsignedSmallInteger('rest_time')->nullable();
            $table->unsignedSmallInteger('duration')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('workout_exercise_id');
            $table->index(['workout_exercise_id', 'set_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_sets');
        Schema::dropIfExists('workout_exercises');
        Schema::dropIfExists('workout_days');
        Schema::dropIfExists('workout_plans');
    }
};
