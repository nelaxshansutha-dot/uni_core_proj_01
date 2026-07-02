import React, { useState, useEffect } from 'react';
import { Share2, Send, Clock, BookOpen, User, CheckCircle, XCircle } from 'lucide-react';
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
            setError('Could not load peer learning requests. Please try again.');
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
                    unitCounts.map((unit, idx) => (
                        <div key={idx} className="col-12 col-md-4">
                            <div className="card shadow-sm border-0 border-start border-primary border-4 h-100">
                                <div className="card-body">
                                    <h5 className="card-title fw-bold mb-1">{unit.unitName || unit.courseCode}</h5>
                                    <span className="badge bg-light text-dark mb-3">{unit.courseCode}</span>
                                    <div className="d-flex align-items-center mt-auto">
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

            <h4 className="fw-bold mb-3 border-top pt-4 text-dark">Individual Peer Learning Requests</h4>
            <div className="row g-4">
                {requests.length === 0 ? (
                    <div className="col-12 text-center py-5">
                        <BookOpen size={48} className="text-muted mb-3 opacity-50" />
                        <h4 className="text-muted">No Kuppy Requests Found</h4>
                        <p className="text-muted">There are no peer learning requests for your batch right now.</p>
                    </div>
                ) : (
                    requests.map((req) => (
                        <div key={req.requestID} className="col-12 col-md-6 col-lg-4">
                            <div className="card h-100 shadow-sm border-0">
                                <div className="card-body">
                                    <div className="d-flex justify-content-between align-items-start mb-3">
                                        <span className={`badge ${req.status === 'pending' ? 'bg-warning' : 'bg-success'}`}>
                                            {req.status.toUpperCase()}
                                        </span>
                                        <span className="text-muted small d-flex align-items-center">
                                            <Clock size={14} className="me-1" />
                                            {new Date(req.created_at).toLocaleDateString()}
                                        </span>
                                    </div>
                                    
                                    <h5 className="card-title fw-bold mb-2 text-primary">
                                        {req.courseUnitName || 'General Topic'}
                                    </h5>
                                    <p className="card-text mb-3 fw-medium">Topic: {req.topic}</p>
                                    
                                    <div className="d-flex align-items-center mb-4 text-muted small">
                                        <User size={16} className="me-2" />
                                        <span>Requested by: {req.studentName} ({req.studentEnrollment})</span>
                                    </div>

                                    <div className="d-flex gap-2 mt-auto">
                                        <button 
                                            className="btn btn-outline-primary btn-sm flex-grow-1 d-flex align-items-center justify-content-center"
                                            onClick={() => handleAction('share_classmates', req.requestID)}
                                        >
                                            <Share2 size={16} className="me-1" /> Classmates
                                        </button>
                                        <button 
                                            className="btn btn-outline-secondary btn-sm flex-grow-1 d-flex align-items-center justify-content-center"
                                            onClick={() => handleAction('forward_seniors', req.requestID)}
                                        >
                                            <Send size={16} className="me-1" /> Seniors
                                        </button>
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
