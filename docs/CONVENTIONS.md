# Convenções GynTraining

## Deleção lógica (Soft Delete)

**Regra:** todos os módulos de negócio usam deleção lógica para manter histórico.

- Model base: `App\Models\BaseModel`
- Migration: sempre `$table->softDeletes()`
- Proibido: `forceDelete()` em entidades de negócio
- Delete em cascata: feito na Service Layer com `$model->delete()` em transação

### Entidades com soft delete

users, gyms, students, trainers, roles, permissions, exercises, muscle_groups, exercise_categories, workout_plans, workout_days, workout_exercises, workout_sets

### Exceções (sem soft delete)

Tabelas técnicas: pivots, cache, jobs, password_reset_tokens, personal_access_tokens, sessions
