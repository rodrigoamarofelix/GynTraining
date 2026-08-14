import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/auth/LoginPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('../pages/auth/RegisterPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: () => import('../pages/auth/ForgotPasswordPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/reset-password',
        name: 'reset-password',
        component: () => import('../pages/auth/ResetPasswordPage.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        name: 'dashboard',
        component: () => import('../pages/DashboardPage.vue'),
        meta: { auth: true },
    },
    {
        path: '/treino',
        name: 'workout',
        component: () => import('../pages/workout/WorkoutPage.vue'),
        meta: { auth: true, roles: ['student'] },
    },
    {
        path: '/treino/executar/:planId/:dayId',
        name: 'workout.execute',
        component: () => import('../pages/workout/WorkoutExecutePage.vue'),
        meta: { auth: true, roles: ['student'] },
    },
    {
        path: '/historico',
        name: 'history',
        component: () => import('../pages/HistoryPage.vue'),
        meta: { auth: true, roles: ['student'] },
    },
    {
        path: '/evolucao',
        name: 'progress',
        component: () => import('../pages/progress/ProgressPage.vue'),
        meta: { auth: true, roles: ['student'] },
    },
    {
        path: '/medidas',
        name: 'measurements',
        component: () => import('../pages/progress/MeasurementsPage.vue'),
        meta: { auth: true, roles: ['student'] },
    },
    {
        path: '/metas',
        name: 'goals',
        component: () => import('../pages/progress/GoalsPage.vue'),
        meta: { auth: true, roles: ['student'] },
    },
    {
        path: '/fotos',
        name: 'photos',
        component: () => import('../pages/progress/PhotosPage.vue'),
        meta: { auth: true, roles: ['student'] },
    },
    {
        path: '/exercicios',
        name: 'exercises',
        component: () => import('../pages/ExercisesPage.vue'),
        meta: { auth: true },
    },
    {
        path: '/notificacoes',
        name: 'notifications',
        component: () => import('../pages/NotificationsPage.vue'),
        meta: { auth: true },
    },
    {
        path: '/perfil',
        name: 'profile',
        component: () => import('../pages/ProfilePage.vue'),
        meta: { auth: true },
    },
    {
        path: '/professor',
        name: 'trainer.panel',
        component: () => import('../pages/trainer/TrainerPanelPage.vue'),
        meta: { auth: true, roles: ['trainer'] },
    },
    {
        path: '/professor/alunos',
        name: 'trainer.students',
        component: () => import('../pages/trainer/StudentsListPage.vue'),
        meta: { auth: true, roles: ['trainer'] },
    },
    {
        path: '/professor/alunos/:id',
        name: 'trainer.student',
        component: () => import('../pages/trainer/StudentDetailPage.vue'),
        meta: { auth: true, roles: ['trainer'] },
    },
    {
        path: '/professor/fichas/nova',
        name: 'trainer.workout.create',
        component: () => import('../pages/trainer/WorkoutPlanFormPage.vue'),
        meta: { auth: true, roles: ['trainer'] },
    },
    {
        path: '/professor/fichas/:id',
        name: 'trainer.workout.show',
        component: () => import('../pages/trainer/WorkoutPlanDetailPage.vue'),
        meta: { auth: true, roles: ['trainer'] },
    },
    {
        path: '/professor/fichas/:id/editar',
        name: 'trainer.workout.edit',
        component: () => import('../pages/trainer/WorkoutPlanFormPage.vue'),
        meta: { auth: true, roles: ['trainer'] },
    },
    {
        path: '/admin',
        name: 'admin.panel',
        component: () => import('../pages/admin/AdminPanelPage.vue'),
        meta: { auth: true, roles: ['admin', 'gym_admin'] },
    },
    {
        path: '/admin/academias',
        name: 'admin.gyms',
        component: () => import('../pages/admin/AdminGymsPage.vue'),
        meta: { auth: true, roles: ['admin', 'gym_admin'] },
    },
    {
        path: '/admin/exercicios',
        name: 'admin.exercises',
        component: () => import('../pages/admin/AdminExercisesPage.vue'),
        meta: { auth: true, roles: ['admin', 'gym_admin'] },
    },
    {
        path: '/admin/grupos',
        name: 'admin.muscle-groups',
        component: () => import('../pages/admin/AdminMuscleGroupsPage.vue'),
        meta: { auth: true, roles: ['admin'] },
    },
    {
        path: '/admin/categorias',
        name: 'admin.exercise-categories',
        component: () => import('../pages/admin/AdminExerciseCategoriesPage.vue'),
        meta: { auth: true, roles: ['admin'] },
    },
    {
        path: '/admin/alunos',
        name: 'admin.students',
        component: () => import('../pages/admin/AdminStudentsPage.vue'),
        meta: { auth: true, roles: ['admin', 'gym_admin'] },
    },
    {
        path: '/admin/professores',
        name: 'admin.trainers',
        component: () => import('../pages/admin/AdminTrainersPage.vue'),
        meta: { auth: true, roles: ['admin', 'gym_admin'] },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior() {
        return { top: 0 };
    },
});

function resolveRoleRedirect(auth) {
    if (auth.isTrainer && ! auth.isPlatformAdmin) {
        return { name: 'trainer.panel' };
    }

    if (auth.isAdmin) {
        return { name: 'admin.panel' };
    }

    return { name: 'dashboard' };
}

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (! auth.initialized) {
        await auth.initialize();
    }

    if (to.meta.auth && ! auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return resolveRoleRedirect(auth);
    }

    if (to.meta.roles?.length) {
        const allowed = to.meta.roles.some((role) => auth.roles.includes(role));

        if (! allowed) {
            return resolveRoleRedirect(auth);
        }
    }

    return true;
});

export default router;
