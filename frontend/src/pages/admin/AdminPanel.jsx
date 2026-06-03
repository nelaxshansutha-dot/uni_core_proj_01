import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { ShieldAlert, Search, UserCheck } from 'lucide-react';

const AdminPanel = () => {
    const [searchQuery, setSearchQuery] = useState('');
    const [students, setStudents] = useState([]);
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState({ type: '', text: '' });

    const handleSearch = async (e) => {
        e.preventDefault();
        if (!searchQuery) return;
        
        setLoading(true);
        try {
            const res = await api.get(`/admin.php?action=search-students&q=${searchQuery}`);
            if (res.data.status === 'success') {
                setStudents(res.data.data);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    const makeRep = async (userId) => {
        if (!window.confirm("Are you sure you want to promote this student to Course Rep?")) return;
        
        try {
            const res = await api.post('/admin.php?action=assign-rep', { user_id: userId });
            if (res.data.status === 'success') {
                setMessage({ type: 'success', text: `Successfully assigned as Rep! Credentials sent via email. (Check log)` });
                // Update local state
                setStudents(students.map(s => s.id === userId ? { ...s, role: 'rep' } : s));
            } else {
                setMessage({ type: 'danger', text: res.data.message });
            }
        } catch (err) {
            setMessage({ type: 'danger', text: 'Error assigning rep.' });
        }
    };

    return (
        <div>
            <div className="mb-4">
                <h3 className="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                    <ShieldAlert className="text-warning" />
                    Admin Panel
                </h3>
                <p className="text-muted m-0">Manage system settings and roles</p>
            </div>

            {message.text && (
                <div className={`alert alert-${message.type} alert-dismissible fade show`} role="alert">
                    {message.text}
                    <button type="button" className="btn-close" onClick={() => setMessage({ type: '', text: '' })}></button>
                </div>
            )}

            <div className="card border-0 shadow-sm mb-4">
                <div className="card-header bg-white border-bottom p-4">
                    <h5 className="fw-bold m-0">Assign Course Representative</h5>
                </div>
                <div className="card-body p-4">
                    <form onSubmit={handleSearch} className="d-flex gap-2 mb-4">
                        <div className="flex-grow-1 position-relative">
                            <Search className="position-absolute top-50 translate-middle-y text-muted ms-3" size={18} />
                            <input 
                                type="text" 
                                className="form-control form-control-lg ps-5" 
                                placeholder="Search student by Name or Enrollment No..." 
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                            />
                        </div>
                        <button type="submit" className="btn btn-primary px-4" disabled={loading}>
                            {loading ? 'Searching...' : 'Search'}
                        </button>
                    </form>

                    {students.length > 0 && (
                        <div className="table-responsive">
                            <table className="table align-middle table-hover">
                                <thead className="table-light">
                                    <tr>
                                        <th>Enrollment No</th>
                                        <th>Name</th>
                                        <th>Course</th>
                                        <th>Role</th>
                                        <th className="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {students.map(student => (
                                        <tr key={student.id}>
                                            <td className="fw-medium">{student.enrollment_no}</td>
                                            <td>{student.first_name} {student.last_name}</td>
                                            <td>{student.course} (Year {student.year})</td>
                                            <td>
                                                <span className={`badge ${student.role === 'rep' ? 'bg-success' : 'bg-secondary'}`}>
                                                    {student.role.toUpperCase()}
                                                </span>
                                            </td>
                                            <td className="text-end">
                                                {student.role === 'student' ? (
                                                    <button 
                                                        className="btn btn-sm btn-outline-success rounded-pill d-inline-flex align-items-center gap-1"
                                                        onClick={() => makeRep(student.id)}
                                                    >
                                                        <UserCheck size={14} /> Promote to Rep
                                                    </button>
                                                ) : (
                                                    <span className="text-success small fw-bold">Current Rep</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                    {students.length === 0 && searchQuery && !loading && (
                        <div className="text-center text-muted py-3">No students found.</div>
                    )}
                </div>
            </div>
        </div>
    );
};

export default AdminPanel;
