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
    const [editingItem, setEditingItem] = useState(null);
    const [selectedItem, setSelectedItem] = useState(null);
    const [activeImageIndex, setActiveImageIndex] = useState(0);
    const [confirmDeleteId, setConfirmDeleteId] = useState(null);

    const emptyForm = {
        productName: '',
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
            if (res.data.status === 'success') {
                setItems(res.data.data);
                if (selectedItem) {
                    const updated = res.data.data.find(i => i.productID === selectedItem.productID);
                    if (updated) setSelectedItem(updated);
                }
            }
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

    const handleEditClick = (item, e) => {
        e.stopPropagation();
        setEditingItem(item);
        setFormData({
            productID: item.productID,
            productName: item.productName,
            condition_type: item.condition_type,
            description: item.description,
            price: item.price,
            location: item.location,
            phone_number: item.phone_number,
            usage_duration: item.usage_duration || '',
            image_url: item.image_url || '',
            image_url2: item.image_url2 || '',
            image_url3: item.image_url3 || '',
            image_url4: item.image_url4 || '',
        });
        setShowModal(true);
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
            let res;
            if (editingItem) {
                res = await api.put('/marketplace.php', formData);
            } else {
                res = await api.post('/marketplace.php', formData);
            }
            if (res.data.status === 'success') {
                setShowModal(false);
                setFormData(emptyForm);
                setEditingItem(null);
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

    const handleMarkSold = async (productID, e) => {
        if (e) e.stopPropagation();
        try {
            await api.put('/marketplace.php', { productID, status: 'sold' });
            fetchItems();
        } catch (err) { console.error(err); }
    };

    const handleDelete = async (productID) => {
        setDeletingId(productID);
        try {
            await api.delete('/marketplace.php', { data: { productID } });
            if (selectedItem && selectedItem.productID === productID) {
                setSelectedItem(null);
            }
            setConfirmDeleteId(null);
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
                <div className="row g-3">
                    {items.length === 0 ? (
                        <div className="col-12 text-center text-muted py-5">
                            <ShoppingBag size={48} className="mb-3 opacity-25" />
                            <h5>No items listed yet</h5>
                            <p className="small">Be the first to list an item!</p>
                        </div>
                    ) : (
                        items.map(item => {
                            const images = [item.image_url, item.image_url2, item.image_url3, item.image_url4].filter(Boolean);
                            const isMine = item.userID === user?.id;
                            return (
                                <div className="col-sm-6 col-lg-4 col-xl-3" key={item.productID} onClick={() => { setSelectedItem(item); setActiveImageIndex(0); }}>
                                    <div className="card h-100 border-0 shadow-sm overflow-hidden market-card" style={{ cursor: 'pointer' }}>
                                        {/* Image Area */}
                                        {images.length > 0 ? (
                                            <div className="market-img-grid" data-count={Math.min(images.length, 4)}>
                                                {images.slice(0, 4).map((url, i) => (
                                                    <img key={i} src={url} alt={item.productName} className="market-thumb" />
                                                ))}
                                            </div>
                                        ) : (
                                            <div className="bg-light d-flex align-items-center justify-content-center" style={{ height: '140px' }}>
                                                <ShoppingBag size={30} className="text-secondary opacity-25" />
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
                                                <h5 className="fw-bold m-0 text-dark" style={{ fontSize: '1rem' }}>{item.productName}</h5>
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
                                            <div className="d-flex flex-column gap-2" onClick={(e) => e.stopPropagation()}>
                                                {/* Seller: Edit + mark sold + delete */}
                                                {isMine && item.status === 'available' && (
                                                    <div className="d-flex gap-2">
                                                        <button
                                                            className="btn btn-outline-primary btn-sm rounded-pill flex-grow-1"
                                                            onClick={(e) => handleEditClick(item, e)}
                                                        >
                                                            Edit Listing
                                                        </button>
                                                        <button
                                                            className="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1"
                                                            onClick={(e) => handleMarkSold(item.productID, e)}
                                                        >
                                                            Mark as Sold
                                                        </button>
                                                    </div>
                                                )}

                                                {isMine && (
                                                    <button
                                                        className="btn btn-outline-danger btn-sm rounded-pill d-flex align-items-center justify-content-center gap-1"
                                                        onClick={(e) => { e.stopPropagation(); setConfirmDeleteId(item.productID); }}
                                                        disabled={deletingId === item.productID}
                                                    >
                                                        {deletingId === item.productID
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
                                                        onClick={(e) => e.stopPropagation()}
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

            {/* ── List/Edit Item Modal ── */}
            {showModal && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.55)' }}>
                    <div className="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                        <div className="modal-content border-0 shadow-lg">
                            <div className="modal-header border-0 pb-0 px-4 pt-4">
                                <div className="d-flex align-items-center gap-2">
                                    <Package size={22} className="text-primary" />
                                <h5 className="fw-bold m-0">{editingItem ? 'Edit Listing Details' : 'List an Item for Sale'}</h5>
                                </div>
                                <button type="button" className="btn-close" onClick={() => { setShowModal(false); setFormData(emptyForm); setEditingItem(null); setSubmitError(''); }} />
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
                                                name="productName"
                                                placeholder="e.g. Calculus Textbook, Laptop Stand…"
                                                value={formData.productName}
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
                                                placeholder="e.g. 0771234567"
                                                value={formData.phone_number}
                                                onChange={(e) => {
                                                    const val = e.target.value;
                                                    if (val === '' || /^[0-9]+$/.test(val)) {
                                                        setFormData(prev => ({
                                                            ...prev,
                                                            phone_number: val
                                                        }));
                                                    }
                                                }}
                                                required
                                            />
                                        </div>

                                        {/* Usage Duration — only if Used */}
                                        {isUsed && (
                                            <div className="col-12">
                                                <label className="form-label fw-semibold text-dark">
                                                    <Clock size={14} className="me-1 text-warning" />
                                                    Used Duration <span className="text-danger">*</span>
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
                                            onClick={() => { setShowModal(false); setFormData(emptyForm); setEditingItem(null); setSubmitError(''); }}
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
                                            {submitting ? 'Submitting…' : (editingItem ? 'Save Changes' : 'Submit Listing')}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* ── Item Detail / Photos Carousel Modal ── */}
            {selectedItem && (() => {
                const detailImages = [
                    selectedItem.image_url,
                    selectedItem.image_url2,
                    selectedItem.image_url3,
                    selectedItem.image_url4
                ].filter(Boolean);
                const isMine = selectedItem.userID === user?.id;

                return (
                    <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.65)' }} onClick={() => setSelectedItem(null)}>
                        <div className="modal-dialog modal-lg modal-dialog-centered" onClick={(e) => e.stopPropagation()}>
                            <div className="modal-content border-0 shadow-lg overflow-hidden" style={{ borderRadius: '20px' }}>
                                <div className="row g-0">
                                    {/* Left side: Photo Gallery */}
                                    <div className="col-md-6 bg-light d-flex flex-column justify-content-between p-3" style={{ minHeight: '380px' }}>
                                        <div className="d-flex justify-content-between align-items-center mb-2">
                                            <span className={`badge rounded-pill px-2 ${selectedItem.condition_type === 'new' ? 'bg-success' : 'bg-warning text-dark'}`}>
                                                {selectedItem.condition_type === 'new' ? 'New' : 'Used'}
                                            </span>
                                            <span className={`badge rounded-pill px-2 ${selectedItem.status === 'available' ? 'bg-info text-dark' : 'bg-secondary'}`}>
                                                {selectedItem.status === 'available' ? 'Available' : 'Sold'}
                                            </span>
                                        </div>

                                        <div className="flex-grow-1 d-flex align-items-center justify-content-center position-relative mb-3">
                                            {detailImages.length > 0 ? (
                                                <img
                                                    src={detailImages[activeImageIndex]}
                                                    alt={selectedItem.productName}
                                                    style={{ maxWidth: '100%', maxHeight: '280px', objectFit: 'contain', borderRadius: '10px' }}
                                                />
                                            ) : (
                                                <div className="text-muted text-center">
                                                    <ShoppingBag size={64} className="opacity-25 mb-2" />
                                                    <p className="small m-0">No photos uploaded</p>
                                                </div>
                                            )}
                                        </div>

                                        {/* Thumbnail strip */}
                                        {detailImages.length > 1 && (
                                            <div className="d-flex justify-content-center gap-2 overflow-auto py-1">
                                                {detailImages.map((url, i) => (
                                                    <img
                                                        key={i}
                                                        src={url}
                                                        alt={`thumbnail ${i}`}
                                                        onClick={() => setActiveImageIndex(i)}
                                                        style={{
                                                            width: '50px',
                                                            height: '50px',
                                                            objectFit: 'cover',
                                                            borderRadius: '6px',
                                                            cursor: 'pointer',
                                                            border: activeImageIndex === i ? '2px solid var(--primary-color, #5c2d53)' : '2px solid transparent',
                                                            opacity: activeImageIndex === i ? 1 : 0.6
                                                        }}
                                                    />
                                                ))}
                                            </div>
                                        )}
                                    </div>

                                    {/* Right side: Listing Details */}
                                    <div className="col-md-6 p-4 d-flex flex-column justify-content-between" style={{ backgroundColor: '#fff' }}>
                                        <div>
                                            <div className="d-flex justify-content-between align-items-start mb-3">
                                                <h4 className="fw-bold text-dark m-0">{selectedItem.productName}</h4>
                                                <button type="button" className="btn-close" onClick={() => setSelectedItem(null)} />
                                            </div>

                                            <h3 className="fw-bold text-primary mb-3">Rs. {parseFloat(selectedItem.price).toLocaleString()}</h3>
                                            
                                            <div className="mb-4">
                                                <label className="text-muted small fw-bold text-uppercase mb-1">Description</label>
                                                <p className="text-dark small" style={{ whiteSpace: 'pre-line', lineHeight: '1.5' }}>
                                                    {selectedItem.description}
                                                </p>
                                            </div>

                                            <div className="d-flex flex-column gap-2 mb-4">
                                                {selectedItem.condition_type === 'used' && selectedItem.usage_duration && (
                                                    <div className="d-flex align-items-center gap-2 text-secondary small">
                                                        <Clock size={15} className="text-warning" />
                                                        <span><strong>Used Duration:</strong> {selectedItem.usage_duration}</span>
                                                    </div>
                                                )}
                                                {selectedItem.location && (
                                                    <div className="d-flex align-items-center gap-2 text-secondary small">
                                                        <MapPin size={15} />
                                                        <span><strong>Location:</strong> {selectedItem.location}</span>
                                                    </div>
                                                )}
                                                {selectedItem.phone_number && (
                                                    <div className="d-flex align-items-center gap-2 text-secondary small">
                                                        <Phone size={15} />
                                                        <span><strong>Phone:</strong> {selectedItem.phone_number}</span>
                                                    </div>
                                                )}
                                                <div className="d-flex align-items-center gap-2 text-secondary small">
                                                    <Tag size={15} />
                                                    <span><strong>Seller:</strong> {selectedItem.seller_name || selectedItem.enrollment_no}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {/* Actions */}
                                        <div className="d-flex flex-column gap-2 mt-3">
                                            {isMine && selectedItem.status === 'available' && (
                                                <div className="d-flex gap-2">
                                                    <button
                                                        className="btn btn-outline-primary rounded-pill flex-grow-1"
                                                        onClick={(e) => handleEditClick(selectedItem, e)}
                                                    >
                                                        Edit Details
                                                    </button>
                                                    <button
                                                        className="btn btn-outline-secondary rounded-pill flex-grow-1"
                                                        onClick={(e) => handleMarkSold(selectedItem.productID, e)}
                                                    >
                                                        Mark as Sold
                                                    </button>
                                                </div>
                                            )}

                                            {isMine && (
                                                <button
                                                    className="btn btn-danger rounded-pill d-flex align-items-center justify-content-center gap-2"
                                                    onClick={(e) => handleDelete(selectedItem.productID, e)}
                                                    disabled={deletingId === selectedItem.productID}
                                                >
                                                    {deletingId === selectedItem.productID
                                                        ? <span className="spinner-border spinner-border-sm" />
                                                        : <><Trash2 size={16} /> Delete Listing</>
                                                    }
                                                </button>
                                            )}

                                            {!isMine && selectedItem.status === 'available' && (
                                                <a
                                                    href={`tel:${selectedItem.phone_number}`}
                                                    className="btn btn-primary rounded-pill d-flex align-items-center justify-content-center gap-2 py-2"
                                                >
                                                    <Phone size={16} /> Call Seller ({selectedItem.phone_number})
                                                </a>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                );
            })()}

            {/* ── Custom Deletion Confirmation Modal ── */}
            {confirmDeleteId && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.6)', zIndex: 1060 }} onClick={() => setConfirmDeleteId(null)}>
                    <div className="modal-dialog modal-dialog-centered" style={{ maxWidth: '400px' }} onClick={(e) => e.stopPropagation()}>
                        <div className="modal-content border-0 shadow-lg p-3 text-center" style={{ borderRadius: '16px' }}>
                            <div className="modal-body">
                                <div className="text-danger mb-3">
                                    <XCircle size={48} className="opacity-75" />
                                </div>
                                <h5 className="fw-bold mb-2">Delete Listing?</h5>
                                <p className="text-muted small mb-4">Are you sure you want to delete this listing? This action cannot be undone.</p>
                                <div className="d-flex gap-2 justify-content-center">
                                    <button 
                                        type="button" 
                                        className="btn btn-light rounded-pill px-4" 
                                        onClick={() => setConfirmDeleteId(null)}
                                    >
                                        No, Keep it
                                    </button>
                                    <button 
                                        type="button" 
                                        className="btn btn-danger rounded-pill px-4" 
                                        onClick={() => { handleDelete(confirmDeleteId); setConfirmDeleteId(null); }}
                                    >
                                        Yes, Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default Marketplace;
