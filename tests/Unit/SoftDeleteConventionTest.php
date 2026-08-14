<?php

namespace Tests\Unit;

use App\Models\BaseModel;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use Tests\TestCase;

class SoftDeleteConventionTest extends TestCase
{
    public function test_all_business_models_use_soft_deletes(): void
    {
        $modelsPath = app_path('Models');
        $files = File::files($modelsPath);

        $excluded = ['BaseModel.php', 'User.php'];

        foreach ($files as $file) {
            if (in_array($file->getFilename(), $excluded, true)) {
                continue;
            }

            $class = 'App\\Models\\'.Str::before($file->getFilename(), '.php');
            $reflection = new ReflectionClass($class);

            $this->assertTrue(
                $reflection->isSubclassOf(BaseModel::class),
                "{$class} deve estender BaseModel para garantir deleção lógica.",
            );
        }
    }

    public function test_user_model_uses_soft_deletes(): void
    {
        $traits = class_uses_recursive(User::class);

        $this->assertContains(
            SoftDeletes::class,
            $traits,
            'User deve usar SoftDeletes (Authenticatable não estende BaseModel).',
        );
    }

    public function test_base_model_uses_soft_deletes(): void
    {
        $traits = class_uses_recursive(BaseModel::class);

        $this->assertContains(SoftDeletes::class, $traits);
        $this->assertTrue(is_subclass_of(Gym::class, BaseModel::class));
    }
}
