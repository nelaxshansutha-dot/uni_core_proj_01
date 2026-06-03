import React, { useState, useEffect, useContext } from 'react';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';
import { Plus, Search, MapPin } from 'lucide-react';

const LostItems = () => {
    const { user } = useContext(AuthContext);
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    
    const [formData, setFormData] = useState({
        title: '',
        description: '',
        image_url: ''
    });

    const fetchItems = async () => {
        try {
            const res = await api.get('/lost-items.php');
            if (res.data.status === 'success') {
                setItems(res.data.data);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchItems();
    }, []);

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const res = await api.post('/lost-items.php', formData);
            if (res.data.status === 'success') {
                setShowModal(false);
                setFormData({ title: '', description: '', image_url: '' });
                fetchItems();
            }
        } catch (err) {
            console.error(err);
        }
    };

    const handleUpdateStatus = async (id, newStatus) => {
        try {
            const res = await api.put('/lost-items.php', { id, status: newStatus });
            if (res.data.status === 'success') {
                fetchItems();
            }
        } catch (err) {
            console.error(err);
        }
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 className="fw-bold text-dark mb-1">Lost & Found</h3>
                    <p className="text-muted m-0">Report lost items or help others find theirs</p>
                </div>
                <button className="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-4" onClick={() => setShowModal(true)}>
                    <Plus size={18} />
                    Report Item
                </button>
            </div>

            {loading ? (
                <div className="text-center mt-5"><div className="spinner-border text-primary"></div></div>
            ) : (
                <div className="row g-4">
                    {items.length === 0 ? (
                        <div className="col-12 text-center text-muted py-5">
                            <Search size={48} className="mb-3 opacity-50" />
                            <h5>No items reported yet</h5>
                        </div>
                    ) : (
                        items.map(item => (
                            <div className="col-md-6 col-lg-4" key={item.id}>
                                <div className="card h-100 border-0 shadow-sm overflow-hidden">
                                    {item.image_url ? (
                                        <img src={item.image_url} alt={item.title} style={{ height: '200px', objectFit: 'cover' }} />
                                    ) : (
                                        <div className="bg-light d-flex align-items-center justify-content-center" style={{ height: '200px' }}>
                                            <Search size={40} className="text-secondary opacity-25" />
                                        </div>
                                    )}
                                    <div className="card-body p-4">
                                        <div className="d-flex justify-content-between align-items-start mb-2">
                                            <h5 className="fw-bold m-0">{item.title}</h5>
                                            <span className={`badge rounded-pill ${item.status === 'lost' ? 'bg-danger' : 'bg-success'}`}>
                                                {item.status.toUpperCase()}
                                            </span>
                                        </div>
                                        <p className="text-muted small mb-3">{item.description}</p>
                                        <div className="d-flex align-items-center text-secondary small mb-3">
                                            <MapPin size={14} className="me-1" />
                                            Reported by: {item.enrollment_no}
                                        </div>
                                        
                                        {item.user_id === user?.id && item.status === 'lost' && (
                                            <button 
                                                className="btn btn-outline-success btn-sm w-100 rounded-pill"
                                                onClick={() => handleUpdateStatus(item.id, 'found')}
                                            >
                                                Mark as Found
                                            </button>
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
                                <h5 className="fw-bold">Report Lost Item</h5>
                                <button type="button" className="btn-close" onClick={() => setShowModal(false)}></button>
                            </div>
                            <div className="modal-body p-4">
                                <form onSubmit={handleSubmit}>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">Item Title</label>
                                        <input type="text" className="form-control" value={formData.title} onChange={e => setFormData({...formData, title: e.target.value})} required />
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">Description</label>
                                        <textarea className="form-control" rows="3" value={formData.description} onChange={e => setFormData({...formData, description: e.target.value})} required></textarea>
                                    </div>
                                    <div className="mb-4">
                                        <label className="form-label text-muted small fw-bold">Image URL (Optional)</label>
                                        <input type="url" className="form-control" value={formData.image_url} onChange={e => setFormData({...formData, image_url: e.target.value})} />
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

export default LostItems;
