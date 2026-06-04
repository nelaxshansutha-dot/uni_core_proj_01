import React, { useContext } from 'react';
import { useNavigate } from 'react-router-dom';
import { AuthContext } from '../../context/AuthContext';
import { LogOut, User } from 'lucide-react';

const Topbar = () => {
    const { user, logout } = useContext(AuthContext);
    const navigate = useNavigate();

    const handleLogout = () => {
        logout();
        navigate('/login');
    };

    return (
        <div className="topbar d-flex justify-content-between align-items-center px-4 py-3 sticky-top z-3">
            <h5 className="m-0 text-muted fw-normal">Welcome back, {user?.first_name || 'User'}!</h5>
            
            <div className="d-flex align-items-center gap-3">
                <div 
                    className="d-flex align-items-center bg-light rounded-pill px-3 py-2 border shadow-sm"
                    style={{ cursor: 'pointer' }}
                    onClick={() => navigate('/profile')}
                >
                    <User size={18} className="text-secondary me-2" />
                    <span className="fw-medium text-dark">{user?.enrollment_no}</span>
                    <span className="badge bg-primary ms-2 rounded-pill text-capitalize">{user?.role}</span>
                </div>
                
                <button 
                    onClick={handleLogout}
                    className="btn btn-outline-danger d-flex align-items-center gap-2 rounded-pill px-3 py-2"
                >
                    <LogOut size={18} />
                    Logout
                </button>
            </div>
        </div>
    );
};

export default Topbar;
