<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutDay;

class WorkoutDayPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return (new WorkoutPlanPolicy)->viewAny($user);
    }

    public function view(User $user, WorkoutDay $workoutDay): bool
    {
        $workoutDay->loadMissing('workoutPlan');

        return (new WorkoutPlanPolicy)->view($user, $workoutDay->workoutPlan);
    }

    public function create(User $user): bool
    {
        return (new WorkoutPlanPolicy)->create($user);
    }

    public function update(User $user, WorkoutDay $workoutDay): bool
    {
        $workoutDay->loadMissing('workoutPlan');

        return (new WorkoutPlanPolicy)->update($user, $workoutDay->workoutPlan);
    }

    public function delete(User $user, WorkoutDay $workoutDay): bool
    {
        return $this->update($user, $workoutDay);
    }
}
