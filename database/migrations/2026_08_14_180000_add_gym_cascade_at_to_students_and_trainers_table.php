<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->timestamp('gym_cascade_at')->nullable()->after('status');
            $table->index('gym_cascade_at');
        });

        Schema::table('trainers', function (Blueprint $table) {
            $table->timestamp('gym_cascade_at')->nullable()->after('status');
            $table->index('gym_cascade_at');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['gym_cascade_at']);
            $table->dropColumn('gym_cascade_at');
        });

        Schema::table('trainers', function (Blueprint $table) {
            $table->dropIndex(['gym_cascade_at']);
            $table->dropColumn('gym_cascade_at');
        });
    }
};
