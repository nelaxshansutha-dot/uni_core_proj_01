import React, { useState, useEffect, useContext } from 'react';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';
import { Users, Plus, CheckCircle, XCircle, BookOpen } from 'lucide-react';

const PeerLearning = () => {
    const { user } = useContext(AuthContext);
    const [requests, setRequests] = useState([]);
    
    // Semester/Module Selection State
    const [showSemPopup, setShowSemPopup] = useState(user?.role === 'student');
    const [loading, setLoading] = useState(user?.role === 'rep' || (user?.role !== 'student'));
    const [showModal, setShowModal] = useState(false);
    const [courseFilters, setCourseFilters] = useState({ year: '1', semester: '1' });

    const [modules, setModules] = useState([]);
    
    const [formData, setFormData] = useState({
        courseUnitName: '',
        courseUnitID: '',
        description: ''
    });

    const [actionStatus, setActionStatus] = useState({ show: false, message: '', type: '' });

    const showToast = (message, type) => {
        setActionStatus({ show: true, message, type });
        setTimeout(() => setActionStatus({ show: false, message: '', type: '' }), 4000);
    };

    // Update state when user object loads
    useEffect(() => {
        if (user && user.role === 'student' && modules.length === 0) {
            setShowSemPopup(true);
            setLoading(false);
        } else if (user && user.role === 'rep') {
            setShowSemPopup(false);
            setLoading(true); // fetchRequests will set to false
        }
    }, [user]);

    const fetchRequests = async () => {
        try {
            setLoading(true);
            const endpoint = user?.role === 'rep' 
                ? '/peer-learning.php?action=course-requests&courseUnitID=CS101' 
                : '/peer-learning.php?action=my-requests';
            // Note: hardcoded CS101 for rep to simplify, ideally should fetch rep's course
            const res = await api.get(endpoint);
            if (res.data.status === 'success') {
                setRequests(res.data.data);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const fetchModules = async (e) => {
        e?.preventDefault();
        try {
            setLoading(true);
            // courseID is intentionally omitted — backend auto-extracts it from the user's enrollment number
            const res = await api.get(`/courses.php?action=modules&year=${courseFilters.year}&semester=${courseFilters.semester}`);
            if (res.data.status === 'success') {
                setModules(res.data.data);
                setShowSemPopup(false);
            } else {
                console.error(res.data.message);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    // Always fetch requests on mount for any logged-in user
    useEffect(() => {
        if (user) {
            fetchRequests();
        }
    }, [user]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const res = await api.post('/peer-learning.php', formData);
            if (res.data.status === 'success') {
                setShowModal(false);
                setFormData({ courseUnitName: '', courseUnitID: '', description: '' });
                fetchRequests();
                showToast('Request submitted successfully!', 'success');
            } else {
                showToast(res.data.message || 'Failed to submit request.', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('An error occurred while submitting the request.', 'danger');
        }
    };

    const handleStatusUpdate = async (courseUnitName, courseUnitID, status) => {
        try {
            const res = await api.put('/peer-learning.php', { courseUnitName, courseUnitID, status });
            if (res.data.status === 'success') {
                fetchRequests();
            }
        } catch (err) {
            console.error(err);
        }
    };

    const handleModuleClick = (courseUnitID) => {
        setFormData({ ...formData, courseUnitID: courseUnitID });
        setShowModal(true);
    };

    return (
        <div>
            {actionStatus.show && (
                <div className={`alert alert-${actionStatus.type} d-flex align-items-center position-fixed top-0 end-0 m-3 z-index-toast shadow`} style={{ zIndex: 1050 }}>
                    {actionStatus.type === 'success' ? <CheckCircle className="me-2" size={20} /> : <XCircle className="me-2" size={20} />}
                    {actionStatus.message}
                </div>
            )}
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 className="fw-bold text-dark mb-1">Peer Learning</h3>
                    <p className="text-muted m-0">Request help or assist peers</p>
                </div>
                {user?.role === 'student' && !showSemPopup && (
                    <button className="btn btn-outline-primary rounded-pill px-4" onClick={() => setShowSemPopup(true)}>
                        Change Semester / Year
                    </button>
                )}
            </div>

            {loading ? (
                <div className="text-center mt-5"><div className="spinner-border text-primary"></div></div>
            ) : showSemPopup ? (
                <>
                    <div className="card border-0 shadow-sm mx-auto mb-4" style={{maxWidth: '500px'}}>
                        <div className="card-body p-4">
                            <h5 className="fw-bold mb-1">Select Semester Details</h5>
                            <p className="text-muted small mb-4">Your course is automatically detected from your profile.</p>
                            <form onSubmit={fetchModules}>
                                <div className="mb-3">
                                    <label className="form-label text-muted small fw-bold">YEAR</label>
                                    <select className="form-select" value={courseFilters.year} onChange={e => setCourseFilters({...courseFilters, year: e.target.value})}>
                                        <option value="1">Year 1</option>
                                        <option value="2">Year 2</option>
                                        <option value="3">Year 3</option>
                                        <option value="4">Year 4</option>
                                    </select>
                                </div>
                                <div className="mb-4">
                                    <label className="form-label text-muted small fw-bold">SEMESTER</label>
                                    <select className="form-select" value={courseFilters.semester} onChange={e => setCourseFilters({...courseFilters, semester: e.target.value})}>
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>
                                <button type="submit" className="btn btn-primary w-100 rounded-pill">Load Modules</button>
                            </form>
                        </div>
                    </div>

                    {/* Always show existing requests even when popup is open */}
                    {requests.length > 0 && (
                        <>
                            <h5 className="mb-3 fw-bold border-top pt-4">Your Requests</h5>
                            <div className="row g-4">
                                {requests.map((req, idx) => (
                                    <div className="col-md-6" key={req.requestID || idx}>
                                        <div className="card border-0 shadow-sm">
                                            <div className="card-body p-4">
                                                <div className="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 className="fw-bold m-0">{req.courseUnitName}</h6>
                                                </div>
                                                <span className="badge bg-secondary mb-2">{req.courseUnitID}</span>
                                                <p className="text-muted small mb-0">{req.description}</p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </>
                    )}
                </>
            ) : user?.role === 'student' && modules.length > 0 ? (
                <>
                    <h5 className="mb-3 fw-bold">Select a Module for Peer Learning</h5>
                    <div className="row g-3 mb-5">
                        {modules.map(mod => (
                            <div className="col-md-4" key={mod.courseUnitID}>
                                <div className="card border-0 shadow-sm h-100">
                                    <div className="card-body p-4 text-center d-flex flex-column">
                                        <BookOpen className="text-primary mb-3 mx-auto" size={32} />
                                        <h6 className="fw-bold m-0 flex-grow-1">{mod.courseUnitName}</h6>
                                        <span className="badge bg-light text-dark mt-2 mb-3 border mx-auto">{mod.courseUnitID}</span>
                                        <button 
                                            className="btn btn-sm btn-outline-primary mt-auto rounded-pill"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                const autoSubmit = async () => {
                                                    try {
                                                        const res = await api.post('/peer-learning.php', {
                                                            courseUnitID: mod.courseUnitID,
                                                            courseUnitName: mod.courseUnitName,
                                                            description: 'General unit request'
                                                        });
                                                        if (res.data.status === 'success') {
                                                            fetchRequests();
                                                            showToast('Unit request submitted!', 'success');
                                                        }
                                                    } catch (err) {
                                                        showToast('Failed to request unit.', 'danger');
                                                    }
                                                };
                                                autoSubmit();
                                            }}
                                        >
                                            Request Unit
                                        </button>
                                        <button 
                                            className="btn btn-link text-decoration-none small text-muted mt-2 p-0"
                                            onClick={() => handleModuleClick(mod.courseUnitID)}
                                        >
                                            Ask specific question
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                    
                    <h5 className="mb-3 fw-bold border-top pt-4">Your Requests</h5>
                    <div className="row g-4">
                        {requests.length === 0 ? (
                            <div className="col-12 text-center text-muted py-5">
                                <Users size={48} className="mb-3 opacity-50" />
                                <h6>No requests found</h6>
                            </div>
                        ) : (
                            requests.map((req, idx) => (
                                <div className="col-md-6" key={req.requestID || idx}>
                                    <div className="card border-0 shadow-sm">
                                        <div className="card-body p-4">
                                            <div className="d-flex justify-content-between align-items-start mb-2">
                                                <h6 className="fw-bold m-0">{req.courseUnitName}</h6>
                                            </div>
                                            <span className="badge bg-secondary mb-2">{req.courseUnitID}</span>
                                            <p className="text-muted small mb-0">{req.description}</p>
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </>
            ) : (
                <div className="row g-4">
                    {requests.length === 0 ? (
                        <div className="col-12 text-center text-muted py-5">
                            <Users size={48} className="mb-3 opacity-50" />
                            <h5>No requests found</h5>
                        </div>
                    ) : (
                        requests.map((req, idx) => (
                            <div className="col-md-6" key={req.id || idx}>
                                <div className="card h-100 border-0 shadow-sm">
                                    <div className="card-body p-4">
                                        <div className="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 className="fw-bold m-0">{req.courseUnitName}</h5>
                                                <span className="badge bg-secondary mt-1">{req.courseUnitID}</span>
                                            </div>
                                        </div>
                                        
                                        {user?.role === 'rep' && req.request_count && (
                                            <div className="alert alert-info py-2 small fw-bold mb-3">
                                                {req.request_count} student(s) requested this
                                            </div>
                                        )}

                                        {!req.request_count && <p className="text-muted small">{req.description}</p>}

                                        {user?.role === 'rep' && req.status === 'pending' && (
                                            <div className="d-flex flex-column gap-2 mt-3">
                                                <button className="btn btn-sm btn-success w-100 rounded-pill mb-2" onClick={() => handleStatusUpdate(req.courseUnitName, req.courseUnitID, 'approved')}>
                                                    Approve
                                                </button>
                                                <button className="btn btn-sm btn-danger w-100 rounded-pill" onClick={() => handleStatusUpdate(req.courseUnitName, req.courseUnitID, 'rejected')}>
                                                    <XCircle size={16} /> Reject
                                                </button>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            )}

            {/* Request Modal */}
            {showModal && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border-0 shadow">
                            <div className="modal-header border-0 pb-0">
                                <h5 className="fw-bold">Request Peer Learning</h5>
                                <button type="button" className="btn-close" onClick={() => setShowModal(false)}></button>
                            </div>
                            <div className="modal-body p-4">
                                <form onSubmit={handleSubmit}>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">Course Code</label>
                                        <input type="text" className="form-control bg-light" value={formData.courseUnitID} readOnly />
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">Course Unit Name</label>
                                        <input type="text" className="form-control" placeholder="e.g. Loops in Java" value={formData.courseUnitName} onChange={e => setFormData({...formData, courseUnitName: e.target.value})} required />
                                    </div>
                                    <div className="mb-4">
                                        <label className="form-label text-muted small fw-bold">Description / Where you need help</label>
                                        <textarea className="form-control" rows="3" placeholder="Provide specific details..." value={formData.description} onChange={e => setFormData({...formData, description: e.target.value})} required></textarea>
                                    </div>
                                    <div className="d-flex gap-2 justify-content-end">
                                        <button type="button" className="btn btn-light rounded-pill px-4" onClick={() => setShowModal(false)}>Cancel</button>
                                        <button type="submit" className="btn btn-primary rounded-pill px-4">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default PeerLearning;
