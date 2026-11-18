<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    // Controllers scope the list: examinees get published exams only.
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Exam $exam): bool
    {
        return $user->isStaff() || $exam->is_published;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /**
     * Determine whether the user can update the model.
     */
    // Published exams are locked for everyone until an admin unpublishes them.
    public function update(User $user, Exam $exam): bool
    {
        return $user->isStaff() && ! $exam->is_published;
    }

    public function publish(User $user, Exam $exam): bool
    {
        // publish and unpublish
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Exam $exam): bool
    {
        return $user->isAdmin() && ! $exam->hasAttempts();
    }

    public function take(User $user, Exam $exam): bool
    {
        return $exam->is_published;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Exam $exam): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Exam $exam): bool
    {
        return false;
    }
}
