<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gyms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('gym_user', function (Blueprint $table) {
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['gym_id', 'user_id']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('gym_id');
            $table->index('status');
        });

        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('gym_id')->constrained()->cascadeOnDelete();
            $table->text('bio')->nullable();
            $table->string('specialty')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('gym_id');
            $table->index('status');
        });

        Schema::create('muscle_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('exercise_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->foreignId('exercise_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('muscle_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gym_id')->nullable()->constrained()->nullOnDelete();
            $table->string('equipment')->nullable();
            $table->string('difficulty')->default('beginner');
            $table->string('video_url')->nullable();
            $table->string('image_url')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('exercise_category_id');
            $table->index('muscle_group_id');
            $table->index('gym_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
        Schema::dropIfExists('exercise_categories');
        Schema::dropIfExists('muscle_groups');
        Schema::dropIfExists('trainers');
        Schema::dropIfExists('students');
        Schema::dropIfExists('gym_user');
        Schema::dropIfExists('gyms');
    }
};
