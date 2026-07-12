import React, { useState, useEffect } from 'react';
import { useSearchParams } from 'react-router-dom';
import api from '../../services/api';
import { 
    ShieldAlert, Search, UserCheck, Users, Activity, 
    BookOpen, ShoppingBag, AlertTriangle, EyeOff, Eye,
    Trash2, RefreshCw, CheckCircle, UserPlus, Edit3, Shield, UserX
} from 'lucide-react';

const AdminPanel = () => {
    const [searchParams, setSearchParams] = useSearchParams();
    const activeTab = searchParams.get('tab') || 'overview';
    
    const setActiveTab = (tab) => {
        setSearchParams({ tab });
    };
    
    // Stats State
    const [stats, setStats] = useState({
        total_users: 0,
        active_users: 0,
        deactivated_users: 0,
        total_reps: 0,
        total_posts: 0,
        active_posts: 0,
        hidden_posts: 0,
        recent_logs: []
    });

    // Users State
    const [users, setUsers] = useState([]);
    const [userSearch, setUserSearch] = useState('');
    const [roleFilter, setRoleFilter] = useState('');
    const [showUserModal, setShowUserModal] = useState(false);
    const [editingUser, setEditingUser] = useState(null);
    
    // Deactivate User Modal State
    const [showDeactivateModal, setShowDeactivateModal] = useState(false);
    const [deactivateUserTarget, setDeactivateUserTarget] = useState(null);
    const [deactivateReason, setDeactivateReason] = useState('');
    
    // Delete Content Modal State
    const [deleteModal, setDeleteModal] = useState({
        isOpen: false,
        type: '',
        id: null,
        reason: ''
    });
    
    const [userForm, setUserForm] = useState({
        enrollment_no: '',
        email: '',
        password: '',
        role: 'student',
        first_name: '',
        last_name: '',
        phone_number: '',
        course: '',
        year: ''
    });

    // Rep Management State
    const [repSearch, setRepSearch] = useState('');
    const [repStudents, setRepStudents] = useState([]);
    const [selectedStudent, setSelectedStudent] = useState(null);
    const [repForm, setRepForm] = useState({
        fname: '',
        lname: '',
        phone: '',
        email: '',
        rep_id: '',
        password: '',
        course: '',
        year: '1'
    });

    // Content Moderation State
    const [contentTab, setContentTab] = useState('lost_item');
    const [content, setContent] = useState({
        lost_items: [],
        marketplace: [],
        notes: []
    });
    
    const [showItemModal, setShowItemModal] = useState(false);
    const [selectedViewItem, setSelectedViewItem] = useState(null);
    const [viewItemType, setViewItemType] = useState('');
    
    const handleViewItem = (item, type) => {
        setSelectedViewItem(item);
        setViewItemType(type);
        setShowItemModal(true);
    };

    // Reports State
    const [reports, setReports] = useState([]);

    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState({ type: '', text: '' });

    // Fetch initial data based on active tab
    useEffect(() => {
        fetchTabData();
    }, [activeTab, contentTab]);

    const fetchTabData = async () => {
        setLoading(true);
        try {
            if (activeTab === 'overview') {
                const res = await api.get('/admin/dashboard');
                if (res.data.status === 'success' || res.data.success) {
                    setStats(res.data.data);
                }
            } else if (activeTab === 'users') {
                const res = await api.get(`/admin/users?q=${userSearch}&role=${roleFilter}`);
                if (res.data.status === 'success' || res.data.success) {
                    setUsers(res.data.data);
                }
            } else if (activeTab === 'content') {
                const res = await api.get('/admin/content');
                if (res.data.status === 'success' || res.data.success) {
                    setContent(res.data.data);
                }
            } else if (activeTab === 'reports') {
                const res = await api.get('/admin/reports');
                if (res.data.status === 'success' || res.data.success) {
                    setReports(res.data.data);
                }
            }
        } catch (err) {
            console.error(err);
            showFeedback('danger', 'Failed to fetch data.');
        } finally {
            setLoading(false);
        }
    };

    const showFeedback = (type, text) => {
        setMessage({ type, text });
        setTimeout(() => setMessage({ type: '', text: '' }), 5000);
    };

    // User Operations
    const handleUserSearchSubmit = (e) => {
        e.preventDefault();
        fetchTabData();
    };

    const handleSaveUser = async (e) => {
        e.preventDefault();
        const nameRegex = /^[a-zA-Z\s]+$/;
        if (!nameRegex.test((userForm.first_name || '').trim()) || !nameRegex.test((userForm.last_name || '').trim())) {
            showFeedback('danger', 'First name and last name must contain only letters and spaces.');
            return;
        }
        try {
            if (editingUser) {
                // Update User
                const res = await api.put(`/admin/users?id=${editingUser.id}`, userForm);
                if (res.data.status === 'success' || res.data.success) {
                    showFeedback('success', 'User updated successfully.');
                    setShowUserModal(false);
                    fetchTabData();
                } else {
                    showFeedback('danger', res.data.message);
                }
            } else {
                // Create User
                const res = await api.post('/admin/users', userForm);
                if (res.data.status === 'success' || res.data.success) {
                    showFeedback('success', 'User created successfully.');
                    setShowUserModal(false);
                    fetchTabData();
                } else {
                    showFeedback('danger', res.data.message);
                }
            }
        } catch (err) {
            showFeedback('danger', err.response?.data?.message || 'Error saving user.');
        }
    };

    const openEditModal = (user) => {
        setEditingUser(user);
        setUserForm({
            enrollment_no: user.enrollment_no,
            email: user.email,
            password: '', // Do not populate password for edits
            role: user.role,
            first_name: user.first_name || '',
            last_name: user.last_name || '',
            phone_number: user.phone_number || '',
            course: user.course || '',
            year: user.year || ''
        });
        setShowUserModal(true);
    };

    const openAddModal = () => {
        setEditingUser(null);
        setUserForm({
            enrollment_no: '',
            email: '',
            password: '',
            role: 'student',
            first_name: '',
            last_name: '',
            phone_number: '',
            course: '',
            year: ''
        });
        setShowUserModal(true);
    };

    const toggleUserActiveStatus = async (userId, currentStatus, reason = '') => {
        try {
            const payload = { is_active: !currentStatus };
            if (reason) payload.reason = reason;
            
            const res = await api.patch(`/admin/users-status?id=${userId}`, payload);
            if (res.data.status === 'success' || res.data.success) {
                showFeedback('success', `User successfully ${!currentStatus ? 'activated' : 'deactivated'}.`);
                fetchTabData();
            }
        } catch (err) {
            showFeedback('danger', 'Error updating user status.');
        }
    };
    
    const handleDeactivateClick = (user) => {
        if (user.is_active) {
            setDeactivateUserTarget(user);
            setDeactivateReason('');
            setShowDeactivateModal(true);
        } else {
            // Activating user doesn't require a reason
            toggleUserActiveStatus(user.id, user.is_active);
        }
    };
    
    const confirmDeactivation = async (e) => {
        e.preventDefault();
        if (!deactivateReason.trim()) {
            showFeedback('danger', 'Please provide a reason for deactivation.');
            return;
        }
        await toggleUserActiveStatus(deactivateUserTarget.id, true, deactivateReason);
        setShowDeactivateModal(false);
        setDeactivateUserTarget(null);
        setDeactivateReason('');
    };

    // Course Rep Operations
    const handleRepSearch = async (e) => {
        e.preventDefault();
        if (!repSearch) return;
        setLoading(true);
        try {
            const res = await api.get(`/admin/search-students?q=${repSearch}`);
            if (res.data.status === 'success' || res.data.success) {
                setRepStudents(res.data.data);
            }
        } catch (err) {
            showFeedback('danger', 'Error searching students.');
        } finally {
            setLoading(false);
        }
    };

    const handleAssignRep = async (e) => {
        e.preventDefault();
        if (!selectedStudent) return;
        const nameRegex = /^[a-zA-Z\s]+$/;
        if (!nameRegex.test((repForm.fname || '').trim()) || !nameRegex.test((repForm.lname || '').trim())) {
            showFeedback('danger', 'First name and last name must contain only letters and spaces.');
            return;
        }
        try {
            const res = await api.post('/admin/assign-rep', {
                user_id: selectedStudent.id,
                fname: repForm.fname,
                lname: repForm.lname,
                phone: repForm.phone,
                email: repForm.email,
                rep_id: repForm.rep_id,
                password: repForm.password,
                course: repForm.course,
                year: repForm.year
            });
            if (res.data.status === 'success' || res.data.success) {
                showFeedback('success', `Successfully assigned! Credentials have been emailed to ${selectedStudent.enrollment_no}.`);
                setSelectedStudent(null);
                setRepStudents([]);
                setRepSearch('');
                setRepForm({ fname: '', lname: '', phone: '', email: '', rep_id: '', password: '', course: '', year: '1' });
            } else {
                showFeedback('danger', res.data.message);
            }
        } catch (err) {
            showFeedback('danger', err.response?.data?.message || 'Error promoting student.');
        }
    };

    const handleDemoteRep = async (student) => {
        if (!window.confirm(`Are you sure you want to demote ${student.first_name} back to a regular student?`)) return;
        try {
            const res = await api.patch(`/admin/users-status?id=rep_${student.id}`, { is_active: false });
            if (res.data.status === 'success' || res.data.success) {
                showFeedback('success', `Successfully demoted ${student.first_name} to student.`);
                setSelectedStudent(null);
                setRepStudents([]);
                setRepSearch('');
                fetchTabData(); // refresh stats
            }
        } catch (err) {
            showFeedback('danger', 'Error demoting rep.');
        }
    };

    // Content Moderation
    const handleContentModeration = async (type, id, newStatus, reason = '') => {
        try {
            const res = await api.patch('/admin/content-status', {
                content_type: type,
                content_id: id,
                status: newStatus,
                reason: reason
            });
            if (res.data.status === 'success' || res.data.success) {
                showFeedback('success', `Content marked as ${newStatus}.`);
                fetchTabData();
                if (newStatus === 'removed') {
                    setDeleteModal({ isOpen: false, type: '', id: null, reason: '' });
                }
            }
        } catch (err) {
            showFeedback('danger', 'Error updating content status.');
        }
    };

    const openDeleteModal = (type, id) => {
        setDeleteModal({ isOpen: true, type, id, reason: '' });
    };



    // Report Operations
    const handleReportAction = async (reportId, reportStatus) => {
        try {
            const res = await api.patch('/admin/reports-status', {
                report_id: reportId,
                status: reportStatus
            });
            if (res.data.status === 'success' || res.data.success) {
                showFeedback('success', `Report marked as ${reportStatus}.`);
                fetchTabData();
            }
        } catch (err) {
            showFeedback('danger', 'Error updating report.');
        }
    };

    return (
        <div>
            {/* Header */}
            <div className="admin-hero-banner mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div style={{ position: 'relative', zIndex: 1 }}>
                    <h2 className="fw-bold text-white mb-1 d-flex align-items-center gap-2">
                        <ShieldAlert className="text-warning" size={32} />
                        Admin Dashboard
                    </h2>
                    <p className="text-white-50 m-0 fs-6">Platform Overview, Content Moderation, and User Roles</p>
                </div>
                <button className="btn btn-light btn-sm d-flex align-items-center gap-2 px-3 py-2 fw-semibold" style={{ position: 'relative', zIndex: 1 }} onClick={fetchTabData}>
                    <RefreshCw size={16} className={loading ? 'spin-animation' : ''} /> Refresh Data
                </button>
            </div>

            {/* Notification message */}
            {message.text && (
                <div className={`alert alert-${message.type} alert-dismissible fade show`} role="alert">
                    {message.text}
                    <button type="button" className="btn-close" onClick={() => setMessage({ type: '', text: '' })}></button>
                </div>
            )}

            {/* Navigation Tabs */}
            <div className="admin-nav-pills mb-4">
                <button className={`admin-nav-link ${activeTab === 'overview' ? 'active' : ''}`} onClick={() => setActiveTab('overview')}>
                    Overview
                </button>
                <button className={`admin-nav-link ${activeTab === 'users' ? 'active' : ''}`} onClick={() => setActiveTab('users')}>
                    User Management
                </button>
                <button className={`admin-nav-link ${activeTab === 'course-rep' ? 'active' : ''}`} onClick={() => setActiveTab('course-rep')}>
                    Course Representatives
                </button>
                <button className={`admin-nav-link ${activeTab === 'content' ? 'active' : ''}`} onClick={() => setActiveTab('content')}>
                    Content Moderation
                </button>

            </div>

            {/* TAB CONTENT: OVERVIEW */}
            {activeTab === 'overview' && (
                <div>
                    {/* Stats Grid */}
                    <div className="row g-4 mb-4">
                        <div className="col-md-3">
                            <div className="admin-stat-card stat-users d-flex align-items-center gap-3">
                                <Users size={60} className="admin-stat-icon-bg" />
                                <div className="p-3 rounded-circle bg-white bg-opacity-10 text-white">
                                    <Users size={24} />
                                </div>
                                <div>
                                    <h3 className="fw-bold mb-0 text-white">{stats.total_users}</h3>
                                    <p className="text-white-50 small mb-0 fw-semibold">Total Users</p>
                                </div>
                            </div>
                        </div>
                        <div className="col-md-3">
                            <div className="admin-stat-card stat-active d-flex align-items-center gap-3">
                                <Activity size={60} className="admin-stat-icon-bg" />
                                <div className="p-3 rounded-circle bg-white bg-opacity-10 text-white">
                                    <Activity size={24} />
                                </div>
                                <div>
                                    <h3 className="fw-bold mb-0 text-white">{stats.active_users}</h3>
                                    <p className="text-white-50 small mb-0 fw-semibold">Active Users</p>
                                </div>
                            </div>
                        </div>
                        <div className="col-md-3">
                            <div className="admin-stat-card stat-reps d-flex align-items-center gap-3">
                                <Shield size={60} className="admin-stat-icon-bg" />
                                <div className="p-3 rounded-circle bg-white bg-opacity-10 text-white">
                                    <Shield size={24} />
                                </div>
                                <div>
                                    <h3 className="fw-bold mb-0 text-white">{stats.total_reps}</h3>
                                    <p className="text-white-50 small mb-0 fw-semibold">Course Reps</p>
                                </div>
                            </div>
                        </div>
                        <div className="col-md-3">
                            <div className="admin-stat-card stat-posts d-flex align-items-center gap-3">
                                <BookOpen size={60} className="admin-stat-icon-bg" />
                                <div className="p-3 rounded-circle bg-white bg-opacity-10 text-white">
                                    <BookOpen size={24} />
                                </div>
                                <div>
                                    <h3 className="fw-bold mb-0 text-white">{stats.total_posts}</h3>
                                    <p className="text-white-50 small mb-0 fw-semibold">Posts ({stats.hidden_posts} hidden)</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Recent Activities Log */}
                    <div className="card border-0 shadow-sm">
                        <div className="card-header bg-white border-bottom p-4">
                            <h5 className="fw-bold m-0">Recent Admin Action Logs</h5>
                        </div>
                        <div className="card-body p-4">
                            {stats.recent_logs.length > 0 ? (
                                <ul className="list-group list-group-flush">
                                    {stats.recent_logs.map(log => (
                                        <li key={log.id} className="list-group-item px-0 py-3 d-flex justify-content-between align-items-start">
                                            <div>
                                                <div className="fw-semibold text-dark">{log.action}</div>
                                                <small className="text-muted">Target: {log.target_type} (ID: {log.target_id}) {log.details && `| Details: ${log.details}`}</small>
                                            </div>
                                            <div className="text-end">
                                                <span className="badge bg-light text-dark">{log.admin_name}</span>
                                                <div className="text-muted small mt-1">{new Date(log.created_at).toLocaleString()}</div>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <div className="text-center text-muted py-5">No logs available. Actions you perform will appear here.</div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* TAB CONTENT: USER MANAGEMENT */}
            {activeTab === 'users' && (
                <div>
                    <div className="card border-0 shadow-sm mb-4">
                        <div className="card-body p-4">
                            <div className="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                                <form onSubmit={handleUserSearchSubmit} className="d-flex gap-2 flex-grow-1">
                                    <div className="position-relative flex-grow-1">
                                        <Search className="position-absolute top-50 translate-middle-y text-muted ms-3" size={18} />
                                        <input 
                                            type="text" 
                                            className="form-control ps-5" 
                                            placeholder="Search by Name, Email or Enrollment No..." 
                                            value={userSearch}
                                            onChange={(e) => setUserSearch(e.target.value)}
                                        />
                                    </div>
                                    <select className="form-select w-auto" value={roleFilter} onChange={(e) => setRoleFilter(e.target.value)}>
                                        <option value="">All Roles</option>
                                        <option value="student">Student</option>
                                        <option value="rep">Course Representative</option>
                                        <option value="staff">Staff</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                    <button type="submit" className="btn btn-primary px-4">Search</button>
                                </form>
                            </div>

                            <div className="table-responsive">
                                <table className="table align-middle table-hover">
                                    <thead className="table-light">
                                        <tr>
                                            <th>Enrollment No / Staff ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th className="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {users.map(u => (
                                            <tr key={u.id}>
                                                <td className="fw-semibold">{u.enrollment_no || u.staff_id || '-'}</td>
                                                <td>{u.first_name} {u.last_name}</td>
                                                <td>{u.email}</td>
                                                <td>
                                                    <span className={`badge ${u.role === 'admin' ? 'bg-danger' : (u.role === 'rep' ? 'bg-warning text-dark' : (u.role === 'staff' ? 'bg-info text-dark' : 'bg-secondary'))}`}>
                                                        {u.role.toUpperCase()}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span className={`badge ${u.is_active ? 'bg-success' : 'bg-danger'}`}>
                                                        {u.is_active ? 'Active' : 'Inactive'}
                                                    </span>
                                                </td>
                                                <td className="text-end">
                                                    <div className="d-flex justify-content-end gap-2">
                                                        <button 
                                                            className={`btn btn-sm ${u.is_active ? 'btn-outline-danger' : 'btn-outline-success'}`}
                                                            onClick={() => handleDeactivateClick(u)}
                                                        >
                                                            {u.is_active ? <UserX size={14} /> : <UserCheck size={14} />}
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {/* MODAL FOR ADD/EDIT USER */}
                    {showUserModal && (
                        <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                            <div className="modal-dialog modal-dialog-centered">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title fw-bold">{editingUser ? 'Edit User Details' : 'Add New User'}</h5>
                                        <button type="button" className="btn-close" onClick={() => setShowUserModal(false)}></button>
                                    </div>
                                    <form onSubmit={handleSaveUser}>
                                        <div className="modal-body">
                                            <div className="row g-3">
                                                <div className="col-md-6">
                                                    <label className="form-label">Enrollment No / User ID</label>
                                                    <input 
                                                        type="text" 
                                                        className="form-control" 
                                                        value={userForm.enrollment_no}
                                                        onChange={(e) => setUserForm({ ...userForm, enrollment_no: e.target.value })}
                                                        required 
                                                        disabled={!!editingUser} 
                                                    />
                                                </div>
                                                <div className="col-md-6">
                                                    <label className="form-label">Email Address</label>
                                                    <input 
                                                        type="email" 
                                                        className={`form-control ${editingUser ? 'bg-light text-muted' : ''}`} 
                                                        value={userForm.email}
                                                        onChange={(e) => setUserForm({ ...userForm, email: e.target.value })}
                                                        required 
                                                        disabled={!!editingUser}
                                                    />
                                                </div>
                                                {!editingUser && (
                                                    <div className="col-12">
                                                        <label className="form-label">Password</label>
                                                        <input 
                                                            type="password" 
                                                            className="form-control" 
                                                            value={userForm.password}
                                                            onChange={(e) => setUserForm({ ...userForm, password: e.target.value })}
                                                            required 
                                                        />
                                                    </div>
                                                )}
                                                <div className="col-md-6">
                                                    <label className="form-label">First Name</label>
                                                    <input 
                                                        type="text" 
                                                        className="form-control" 
                                                        value={userForm.first_name}
                                                        onChange={(e) => { if (/^[a-zA-Z\s]*$/.test(e.target.value)) setUserForm({ ...userForm, first_name: e.target.value }); }}
                                                        required 
                                                    />
                                                </div>
                                                <div className="col-md-6">
                                                    <label className="form-label">Last Name</label>
                                                    <input 
                                                        type="text" 
                                                        className="form-control" 
                                                        value={userForm.last_name}
                                                        onChange={(e) => { if (/^[a-zA-Z\s]*$/.test(e.target.value)) setUserForm({ ...userForm, last_name: e.target.value }); }}
                                                        required 
                                                    />
                                                </div>
                                                <div className="col-md-6">
                                                    <label className="form-label">Role</label>
                                                    <select 
                                                        className="form-select" 
                                                        value={userForm.role}
                                                        onChange={(e) => setUserForm({ ...userForm, role: e.target.value })}
                                                        disabled={!!editingUser}
                                                    >
                                                        <option value="student">Student</option>
                                                        <option value="rep">Course Representative</option>
                                                        <option value="staff">Staff</option>
                                                        <option value="admin">Admin</option>
                                                    </select>
                                                </div>
                                                <div className="col-md-6">
                                                    <label className="form-label">Phone Number</label>
                                                    <input 
                                                        type="text" 
                                                        className="form-control" 
                                                        value={userForm.phone_number}
                                                        onChange={(e) => setUserForm({ ...userForm, phone_number: e.target.value })}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div className="modal-footer">
                                            <button type="button" className="btn btn-light" onClick={() => setShowUserModal(false)}>Cancel</button>
                                            <button type="submit" className="btn btn-primary">Save User</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    )}
                    
                    {/* MODAL FOR DEACTIVATE USER */}
                    {showDeactivateModal && deactivateUserTarget && (
                        <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                            <div className="modal-dialog modal-dialog-centered">
                                <div className="modal-content">
                                    <div className="modal-header">
                                        <h5 className="modal-title fw-bold text-danger">Deactivate User</h5>
                                        <button type="button" className="btn-close" onClick={() => setShowDeactivateModal(false)}></button>
                                    </div>
                                    <form onSubmit={confirmDeactivation}>
                                        <div className="modal-body">
                                            <div className="mb-3">
                                                <label className="form-label">User Email</label>
                                                <input type="text" className="form-control" value={deactivateUserTarget.email} readOnly disabled />
                                            </div>
                                            <div className="mb-3">
                                                <label className="form-label">Reason for Deactivation</label>
                                                <textarea 
                                                    className="form-control" 
                                                    rows="3" 
                                                    placeholder="Please provide a reason to deactivate this user..."
                                                    value={deactivateReason}
                                                    onChange={(e) => setDeactivateReason(e.target.value)}
                                                    required
                                                ></textarea>
                                                <div className="form-text">This reason will be sent to the user via email.</div>
                                            </div>
                                        </div>
                                        <div className="modal-footer">
                                            <button type="button" className="btn btn-light" onClick={() => setShowDeactivateModal(false)}>Cancel</button>
                                            <button type="submit" className="btn btn-danger">Confirm Deactivation</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* TAB CONTENT: COURSE REPRESENTATIVE */}
            {activeTab === 'course-rep' && (
                <div>
                    <div className="row g-4">
                        {/* Search column */}
                        <div className="col-md-6">
                            <div className="card border-0 shadow-sm h-100">
                                <div className="card-header bg-white border-bottom p-4">
                                    <h5 className="fw-bold m-0">1. Search Student to Promote</h5>
                                </div>
                                <div className="card-body p-4">
                                    <form onSubmit={handleRepSearch} className="d-flex gap-2 mb-4">
                                        <div className="flex-grow-1 position-relative">
                                            <Search className="position-absolute top-50 translate-middle-y text-muted ms-3" size={18} />
                                            <input 
                                                type="text" 
                                                className="form-control ps-5" 
                                                placeholder="Enrollment No, First or Last Name..." 
                                                value={repSearch}
                                                onChange={(e) => setRepSearch(e.target.value)}
                                            />
                                        </div>
                                        <button type="submit" className="btn btn-primary px-4">Search</button>
                                    </form>

                                    {repStudents.length > 0 ? (
                                        <div className="list-group">
                                            {repStudents.map(student => (
                                                <button 
                                                    key={student.id} 
                                                    className={`list-group-item list-group-item-action p-3 d-flex justify-content-between align-items-center ${selectedStudent?.id === student.id ? 'active' : ''}`}
                                                    onClick={() => {
                                                        setSelectedStudent(student);
                                                        setRepForm({
                                                            ...repForm,
                                                            fname: student.first_name || '',
                                                            lname: student.last_name || '',
                                                            phone: student.phone_number || '',
                                                            email: student.email || '',
                                                            rep_id: 'REP_' + student.enrollment_no,
                                                            course: student.course || '',
                                                            year: student.year || '1'
                                                        });
                                                    }}
                                                >
                                                    <div>
                                                        <div className="fw-semibold">{student.first_name} {student.last_name}</div>
                                                        <small className={selectedStudent?.id === student.id ? 'text-white-50' : 'text-muted'}>{student.enrollment_no}</small>
                                                    </div>
                                                    <span className={`badge ${student.role === 'rep' ? 'bg-success' : 'bg-secondary'}`}>
                                                        {student.role.toUpperCase()}
                                                    </span>
                                                </button>
                                            ))}
                                        </div>
                                    ) : repSearch && (
                                        <div className="text-center text-muted py-3">No students found.</div>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Promotion detail column */}
                        <div className="col-md-6">
                            <div className="card border-0 shadow-sm h-100">
                                <div className="card-header bg-white border-bottom p-4">
                                    <h5 className="fw-bold m-0">2. Assign Credentials & Details</h5>
                                </div>
                                <div className="card-body p-4">
                                    {selectedStudent ? (
                                        <form onSubmit={handleAssignRep}>
                                            {selectedStudent.role === 'rep' ? (
                                                <div className="alert alert-success py-2 small mb-4">
                                                    <strong>{selectedStudent.first_name} {selectedStudent.last_name}</strong> ({selectedStudent.enrollment_no}) is <strong>already assigned</strong> as a Course Representative!
                                                </div>
                                            ) : (
                                                <div className="alert alert-info py-2 small mb-4">
                                                    Promoting <strong>{selectedStudent.first_name} {selectedStudent.last_name}</strong> ({selectedStudent.enrollment_no}) to Course Representative.
                                                </div>
                                            )}

                                            <div className="mb-3 row">
                                                <div className="col-md-6">
                                                    <label className="form-label">First Name</label>
                                                    <input 
                                                        type="text" 
                                                        className="form-control" 
                                                        value={repForm.fname}
                                                        onChange={(e) => { if (/^[a-zA-Z\s]*$/.test(e.target.value)) setRepForm({ ...repForm, fname: e.target.value }); }}
                                                        required 
                                                    />
                                                </div>
                                                <div className="col-md-6">
                                                    <label className="form-label">Last Name</label>
                                                    <input 
                                                        type="text" 
                                                        className="form-control" 
                                                        value={repForm.lname}
                                                        onChange={(e) => { if (/^[a-zA-Z\s]*$/.test(e.target.value)) setRepForm({ ...repForm, lname: e.target.value }); }}
                                                        required 
                                                    />
                                                </div>
                                            </div>

                                            <div className="mb-3 row">
                                                <div className="col-md-6">
                                                    <label className="form-label">Phone Number</label>
                                                    <input 
                                                        type="text" 
                                                        className="form-control" 
                                                        value={repForm.phone}
                                                        onChange={(e) => setRepForm({ ...repForm, phone: e.target.value })}
                                                    />
                                                </div>
                                                <div className="col-md-6">
                                                    <label className="form-label">Personal Email</label>
                                                    <input 
                                                        type="email" 
                                                        className="form-control" 
                                                        value={repForm.email}
                                                        onChange={(e) => setRepForm({ ...repForm, email: e.target.value })}
                                                        required 
                                                    />
                                                </div>
                                            </div>



                                            <div className="mb-3">
                                                <label className="form-label">Rep ID</label>
                                                <input 
                                                    type="text" 
                                                    className="form-control" 
                                                    value={repForm.rep_id}
                                                    onChange={(e) => setRepForm({ ...repForm, rep_id: e.target.value })}
                                                    required 
                                                />
                                            </div>

                                            <div className="mb-4">
                                                <label className="form-label">Rep Login Password</label>
                                                <input 
                                                    type="password" 
                                                    className="form-control" 
                                                    value={repForm.password}
                                                    onChange={(e) => setRepForm({ ...repForm, password: e.target.value })}
                                                    placeholder="Set a dedicated password for the Rep dashboard..."
                                                    required 
                                                />
                                                <div className="form-text small"> emailed to the student.</div>
                                            </div>

                                            <button type="submit" className="btn btn-warning w-100 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
                                                <UserCheck size={18} /> {selectedStudent.role === 'rep' ? 'Update Credentials' : 'Assign'}
                                            </button>
                                            
                                            {selectedStudent.role === 'rep' && (
                                                <button type="button" className="btn btn-outline-danger w-100 mt-2 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" onClick={() => handleDemoteRep(selectedStudent)}>
                                                    <UserX size={18} /> Demote to Student
                                                </button>
                                            )}
                                        </form>
                                    ) : (
                                        <div className="text-center text-muted py-5">Select a student from the search list to proceed with promotion.</div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* TAB CONTENT: CONTENT MODERATION */}
            {activeTab === 'content' && (
                <div>
                    {/* Inner content switcher */}
                    <div className="btn-group mb-4" role="group">
                        <button type="button" className={`btn ${contentTab === 'lost_item' ? 'btn-primary' : 'btn-light'}`} onClick={() => setContentTab('lost_item')}>
                            <Search size={16} className="me-2" /> Lost Items
                        </button>
                        <button type="button" className={`btn ${contentTab === 'marketplace' ? 'btn-primary' : 'btn-light'}`} onClick={() => setContentTab('marketplace')}>
                            <ShoppingBag size={16} className="me-2" /> Marketplace
                        </button>
                        <button type="button" className={`btn ${contentTab === 'notes' ? 'btn-primary' : 'btn-light'}`} onClick={() => setContentTab('notes')}>
                            <BookOpen size={16} className="me-2" /> Shared Notes
                        </button>
                    </div>

                    <div className="card border-0 shadow-sm">
                        <div className="card-body p-4">
                            <div className="table-responsive">
                                <table className="table align-middle table-hover">
                                    <thead className="table-light">
                                        <tr>
                                            <th>Item / Title</th>
                                            <th>User / Email</th>
                                            <th>Status</th>
                                            <th>Created At</th>
                                            <th className="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {/* Lost Items Render */}
                                        {contentTab === 'lost_item' && content.lost_items.map(item => (
                                            <tr key={item.lost_id}>
                                                <td className="fw-semibold">{item.lostItemName}</td>
                                                <td>{item.email}</td>
                                                <td>
                                                    <span className={`badge ${item.status === 'hidden' ? 'bg-warning text-dark' : (item.status === 'removed' ? 'bg-danger' : 'bg-success')}`}>
                                                        {item.status.toUpperCase()}
                                                    </span>
                                                </td>
                                                <td>{new Date(item.created_at).toLocaleDateString()}</td>
                                                <td className="text-end">
                                                    <div className="d-flex justify-content-end gap-2">
                                                        <button className="btn btn-sm btn-outline-info" onClick={() => handleViewItem(item, 'lost_item')} title="View Details">
                                                            <Eye size={14} />
                                                        </button>
                                                        {item.status !== 'removed' && (
                                                            <button className="btn btn-sm btn-outline-danger" onClick={() => openDeleteModal('lost_item', item.lost_id)} title="Soft Delete">
                                                                <Trash2 size={14} />
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}

                                        {/* Marketplace Render */}
                                        {contentTab === 'marketplace' && content.marketplace.map(item => (
                                            <tr key={item.id}>
                                                <td className="fw-semibold">
                                                    <div>{item.title}</div>
                                                    <small className="text-muted">${item.price}</small>
                                                </td>
                                                <td>{item.email}</td>
                                                <td>
                                                    <span className={`badge ${item.status === 'hidden' ? 'bg-warning text-dark' : (item.status === 'removed' ? 'bg-danger' : 'bg-success')}`}>
                                                        {item.status.toUpperCase()}
                                                    </span>
                                                </td>
                                                <td>{new Date(item.created_at).toLocaleDateString()}</td>
                                                <td className="text-end">
                                                    <div className="d-flex justify-content-end gap-2">
                                                        <button className="btn btn-sm btn-outline-info" onClick={() => handleViewItem(item, 'marketplace')} title="View Details">
                                                            <Eye size={14} />
                                                        </button>
                                                        {item.status !== 'removed' && (
                                                            <button className="btn btn-sm btn-outline-danger" onClick={() => openDeleteModal('marketplace', item.id)} title="Soft Delete">
                                                                <Trash2 size={14} />
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}

                                        {/* Notes Render */}
                                        {contentTab === 'notes' && content.notes.map(note => (
                                            <tr key={note.id}>
                                                <td className="fw-semibold">
                                                    <div>{note.title}</div>
                                                    <small className="text-muted">{note.courseUnitID}</small>
                                                </td>
                                                <td>{note.email}</td>
                                                <td>
                                                    <span className={`badge ${note.status === 'hidden' ? 'bg-warning text-dark' : (note.status === 'removed' ? 'bg-danger' : 'bg-success')}`}>
                                                        {note.status.toUpperCase()}
                                                    </span>
                                                </td>
                                                <td>{new Date(note.created_at).toLocaleDateString()}</td>
                                                <td className="text-end">
                                                    <div className="d-flex justify-content-end gap-2">
                                                        <button className="btn btn-sm btn-outline-info" onClick={() => handleViewItem(note, 'notes')} title="View Details">
                                                            <Eye size={14} />
                                                        </button>
                                                        {note.status !== 'removed' && (
                                                            <button className="btn btn-sm btn-outline-danger" onClick={() => openDeleteModal('notes', note.id)} title="Soft Delete">
                                                                <Trash2 size={14} />
                                                            </button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            )}


            {/* View Item Modal */}
            {showItemModal && selectedViewItem && (
                <div className="modal fade show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }} tabIndex="-1">
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border-0 shadow">
                            <div className="modal-header">
                                <h5 className="modal-title fw-bold">
                                    {viewItemType === 'lost_item' ? 'Lost Item Details' : 
                                     viewItemType === 'marketplace' ? 'Marketplace Item Details' : 'Shared Note Details'}
                                </h5>
                                <button type="button" className="btn-close" onClick={() => setShowItemModal(false)}></button>
                            </div>
                            <div className="modal-body">
                                {viewItemType === 'lost_item' && (
                                    <>
                                        <h6 className="fw-bold fs-5 mb-3">{selectedViewItem.lostItemName}</h6>
                                        {selectedViewItem.item_image && (
                                            <img src={selectedViewItem.item_image.startsWith('http') ? selectedViewItem.item_image : `http://localhost/uni_core_proj_01/${selectedViewItem.item_image}`} alt={selectedViewItem.lostItemName} className="img-fluid rounded mb-3 w-100 object-fit-cover" style={{maxHeight: '250px'}} />
                                        )}
                                        <p><strong>Contact:</strong> {selectedViewItem.contact_no}</p>
                                        <p><strong>Last Seen:</strong> {selectedViewItem.last_seen_datetime}</p>
                                        <p><strong>Reported By:</strong> {selectedViewItem.email}</p>
                                        <p><strong>Status:</strong> <span className="badge bg-secondary">{selectedViewItem.status}</span></p>
                                    </>
                                )}
                                {viewItemType === 'marketplace' && (
                                    <>
                                        <h6 className="fw-bold fs-5 mb-3">{selectedViewItem.title}</h6>
                                        {selectedViewItem.product_image && (
                                            <img src={selectedViewItem.product_image.startsWith('http') ? selectedViewItem.product_image : `http://localhost/uni_core_proj_01/${selectedViewItem.product_image}`} alt={selectedViewItem.title} className="img-fluid rounded mb-3 w-100 object-fit-cover" style={{maxHeight: '250px'}} />
                                        )}
                                        <p><strong>Price:</strong> ${selectedViewItem.price}</p>
                                        <p><strong>Location:</strong> {selectedViewItem.location}</p>
                                        <p><strong>Contact:</strong> {selectedViewItem.contact_no}</p>
                                        <p><strong>Posted By:</strong> {selectedViewItem.email}</p>
                                        <p><strong>Status:</strong> <span className="badge bg-secondary">{selectedViewItem.status}</span></p>
                                    </>
                                )}
                                {viewItemType === 'notes' && (
                                    <>
                                        <h6 className="fw-bold fs-5 mb-3">{selectedViewItem.title}</h6>
                                        <p><strong>Course Unit:</strong> {selectedViewItem.courseUnitID}</p>
                                        <p><strong>File:</strong> <a href={selectedViewItem.file_url?.startsWith('http') ? selectedViewItem.file_url : `http://localhost/uni_core_proj_01/${selectedViewItem.file_url}`} target="_blank" rel="noreferrer" className="btn btn-sm btn-outline-primary">View/Download File</a></p>
                                        <p><strong>Shared By:</strong> {selectedViewItem.email}</p>
                                        <p><strong>Status:</strong> <span className="badge bg-secondary">{selectedViewItem.status}</span></p>
                                    </>
                                )}
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-secondary" onClick={() => setShowItemModal(false)}>Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* DELETE CONTENT MODAL */}
            {deleteModal.isOpen && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content">
                            <div className="modal-header">
                                <h5 className="modal-title fw-bold text-danger">Delete Content</h5>
                                <button type="button" className="btn-close" onClick={() => setDeleteModal({ ...deleteModal, isOpen: false })}></button>
                            </div>
                            <div className="modal-body">
                                <div className="mb-3">
                                    <label className="form-label">Reason for Deletion</label>
                                    <textarea 
                                        className="form-control" 
                                        rows="3" 
                                        placeholder="Please provide a reason to delete this content..."
                                        value={deleteModal.reason}
                                        onChange={(e) => setDeleteModal({ ...deleteModal, reason: e.target.value })}
                                        required
                                    ></textarea>
                                    <div className="form-text">This reason will be sent to the content owner via email.</div>
                                </div>
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="btn btn-light" onClick={() => setDeleteModal({ ...deleteModal, isOpen: false })}>Cancel</button>
                                <button 
                                    type="button" 
                                    className="btn btn-danger"
                                    disabled={!deleteModal.reason.trim()}
                                    onClick={() => handleContentModeration(deleteModal.type, deleteModal.id, 'removed', deleteModal.reason)}
                                >
                                    Delete & Send Email
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default AdminPanel;
