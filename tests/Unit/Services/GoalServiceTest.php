<?php

namespace Tests\Unit\Services;

use App\Models\Goal;
use App\Models\Gym;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class GoalServiceTest extends TestCase
{
    public function test_goal_progress_percentage_is_capped_at_100(): void
    {
        $goal = new Goal([
            'target' => 10,
            'current_value' => 15,
        ]);

        $this->assertSame(100.0, $goal->progressPercentage());
    }

    public function test_goal_progress_percentage_calculates_correctly(): void
    {
        $goal = new Goal([
            'target' => 4,
            'current_value' => 1,
        ]);

        $this->assertSame(25.0, $goal->progressPercentage());
    }

    public function test_goal_progress_percentage_returns_zero_for_invalid_target(): void
    {
        $goal = new Goal([
            'target' => 0,
            'current_value' => 5,
        ]);

        $this->assertSame(0.0, $goal->progressPercentage());
    }
}
