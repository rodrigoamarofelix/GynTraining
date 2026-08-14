<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\BodyMeasurementController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ExerciseCategoryController;
use App\Http\Controllers\Api\V1\ExerciseController;
use App\Http\Controllers\Api\V1\GoalController;
use App\Http\Controllers\Api\V1\GymController;
use App\Http\Controllers\Api\V1\HistoryController;
use App\Http\Controllers\Api\V1\MuscleGroupController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProgressController;
use App\Http\Controllers\Api\V1\ProgressPhotoController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\TrainerController;
use App\Http\Controllers\Api\V1\WorkoutDayController;
use App\Http\Controllers\Api\V1\WorkoutExecutionController;
use App\Http\Controllers\Api\V1\WorkoutExerciseController;
use App\Http\Controllers\Api\V1\WorkoutPlanController;
use App\Http\Controllers\Api\V1\WorkoutSessionController;
use App\Http\Controllers\Api\V1\WorkoutSetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::get('registration-gyms', [AuthController::class, 'registrationGyms']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::put('profile', [AuthController::class, 'updateProfile']);
            Route::put('password', [AuthController::class, 'updatePassword']);
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('gyms/{gym}/members', [GymController::class, 'members']);
        Route::get('gyms/{gym}/activity-logs', [GymController::class, 'activityLogs']);
        Route::post('gyms/{gym}/restore', [GymController::class, 'restore']);
        Route::apiResource('gyms', GymController::class);
        Route::get('students/{student}/activity-logs', [StudentController::class, 'activityLogs']);
        Route::post('students/{student}/restore', [StudentController::class, 'restore']);
        Route::apiResource('students', StudentController::class);
        Route::get('trainers/{trainer}/activity-logs', [TrainerController::class, 'activityLogs']);
        Route::post('trainers/{trainer}/restore', [TrainerController::class, 'restore']);
        Route::apiResource('trainers', TrainerController::class);
        Route::get('muscle-groups/{muscle_group}/activity-logs', [MuscleGroupController::class, 'activityLogs']);
        Route::post('muscle-groups/{muscle_group}/restore', [MuscleGroupController::class, 'restore']);
        Route::apiResource('muscle-groups', MuscleGroupController::class);
        Route::get('exercise-categories/{exercise_category}/activity-logs', [ExerciseCategoryController::class, 'activityLogs']);
        Route::post('exercise-categories/{exercise_category}/restore', [ExerciseCategoryController::class, 'restore']);
        Route::apiResource('exercise-categories', ExerciseCategoryController::class);
        Route::get('exercises/{exercise}/activity-logs', [ExerciseController::class, 'activityLogs']);
        Route::post('exercises/{exercise}/restore', [ExerciseController::class, 'restore']);
        Route::apiResource('exercises', ExerciseController::class);

        Route::post('workouts/{workout}/restore', [WorkoutPlanController::class, 'restore']);
        Route::apiResource('workouts', WorkoutPlanController::class)
            ->parameters(['workouts' => 'workout'])
            ->withTrashed(['show']);
        Route::post('workouts/{workout}/start', [WorkoutExecutionController::class, 'start']);
        Route::post('workouts/{workout}/finish', [WorkoutExecutionController::class, 'finish']);
        Route::apiResource('workout-days', WorkoutDayController::class);
        Route::apiResource('workout-exercises', WorkoutExerciseController::class);
        Route::apiResource('workout-sets', WorkoutSetController::class);

        Route::get('workout-sessions', [WorkoutSessionController::class, 'index']);
        Route::post('workout-sessions', [WorkoutSessionController::class, 'store']);
        Route::get('workout-sessions/{workout_session}', [WorkoutSessionController::class, 'show']);
        Route::post('workout-sessions/{workout_session}/finish', [WorkoutSessionController::class, 'finish']);
        Route::post('workout-sessions/{workout_session}/cancel', [WorkoutSessionController::class, 'cancel']);

        Route::get('history', [HistoryController::class, 'index']);

        Route::get('progress', [ProgressController::class, 'index']);
        Route::get('dashboard', [DashboardController::class, 'index']);
        Route::apiResource('body-measurements', BodyMeasurementController::class);
        Route::apiResource('progress-photos', ProgressPhotoController::class);
        Route::apiResource('goals', GoalController::class);

        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notification-preferences', [NotificationController::class, 'preferences']);
        Route::put('notification-preferences', [NotificationController::class, 'updatePreferences']);
    });
});
