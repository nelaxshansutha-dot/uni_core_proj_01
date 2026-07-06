import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { User, Save, ShieldAlert, CheckCircle, AlertTriangle } from 'lucide-react';

const Profile = () => {
    const [profile, setProfile] = useState({
        first_name: '',
        last_name: '',
        email: '',
        enrollment_no: '',
        phone_number: '',
        role: '',
        lost_item_sms_notification: 0,
        peer_learning_app_notification: 1,
        course: '',
        year: '',
        department: ''
    });
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [statusMessage, setStatusMessage] = useState({ type: '', text: '' });

    useEffect(() => {
        fetchProfile();
    }, []);

    const fetchProfile = async () => {
        try {
            const res = await api.get('/profile.php');
            if (res.data.status === 'success') {
                setProfile(res.data.data);
            }
        } catch (err) {
            console.error(err);
            setStatusMessage({ type: 'danger', text: 'Failed to load profile data.' });
        } finally {
            setLoading(false);
        }
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setProfile({
            ...profile,
            [name]: type === 'checkbox' ? (checked ? 1 : 0) : value
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setStatusMessage({ type: '', text: '' });

        try {
            const res = await api.put('/profile.php', profile);
            if (res.data.status === 'success') {
                setStatusMessage({ type: 'success', text: 'Profile updated successfully!' });
                fetchProfile();
            } else {
                setStatusMessage({ type: 'danger', text: res.data.message || 'Failed to update profile.' });
            }
        } catch (err) {
            console.error(err);
            setStatusMessage({ type: 'danger', text: 'Error updating profile.' });
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="text-center mt-5">
                <div className="spinner-border text-primary"></div>
            </div>
        );
    }

    return (
        <div className="container py-4">
            <div className="mb-4">
                <h3 className="fw-bold text-dark mb-1">Manage Profile</h3>
                <p className="text-muted m-0">View your details and update your notification preferences</p>
            </div>

            {statusMessage.text && (
                <div className={`alert alert-${statusMessage.type} alert-dismissible fade show d-flex align-items-center gap-2`} role="alert">
                    {statusMessage.type === 'success' ? <CheckCircle size={18} /> : <AlertTriangle size={18} />}
                    <span>{statusMessage.text}</span>
                    <button type="button" className="btn-close" onClick={() => setStatusMessage({ type: '', text: '' })}></button>
                </div>
            )}

            <div className="row g-4">
                <div className="col-lg-8">
                    <div className="card border-0 shadow-sm">
                        <div className="card-body p-4">
                            <form onSubmit={handleSubmit}>
                                <h5 className="fw-bold mb-4 d-flex align-items-center gap-2">
                                    <User className="text-primary" />
                                    Personal Details
                                </h5>

                                <div className="row g-3 mb-4">
                                    <div className="col-md-6">
                                        <label className="form-label text-muted small fw-bold">First Name</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            name="first_name"
                                            value={profile.first_name || ''}
                                            onChange={handleChange}
                                            required
                                        />
                                    </div>
                                    <div className="col-md-6">
                                        <label className="form-label text-muted small fw-bold">Last Name</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            name="last_name"
                                            value={profile.last_name || ''}
                                            onChange={handleChange}
                                            required
                                        />
                                    </div>
                                    <div className="col-md-6">
                                        <label className="form-label text-muted small fw-bold">Email Address</label>
                                        <input
                                            type="email"
                                            className="form-control bg-light"
                                            value={profile.email || ''}
                                            disabled
                                        />
                                        <div className="form-text">Email address cannot be changed.</div>
                                    </div>
                                    <div className="col-md-6">
                                        <label className="form-label text-muted small fw-bold">
                                            {profile.role === 'admin' ? 'Admin ID' : 'Enrollment No / Staff ID'}
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control bg-light"
                                            value={profile.role === 'admin' ? (profile.admin_id || '') : (profile.enrollment_no || '')}
                                            disabled
                                        />
                                    </div>
                                    <div className="col-md-12">
                                        <label className="form-label text-muted small fw-bold">Phone Number (for SMS Notifications)</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            name="phone_number"
                                            placeholder="e.g. +94771234567"
                                            value={profile.phone_number || ''}
                                            onChange={handleChange}
                                        />
                                    </div>



                                    {profile.role === 'staff' && (
                                        <div className="col-md-12">
                                            <label className="form-label text-muted small fw-bold">Department</label>
                                            <input
                                                type="text"
                                                className="form-control"
                                                name="department"
                                                value={profile.department || ''}
                                                onChange={handleChange}
                                            />
                                        </div>
                                    )}
                                </div>

                                <hr className="my-4 text-secondary" />

                                <h5 className="fw-bold mb-3 d-flex align-items-center gap-2">
                                    <ShieldAlert className="text-warning" />
                                    Preferences
                                </h5>

                                <div className="card bg-light border-0 p-3 mb-3">
                                    <div className="form-check form-switch d-flex align-items-center justify-content-between p-0">
                                        <div>
                                            <label className="form-check-label fw-bold text-dark" htmlFor="smsNotifSwitch">
                                                SMS Notifications
                                            </label>
                                            <div className="text-muted small">
                                                Receive instant text message alerts when new lost items are reported on campus.
                                            </div>
                                        </div>
                                        <input
                                            className="form-check-input ms-0"
                                            type="checkbox"
                                            id="smsNotifSwitch"
                                            name="lost_item_sms_notification"
                                            checked={profile.lost_item_sms_notification === 1}
                                            onChange={handleChange}
                                            style={{ width: '2.5em', height: '1.25em', cursor: 'pointer' }}
                                        />
                                    </div>
                                </div>

                                <div className="card bg-light border-0 p-3 mb-4">
                                    <div className="form-check form-switch d-flex align-items-center justify-content-between p-0">
                                        <div>
                                            <label className="form-check-label fw-bold text-dark" htmlFor="peerNotifSwitch">
                                                Peer Learning Notifications
                                            </label>
                                            <div className="text-muted small">
                                                Receive app notifications when peer learning requests are submitted/updated.
                                            </div>
                                        </div>
                                        <input
                                            className="form-check-input ms-0"
                                            type="checkbox"
                                            id="peerNotifSwitch"
                                            name="peer_learning_app_notification"
                                            checked={profile.peer_learning_app_notification === 1}
                                            onChange={handleChange}
                                            style={{ width: '2.5em', height: '1.25em', cursor: 'pointer' }}
                                        />
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    className="btn btn-primary d-flex align-items-center gap-2 px-4 rounded-pill"
                                    disabled={saving}
                                >
                                    <Save size={18} />
                                    {saving ? 'Saving...' : 'Save Settings'}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Profile;
