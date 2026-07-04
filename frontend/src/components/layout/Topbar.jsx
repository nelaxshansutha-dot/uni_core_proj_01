import React, { useContext, useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { AuthContext } from '../../context/AuthContext';
import { LogOut, User, Menu } from 'lucide-react';

const Topbar = ({ toggleSidebar }) => {
    const { user, logout } = useContext(AuthContext);
    const navigate = useNavigate();
    const [dropdownOpen, setDropdownOpen] = useState(false);

    const handleLogout = () => {
        logout();
        navigate('/login');
    };

    const getInitials = () => {
        if (!user) return '?';
        const first = user.first_name ? user.first_name.charAt(0) : '';
        const last = user.last_name ? user.last_name.charAt(0) : '';
        return (first + last).toUpperCase() || user.enrollment_no.substring(0, 2);
    };

    return (
        <div className="topbar d-flex justify-content-between align-items-center px-3 px-md-4 py-3 sticky-top z-3 bg-white border-bottom shadow-sm">
            <div className="d-flex align-items-center gap-3">
                <button 
                    className="btn btn-light d-md-none d-flex align-items-center justify-content-center p-2 border shadow-sm rounded"
                    onClick={toggleSidebar}
                    aria-label="Toggle Sidebar"
                >
                    <Menu size={20} />
                </button>
                <h5 className="m-0 text-dark fw-semibold d-none d-sm-block">Welcome back, {user?.first_name || 'User'}!</h5>
                <h5 className="m-0 text-dark fw-semibold d-block d-sm-none">Hi, {user?.first_name || 'User'}!</h5>
            </div>
            
            <div className="d-flex align-items-center gap-3">


                {/* Round Profile Icon with Dropdown */}
                <div className="dropdown position-relative">
                    <button 
                        className="btn btn-light d-flex align-items-center gap-2 rounded-pill px-3 py-1 border shadow-sm"
                        type="button"
                        onClick={() => setDropdownOpen(!dropdownOpen)}
                        style={{ height: '40px' }}
                    >
                        <div 
                            className="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold text-xs" 
                            style={{ width: '28px', height: '28px', fontSize: '0.78rem' }}
                        >
                            {getInitials()}
                        </div>
                        <span className="fw-medium text-dark d-none d-md-inline" style={{ fontSize: '0.85rem' }}>
                            {user?.first_name || 'Profile'}
                        </span>
                    </button>
                    
                    {dropdownOpen && (
                        <>
                            <div 
                                className="dropdown-backdrop position-fixed top-0 bottom-0 start-0 end-0 z-1" 
                                style={{ background: 'transparent' }}
                                onClick={() => setDropdownOpen(false)}
                            ></div>
                            <ul 
                                className="dropdown-menu show dropdown-menu-end shadow border-0 mt-2 p-2 rounded-3 z-2 position-absolute" 
                                style={{ right: 0, minWidth: '160px' }}
                            >
                                <li>
                                    <Link 
                                        to="/settings" 
                                        className="dropdown-item d-flex align-items-center gap-2 rounded-2 py-2" 
                                        onClick={() => setDropdownOpen(false)}
                                    >
                                        <User size={16} className="text-secondary" />
                                        Manage Profile
                                    </Link>
                                </li>
                                <li><hr className="dropdown-divider border-light" /></li>
                                <li>
                                    <button 
                                        onClick={() => { setDropdownOpen(false); handleLogout(); }} 
                                        className="dropdown-item d-flex align-items-center gap-2 text-danger rounded-2 py-2"
                                    >
                                        <LogOut size={16} />
                                        Logout
                                    </button>
                                </li>
                            </ul>
                        </>
                    )}
                </div>
            </div>
        </div>
    );
};

export default Topbar;
