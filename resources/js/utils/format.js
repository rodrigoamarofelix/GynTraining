export function formatDuration(seconds) {
    if (! seconds && seconds !== 0) {
        return '—';
    }

    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;

    return `${mins}:${String(secs).padStart(2, '0')}`;
}

export function formatDate(value, options = {}) {
    if (! value) {
        return '—';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        ...options,
    }).format(new Date(value));
}

export function formatDateTime(value) {
    if (! value) {
        return '—';
    }

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

export function formatWeight(value) {
    if (value === null || value === undefined) {
        return '—';
    }

    return `${Number(value).toFixed(1)} kg`;
}

export function formatNumber(value) {
    if (value === null || value === undefined) {
        return '—';
    }

    return new Intl.NumberFormat('pt-BR').format(value);
}

export function profileStatusLabel(status) {
    const labels = {
        pending: 'Pendente',
        active: 'Ativo',
        inactive: 'Inativo',
        draft: 'Rascunho',
        completed: 'Concluída',
    };

    return labels[status] ?? status;
}

export function studentActivityActionLabel(action) {
    const labels = {
        created: 'Cadastro',
        updated: 'Alteração',
        deleted: 'Exclusão',
        restored: 'Reativação',
        approved: 'Aprovação',
        registered: 'Auto-cadastro',
    };

    return labels[action] ?? action;
}

export function trainerActivityActionLabel(action) {
    const labels = {
        created: 'Cadastro',
        updated: 'Alteração',
        deleted: 'Exclusão',
        restored: 'Reativação',
    };

    return labels[action] ?? action;
}

export function gymActivityActionLabel(action) {
    const labels = {
        created: 'Cadastro',
        updated: 'Alteração',
        deleted: 'Exclusão',
        restored: 'Reativação',
    };

    return labels[action] ?? action;
}

export function gymStatusLabel(status) {
    const labels = {
        active: 'Ativa',
        inactive: 'Inativa',
    };

    return labels[status] ?? status;
}

export function muscleGroupActivityActionLabel(action) {
    const labels = {
        created: 'Cadastro',
        updated: 'Alteração',
        deleted: 'Exclusão',
        restored: 'Reativação',
    };

    return labels[action] ?? action;
}

export function exerciseCategoryActivityActionLabel(action) {
    const labels = {
        created: 'Cadastro',
        updated: 'Alteração',
        deleted: 'Exclusão',
        restored: 'Reativação',
    };

    return labels[action] ?? action;
}

export function exerciseActivityActionLabel(action) {
    const labels = {
        created: 'Cadastro',
        updated: 'Alteração',
        deleted: 'Exclusão',
        restored: 'Reativação',
    };

    return labels[action] ?? action;
}

export function exerciseDifficultyLabel(difficulty) {
    const labels = {
        beginner: 'Iniciante',
        intermediate: 'Intermediário',
        advanced: 'Avançado',
    };

    return labels[difficulty] ?? difficulty;
}

export function roleLabel(role) {
    const labels = {
        admin: 'Administrador',
        gym_admin: 'Admin da academia',
        trainer: 'Professor',
        student: 'Aluno',
    };

    return labels[role] ?? role;
}

export function firstValidationError(errors) {
    if (! errors || typeof errors !== 'object') {
        return null;
    }

    const firstField = Object.values(errors)[0];

    if (Array.isArray(firstField)) {
        return firstField[0] ?? null;
    }

    return firstField ?? null;
}
