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
        Schema::table('quizzes', function (Blueprint $table) {
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('show_results_immediately')->default(true);
            $table->boolean('allow_answer_review')->default(true);
            $table->boolean('enable_anti_cheat')->default(true);
            $table->integer('max_violations')->default(3);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn([
                'shuffle_questions',
                'show_results_immediately',
                'allow_answer_review',
                'enable_anti_cheat',
                'max_violations',
            ]);
        });
    }
};
