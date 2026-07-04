import axios from 'axios';

const api = axios.create({
    baseURL: 'http://localhost/uni_core_proj_01/backend/api',
    withCredentials: true,
    headers: {
        'Content-Type': 'application/json'
    }
});

// Add a response interceptor to handle 401 Unauthorized errors globally
api.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        if (error.response && error.response.status === 401) {
            // Do not globally redirect if checking auth state
            if (error.config.url && error.config.url.includes('action=me')) {
                return Promise.reject(error);
            }

            console.warn("Unauthorized access - clearing token and redirecting to login");
            
            // Redirect to login if not already there or on the public landing page
            if (window.location.pathname !== '/login' && window.location.pathname !== '/') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);

export default api;
