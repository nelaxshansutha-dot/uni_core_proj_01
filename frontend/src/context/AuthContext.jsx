import React, { createContext, useState, useEffect } from 'react';
import api from '../services/api';

export const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const checkAuth = async () => {
            const token = localStorage.getItem('token') || getCookie('token');
            const storedUser = localStorage.getItem('user');

            if (token && storedUser) {
                try {
                    // Start by trusting the stored user for instant UI rendering
                    setUser(JSON.parse(storedUser));
                    
                    // Securely verify and fetch true profile from the backend
                    const res = await api.get('/profile');
                    if (res.data.success && res.data.data) {
                        // The backend returned the verified user data (which cannot be forged)
                        const verifiedUser = res.data.data;
                        setUser(verifiedUser);
                        localStorage.setItem('user', JSON.stringify(verifiedUser)); // Fix local storage if it was tampered with
                    } else {
                        // Token might be invalid or expired
                        logout();
                    }
                } catch (e) {
                    console.error("Auth validation failed", e);
                    logout();
                }
            } else {
                setUser(null);
            }
            setLoading(false);
        };
        checkAuth();
    }, []);

    const login = (token, userData) => {
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(userData));
        
        // Also store as cookie per user request (7 days expiry)
        const d = new Date();
        d.setTime(d.getTime() + (7*24*60*60*1000));
        document.cookie = `token=${token}; expires=${d.toUTCString()}; path=/`;

        setUser(userData);
    };

    const logout = async () => {
        try {
            await api.post('/auth/logout');
        } catch (err) {
            console.error("Logout error", err);
        }
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        document.cookie = "token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        setUser(null);
        window.location.href = '/login';
    };

    return (
        <AuthContext.Provider value={{ user, loading, login, logout }}>
            {children}
        </AuthContext.Provider>
    );
};

function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}
