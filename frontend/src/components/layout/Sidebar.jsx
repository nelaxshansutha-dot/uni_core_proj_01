import React, { useContext } from 'react';
import { NavLink } from 'react-router-dom';
import { AuthContext } from '../../context/AuthContext';
import { 
    LayoutDashboard, 
    Search, 
    ShoppingBag, 
    BookOpen, 
    Users, 
    Settings, 
    ShieldAlert 
} from 'lucide-react';

const Sidebar = () => {
    const { user } = useContext(AuthContext);

    return (
        <div className="sidebar d-flex flex-column p-3 position-fixed shadow-sm" style={{ width: '250px', zIndex: 1000 }}>
            <div className="d-flex align-items-center mb-4 px-2 mt-2">
                <div className="bg-primary rounded p-2 me-2">
                    <BookOpen size={24} className="text-white" />
                </div>
                <h4 className="m-0 fw-bold text-white tracking-tight">UniCore</h4>
            </div>
            
            <hr className="border-secondary mt-0" />

            <ul className="nav nav-pills flex-column mb-auto gap-1">
                <li className="nav-item">
                    <NavLink to="/dashboard" className="nav-link d-flex align-items-center gap-3">
                        <LayoutDashboard size={20} />
                        Dashboard
                    </NavLink>
                </li>
                <li>
                    <NavLink to="/lost-items" className="nav-link d-flex align-items-center gap-3">
                        <Search size={20} />
                        Lost & Found
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
                
                {user?.role === 'admin' && (
                    <li className="mt-4">
                        <div className="text-uppercase text-secondary small fw-bold px-3 mb-2">Admin</div>
                        <NavLink to="/admin" className="nav-link d-flex align-items-center gap-3 text-warning">
                            <ShieldAlert size={20} />
                            Admin Panel
                        </NavLink>
                    </li>
                )}
            </ul>
            
            <div className="mt-auto pt-4 pb-2 text-center text-secondary small">
                &copy; 2026 UniCore
            </div>
        </div>
    );
};

export default Sidebar;
