import React, { useContext } from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { AuthContext } from '../context/AuthContext';

const RoleBasedRoute = ({ allowedRoles }) => {
    const { user, loading } = useContext(AuthContext);

    if (loading) return <div className="d-flex justify-content-center mt-5"><div className="spinner-border text-primary" role="status"></div></div>;

    if (!user) return <Navigate to="/login" replace />;

    return allowedRoles.includes(user.role) ? <Outlet /> : <Navigate to="/dashboard" replace />;
};

export default RoleBasedRoute;
