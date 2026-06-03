import React, { useState, useEffect, useContext } from 'react';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';
import { Users, Plus, CheckCircle, XCircle } from 'lucide-react';

const PeerLearning = () => {
    const { user } = useContext(AuthContext);
    const [requests, setRequests] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    
    const [formData, setFormData] = useState({
        course_code: '',
        topic: '',
        description: ''
    });

    const fetchRequests = async () => {
        try {
            const endpoint = user.role === 'rep' ? '/peer-learning.php?action=course-requests&course_code=CS101' : '/peer-learning.php?action=my-requests';
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

    useEffect(() => {
        fetchRequests();
    }, []);

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const res = await api.post('/peer-learning.php', formData);
            if (res.data.status === 'success') {
                setShowModal(false);
                setFormData({ course_code: '', topic: '', description: '' });
                fetchRequests();
            }
        } catch (err) {
            console.error(err);
        }
    };

    const handleUpdateStatus = async (id, status) => {
        try {
            const res = await api.put('/peer-learning.php', { id, status });
            if (res.data.status === 'success') {
                fetchRequests();
            }
        } catch (err) {
            console.error(err);
        }
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 className="fw-bold text-dark mb-1">Peer Learning</h3>
                    <p className="text-muted m-0">Request help or assist peers</p>
                </div>
                {['student', 'rep'].includes(user?.role) && (
                    <button className="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-4" onClick={() => setShowModal(true)}>
                        <Plus size={18} />
                        Request Help
                    </button>
                )}
            </div>

            {loading ? (
                <div className="text-center mt-5"><div className="spinner-border text-primary"></div></div>
            ) : (
                <div className="row g-4">
                    {requests.length === 0 ? (
                        <div className="col-12 text-center text-muted py-5">
                            <Users size={48} className="mb-3 opacity-50" />
                            <h5>No requests found</h5>
                        </div>
                    ) : (
                        requests.map(req => (
                            <div className="col-md-6" key={req.id}>
                                <div className="card h-100 border-0 shadow-sm">
                                    <div className="card-body p-4">
                                        <div className="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h5 className="fw-bold m-0">{req.topic}</h5>
                                                <span className="badge bg-secondary mt-1">{req.course_code}</span>
                                            </div>
                                            <span className={`badge rounded-pill ${
                                                req.status === 'pending' ? 'bg-warning' : 
                                                req.status === 'approved' ? 'bg-success' : 'bg-danger'
                                            }`}>
                                                {req.status.toUpperCase()}
                                            </span>
                                        </div>
                                        <p className="text-muted small">{req.description}</p>
                                        
                                        <div className="mt-3 pt-3 border-top text-secondary small">
                                            Requested by: {req.student_enrollment || user.enrollment_no}
                                        </div>

                                        {user?.role === 'rep' && req.status === 'pending' && (
                                            <div className="d-flex gap-2 mt-3">
                                                <button className="btn btn-sm btn-success w-100 rounded-pill d-flex justify-content-center align-items-center gap-1" onClick={() => handleUpdateStatus(req.id, 'approved')}>
                                                    <CheckCircle size={16} /> Approve
                                                </button>
                                                <button className="btn btn-sm btn-danger w-100 rounded-pill d-flex justify-content-center align-items-center gap-1" onClick={() => handleUpdateStatus(req.id, 'rejected')}>
                                                    <XCircle size={16} /> Reject
                                                </button>
                                            </div>
                                        )}
                                        
                                        {req.status === 'approved' && (
                                            <div className="alert alert-success mt-3 mb-0 p-2 small text-center rounded-pill">
                                                Assigned Rep ID: {req.rep_id || 'Pending assignment'}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            )}

            {/* Modal */}
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
                                        <input type="text" className="form-control" value={formData.course_code} onChange={e => setFormData({...formData, course_code: e.target.value})} required />
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">Topic</label>
                                        <input type="text" className="form-control" value={formData.topic} onChange={e => setFormData({...formData, topic: e.target.value})} required />
                                    </div>
                                    <div className="mb-4">
                                        <label className="form-label text-muted small fw-bold">Description / Where you need help</label>
                                        <textarea className="form-control" rows="3" value={formData.description} onChange={e => setFormData({...formData, description: e.target.value})} required></textarea>
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
