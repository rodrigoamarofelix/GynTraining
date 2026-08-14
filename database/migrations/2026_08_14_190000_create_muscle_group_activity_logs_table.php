<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muscle_group_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('muscle_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('changes')->nullable();
            $table->text('summary');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['muscle_group_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muscle_group_activity_logs');
    }
};
