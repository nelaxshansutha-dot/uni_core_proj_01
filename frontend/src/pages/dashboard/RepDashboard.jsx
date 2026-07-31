import React, { useState, useEffect } from 'react';
import { Share2, Send, Clock, BookOpen, User, CheckCircle, XCircle, TrendingUp, Users, FileText } from 'lucide-react';
import api from '../../services/api';

const RepDashboard = () => {
    const [requests, setRequests] = useState([]);
    const [unitCounts, setUnitCounts] = useState([]);
    const [repContext, setRepContext] = useState(null);
    const [recentNotes, setRecentNotes] = useState([]);
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
        }

        try {
            const notesRes = await api.get('/notes');
            if (notesRes.data.success) {
                setRecentNotes(notesRes.data.data.slice(0, 3)); // show top 3 recent notes
            }
        } catch (err) {
            console.error('Could not load notes.', err);
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
        <div className="container-fluid py-4 px-lg-5">
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

            {/* Quick Stats Row */}
            <div className="row g-4 mb-5">
                <div className="col-md-4">
                    <div className="card border-0 shadow-sm rounded-4 h-100 bg-primary text-white">
                        <div className="card-body p-4 d-flex align-items-center">
                            <div className="bg-white bg-opacity-25 p-3 rounded-circle me-4">
                                <BookOpen size={32} className="text-white" />
                            </div>
                            <div>
                                <h6 className="text-white-50 fw-bold text-uppercase mb-1">Total Requests</h6>
                                <h2 className="fw-bold mb-0">{requests.length}</h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="card border-0 shadow-sm rounded-4 h-100 bg-warning text-dark">
                        <div className="card-body p-4 d-flex align-items-center">
                            <div className="bg-white bg-opacity-50 p-3 rounded-circle me-4">
                                <Clock size={32} className="text-dark" />
                            </div>
                            <div>
                                <h6 className="text-dark fw-bold text-uppercase mb-1" style={{ opacity: 0.7 }}>Pending</h6>
                                <h2 className="fw-bold mb-0">
                                    {requests.filter(r => r.status === 'pending').length}
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="card border-0 shadow-sm rounded-4 h-100 bg-success text-white">
                        <div className="card-body p-4 d-flex align-items-center">
                            <div className="bg-white bg-opacity-25 p-3 rounded-circle me-4">
                                <CheckCircle size={32} className="text-white" />
                            </div>
                            <div>
                                <h6 className="text-white-50 fw-bold text-uppercase mb-1">Completed</h6>
                                <h2 className="fw-bold mb-0">
                                    {requests.filter(r => r.status === 'completed').length}
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div className="d-flex justify-content-between align-items-end mb-3 mt-4">
                <h4 className="fw-bold mb-0 text-dark">Course Unit Requests (Aggregated)</h4>
                <span className="badge bg-secondary text-white rounded-pill px-3 py-2">
                    {unitCounts.length} Modules
                </span>
            </div>

            <div className="row g-4 mb-5">
                {unitCounts.length === 0 ? (
                    <div className="col-12">
                        <div className="card border-0 shadow-sm rounded-4 text-center p-5 bg-light">
                            <div className="mb-4">
                                <Users size={64} className="text-muted opacity-50" />
                            </div>
                            <h4 className="fw-bold text-dark">No Requests Yet</h4>
                            <p className="text-muted mb-0">Your batch hasn't submitted any peer learning requests for this semester.</p>
                        </div>
                    </div>
                ) : (
                    unitCounts.map((unit) => (
                        <div className="col-md-6 col-lg-4" key={unit.courseUnitID}>
                            <div className="card shadow-sm border-0 rounded-4 h-100">
                                <div className="card-body p-4 d-flex flex-column text-center">
                                    <div className="mb-3">
                                        <TrendingUp className="text-primary" size={32} />
                                    </div>
                                    <h5 className="card-title fw-bold mb-2">{unit.unitName || unit.courseUnitID}</h5>
                                    <div className="mb-3">
                                        <span className="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                                            {unit.courseUnitID}
                                        </span>
                                    </div>
                                    <div className="d-flex align-items-center mt-auto justify-content-center bg-light rounded-3 py-2">
                                        <Users className="text-primary me-2" size={20} />
                                        <span className="fs-5 fw-bold text-dark">{unit.studentCount}</span>
                                        <span className="text-muted ms-2 small">Students</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* Recent Notes Section */}
            <div className="d-flex justify-content-between align-items-end mb-3 mt-5">
                <h4 className="fw-bold mb-0 text-dark">Recent Course Notes</h4>
                <a href="/dashboard/notes" className="btn btn-sm btn-outline-primary rounded-pill px-3">
                    View All Notes
                </a>
            </div>

            <div className="row g-4 mb-5">
                {recentNotes.length === 0 ? (
                    <div className="col-12">
                        <div className="card border-0 shadow-sm rounded-4 text-center p-5 bg-light">
                            <div className="mb-4">
                                <FileText size={64} className="text-muted opacity-50" />
                            </div>
                            <h5 className="fw-bold text-dark">No Notes Available</h5>
                            <p className="text-muted mb-0">No course materials have been uploaded yet.</p>
                        </div>
                    </div>
                ) : (
                    recentNotes.map((note) => (
                        <div className="col-md-6 col-lg-4" key={note.id}>
                            <div className="card shadow-sm border-0 rounded-4 h-100">
                                <div className="card-body p-4 d-flex flex-column">
                                    <div className="d-flex justify-content-between align-items-start mb-3">
                                        <div className="bg-primary bg-opacity-10 p-2 rounded-3">
                                            <FileText className="text-primary" size={24} />
                                        </div>
                                        <span className="badge bg-light text-dark border">
                                            {note.courseUnitID}
                                        </span>
                                    </div>
                                    <h6 className="card-title fw-bold mb-1 text-truncate" title={note.title || 'Untitled Note'}>
                                        {note.title || 'Untitled Note'}
                                    </h6>
                                    <p className="text-muted small mb-3">
                                        Uploaded by: {note.uploadedByName || 'Unknown'}
                                    </p>
                                    <div className="mt-auto d-flex gap-2">
                                        <a href={note.file_url} target="_blank" rel="noreferrer" className="btn btn-sm btn-primary flex-grow-1 rounded-pill">
                                            View Note
                                        </a>
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
