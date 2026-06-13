import React, { useContext } from 'react';
import { AuthContext } from '../../context/AuthContext';
import { 
    BookOpen, 
    Search, 
    ShoppingBag, 
    Users 
} from 'lucide-react';
import { Link, Navigate } from 'react-router-dom';

const DashboardCard = ({ title, icon, color, link, description }) => (
    <div className="col-md-6 col-lg-3 mb-4">
        <div className="card h-100 border-0 shadow-sm" style={{ transition: 'transform 0.2s', cursor: 'pointer' }} onMouseOver={e => e.currentTarget.style.transform = 'translateY(-5px)'} onMouseOut={e => e.currentTarget.style.transform = 'none'}>
            <div className="card-body p-4 d-flex flex-column align-items-center text-center">
                <div className={`rounded-circle p-3 mb-3 bg-${color} bg-opacity-10 text-${color}`}>
                    {icon}
                </div>
                <h5 className="fw-bold">{title}</h5>
                <p className="text-muted small mb-4">{description}</p>
                <Link to={link} className={`btn btn-outline-${color} w-100 mt-auto rounded-pill`}>
                    Access Module
                </Link>
            </div>
        </div>
    </div>
);

const Dashboard = () => {
    const { user } = useContext(AuthContext);

    if (user?.role === 'admin') {
        return <Navigate to="/admin" replace />;
    }

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 className="fw-bold text-dark mb-1">Dashboard</h3>
                    <p className="text-muted m-0">Overview of your academic tools</p>
                </div>
            </div>

            <div className="row">
                <DashboardCard 
                    title="Lost-Items" 
                    icon={<Search size={28} />} 
                    color="primary" 
                    link="/lost-items"
                    description="Report or find lost items around the campus."
                />
                <DashboardCard 
                    title="Marketplace" 
                    icon={<ShoppingBag size={28} />} 
                    color="success" 
                    link="/marketplace"
                    description="Buy, sell, or exchange academic materials."
                />
                {user?.role !== 'staff' && (
                    <>
                        <DashboardCard 
                            title="Notes Sharing" 
                            icon={<BookOpen size={28} />} 
                            color="warning" 
                            link="/notes"
                            description="Access or upload course notes and PDFs."
                        />
                        <DashboardCard 
                            title="Peer Learning" 
                            icon={<Users size={28} />} 
                            color="info" 
                            link="/peer-learning"
                            description="Request help or assist peers in your courses."
                        />
                    </>
                )}
            </div>

            <div className="card border-0 shadow-sm mt-4">
                <div className="card-body p-4">
                    <h5 className="fw-bold mb-3">Recent Activity</h5>
                    <div className="text-muted text-center py-5">
                        No recent activity to show. Explore the modules above!
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Dashboard;
