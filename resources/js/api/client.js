import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 && ! error.config?.url?.includes('/auth/login')) {
            localStorage.removeItem('auth_token');
            window.location.href = '/login';
        }

        return Promise.reject(error);
    },
);

export function extractData(response) {
    return response.data?.data ?? response.data;
}

export function extractError(error) {
    const data = error.response?.data;

    return {
        message: data?.message ?? 'Não foi possível realizar a operação',
        errors: data?.errors ?? {},
        status: error.response?.status ?? 500,
    };
}

export function postFormData(url, formData) {
    return api.post(url, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    });
}

export default api;
