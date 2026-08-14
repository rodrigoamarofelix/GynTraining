<?php

namespace App\Http\Requests\Concerns;

use App\Support\ManagedGymScope;
use Illuminate\Validation\Validator;

trait ValidatesManagedGym
{
    protected function ensureManagedGym(Validator $validator, ?int $gymId, string $field = 'gym_id'): void
    {
        if ($gymId === null) {
            return;
        }

        $user = $this->user();

        if (! $user || ManagedGymScope::manages($user, $gymId)) {
            return;
        }

        $validator->errors()->add($field, 'Você não tem permissão para esta academia.');
    }
}
