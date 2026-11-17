<?php

namespace App\Models;

use Database\Factories\SubjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    /** @use HasFactory<SubjectFactory> */
    use HasFactory;

    protected $fillable = [
        'board_exam_id',
        'name',
    ];

    /** @return BelongsTo<BoardExam, $this> */
    public function boardExam(): BelongsTo
    {
        return $this->belongsTo(BoardExam::class);
    }

    /** @return HasMany<Topic, $this> */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    /** @return BelongsToMany<Exam, $this> */
    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_subjects')
            ->withPivot('question_count');
    }
}
