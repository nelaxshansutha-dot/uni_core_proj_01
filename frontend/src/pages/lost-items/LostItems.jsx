import React, { useState, useEffect, useContext } from 'react';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';
import { Plus, Search, MapPin, Trash2, BellRing } from 'lucide-react';


console.log("LostItems loaded"); 

const LostItems = () => {
    const { user } = useContext(AuthContext);
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    const [showPrefModal, setShowPrefModal] = useState(false);

    const [formData, setFormData] = useState({
        item_name: '',
        description: '',
        last_seen_datetime: '',
        last_seen_place: '',
        contact_number: '',
        item_image: null,
        send_sms_alert: false
    });

    useEffect(() => {
        fetchItems();
        checkPreferencePopup();
    }, []);

    const checkPreferencePopup = async () => {
        try {
            const res = await api.get('/profile.php');
            if (res.data.status === 'success') {
                const profile = res.data.data;
                // If user is registered/logged in and has_seen_lost_item_popup is 0 (false)
                if (parseInt(profile.has_seen_lost_item_popup) === 0) {
                    setShowPrefModal(true);
                }
            }
        } catch (err) {
            console.error("Failed to check user profile settings", err);
        }
    };

    const handlePreferenceSelection = async (accept) => {
        try {
            const val = accept ? 1 : 0;
            const res = await api.put('/lost-items.php', {
                update_preference: true,
                lost_item_sms_notification: val,
                has_seen_lost_item_popup: 1
            });
            if (res.data.status === 'success') {
                setShowPrefModal(false);
            }
        } catch (err) {
            console.error(err);
        }
    };

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

   const [showDeleteModal, setShowDeleteModal] = useState(false);
const [selectedItemId, setSelectedItemId] = useState(null);

const handleDelete = async () => {
    try {
        const res = await api.delete(
            `/lost-items.php?id=${selectedItemId}`
        );

        if (res.data.status === 'success') {
            fetchItems();
        }

        setShowDeleteModal(false);
        setSelectedItemId(null);

    } catch (err) {
        console.error("Failed to delete post", err);
    }
};

    const handleSubmit = async (e) => {
        e.preventDefault();

        const data = new FormData();

        data.append('item_name', formData.item_name);
        data.append('description', formData.description);
        data.append('last_seen_datetime', formData.last_seen_datetime);
        data.append('last_seen_place', formData.last_seen_place);
        data.append('contact_number', formData.contact_number);
        data.append('send_sms_alert', formData.send_sms_alert);

        if (formData.item_image) {
            data.append('item_image', formData.item_image);
        }

        try {
            const res = await api.post('/lost-items.php', data, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            });

            if (res.data.status === 'success') {
                setShowModal(false);

                setFormData({
                    item_name: '',
                    description: '',
                    last_seen_datetime: '',
                    last_seen_place: '',
                    contact_number: '',
                    item_image: null,
                    send_sms_alert: false
                });

                fetchItems();
            }
        } catch (err) {
            console.error(err);
        }
    };

    return (
        <div>
            {/*Header*/}
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 className="fw-bold">Lost & Found</h3>
                    <p className="text-muted">
                        Report lost items or help others find theirs
                    </p>
                </div>

                <button
                    className="btn btn-primary d-flex align-items-center gap-2"
                    onClick={() => setShowModal(true)}
                >
                    <Plus size={18} />
                    Report Item
                </button>
            </div>

            {/* ITEMS */}
            {loading ? (
                <div className="text-center">
                    <div className="spinner-border text-primary"></div>
                </div>
            ) : (
                <div className="row g-4">
                    {items.length === 0 ? (
                        <div className="col-12 text-center text-muted py-5">
                            <Search size={40} className="mb-2 opacity-50" />
                            <h5>No items found</h5>
                        </div>
                    ) : (
                        items.map((item) => (
                            <div className="col-md-4" key={item.lost_id}>
                                <div className="card shadow-sm h-100 d-flex flex-column justify-content-between">
                                    <div>
                                        {item.item_image ? (
                                            <img
                                                src={`http://localhost/uni_core_proj_01/backend/${item.item_image}`}
                                                alt={item.item_name}
                                                style={{
                                                    height: '200px',
                                                    width: '100%',
                                                    objectFit: 'cover'
                                                }}
                                            />
                                        ) : (
                                            <div
                                                className="bg-light d-flex justify-content-center align-items-center"
                                                style={{ height: '200px' }}
                                            >
                                                <Search size={40} />
                                            </div>
                                        )}

                                        <div className="card-body">
                                            <h5>{item.item_name}</h5>
                                            <p>{item.description}</p>

                                            <p className="mb-1">
                                                <strong>Place:</strong> {item.last_seen_place}
                                            </p>

                                            <p className="mb-1">
                                                <strong>Date:</strong> {item.last_seen_datetime}
                                            </p>

                                            <p className="mb-2">
                                                <strong>Contact:</strong> {item.contact_number}
                                            </p>

                                            <div className="text-muted small d-flex align-items-center">
                                                <MapPin size={14} className="me-1" />
                                                {item.enrollment_no}
                                            </div>
                                        </div>
                                    </div>
                                    {item.user_id === user?.id && (
                                        <div className="card-footer bg-white border-0 pt-0 pb-3 px-3">
                                            <button
    className="btn btn-danger"
    onClick={() => {
        setSelectedItemId(item.lost_id);
        setShowDeleteModal(true);
    }}
>
    Delete Post
</button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))
                    )}
                </div>
            )}

            {/* PREFERENCE POPUP MODAL */}
            {showPrefModal && (
                <div
                    className="modal show d-block"
                    style={{ backgroundColor: 'rgba(0,0,0,0.6)', zIndex: 1060 }}
                >
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border-0 shadow-lg">
                            <div className="modal-header border-0 pb-0 justify-content-center text-center mt-3">
                                <div className="bg-primary bg-opacity-10 text-primary p-3 rounded-circle mb-2">
                                    <BellRing size={40} />
                                </div>
                            </div>
                            <div className="modal-body text-center px-4 pb-4">
                                <h4 className="fw-bold mb-2">Enable SMS Alerts?</h4>
                                <p className="text-muted">
                                    Would you like to receive instant SMS notifications when new lost items are reported on campus?
                                </p>
                                <div className="d-flex flex-column gap-2 mt-4">
                                    <button
                                        type="button"
                                        className="btn btn-primary btn-lg w-100 rounded-pill"
                                        onClick={() => handlePreferenceSelection(true)}
                                    >
                                        Accept & Subscribe
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-light btn-lg w-100 rounded-pill text-secondary"
                                        onClick={() => handlePreferenceSelection(false)}
                                    >
                                        Reject / Not Now
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* MODAL */}
            {showModal && (
                <div
                    className="modal show d-block"
                    style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}
                >
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content">

                            <div className="modal-header">
                                <h5>Report Lost Item</h5>
                                <button
                                    className="btn-close"
                                    onClick={() => setShowModal(false)}
                                />
                            </div>

                            <div className="modal-body">
                                <form onSubmit={handleSubmit}>

                                    <input
                                        className="form-control mb-2"
                                        placeholder="Item Name"
                                        value={formData.item_name}
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                item_name: e.target.value
                                            })
                                        }
                                        required
                                    />

                                    <textarea
                                        className="form-control mb-2"
                                        placeholder="Description"
                                        value={formData.description}
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                description: e.target.value
                                            })
                                        }
                                        required
                                    />

                                    <input
                                        type="datetime-local"
                                        className="form-control mb-2"
                                        value={formData.last_seen_datetime}
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                last_seen_datetime: e.target.value
                                            })
                                        }
                                        required
                                    />

                                    <input
                                        className="form-control mb-2"
                                        placeholder="Last Seen Place"
                                        value={formData.last_seen_place}
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                last_seen_place: e.target.value
                                            })
                                        }
                                        required
                                    />

                                    <input
                                        className="form-control mb-2"
                                        placeholder="Contact Number"
                                        value={formData.contact_number}
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                contact_number: e.target.value
                                            })
                                        }
                                        required
                                    />

                                    <input
                                        type="file"
                                        className="form-control mb-3"
                                        accept="image/*"
                                        onChange={(e) =>
                                            setFormData({
                                                ...formData,
                                                item_image: e.target.files[0]
                                            })
                                        }
                                    />

                                    <div className="form-check mb-4">
                                        <input
                                            className="form-check-input"
                                            type="checkbox"
                                            id="sendSmsAlert"
                                            checked={formData.send_sms_alert}
                                            onChange={(e) => setFormData({ ...formData, send_sms_alert: e.target.checked })}
                                        />
                                        <label className="form-check-label text-muted small" htmlFor="sendSmsAlert">
                                            Send SMS notification to subscribed users
                                        </label>
                                    </div>

                                    {/* ✅ BUTTON FIXED HERE */}
                                    <div className="d-flex justify-content-end gap-2">
                                        <button
                                            type="button"
                                            className="btn btn-secondary"
                                            onClick={() => setShowModal(false)}
                                        >
                                            Cancel
                                        </button>

                                        <button
                                            type="submit"
                                            className="btn btn-primary"
                                        >
                                            Submit
                                        </button>
                                    </div>

                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            )}
            {/* DELETE CONFIRMATION MODAL */}
            {showDeleteModal && (
                <div
                    className="modal show d-block"
                    style={{ backgroundColor: 'rgba(0,0,0,0.6)', zIndex: 1060 }}
                >
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border-0 shadow-lg">
                            <div className="modal-body text-center px-4 py-4">
                                <div className="text-danger mb-3">
                                    <Trash2 size={48} />
                                </div>
                                <h5 className="mb-4">Are you sure want to delete this post?</h5>
                                <div className="d-flex gap-3 justify-content-center">
                                    <button
                                        type="button"
                                        className="btn btn-danger px-4"
                                        onClick={handleDelete}
                                    >
                                        Yes
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-secondary px-4"
                                        onClick={() => {
                                            setShowDeleteModal(false);
                                            setSelectedItemId(null);
                                        }}
                                    >
                                        No
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

export default LostItems;