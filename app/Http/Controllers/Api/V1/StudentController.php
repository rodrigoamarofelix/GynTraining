<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreStudentRequest;
use App\Http\Requests\Api\V1\UpdateStudentRequest;
use App\Http\Resources\StudentActivityLogResource;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Services\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends ApiController
{
    public function __construct(
        private readonly StudentService $studentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Student::class);

        $filters = array_merge(
            $request->only(['search', 'scope', 'gym_id']),
            $this->studentService->filtersForUser($request->user()),
        );

        if (! isset($filters['scope'])) {
            $filters['scope'] = 'active';
        }

        $students = $this->studentService->list($filters);

        return $this->paginatedResponse($students, StudentResource::class);
    }

    public function store(StoreStudentRequest $request): JsonResponse
    {
        $student = $this->studentService->create($request->validated(), $request->user());

        return $this->successResponse(new StudentResource($student), 'Aluno criado com sucesso', 201);
    }

    public function show(Student $student): JsonResponse
    {
        $this->authorize('view', $student);

        return $this->successResponse(new StudentResource($student->load(['user', 'gym', 'trainer.user'])));
    }

    public function update(UpdateStudentRequest $request, Student $student): JsonResponse
    {
        $student = $this->studentService->update($student, $request->validated(), $request->user());

        return $this->successResponse(new StudentResource($student), 'Aluno atualizado com sucesso');
    }

    public function destroy(Request $request, Student $student): JsonResponse
    {
        $this->authorize('delete', $student);

        $this->studentService->delete($student, $request->user());

        return $this->successResponse(message: 'Aluno removido com sucesso');
    }

    public function restore(Request $request, int $student): JsonResponse
    {
        $studentModel = $this->studentService->find($student, true);

        abort_if(! $studentModel, 404, 'Aluno não encontrado.');

        $this->authorize('update', $studentModel);

        $restored = $this->studentService->restore($student, $request->user());

        return $this->successResponse(new StudentResource($restored), 'Aluno reativado com sucesso');
    }

    public function activityLogs(int $student, Request $request): JsonResponse
    {
        $studentModel = $this->studentService->find($student, true);

        abort_if(! $studentModel, 404, 'Aluno não encontrado.');

        $this->authorize('view', $studentModel);

        $logs = $this->studentService->activityLogs(
            $student,
            (int) $request->integer('per_page', 20),
        );

        return $this->paginatedResponse($logs, StudentActivityLogResource::class);
    }
}
