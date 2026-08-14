<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkoutExercise;

class WorkoutExercisePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return (new WorkoutPlanPolicy)->viewAny($user);
    }

    public function view(User $user, WorkoutExercise $workoutExercise): bool
    {
        $workoutExercise->loadMissing('workoutDay.workoutPlan');

        return (new WorkoutPlanPolicy)->view($user, $workoutExercise->workoutDay->workoutPlan);
    }

    public function create(User $user): bool
    {
        return (new WorkoutPlanPolicy)->create($user);
    }

    public function update(User $user, WorkoutExercise $workoutExercise): bool
    {
        $workoutExercise->loadMissing('workoutDay.workoutPlan');

        return (new WorkoutPlanPolicy)->update($user, $workoutExercise->workoutDay->workoutPlan);
    }

    public function delete(User $user, WorkoutExercise $workoutExercise): bool
    {
        return $this->update($user, $workoutExercise);
    }
}
