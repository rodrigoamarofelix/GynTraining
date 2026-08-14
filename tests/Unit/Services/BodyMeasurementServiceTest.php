<?php

namespace Tests\Unit\Services;

use App\Models\Gym;
use App\Models\Student;
use App\Models\User;
use App\Services\Progress\BodyMeasurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BodyMeasurementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_bmi_when_weight_and_height_are_provided(): void
    {
        $student = $this->createStudent();

        $service = app(BodyMeasurementService::class);

        $measurement = $service->create([
            'student_id' => $student->id,
            'measured_at' => '2026-08-01',
            'weight' => 80,
            'height' => 180,
        ]);

        $this->assertSame(24.69, (float) $measurement->bmi);
    }

    public function test_bmi_is_null_when_height_is_missing(): void
    {
        $student = $this->createStudent();
        $service = app(BodyMeasurementService::class);

        $measurement = $service->create([
            'student_id' => $student->id,
            'measured_at' => '2026-08-01',
            'weight' => 80,
        ]);

        $this->assertNull($measurement->bmi);
    }

    public function test_recalculates_bmi_on_update(): void
    {
        $student = $this->createStudent();
        $service = app(BodyMeasurementService::class);

        $measurement = $service->create([
            'student_id' => $student->id,
            'measured_at' => '2026-08-01',
            'weight' => 80,
            'height' => 180,
        ]);

        $updated = $service->update($measurement, [
            'weight' => 90,
        ]);

        $this->assertSame(27.78, (float) $updated->bmi);
    }

    private function createStudent(): Student
    {
        $gym = Gym::query()->create([
            'name' => 'Gym Unit',
            'slug' => 'gym-unit',
            'status' => 'active',
        ]);

        $user = User::factory()->create();

        return Student::query()->create([
            'user_id' => $user->id,
            'gym_id' => $gym->id,
            'status' => 'active',
        ]);
    }
}
