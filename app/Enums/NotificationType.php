<?php

namespace App\Enums;

enum NotificationType: string
{
    case WorkoutAvailable = 'workout_available';
    case InactiveStudent = 'inactive_student';
    case WorkoutPlanUpdated = 'workout_plan_updated';
    case WorkoutReminder = 'workout_reminder';
    case PendingStudentRegistration = 'pending_student_registration';
}
