<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_day_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status')->default('in_progress');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('student_id');
            $table->index('workout_plan_id');
            $table->index('workout_day_id');
            $table->index('status');
            $table->index('started_at');
        });

        Schema::create('workout_session_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('order')->default(1);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('workout_session_id');
            $table->index('exercise_id');
        });

        Schema::create('exercise_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_session_exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('set_number');
            $table->unsignedSmallInteger('repetitions')->nullable();
            $table->decimal('load', 8, 2)->nullable();
            $table->unsignedSmallInteger('rest_time')->nullable();
            $table->unsignedSmallInteger('duration')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();
            $table->softDeletes();

            $table->index('exercise_id');
            $table->index('student_id');
            $table->index('workout_session_id');
            $table->index('logged_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_logs');
        Schema::dropIfExists('workout_session_exercises');
        Schema::dropIfExists('workout_sessions');
    }
};
