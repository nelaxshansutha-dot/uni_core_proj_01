import React from 'react';
import { Outlet } from 'react-router-dom';
import Sidebar from './Sidebar';
import Topbar from './Topbar';

const DashboardLayout = () => {
    return (
        <div className="d-flex" style={{ minHeight: '100vh' }}>
            <Sidebar />
            <div className="flex-grow-1 d-flex flex-column" style={{ marginLeft: '250px' }}>
                <Topbar />
                <main className="p-4" style={{ backgroundColor: 'var(--bg-color)', flexGrow: 1 }}>
                    <Outlet />
                </main>
            </div>
        </div>
    );
};

export default DashboardLayout;
