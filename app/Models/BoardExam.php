<?php

namespace App\Models;

use Database\Factories\BoardExamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardExam extends Model
{
    /** @use HasFactory<BoardExamFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    /** @return HasMany<Subject, $this> */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }

    /** @return HasMany<Exam, $this> */
    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class);
    }
}
