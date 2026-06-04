import React, { useState, useEffect, useContext, useRef } from 'react';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';
import {
    Plus, ShoppingBag, Tag, MapPin, Phone, Trash2,
    X, Upload, CheckCircle, XCircle, Clock, Package
} from 'lucide-react';
import './Marketplace.css';

const BASE_URL = 'http://localhost/uni_core_proj_01/backend/api';

/* ─── Image Uploader Component ─── */
const ImageUploader = ({ label, value, onChange }) => {
    const inputRef = useRef();
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState('');

    const handleFile = async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        setError('');
        setUploading(true);
        try {
            const form = new FormData();
            form.append('image', file);
            const token = localStorage.getItem('token');
            const res = await fetch(`${BASE_URL}/upload.php`, {
                method: 'POST',
                headers: { Authorization: `Bearer ${token}` },
                body: form
            });
            const json = await res.json();
            if (json.status === 'success') {
                onChange(json.data.url);
            } else {
                setError(json.message || 'Upload failed');
            }
        } catch {
            setError('Upload failed. Try again.');
        } finally {
            setUploading(false);
        }
    };

    return (
        <div className="img-upload-box" onClick={() => !value && inputRef.current.click()}>
            <input ref={inputRef} type="file" accept="image/*" className="d-none" onChange={handleFile} />
            {value ? (
                <div className="img-preview-wrap">
                    <img src={value} alt={label} className="img-preview" />
                    <button
                        type="button"
                        className="img-remove-btn"
                        onClick={(e) => { e.stopPropagation(); onChange(''); }}
                    >
                        <X size={14} />
                    </button>
                </div>
            ) : uploading ? (
                <div className="d-flex flex-column align-items-center justify-content-center gap-1 text-primary">
                    <div className="spinner-border spinner-border-sm" />
                    <span style={{ fontSize: '0.7rem' }}>Uploading…</span>
                </div>
            ) : (
                <div className="d-flex flex-column align-items-center justify-content-center gap-1 text-muted">
                    <Upload size={22} />
                    <span style={{ fontSize: '0.7rem' }}>{label}</span>
                </div>
            )}
            {error && <div className="img-upload-error">{error}</div>}
        </div>
    );
};

