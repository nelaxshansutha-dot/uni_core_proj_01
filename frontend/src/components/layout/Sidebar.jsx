import React, { useContext } from 'react';
import { NavLink, Link, useLocation } from 'react-router-dom';
import { AuthContext } from '../../context/AuthContext';
import { 
    LayoutDashboard, 
    Search, 
    ShoppingBag, 
    BookOpen, 
    Users, 
    Settings,
    ShieldAlert,
    UserCheck,
    AlertTriangle,
    Activity
} from 'lucide-react';
import logo from '../../assets/logo.jpg';

const Sidebar = () => {
    const { user } = useContext(AuthContext);
    const location = useLocation();

    const isActive = (path, tab = null) => {
        const searchParams = new URLSearchParams(location.search);
        const currentTab = searchParams.get('tab');
        if (tab) {
            return location.pathname === path && currentTab === tab;
        }
        return location.pathname === path && !currentTab;
    };

    return (
        <div className="sidebar d-flex flex-column p-3 position-fixed shadow-sm" style={{ width: '250px', zIndex: 1000 }}>
            <div className="d-flex align-items-center mb-4 px-2 mt-2">
                <div className="bg-white rounded p-1 me-2 d-flex align-items-center justify-content-center" style={{ width: '40px', height: '40px' }}>
                    <img src={logo} alt="UniCore Logo" style={{ width: '100%', height: '100%', objectFit: 'contain' }} />
                </div>
                <h4 className="m-0 fw-bold text-white tracking-tight">UniCore</h4>
            </div>
            
            <hr className="border-secondary mt-0" />

            <ul className="nav nav-pills flex-column mb-auto gap-1">
                {user?.role === 'admin' ? (
                    <>
                        <li className="nav-item">
                            <Link 
                                to="/admin" 
                                className={`nav-link d-flex align-items-center gap-3 ${isActive('/admin') ? 'active' : ''}`}
                            >
                                <LayoutDashboard size={20} />
                                Dashboard
                            </Link>
                        </li>
                        <li>
                            <Link 
                                to="/admin?tab=users" 
                                className={`nav-link d-flex align-items-center gap-3 ${isActive('/admin', 'users') ? 'active' : ''}`}
                            >
                                <Users size={20} />
                                User Management
                            </Link>
                        </li>
                        <li>
                            <Link 
                                to="/admin?tab=course-rep" 
                                className={`nav-link d-flex align-items-center gap-3 ${isActive('/admin', 'course-rep') ? 'active' : ''}`}
                            >
                                <UserCheck size={20} />
                                Course Reps
                            </Link>
                        </li>
                        <li>
                            <Link 
                                to="/admin?tab=content" 
                                className={`nav-link d-flex align-items-center gap-3 ${isActive('/admin', 'content') ? 'active' : ''}`}
                            >
                                <Activity size={20} />
                                Content Moderation
                            </Link>
                        </li>
                        <li>
                            <Link 
                                to="/admin?tab=reports" 
                                className={`nav-link d-flex align-items-center gap-3 ${isActive('/admin', 'reports') ? 'active' : ''}`}
                            >
                                <AlertTriangle size={20} />
                                Reports &amp; Complaints
                            </Link>
                        </li>
                        <li className="mt-4">
                            <div className="text-uppercase text-secondary small fw-bold px-3 mb-2">System</div>
                        </li>
                        <li>
                            <NavLink to="/settings" className="nav-link d-flex align-items-center gap-3">
                                <Settings size={20} />
                                Settings
                            </NavLink>
                        </li>
                    </>
                ) : (
                    <>
                        <li className="nav-item">
                            <NavLink to="/dashboard" className="nav-link d-flex align-items-center gap-3">
                                <LayoutDashboard size={20} />
                                Dashboard
                            </NavLink>
                        </li>
                        <li>
                            <NavLink to="/lost-items" className="nav-link d-flex align-items-center gap-3">
                                <Search size={20} />
                                Lost-Items
                            </NavLink>
                        </li>
                        <li>
                            <NavLink to="/marketplace" className="nav-link d-flex align-items-center gap-3">
                                <ShoppingBag size={20} />
                                Marketplace
                            </NavLink>
                        </li>
                        <li>
                            <NavLink to="/notes" className="nav-link d-flex align-items-center gap-3">
                                <BookOpen size={20} />
                                Notes Sharing
                            </NavLink>
                        </li>
                        <li>
                            <NavLink to="/peer-learning" className="nav-link d-flex align-items-center gap-3">
                                <Users size={20} />
                                Peer Learning
                            </NavLink>
                        </li>
                        <li>
                            <NavLink to="/settings" className="nav-link d-flex align-items-center gap-3">
                                <Settings size={20} />
                                Settings
                            </NavLink>
                        </li>
                    </>
                )}
            </ul>
            
            <div className="mt-auto pt-4 pb-2 text-center text-secondary small">
                &copy; 2026 UniCore
            </div>
        </div>
    );
};

export default Sidebar;
