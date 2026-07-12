import React, { useState, useEffect } from 'react';
import { Share2, Send, Clock, BookOpen, User, CheckCircle, XCircle, TrendingUp, Users } from 'lucide-react';
import api from '../../services/api';

const RepDashboard = () => {
    const [requests, setRequests] = useState([]);
    const [unitCounts, setUnitCounts] = useState([]);
    const [repContext, setRepContext] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState('');
    const [actionStatus, setActionStatus] = useState({ show: false, message: '', type: '' });

    useEffect(() => {
        fetchDashboardData();
    }, []);

    const fetchDashboardData = async () => {
        try {
            const response = await api.get('/get_rep_dashboard.php');
            if (response.data.status === 'success') {
                setRequests(response.data.data.requests || []);
                setUnitCounts(response.data.data.unit_counts || []);
                setRepContext(response.data.data.rep_context);
            } else {
                setError(response.data.message || 'Failed to fetch dashboard data.');
            }
        } catch (err) {
            console.error('Could not load peer learning requests.', err);
        } finally {
            setLoading(false);
        }
    };

    const handleAction = async (actionType, requestId) => {
        try {
            const response = await api.post('/share_notification.php', {
                action: actionType,
                request_id: requestId
            });
            
            if (response.data.status === 'success') {
                showToast(`Success: ${response.data.message} (${response.data.data.notified_count} notified)`, 'success');
            } else {
                showToast(response.data.message || 'Failed to execute action.', 'danger');
            }
        } catch (err) {
            showToast('Server error during action execution.', 'danger');
        }
    };

    const showToast = (message, type) => {
        setActionStatus({ show: true, message, type });
        setTimeout(() => setActionStatus({ show: false, message: '', type: '' }), 4000);
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '60vh' }}>
                <div className="spinner-border text-primary" role="status">
                    <span className="visually-hidden">Loading...</span>
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid py-4">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 className="fw-bold mb-1">Representative Dashboard</h2>
                    {repContext && (
                        <p className="text-muted mb-0">
                            Managing Year {repContext.std_year} Requests
                        </p>
                    )}
                </div>
            </div>

            {error && (
                <div className="alert alert-danger">
                    {error}
                </div>
            )}

            {actionStatus.show && (
                <div className={`alert alert-${actionStatus.type} d-flex align-items-center position-fixed top-0 end-0 m-3 z-index-toast shadow`} style={{ zIndex: 1050 }}>
                    {actionStatus.type === 'success' ? <CheckCircle className="me-2" size={20} /> : <XCircle className="me-2" size={20} />}
                    {actionStatus.message}
                </div>
            )}

            <h4 className="fw-bold mb-3 mt-4 text-dark">Course Unit Requests (Aggregated)</h4>
            <div className="row g-4 mb-5">
                {unitCounts.length === 0 ? (
                    <div className="col-12 text-muted">No course units have been requested yet.</div>
                ) : (
                    unitCounts.map((unit) => (
                        <div className="col-md-6 col-lg-3" key={unit.courseUnitID}>
                            <div className="card shadow-sm border-0 h-100">
                                <div className="card-body p-4 text-center">
                                    <div className="mb-3">
                                        <TrendingUp className="text-primary" size={32} />
                                    </div>
                                    <h5 className="card-title fw-bold mb-1">{unit.unitName || unit.courseUnitID}</h5>
                                    <span className="badge bg-light text-dark mb-3">{unit.courseUnitID}</span>
                                    <div className="d-flex align-items-center mt-auto justify-content-center">
                                        <Users className="text-primary me-2" size={24} />
                                        <span className="fs-4 fw-bold text-dark">{unit.studentCount}</span>
                                        <span className="text-muted ms-2 mt-1">Student(s) requested</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>
        </div>
    );
};

export default RepDashboard;
