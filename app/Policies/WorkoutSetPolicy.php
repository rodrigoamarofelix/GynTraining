<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutSet;

class WorkoutSetPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return (new WorkoutPlanPolicy)->viewAny($user);
    }

    public function view(User $user, WorkoutSet $workoutSet): bool
    {
        $workoutSet->loadMissing('workoutExercise.workoutDay.workoutPlan');

        return (new WorkoutPlanPolicy)->view($user, $workoutSet->workoutExercise->workoutDay->workoutPlan);
    }

    public function create(User $user): bool
    {
        return (new WorkoutPlanPolicy)->create($user);
    }

    public function update(User $user, WorkoutSet $workoutSet): bool
    {
        $workoutSet->loadMissing('workoutExercise.workoutDay.workoutPlan');

        return (new WorkoutPlanPolicy)->update($user, $workoutSet->workoutExercise->workoutDay->workoutPlan);
    }

    public function delete(User $user, WorkoutSet $workoutSet): bool
    {
        return $this->update($user, $workoutSet);
    }
}
