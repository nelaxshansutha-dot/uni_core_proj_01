import React, { useState, useEffect, useContext } from 'react';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';
import { Users, CheckCircle, XCircle, BookOpen, Clock, AlertCircle } from 'lucide-react';

const PeerLearning = () => {
    const { user } = useContext(AuthContext);
    const [requests, setRequests] = useState([]);

    // Semester/Module Selection State
    const [showSemPopup, setShowSemPopup] = useState(false);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [selectedRepModule, setSelectedRepModule] = useState(null);
    const [selectedSemester, setSelectedSemester] = useState(localStorage.getItem('peerLearningSemester') || '1');
    const [detectedYear, setDetectedYear] = useState(null);

    const [modules, setModules] = useState([]);

    const [formData, setFormData] = useState({
        courseUnitName: '',
        courseUnitID: '',
        description: ''
    });

    const [actionStatus, setActionStatus] = useState({ show: false, message: '', type: '' });

    const showToast = (message, type = 'success') => {
        setActionStatus({ show: true, message, type });
        setTimeout(() => setActionStatus({ show: false, message: '', type: '' }), 4000);
    };

    // On mount: show semester popup for students, load requests for reps
    useEffect(() => {
        if (!user) return;
        if (user.role === 'student') {
            const savedSem = localStorage.getItem('peerLearningSemester');
            if (savedSem) {
                fetchModules(null, savedSem);
            } else {
                setShowSemPopup(true);
                setLoading(false);
            }
        } else {
            // Rep or other role — load requests directly
            fetchRequests();
        }
    }, [user]);

    const fetchRequests = async () => {
        try {
            setLoading(true);
            const res = await api.get('/peer-learning-requests');
            if (res.data.status === 'success') {
                setRequests(res.data.data);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const fetchModules = async (e, sem = selectedSemester) => {
        e?.preventDefault();
        try {
            setLoading(true);
            
            // Persist the choice
            localStorage.setItem('peerLearningSemester', sem);
            setSelectedSemester(sem);

            // Year is auto-detected from enrollment number on the backend
            const res = await api.get(`/course-units/my-modules?semester=${sem}`);
            if (res.data.status === 'success') {
                setModules(res.data.data);
                setDetectedYear(res.data.std_year);
                setShowSemPopup(false);
                // Also reload requests for this student
                fetchRequests();
            } else {
                showToast(res.data.message || 'Failed to load modules.', 'danger');
            }
        } catch (err) {
            console.error(err);
            showToast('Network error. Could not load modules.', 'danger');
        } finally {
            setLoading(false);
        }
    };

    const handleRequestUnit = async (mod) => {
        try {
            const res = await api.post('/peer-learning-requests', {
                courseUnitID: mod.courseUnitID,
                courseUnitName: mod.courseUnitName,
                semester: selectedSemester,
                description: 'General unit request'
            });
            if (res.data.status === 'success') {
                fetchRequests();
                showToast(res.data.message || 'Request submitted!', 'success');
            } else {
                showToast(res.data.message || 'Failed to submit request.', 'danger');
            }
        } catch (err) {
            showToast('Failed to request unit.', 'danger');
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const res = await api.post('/peer-learning-requests', {
                ...formData,
                semester: selectedSemester
            });
            if (res.data.status === 'success') {
                setShowModal(false);
                setFormData({ courseUnitName: '', courseUnitID: '', description: '' });
                fetchRequests();
                showToast(res.data.message || 'Request submitted successfully!', 'success');
            } else {
                showToast(res.data.message || 'Failed to submit request.', 'danger');
            }
        } catch (err) {
            showToast('An error occurred while submitting the request.', 'danger');
        }
    };

    const handleStatusUpdate = async (courseUnitID, courseUnitName, status) => {
        try {
            const res = await api.put('/peer-learning-requests', { courseUnitID, courseUnitName, status });
            if (res.data.status === 'success') {
                fetchRequests();
                const msg = status === 'broadcast_help' 
                    ? 'Help request broadcasted to your batch and seniors.' 
                    : `Requests updated.`;
                showToast(msg, 'success');
            } else {
                showToast('Failed to update status.', 'danger');
            }
        } catch (err) {
            console.error(err);
        }
    };

    const getStatusBadge = (status) => {
        const map = {
            pending:  { cls: 'bg-warning text-dark', label: 'Pending' },
            rejected: { cls: 'bg-danger text-white',  label: 'Rejected' },
            completed:{ cls: 'bg-primary text-white', label: 'Completed' },
        };
        const s = map[status] || { cls: 'bg-secondary text-white', label: status };
        return <span className={`badge ${s.cls}`}>{s.label}</span>;
    };

    return (
        <div>
            {/* Toast */}
            {actionStatus.show && (
                <div
                    className={`alert alert-${actionStatus.type} d-flex align-items-center position-fixed top-0 end-0 m-3 shadow`}
                    style={{ zIndex: 1050, minWidth: 280 }}
                >
                    {actionStatus.type === 'success'
                        ? <CheckCircle className="me-2" size={20} />
                        : <XCircle className="me-2" size={20} />}
                    {actionStatus.message}
                </div>
            )}

            {/* Page Header */}
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 className="fw-bold text-dark mb-1">Peer Learning</h3>
                    <p className="text-muted m-0">
                        {user?.role === 'course_representative'
                            ? 'Review and manage student module requests for your batch'
                            : 'Request help from your course representative'}
                    </p>
                </div>
                {user?.role === 'student' && !showSemPopup && modules.length > 0 && (
                    <button
                        className="btn btn-outline-primary rounded-pill px-4"
                        onClick={() => setShowSemPopup(true)}
                    >
                        Change Semester
                    </button>
                )}
            </div>

            {loading ? (
                <div className="text-center mt-5">
                    <div className="spinner-border text-primary" />
                    <p className="text-muted mt-3">Loading...</p>
                </div>

            ) : showSemPopup ? (
                /* ── SEMESTER SELECTION POPUP (Student only) ── */
                <>
                    <div className="card border-0 shadow-sm mx-auto mb-4" style={{ maxWidth: 500 }}>
                        <div className="card-body p-4">
                            <h5 className="fw-bold mb-4">Select Semester</h5>
                            <form onSubmit={fetchModules}>
                                <div className="mb-4">
                                    <label className="form-label text-muted small fw-bold">SEMESTER</label>
                                    <select
                                        className="form-select"
                                        value={selectedSemester}
                                        onChange={e => setSelectedSemester(e.target.value)}
                                    >
                                        <option value="1">Semester 1</option>
                                        <option value="2">Semester 2</option>
                                    </select>
                                </div>
                                <button type="submit" className="btn btn-primary w-100 rounded-pill">
                                    Load Modules
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* Show existing requests even while popup is open */}
                    {requests.length > 0 && (
                        <>
                            <h5 className="mb-3 fw-bold border-top pt-4">Your Existing Requests</h5>
                            <div className="row g-3">
                                {requests.map((req, idx) => (
                                    <div className="col-md-6" key={req.requestID || idx}>
                                        <div className="card border-0 shadow-sm">
                                            <div className="card-body p-3 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 className="fw-bold m-0">{req.courseUnitName}</h6>
                                                    <small className="text-muted">{req.courseUnitID}</small>
                                                </div>
                                                {getStatusBadge(req.status)}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </>
                    )}
                </>

            ) : user?.role === 'student' && modules.length > 0 ? (
                /* ── STUDENT MODULE GRID ── */
                <>


                    <h5 className="mb-3 fw-bold">Select a Module to Request Peer Learning</h5>
                    <div className="row g-3 mb-5">
                        {modules.map(mod => {
                            const alreadyRequested = requests.some(
                                r => r.courseUnitID === mod.courseUnitID && r.status === 'pending'
                            );
                            return (
                                <div className="col-md-4" key={mod.courseUnitID}>
                                    <div className={`card border-0 shadow-sm h-100 ${alreadyRequested ? 'border border-success' : ''}`}>
                                        <div className="card-body p-4 text-center d-flex flex-column">
                                            <BookOpen className="text-primary mb-3 mx-auto" size={32} />
                                            <h6 className="fw-bold flex-grow-1">{mod.courseUnitName}</h6>
                                            <span className="badge bg-light text-dark mt-2 mb-3 border mx-auto">
                                                {mod.courseUnitID}
                                            </span>
                                            {alreadyRequested ? (
                                                <span className="badge bg-success py-2 mb-2 w-100">
                                                    <CheckCircle size={14} className="me-1" /> Request Sent
                                                </span>
                                            ) : (
                                                <button
                                                    className="btn btn-sm btn-primary mt-auto w-100 rounded-pill mb-2"
                                                    onClick={() => handleRequestUnit(mod)}
                                                >
                                                    Request Unit
                                                </button>
                                            )}
                                            <button
                                                className="btn btn-link text-decoration-none small text-muted p-0"
                                                onClick={() => {
                                                    setFormData({ ...formData, courseUnitID: mod.courseUnitID, courseUnitName: mod.courseUnitName });
                                                    setShowModal(true);
                                                }}
                                            >
                                                Ask specific question
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* My Requests */}
                    <h5 className="mb-3 fw-bold border-top pt-4">My Submitted Requests</h5>
                    {requests.length === 0 ? (
                        <div className="text-center text-muted py-4">
                            <Clock size={40} className="mb-2 opacity-50" />
                            <p>No requests submitted yet.</p>
                        </div>
                    ) : (
                        <div className="row g-3">
                            {requests.map((req, idx) => (
                                <div className="col-md-6" key={req.requestID || idx}>
                                    <div className="card border-0 shadow-sm">
                                        <div className="card-body p-3 d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 className="fw-bold m-0">{req.courseUnitName}</h6>
                                                <small className="text-muted">{req.courseUnitID}</small>
                                                {req.description && (
                                                    <p className="text-muted small mt-1 mb-0">{req.description}</p>
                                                )}
                                            </div>
                                            {getStatusBadge(req.status)}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </>

            ) : user?.role === 'course_representative' ? (
                /* ── REP VIEW — Grouped requests ── */
                <>
                    {requests.length === 0 ? (
                        <div className="text-center text-muted py-5">
                            <Users size={56} className="mb-3 opacity-50" />
                            <h5>No requests yet</h5>
                            <p className="small">Students from your batch will appear here when they request peer learning.</p>
                        </div>
                    ) : (
                        <>
                            {/* Group requests by semester */}
                            {Object.entries(requests.reduce((acc, req) => {
                                const sem = req.semester || 'Other';
                                if (!acc[sem]) acc[sem] = [];
                                acc[sem].push(req);
                                return acc;
                            }, {})).sort(([a], [b]) => a.localeCompare(b)).map(([semester, semsRequests]) => (
                                <div key={semester} className="mb-5">
                                    <h5 className="fw-bold mb-3 border-bottom pb-2">
                                        {semester === 'Other' ? 'Other Semesters' : `Semester ${semester}`}
                                    </h5>
                                    <div className="row g-4">
                                        {semsRequests.map((req, idx) => (
                                            <div className="col-md-6" key={idx}>
                                                <div className="card border-0 shadow-sm h-100">
                                                    <div className="card-body p-4">
                                                        <div className="d-flex justify-content-between align-items-start mb-2">
                                                            <div>
                                                                <h5 className="fw-bold m-0">{req.courseUnitName}</h5>
                                                                <span className="badge bg-light text-dark border mt-1">
                                                                    {req.courseUnitID}
                                                                </span>
                                                            </div>
                                                            {getStatusBadge(req.status)}
                                                        </div>

                                                        {/* Student count progress bar (line graph style) */}
                                                        <div className="mt-4 mb-2">
                                                            <div className="d-flex justify-content-between align-items-center mb-1">
                                                                <span className="text-muted small fw-bold">
                                                                    <Users size={14} className="me-1" />
                                                                    {req.request_count} student{req.request_count > 1 ? 's' : ''} requested
                                                                </span>
                                                            </div>
                                                            <div className="progress" style={{ height: '8px' }}>
                                                                <div 
                                                                    className="progress-bar bg-primary rounded-pill" 
                                                                    role="progressbar" 
                                                                    style={{ width: `${Math.min((req.request_count / 30) * 100, 100)}%` }} 
                                                                    aria-valuenow={req.request_count} 
                                                                    aria-valuemin="0" 
                                                                    aria-valuemax="30"
                                                                ></div>
                                                            </div>
                                                        </div>

                                                        {req.semester && (
                                                            <p className="text-muted small mb-0 mt-1">
                                                                Semester {req.semester}
                                                                {req.std_year ? ` · Year ${req.std_year}` : ''}
                                                            </p>
                                                        )}

                                                        {/* View Topics Button */}
                                                        {req.descriptions_list && req.descriptions_list.length > 0 && (
                                                            <div className="mt-3">
                                                                <button
                                                                    className="btn btn-outline-primary btn-sm rounded-pill w-100"
                                                                    onClick={() => setSelectedRepModule(req)}
                                                                >
                                                                    <BookOpen size={14} className="me-1" /> View Requested Topics
                                                                </button>
                                                            </div>
                                                        )}
                                                        {req.status === 'pending' && (
                                                            <div className="d-flex mt-3">
                                                                <button
                                                                    className="btn btn-success btn-sm rounded-pill w-100"
                                                                    onClick={() => {
                                                                        handleStatusUpdate(req.courseUnitID, req.courseUnitName, 'broadcast_help');
                                                                    }}
                                                                >
                                                                    <CheckCircle size={14} className="me-1" /> Send Notification
                                                                </button>
                                                            </div>
                                                        )}

                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </>
                    )}
                </>

            ) : (
                /* Fallback: student with no modules loaded yet */
                <div className="text-center mt-5">
                    <BookOpen size={56} className="text-muted mb-3 opacity-50" />
                    <h5 className="text-muted">No modules loaded</h5>
                    <button className="btn btn-primary rounded-pill mt-2" onClick={() => setShowSemPopup(true)}>
                        Select Semester
                    </button>
                </div>
            )}

            {/* Ask Specific Question Modal */}
            {showModal && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border-0 shadow">
                            <div className="modal-header border-0 pb-0">
                                <h5 className="fw-bold">Request Peer Learning</h5>
                                <button type="button" className="btn-close" onClick={() => setShowModal(false)} />
                            </div>
                            <div className="modal-body p-4">
                                <form onSubmit={handleSubmit}>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">COURSE CODE</label>
                                        <input type="text" className="form-control bg-light" value={formData.courseUnitID} readOnly />
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">MODULE NAME</label>
                                        <input type="text" className="form-control bg-light" value={formData.courseUnitName} readOnly />
                                    </div>
                                    <div className="mb-4">
                                        <label className="form-label text-muted small fw-bold">DESCRIBE WHERE YOU NEED HELP</label>
                                        <textarea
                                            className="form-control"
                                            rows="3"
                                            placeholder="Provide specific details about what you need help with..."
                                            value={formData.description}
                                            onChange={e => setFormData({ ...formData, description: e.target.value })}
                                            required
                                        />
                                    </div>
                                    <div className="d-flex gap-2 justify-content-end">
                                        <button type="button" className="btn btn-light rounded-pill px-4" onClick={() => setShowModal(false)}>
                                            Cancel
                                        </button>
                                        <button type="submit" className="btn btn-primary rounded-pill px-4">
                                            Submit Request
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Rep View Anonymous Topics Modal */}
            {selectedRepModule && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1055 }}>
                    <div className="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                        <div className="modal-content border-0 shadow">
                            <div className="modal-header border-0 pb-0">
                                <div>
                                    <h5 className="fw-bold mb-0">Requested Topics</h5>
                                    <small className="text-muted">{selectedRepModule.courseUnitName}</small>
                                </div>
                                <button type="button" className="btn-close" onClick={() => setSelectedRepModule(null)} />
                            </div>
                            <div className="modal-body p-4">
                                
                                {selectedRepModule.descriptions_list && selectedRepModule.descriptions_list.filter(d => d !== 'General unit request').length > 0 ? (
                                    <div className="d-flex flex-column gap-3">
                                        {selectedRepModule.descriptions_list.filter(d => d !== 'General unit request').map((desc, idx) => (
                                            <div key={idx} className="p-3 bg-light rounded-3 border-start border-4 border-primary shadow-sm">
                                                <div className="text-primary small fw-bold mb-1">Anonymous Student</div>
                                                <div className="text-dark" style={{ whiteSpace: 'pre-wrap' }}>{desc}</div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center text-muted py-4">
                                        <p>No specific topics requested for this module.</p>
                                    </div>
                                )}
                            </div>
                            <div className="modal-footer border-0 pt-0">
                                <button type="button" className="btn btn-light rounded-pill px-4 w-100" onClick={() => setSelectedRepModule(null)}>
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default PeerLearning;
