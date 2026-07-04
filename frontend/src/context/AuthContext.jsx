import React, { createContext, useState, useEffect } from 'react';
import api from '../services/api';

export const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Clear any old legacy tokens from local storage
        ['token', 'unicore_token', 'user', 'unicore_user'].forEach(key => {
            localStorage.removeItem(key);
        });

        const checkAuth = async () => {
            try {
                const res = await api.get('/auth.php?action=me');
                if (res.data.status === 'success') {
                    setUser(res.data.data.user);
                }
            } catch (err) {
                console.error("Not authenticated");
                setUser(null);
            } finally {
                setLoading(false);
            }
        };
        checkAuth();
    }, []);

    const login = (token, userData) => {
        // Token is now set in HttpOnly cookie automatically by the backend
        setUser(userData);
    };

    const logout = async () => {
        try {
            await api.post('/auth.php?action=logout');
        } catch (err) {
            console.error("Logout error", err);
        }
        setUser(null);
        window.location.href = '/login';
    };

    return (
        <AuthContext.Provider value={{ user, loading, login, logout }}>
            {children}
        </AuthContext.Provider>
    );
};
