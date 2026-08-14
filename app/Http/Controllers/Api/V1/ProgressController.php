<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ExerciseLog;
use App\Services\Progress\ProgressService;
use App\Services\Progress\StudentProgressAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends ApiController
{
    public function __construct(
        private readonly ProgressService $progressService,
        private readonly StudentProgressAccess $access,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ExerciseLog::class);

        $studentId = $this->access->resolveStudentId(
            $request->user(),
            $request->integer('student_id') ?: null,
        );

        $summary = $this->progressService->summary(
            $studentId,
            $request->integer('exercise_id') ?: null,
            $request->string('period')->toString() ?: null,
        );

        return $this->successResponse($summary, 'Evolução recuperada com sucesso');
    }
}
