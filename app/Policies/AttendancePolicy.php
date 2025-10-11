<?php

namespace App\Policies;

use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AttendancePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user): bool
    {
        return 'ok';
        return in_array($user->role, ['admin', 'teacher']);
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StudentAttendance  $studentAttendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, StudentAttendance $attendance): bool
    {return 'okk';
        // Admin and teachers can view all
        if (in_array($user->role, ['admin', 'teacher'])) {
            return true;
        }

        // Students can only view their own attendance
        if ($user->role === 'student') {
            return $user->student && $user->student->id === $attendance->student_id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'teacher']);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StudentAttendance  $studentAttendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, StudentAttendance $attendance): bool
    {
        return 'okddd';
        // Admin can update any
        if ($user->role === 'admin') {
            return true;
        }

        // Teacher can update their own marked attendance
        if ($user->role === 'teacher') {
            return $attendance->marked_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StudentAttendance  $studentAttendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, StudentAttendance $attendance): bool
    {
        // Only admin can delete
        if ($user->role === 'admin') {
            return true;
        }

        // Teacher can delete their own marked attendance within 24 hours
        if ($user->role === 'teacher') {
            return $attendance->marked_by === $user->id
                && $attendance->marked_at->diffInHours(now()) < 24;
        }

        return false;
    }

    public function viewReports(User $user): bool
    {
        return 'ok';
        return in_array($user->role, ['admin', 'teacher']);
    }

    /**
     * Determine whether the user can export reports.
     */
    public function exportReports(User $user): bool
    {
        return 'ok';
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can mark bulk attendance.
     */
    public function markBulk(User $user): bool
    {
        return 'ok';
        return in_array($user->role, ['admin', 'teacher']);
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StudentAttendance  $studentAttendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, StudentAttendance $studentAttendance)
    {
        return 'ok';
        //
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\StudentAttendance  $studentAttendance
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, StudentAttendance $studentAttendance)
    {
        //
    }
}
