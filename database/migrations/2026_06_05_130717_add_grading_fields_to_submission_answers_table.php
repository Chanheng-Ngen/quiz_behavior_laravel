<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('submission_answers', function (Blueprint $table) {
            $table->decimal('earned_points', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->boolean('is_graded')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submission_answers', function (Blueprint $table) {
            $table->dropColumn(['earned_points', 'feedback', 'is_graded']);
        });
    }
};
