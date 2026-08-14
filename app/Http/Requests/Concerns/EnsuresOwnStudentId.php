<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Validator;

trait EnsuresOwnStudentId
{
    protected function ensureOwnStudentId(Validator $validator): void
    {
        $student = $this->user()?->student;

        if (! $student) {
            return;
        }

        if ($this->filled('student_id') && (int) $this->input('student_id') !== $student->id) {
            $validator->errors()->add('student_id', 'Não é permitido registrar dados para outro aluno.');
        }
    }

    protected function mergeOwnStudentIdIfMissing(): void
    {
        if ($this->user()?->student && ! $this->filled('student_id')) {
            $this->merge(['student_id' => $this->user()->student->id]);
        }
    }

    protected function passedValidation(): void
    {
        if ($this->user()?->student) {
            $this->merge(['student_id' => $this->user()->student->id]);
        }
    }
}
