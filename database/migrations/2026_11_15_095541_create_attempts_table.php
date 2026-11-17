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
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // null = practice
            $table->foreignId('exam_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('mode', 20);
            $table->timestamp('started_at');
            // practice can be untimed
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            // percentage, set on submit
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();
            $table->index(['user_id', 'mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
