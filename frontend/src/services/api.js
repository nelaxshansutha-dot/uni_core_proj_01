import axios from 'axios';

const api = axios.create({
    baseURL: 'http://192.168.1.10/uni_core_proj_01/backend/api',
    headers: {
        'Content-Type': 'application/json'
    }
});

api.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;
