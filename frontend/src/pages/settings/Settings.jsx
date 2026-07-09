import React, { useState, useContext, useEffect } from 'react';
import { AuthContext } from '../../context/AuthContext';
import api from '../../services/api';
import { User, ShieldCheck, XCircle, CheckCircle, Save, Key, Bell } from 'lucide-react';

const Settings = () => {
    const { user, login } = useContext(AuthContext);
    const [formData, setFormData] = useState({
        first_name: '',
        last_name: '',
        email: '',
        phone_number: '',
        course: '',
        year: '',
        department: '',
        lost_item_sms_notification: 0,
        peer_learning_app_notification: 1,
        old_password: '',
        new_password: '',
        confirm_password: ''
    });

    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (user) {
            setFormData({
                first_name: user.first_name || '',
                last_name: user.last_name || '',
                email: user.email || '',
                phone_number: user.phone_number || '',
                course: user.course || '',
                year: user.year || '',
                department: user.department || '',
                lost_item_sms_notification: user.lost_item_sms_notification !== undefined ? parseInt(user.lost_item_sms_notification) : 0,
                peer_learning_app_notification: user.peer_learning_app_notification !== undefined ? parseInt(user.peer_learning_app_notification) : 1,
                old_password: '',
                new_password: '',
                confirm_password: ''
            });
        }
    }, [user]);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        if (name === 'first_name' || name === 'last_name') {
            if (value !== '' && !/^[a-zA-Z\s]*$/.test(value)) {
                return;
            }
        }
        setFormData({
            ...formData,
            [name]: type === 'checkbox' ? (checked ? 1 : 0) : value
        });
        setError('');
        setSuccess('');
    };

    const handleSubmitProfile = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        setSuccess('');

        // Basic validations
        const nameRegex = /^[a-zA-Z\s]+$/;
        if (user?.role !== 'admin' && (!nameRegex.test((formData.first_name || '').trim()) || !nameRegex.test((formData.last_name || '').trim()))) {
            setError('First name and last name must contain only letters and spaces.');
            setLoading(false);
            return;
        }

        const isEmailEmpty = !formData.email;
        const isNameEmpty = user?.role !== 'admin' && (!formData.first_name || !formData.last_name);

        if (isEmailEmpty || isNameEmpty) {
            setError('Please fill in Name and Email fields.');
            setLoading(false);
            return;
        }

        if (formData.new_password) {
            if (!formData.old_password) {
                setError('Current password is required to set a new password.');
                setLoading(false);
                return;
            }
            if (formData.new_password.length < 6) {
                setError('New password must be at least 6 characters long.');
                setLoading(false);
                return;
            }
            if (formData.new_password !== formData.confirm_password) {
                setError('New passwords do not match.');
                setLoading(false);
                return;
            }
        }

        try {
            const response = await api.post('/auth.php?action=update-profile', {
                first_name: user?.role === 'admin' ? 'Admin' : formData.first_name,
                last_name: user?.role === 'admin' ? 'Admin' : formData.last_name,
                email: formData.email,
                phone_number: user?.role === 'admin' ? '0000000000' : formData.phone_number,
                course: user?.role === 'admin' ? '' : formData.course,
                year: user?.role === 'admin' ? '' : formData.year,
                department: user?.role === 'admin' ? '' : formData.department,
                lost_item_sms_notification: user?.role === 'admin' ? 0 : formData.lost_item_sms_notification,
                peer_learning_app_notification: user?.role === 'admin' ? 0 : formData.peer_learning_app_notification,
                old_password: formData.old_password,
                new_password: formData.new_password,
                confirm_password: formData.confirm_password
            });

            if (response.data.status === 'success') {
                setSuccess('Profile updated successfully!');
                // Update local auth context with new details
                login(response.data.data.token, response.data.data.user);
                
                // Clear password fields
                setFormData(prev => ({
                    ...prev,
                    old_password: '',
                    new_password: '',
                    confirm_password: ''
                }));
            } else {
                setError(response.data.message);
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to update profile. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="container-fluid py-4" style={{ maxWidth: '800px' }}>
            <div className="d-flex align-items-center mb-4 gap-2">
                <User size={28} className="text-primary" />
                <h2 className="m-0 fw-bold text-dark">Manage Profile</h2>
            </div>

            {error && (
                <div className="alert alert-danger d-flex align-items-center gap-2 mb-4">
                    <XCircle size={18} />
                    <span>{error}</span>
                </div>
            )}

            {success && (
                <div className="alert alert-success d-flex align-items-center gap-2 mb-4">
                    <CheckCircle size={18} />
                    <span>{success}</span>
                </div>
            )}

            <div className="card shadow-sm border-0 mb-4 bg-white">
                <div className="card-header bg-white border-bottom py-3">
                    <h5 className="mb-0 text-dark fw-bold">Personal Information</h5>
                </div>
                <div className="card-body p-4">
                    <form onSubmit={handleSubmitProfile}>
                        <div className="row g-3">
                            {/* Enrollment / ID (ReadOnly) */}
                            <div className="col-md-6">
                                <label className="form-label text-muted text-uppercase fw-bold" style={{ fontSize: '0.75rem' }}>
                                    {user?.role === 'admin' ? 'Admin ID' : 'Enrollment No / Staff ID'}
                                </label>
                                <input
                                    type="text"
                                    className="form-control bg-light text-muted"
                                    value={user?.role === 'admin' ? (user?.admin_id || '') : (user?.enrollment_no || '')}
                                    readOnly
                                    disabled
                                />
                            </div>

                            {/* Role (ReadOnly) */}
                            <div className="col-md-6">
                                <label className="form-label text-muted text-uppercase fw-bold" style={{ fontSize: '0.75rem' }}>Role</label>
                                <input
                                    type="text"
                                    className="form-control bg-light text-muted text-capitalize"
                                    value={user?.role || ''}
                                    readOnly
                                    disabled
                                />
                            </div>

                            {user?.role === 'admin' ? null : (
                                <>
                                    {/* First Name */}
                                    <div className="col-md-6">
                                        <label className="form-label text-dark fw-semibold">First Name</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            name="first_name"
                                            value={formData.first_name}
                                            onChange={handleChange}
                                            required
                                        />
                                    </div>

                                    {/* Last Name */}
                                    <div className="col-md-6">
                                        <label className="form-label text-dark fw-semibold">Last Name</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            name="last_name"
                                            value={formData.last_name}
                                            onChange={handleChange}
                                            required
                                        />
                                    </div>

                                    {/* Email */}
                                    <div className="col-md-6">
                                        <label className="form-label text-dark fw-semibold">Email Address</label>
                                        <input
                                            type="email"
                                            className="form-control bg-light text-muted"
                                            name="email"
                                            value={formData.email}
                                            readOnly
                                            disabled
                                        />
                                    </div>

                                    {/* Phone Number */}
                                    <div className="col-md-6">
                                        <label className="form-label text-dark fw-semibold">Phone Number</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            name="phone_number"
                                            value={formData.phone_number}
                                            onChange={handleChange}
                                        />
                                    </div>


                                    {/* Staff Fields */}
                                    {user?.role === 'staff' && (
                                        <div className="col-12">
                                            <label className="form-label text-dark fw-semibold">Department</label>
                                            <input
                                                type="text"
                                                className="form-control"
                                                name="department"
                                                placeholder="e.g. Department of Computer Science"
                                                value={formData.department}
                                                onChange={handleChange}
                                            />
                                        </div>
                                    )}

                                    <hr className="my-4 border-light" />
                                    
                                    <h5 className="text-dark fw-bold mb-3 d-flex align-items-center gap-2">
                                        <Bell size={18} className="text-primary" />
                                        <span>Notification Preferences</span>
                                    </h5>

                                    {user?.role !== 'rep' && (
                                        <div className="col-md-6">
                                        <div className="card bg-light border-0 p-3 h-100">
                                            <div className="form-check form-switch d-flex align-items-center justify-content-between p-0">
                                                <div>
                                                    <label className="form-check-label fw-bold text-dark" htmlFor="smsNotifSwitch">
                                                        SMS Notifications
                                                    </label>
                                                    <div className="text-muted small">
                                                        Receive text message alerts when new lost items are reported.
                                                    </div>
                                                </div>
                                                <input
                                                    className="form-check-input ms-0"
                                                    type="checkbox"
                                                    id="smsNotifSwitch"
                                                    name="lost_item_sms_notification"
                                                    checked={formData.lost_item_sms_notification === 1}
                                                    onChange={handleChange}
                                                    style={{ width: '2.5em', height: '1.25em', cursor: 'pointer' }}
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    )}

                                    {user?.role !== 'staff' && (
                                        <div className="col-md-6">
                                            <div className="card bg-light border-0 p-3 h-100">
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
                                                        checked={formData.peer_learning_app_notification === 1}
                                                        onChange={handleChange}
                                                        style={{ width: '2.5em', height: '1.25em', cursor: 'pointer' }}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}

                            <hr className="my-4 border-light" />
                            
                            <h5 className="text-dark fw-bold mb-3 d-flex align-items-center gap-2">
                                <Key size={18} className="text-primary" />
                                <span>Change Password</span>
                            </h5>

                            {/* Password Fields */}
                            <div className="col-md-4">
                                <label className="form-label text-dark fw-semibold">Current Password</label>
                                <input
                                    type="password"
                                    className="form-control"
                                    name="old_password"
                                    placeholder="••••••••"
                                    value={formData.old_password}
                                    onChange={handleChange}
                                />
                            </div>

                            <div className="col-md-4">
                                <label className="form-label text-dark fw-semibold">New Password</label>
                                <input
                                    type="password"
                                    className="form-control"
                                    name="new_password"
                                    placeholder="Minimum 6 characters"
                                    value={formData.new_password}
                                    onChange={handleChange}
                                />
                            </div>

                            <div className="col-md-4">
                                <label className="form-label text-dark fw-semibold">Confirm New Password</label>
                                <input
                                    type="password"
                                    className="form-control"
                                    name="confirm_password"
                                    placeholder="Confirm new password"
                                    value={formData.confirm_password}
                                    onChange={handleChange}
                                />
                            </div>

                            {/* Save Button */}
                            <div className="col-12 mt-4 text-end">
                                <button type="submit" className="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2" disabled={loading}>
                                    {loading ? <span className="spinner-border spinner-border-sm"></span> : <Save size={18} />}
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
};

export default Settings;
