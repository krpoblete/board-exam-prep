<?php

namespace App\Models;

use Database\Factories\ExamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    /** @use HasFactory<ExamFactory> */
    use HasFactory;

    protected $fillable = [
        'board_exam_id',
        'title',
        'level',
        'duration_minutes',
        'passing_score',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    /** @return BelongsTo<BoardExam, $this> */
    public function boardExam(): BelongsTo
    {
        return $this->belongsTo(BoardExam::class);
    }

    /** @return BelongsToMany<Subject, $this> */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'exam_subjects')
            ->withPivot('question_count');
    }

    /** @return BelongsToMany<Question, $this> */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions')
            ->withPivot('position')
            ->orderByPivot('position');
    }

    /** @return HasMany<Attempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function hasAttempts(): bool
    {
        return $this->attempts()->exists();
    }
}
