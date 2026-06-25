import React, { useContext, useEffect, useState } from 'react';
import { AuthContext } from '../../context/AuthContext';
import api from '../../services/api';
import { 
    BookOpen, 
    Search, 
    ShoppingBag, 
    Users,
    Activity,
    ArrowRight,
    Clock
} from 'lucide-react';
import { Link, Navigate } from 'react-router-dom';

const DashboardCard = ({ title, icon, color, link, description }) => (
    <div className="col-md-6 col-xl-3 mb-4">
        <div className="dashboard-module-card p-4">
            <div className="card-content">
                <div className={`icon-wrapper bg-${color} bg-opacity-10 text-${color}`}>
                    {icon}
                </div>
                <h4 className="fw-bold mb-2">{title}</h4>
                <p className="text-muted small mb-4 flex-grow-1">{description}</p>
                <Link to={link} className={`btn btn-outline-${color} w-100 rounded-pill d-flex align-items-center justify-content-center gap-2`} style={{ padding: '0.6rem 1.5rem', fontWeight: '600' }}>
                    Access Module <ArrowRight size={16} />
                </Link>
            </div>
        </div>
    </div>
);

const Dashboard = () => {
    const { user } = useContext(AuthContext);
    const [recentActivities, setRecentActivities] = useState([]);
    const [loadingActivities, setLoadingActivities] = useState(true);

    useEffect(() => {
        const fetchActivities = async () => {
            try {
                const response = await api.get('/dashboard.php?action=recent-activity');
                if (response.data.status === 'success') {
                    setRecentActivities(response.data.data.activities);
                }
            } catch (err) {
                console.error("Failed to fetch recent activities:", err);
            } finally {
                setLoadingActivities(false);
            }
        };

        if (user && user.role !== 'admin') {
            fetchActivities();
        }
    }, [user]);

    if (user?.role === 'admin') {
        return <Navigate to="/admin" replace />;
    }

    const getActivityIcon = (type) => {
        switch (type) {
            case 'lost_item': return <Search size={20} className="text-primary" />;
            case 'marketplace': return <ShoppingBag size={20} className="text-success" />;
            case 'note': return <BookOpen size={20} className="text-warning" />;
            default: return <Activity size={20} className="text-secondary" />;
        }
    };

    const getActivityColor = (type) => {
        switch (type) {
            case 'lost_item': return 'primary';
            case 'marketplace': return 'success';
            case 'note': return 'warning';
            default: return 'secondary';
        }
    };

    const formatDate = (dateString) => {
        const date = new Date(dateString);
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    };

    return (
        <div className="py-2">
            {/* Hero Section */}
            <div className="user-hero-banner d-flex justify-content-between align-items-center">
                <div className="position-relative" style={{ zIndex: 1 }}>
                    <h2 className="fw-bold mb-2">Welcome back, {user?.name || 'Student'}! 👋</h2>
                    <p className="mb-0 text-white-50 fs-5">Here's an overview of your academic tools for today.</p>
                </div>
            </div>

            <div className="row g-4 mb-5">
                <DashboardCard 
                    title="Lost-Items" 
                    icon={<Search size={32} strokeWidth={1.5} />} 
                    color="primary" 
                    link="/lost-items"
                    description="Report or find lost items around the campus quickly and easily."
                />
                <DashboardCard 
                    title="Marketplace" 
                    icon={<ShoppingBag size={32} strokeWidth={1.5} />} 
                    color="success" 
                    link="/marketplace"
                    description="Buy, sell, or exchange academic materials with your peers."
                />
                {user?.role !== 'staff' && (
                    <>
                        <DashboardCard 
                            title="Notes Sharing" 
                            icon={<BookOpen size={32} strokeWidth={1.5} />} 
                            color="warning" 
                            link="/notes"
                            description="Access or upload course notes, past papers, and PDFs."
                        />
                        <DashboardCard 
                            title="Peer Learning" 
                            icon={<Users size={32} strokeWidth={1.5} />} 
                            color="info" 
                            link="/peer-learning"
                            description="Request help or assist peers in your challenging courses."
                        />
                    </>
                )}
            </div>

            <div className="card activity-card border-0 bg-white">
                <div className="card-body p-4 p-md-5">
                    <div className="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom">
                        <div className="d-flex align-items-center">
                            <Activity className="text-primary me-3" size={24} />
                            <h4 className="fw-bold mb-0">Recent Activity</h4>
                        </div>
                    </div>
                    
                    {loadingActivities ? (
                        <div className="text-center py-5">
                            <div className="spinner-border text-primary" role="status">
                                <span className="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    ) : recentActivities.length > 0 ? (
                        <div className="activity-timeline">
                            {recentActivities.map((activity, index) => (
                                <div key={index} className="d-flex mb-4 position-relative">
                                    <div 
                                        className={`rounded-circle bg-${getActivityColor(activity.type)} bg-opacity-10 d-flex align-items-center justify-content-center flex-shrink-0 z-1`} 
                                        style={{ width: '40px', height: '40px', marginLeft: '-20px', border: '4px solid #fff' }}
                                    >
                                        {getActivityIcon(activity.type)}
                                    </div>
                                    <div className="ms-3 flex-grow-1">
                                        <div className="card border-0 bg-light shadow-sm">
                                            <div className="card-body p-3">
                                                <div className="d-flex justify-content-between align-items-start mb-1">
                                                    <h6 className="fw-bold mb-0 text-dark">{activity.title}</h6>
                                                    <small className="text-muted d-flex align-items-center">
                                                        <Clock size={12} className="me-1" />
                                                        {formatDate(activity.created_at)}
                                                    </small>
                                                </div>
                                                <p className="text-muted small mb-2">{activity.description}</p>
                                                <Link to={activity.link} className={`text-${getActivityColor(activity.type)} fw-semibold small text-decoration-none d-inline-flex align-items-center`}>
                                                    View Details <ArrowRight size={14} className="ms-1" />
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="empty-state-container text-center">
                            <div className="mb-3">
                                <Activity size={48} className="text-muted opacity-50" />
                            </div>
                            <h5 className="fw-bold text-dark">No Recent Activity</h5>
                            <p className="text-muted mb-4 max-w-md mx-auto">
                                There is no recent activity across the campus modules right now. Explore the tools above to get started!
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default Dashboard;
