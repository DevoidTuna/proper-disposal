const TOKEN_KEY = 'descarte_admin_token';

export const getToken = () => localStorage.getItem(TOKEN_KEY);
export const setToken = (t) => localStorage.setItem(TOKEN_KEY, t);
export const clearToken = () => localStorage.removeItem(TOKEN_KEY);
export const isAuthenticated = () => !!getToken();

async function request(url, options = {}, { auth = false } = {}) {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        ...(options.headers || {}),
    };
    if (auth && getToken()) {
        headers.Authorization = `Bearer ${getToken()}`;
    }

    const res = await fetch(url, { ...options, headers });

    if (res.status === 401 && auth) {
        clearToken();
    }

    if (!res.ok) {
        let body = null;
        try {
            body = await res.json();
        } catch {
            body = null;
        }
        const error = new Error(body?.message || 'Erro na requisição');
        error.status = res.status;
        error.body = body;
        throw error;
    }

    if (res.status === 204) return null;
    return res.json();
}

export const getApprovedPoints = () => request('/api/points');
export const createPoint = (data) =>
    request('/api/points', { method: 'POST', body: JSON.stringify(data) });

export async function login(username, password) {
    const data = await request('/api/login', {
        method: 'POST',
        body: JSON.stringify({ username, password }),
    });
    setToken(data.token);
    return data.user;
}

export async function logout() {
    try {
        await request('/api/logout', { method: 'POST' }, { auth: true });
    } finally {
        clearToken();
    }
}

export const getAllPoints = () =>
    request('/api/admin/points', {}, { auth: true });

export const approvePoint = (id) =>
    request(`/api/admin/points/${id}/approve`, { method: 'PATCH' }, { auth: true });

export const updatePoint = (id, data) =>
    request(`/api/admin/points/${id}`, { method: 'PATCH', body: JSON.stringify(data) }, { auth: true });

export const deletePoint = (id) =>
    request(`/api/admin/points/${id}`, { method: 'DELETE' }, { auth: true });
