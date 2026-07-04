import React, { useState, useEffect } from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import Sidebar from './Sidebar';
import Topbar from './Topbar';

const DashboardLayout = () => {
    const [isSidebarOpen, setIsSidebarOpen] = useState(false);
    const location = useLocation();

    // Close sidebar on route change on mobile
    useEffect(() => {
        setIsSidebarOpen(false);
    }, [location.pathname]);

    return (
        <div className="d-flex position-relative" style={{ minHeight: '100vh', overflowX: 'hidden' }}>
            {/* Sidebar backdrop for mobile */}
            {isSidebarOpen && (
                <div 
                    className="position-fixed top-0 bottom-0 start-0 end-0 bg-dark bg-opacity-50 z-2 d-md-none"
                    onClick={() => setIsSidebarOpen(false)}
                    style={{ zIndex: 1040 }}
                ></div>
            )}
            
            <Sidebar isOpen={isSidebarOpen} setIsOpen={setIsSidebarOpen} />
            
            <div className="dashboard-content-wrapper flex-grow-1 d-flex flex-column transition-all w-100">
                <Topbar toggleSidebar={() => setIsSidebarOpen(!isSidebarOpen)} />
                <main className="p-2 p-md-4" style={{ backgroundColor: 'var(--bg-color)', flexGrow: 1 }}>
                    <Outlet />
                </main>
            </div>
        </div>
    );
};

export default DashboardLayout;