/* ─── Main Component ─── */
const Marketplace = () => {
    const { user } = useContext(AuthContext);
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [submitError, setSubmitError] = useState('');
    const [deletingId, setDeletingId] = useState(null);

    const emptyForm = {
        item_name: '',
        condition_type: 'new',
        description: '',
        price: '',
        location: '',
        phone_number: '',
        usage_duration: '',
        image_url: '',
        image_url2: '',
        image_url3: '',
        image_url4: '',
    };
    const [formData, setFormData] = useState(emptyForm);

    const fetchItems = async () => {
        setLoading(true);
        try {
            const res = await api.get('/marketplace.php');
            if (res.data.status === 'success') setItems(res.data.data);
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { fetchItems(); }, []);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
        setSubmitError('');
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSubmitError('');

        if (formData.condition_type === 'used' && !formData.usage_duration.trim()) {
            setSubmitError('Please specify how long you have used this item.');
            return;
        }

        setSubmitting(true);
        try {
            const res = await api.post('/marketplace.php', formData);
            if (res.data.status === 'success') {
                setShowModal(false);
                setFormData(emptyForm);
                fetchItems();
            } else {
                setSubmitError(res.data.message);
            }
        } catch (err) {
            setSubmitError(err.response?.data?.message || 'Failed to list item.');
        } finally {
            setSubmitting(false);
        }
    };

    const handleMarkSold = async (id) => {
        try {
            await api.put('/marketplace.php', { id, status: 'sold' });
            fetchItems();
        } catch (err) { console.error(err); }
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Are you sure you want to delete this listing?')) return;
        setDeletingId(id);
        try {
            await api.delete('/marketplace.php', { data: { id } });
            fetchItems();
        } catch (err) { console.error(err); }
        finally { setDeletingId(null); }
    };

    const isUsed = formData.condition_type === 'used';

    return (
        <div>
            {/* ── Header ── */}
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 className="fw-bold text-dark mb-1">Marketplace</h3>
                    <p className="text-muted m-0">Buy and sell academic materials &amp; more</p>
                </div>
                <button
                    className="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-4"
                    onClick={() => setShowModal(true)}
                >
                    <Plus size={18} /> List Item
                </button>
            </div>

            {/* ── Items Grid ── */}
            {loading ? (
                <div className="text-center mt-5"><div className="spinner-border text-primary" /></div>
            ) : (
                <div className="row g-4">
                    {items.length === 0 ? (
                        <div className="col-12 text-center text-muted py-5">
                            <ShoppingBag size={48} className="mb-3 opacity-25" />
                            <h5>No items listed yet</h5>
                            <p className="small">Be the first to list an item!</p>
                        </div>
                    ) : (
                        items.map(item => {
                            const images = [item.image_url, item.image_url2, item.image_url3, item.image_url4].filter(Boolean);
                            const isMine = item.seller_id === user?.id;
                            return (
                                <div className="col-md-6 col-lg-4" key={item.id}>
                                    <div className="card h-100 border-0 shadow-sm overflow-hidden market-card">
                                        {/* Image Area */}
                                        {images.length > 0 ? (
                                            <div className="market-img-grid" data-count={Math.min(images.length, 4)}>
                                                {images.slice(0, 4).map((url, i) => (
                                                    <img key={i} src={url} alt={item.item_name} className="market-thumb" />
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="bg-light d-flex align-items-center justify-content-center" style={{ height: '180px' }}>
                                                <ShoppingBag size={40} className="text-secondary opacity-25" />
                                            </div>
                                        )}

                                        <div className="card-body p-4">
                                            {/* Condition badge */}
                                            <div className="d-flex align-items-center gap-2 mb-2">
                                                <span className={`badge rounded-pill px-2 ${item.condition_type === 'new' ? 'bg-success' : 'bg-warning text-dark'}`}>
                                                    {item.condition_type === 'new' ? 'New' : 'Used'}
                                                </span>
                                                <span className={`badge rounded-pill px-2 ${item.status === 'available' ? 'bg-info text-dark' : 'bg-secondary'}`}>
                                                    {item.status === 'available' ? 'Available' : 'Sold'}
                                                </span>
                                            </div>

                                            {/* Name & Price */}
                                            <div className="d-flex justify-content-between align-items-start mb-1">
                                                <h5 className="fw-bold m-0 text-dark" style={{ fontSize: '1rem' }}>{item.item_name}</h5>
                                                <span className="fw-bold text-primary ms-2" style={{ whiteSpace: 'nowrap' }}>Rs. {parseFloat(item.price).toLocaleString()}</span>
                                            </div>

                                            {/* Description */}
                                            <p className="text-muted small mb-2" style={{ lineHeight: '1.4' }}>
                                                {item.description.length > 100 ? item.description.slice(0, 100) + '…' : item.description}
                                            </p>

                                            {/* Usage Duration (used items) */}
                                            {item.condition_type === 'used' && item.usage_duration && (
                                                <div className="d-flex align-items-center gap-1 text-secondary small mb-1">
                                                    <Clock size={13} />
                                                    <span>Used for: {item.usage_duration}</span>
                                                </div>
                                            )}

                                            {/* Location */}
                                            {item.location && (
                                                <div className="d-flex align-items-center gap-1 text-secondary small mb-1">
                                                    <MapPin size={13} />
                                                    <span>{item.location}</span>
                                                </div>
                                            )}

                                            {/* Phone */}
                                            {item.phone_number && (
                                                <div className="d-flex align-items-center gap-1 text-secondary small mb-2">
                                                    <Phone size={13} />
                                                    <span>{item.phone_number}</span>
                                                </div>
                                            )}

                                            <div className="d-flex align-items-center gap-1 text-secondary small mb-3">
                                                <Tag size={13} />
                                                <span>Seller: {item.seller_name || item.enrollment_no}</span>
                                            </div>

                                            {/* Action Buttons */}
                                            <div className="d-flex flex-column gap-2">
                                                {/* Seller: mark sold + delete */}
                                                {isMine && item.status === 'available' && (
                                                    <button
                                                        className="btn btn-outline-secondary btn-sm rounded-pill"
                                                        onClick={() => handleMarkSold(item.id)}
                                                    >
                                                        Mark as Sold
                                                    </button>
                                                )}

                                                {isMine && (
                                                    <button
                                                        className="btn btn-outline-danger btn-sm rounded-pill d-flex align-items-center justify-content-center gap-1"
                                                        onClick={() => handleDelete(item.id)}
                                                        disabled={deletingId === item.id}
                                                    >
                                                        {deletingId === item.id
                                                            ? <span className="spinner-border spinner-border-sm" />
                                                            : <><Trash2 size={14} /> Delete Listing</>
                                                        }
                                                    </button>
                                                )}

                                                {/* Buyer: contact */}
                                                {!isMine && item.status === 'available' && (
                                                    <a
                                                        href={`tel:${item.phone_number}`}
                                                        className="btn btn-primary btn-sm rounded-pill d-flex align-items-center justify-content-center gap-1"
                                                    >
                                                        <Phone size={14} /> Contact Seller
                                                    </a>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>
            )}

            {/* ── List Item Modal ── */}
            {showModal && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.55)' }}>
                    <div className="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div className="modal-content border-0 shadow-lg">
                            <div className="modal-header border-0 pb-0 px-4 pt-4">
                                <div className="d-flex align-items-center gap-2">
                                    <Package size={22} className="text-primary" />
                                    <h5 className="fw-bold m-0">List an Item for Sale</h5>
                                </div>
                                <button type="button" className="btn-close" onClick={() => { setShowModal(false); setFormData(emptyForm); setSubmitError(''); }} />
                            </div>

                            <div className="modal-body px-4 py-3">
                                {submitError && (
                                    <div className="alert alert-danger d-flex align-items-center gap-2 py-2">
                                        <XCircle size={16} /><span>{submitError}</span>
                                    </div>
                                )}
                                <form onSubmit={handleSubmit}>
                                    <div className="row g-3">

                                        {/* Item Name */}
                                        <div className="col-md-8">
                                            <label className="form-label fw-semibold text-dark">Item Name <span className="text-danger">*</span></label>
                                            <input
                                                type="text"
                                                className="form-control"
                                                name="item_name"
                                                placeholder="e.g. Calculus Textbook, Laptop Stand…"
                                                value={formData.item_name}
                                                onChange={handleChange}
                                                required
                                            />
                                        </div>

                                        {/* Condition Dropdown */}
                                        <div className="col-md-4">
                                            <label className="form-label fw-semibold text-dark">Condition <span className="text-danger">*</span></label>
                                            <select
                                                className="form-select"
                                                name="condition_type"
                                                value={formData.condition_type}
                                                onChange={handleChange}
                                                required
                                            >
                                                <option value="new">🟢 New</option>
                                                <option value="used">🟡 Used</option>
                                            </select>
                                        </div>

                                        {/* Price */}
                                        <div className="col-md-4">
                                            <label className="form-label fw-semibold text-dark">Price (Rs.) <span className="text-danger">*</span></label>
                                            <input
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                className="form-control"
                                                name="price"
                                                placeholder="e.g. 1500"
                                                value={formData.price}
                                                onChange={handleChange}
                                                required
                                            />
                                        </div>

                                        {/* Location */}
                                        <div className="col-md-4">
                                            <label className="form-label fw-semibold text-dark">Location <span className="text-danger">*</span></label>
                                            <input
                                                type="text"
                                                className="form-control"
                                                name="location"
                                                placeholder="e.g. Badulla, Hostel Block A"
                                                value={formData.location}
                                                onChange={handleChange}
                                                required
                                            />
                                        </div>

                                        {/* Phone Number */}
                                        <div className="col-md-4">
                                            <label className="form-label fw-semibold text-dark">Phone Number <span className="text-danger">*</span></label>
                                            <input
                                                type="tel"
                                                className="form-control"
                                                name="phone_number"
                                                placeholder="e.g. +94 77 123 4567"
                                                value={formData.phone_number}
                                                onChange={handleChange}
                                                required
                                            />
                                        </div>

                                        {/* Usage Duration — only if Used */}
                                        {isUsed && (
                                            <div className="col-12">
                                                <label className="form-label fw-semibold text-dark">
                                                    <Clock size={14} className="me-1 text-warning" />
                                                    Duration of Use <span className="text-danger">*</span>
                                                </label>
                                                <input
                                                    type="text"
                                                    className="form-control"
                                                    name="usage_duration"
                                                    placeholder="e.g. 6 months, 1 year, 2 semesters…"
                                                    value={formData.usage_duration}
                                                    onChange={handleChange}
                                                />
                                            </div>
                                        )}

                                        {/* Description */}
                                        <div className="col-12">
                                            <label className="form-label fw-semibold text-dark">Description <span className="text-danger">*</span></label>
                                            <textarea
                                                className="form-control"
                                                name="description"
                                                rows={3}
                                                placeholder={isUsed
                                                    ? "Describe the item's condition, any defects, reason for selling…"
                                                    : "Describe the item — edition, features, what's included…"
                                                }
                                                value={formData.description}
                                                onChange={handleChange}
                                                required
                                            />
                                        </div>

                                        {/* Image Upload — 4 slots */}
                                        <div className="col-12">
                                            <label className="form-label fw-semibold text-dark">
                                                <Upload size={14} className="me-1" />
                                                Upload Images (up to 4)
                                            </label>
                                            <div className="img-upload-grid">
                                                {[
                                                    ['image_url',  'Photo 1 (Main)'],
                                                    ['image_url2', 'Photo 2'],
                                                    ['image_url3', 'Photo 3'],
                                                    ['image_url4', 'Photo 4'],
                                                ].map(([field, label]) => (
                                                    <ImageUploader
                                                        key={field}
                                                        label={label}
                                                        value={formData[field]}
                                                        onChange={(url) => setFormData(prev => ({ ...prev, [field]: url }))}
                                                    />
                                                ))}
                                            </div>
                                            <div className="text-muted" style={{ fontSize: '0.75rem' }}>
                                                Accepted: JPG, PNG, WebP, GIF · Max 5 MB each
                                            </div>
                                        </div>

                                    </div>

                                    <div className="d-flex gap-2 justify-content-end mt-4">
                                        <button
                                            type="button"
                                            className="btn btn-light rounded-pill px-4"
                                            onClick={() => { setShowModal(false); setFormData(emptyForm); setSubmitError(''); }}
                                        >
                                            Cancel
                                        </button>
                                        <button
                                            type="submit"
                                            className="btn btn-primary rounded-pill px-4 d-flex align-items-center gap-2"
                                            disabled={submitting}
                                        >
                                            {submitting
                                                ? <span className="spinner-border spinner-border-sm" />
                                                : <CheckCircle size={16} />
                                            }
                                            {submitting ? 'Submitting…' : 'Submit Listing'}
                                        </button>
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

export default Marketplace;
