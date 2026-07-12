import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import ProtectedRoute from './ProtectedRoute';
import RoleBasedRoute from './RoleBasedRoute';
import DashboardLayout from '../components/layout/DashboardLayout';

import Login from '../pages/auth/Login';
import Register from '../pages/auth/Register';
import OTPVerification from '../pages/auth/OTPVerification';
import ForgotPassword from '../pages/auth/ForgotPassword';
import ResetPassword from '../pages/auth/ResetPassword';
import ChangeRepPassword from '../pages/auth/ChangeRepPassword';

import Dashboard from '../pages/dashboard/Dashboard';
import RepDashboard from '../pages/dashboard/RepDashboard';
import LostItems from '../pages/lost-items/LostItems';
import Marketplace from '../pages/marketplace/Marketplace';
import Notes from '../pages/notes/Notes';
import NotesMonitoring from '../pages/notes/NotesMonitoring';
import PeerLearning from '../pages/peer-learning/PeerLearning';
import Notifications from '../pages/notifications/Notifications';
import Profile from '../pages/auth/Profile';
import AdminPanel from '../pages/admin/AdminPanel';
import Home from '../pages/home/Home';
import Settings from '../pages/settings/Settings';

const AppRoutes = () => {
    return (
        <Routes>
            {/* Public Routes */}
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            <Route path="/otp" element={<OTPVerification />} />
            <Route path="/forgot-password" element={<ForgotPassword />} />
            <Route path="/reset-password" element={<ResetPassword />} />
            <Route path="/change-rep-password" element={<ChangeRepPassword />} />
            <Route path="/" element={<Home />} />

            {/* Protected Routes inside Dashboard Layout */}
            <Route element={<ProtectedRoute />}>
                <Route element={<DashboardLayout />}>
                    <Route path="/dashboard" element={<Dashboard />} />
                    
                    {/* Course Rep Only Route */}
                    <Route element={<RoleBasedRoute allowedRoles={['course_representative', 'rep']} />}>
                        <Route path="/rep-dashboard" element={<RepDashboard />} />
                        <Route path="/notes-monitoring" element={<NotesMonitoring />} />
                    </Route>

                    <Route path="/lost-items" element={<LostItems />} />
                    <Route path="/marketplace" element={<Marketplace />} />
                    <Route path="/notes" element={<Notes />} />
                    <Route path="/peer-learning" element={<PeerLearning />} />
                    <Route path="/notifications" element={<Notifications />} />
                    <Route path="/settings" element={<Settings />} />
                    
                    <Route path="/profile" element={<Profile />} />

                    {/* Admin Only Route */}
                    <Route element={<RoleBasedRoute allowedRoles={['admin']} />}>
                        <Route path="/admin" element={<AdminPanel />} />
                    </Route>
                </Route>
            </Route>

            {/* Fallback */}
            <Route path="*" element={<Navigate to="/dashboard" replace />} />
        </Routes>
    );
};

export default AppRoutes;
