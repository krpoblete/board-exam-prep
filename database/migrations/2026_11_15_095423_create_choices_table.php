<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();
        });

        // At most one correct choice per question (Postgres partial unique index).
        DB::statement(
            'CREATE UNIQUE INDEX choices_one_correct_per_question
            ON choices (question_id) WHERE is_correct = true'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('choices');
    }
};
