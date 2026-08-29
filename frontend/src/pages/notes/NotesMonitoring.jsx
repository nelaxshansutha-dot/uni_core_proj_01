import React, { useState, useEffect, useContext } from 'react';
import { BookOpen, FileText, Download, Trash2, Search } from 'lucide-react';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';

const NotesMonitoring = () => {
    const { user } = useContext(AuthContext);
    const [notes, setNotes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [deleteNoteID, setDeleteNoteID] = useState(null);

    const fetchNotes = async () => {
        setLoading(true);
        try {
            // For Notes Monitoring (rep view), only show notes from the rep's own course.
            // Extract the course code from the rep's enrollment_no e.g. UWU/CST/23/001 → CST
            const enrollmentNo = user?.enrollment_no || '';
            const parts = enrollmentNo.split('/');
            const repCourseCode = parts.length >= 2 ? parts[1].toUpperCase() : '';

            const params = repCourseCode ? `?courseCode=${repCourseCode}` : '';
            const res = await api.get(`/notes${params}`);
            if (res.data.success) {
                setNotes(res.data.data);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (user) {
            fetchNotes();
        }
    }, [user]);

    const handleDeleteNote = async () => {
        if (!deleteNoteID) return;
        try {
            const res = await api.delete(`/notes/${deleteNoteID}`);
            if (res.data.success) {
                fetchNotes();
                setDeleteNoteID(null);
            } else {
                alert(res.data.message || 'Failed to delete note');
            }
        } catch (err) {
            console.error(err);
            alert('An error occurred while deleting the note. Make sure you have permission.');
        }
    };

    if (loading) {
        return (
            <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '60vh' }}>
                <div className="spinner-border text-primary" role="status">
                    <span className="visually-hidden">Loading...</span>
                </div>
            </div>
        );
    }

    return (
        <div className="container-fluid p-4">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 className="fw-bold mb-1 d-flex align-items-center gap-2">
                        <BookOpen size={28} className="text-primary" />
                        Notes Monitoring
                    </h2>
                    <p className="text-muted mb-0">Monitor and manage shared notes for your batch</p>
                </div>
            </div>

            {notes.length === 0 ? (
                <div className="text-center py-5">
                    <FileText size={48} className="text-secondary mb-3 opacity-50" />
                    <h5 className="text-secondary">No notes to monitor</h5>
                    <p className="text-muted">There are currently no notes uploaded for your course.</p>
                </div>
            ) : (
                <div className="card shadow-sm border-0">
                    <div className="card-body p-0">
                        <div className="table-responsive">
                            <table className="table table-hover align-middle mb-0">
                                <thead className="table-light">
                                    <tr>
                                        <th>Title / Module</th>
                                        <th>Uploader</th>
                                        <th>Type</th>
                                        <th>Year</th>
                                        <th>Date</th>
                                        <th className="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {notes.map(note => (
                                        <tr key={note.noteID}>
                                            <td>
                                                <div className="fw-bold text-dark">{note.title || 'Untitled'}</div>
                                                <div className="small text-muted">{note.courseUniName || note.courseUnitID}</div>
                                            </td>
                                            <td>
                                                <span className="badge bg-light text-dark border">
                                                    {note.enrollmentNo}
                                                </span>
                                            </td>
                                            <td>
                                                <span className="text-capitalize text-muted small">
                                                    {note.noteType ? note.noteType.replace('_', ' ') : 'Notes'}
                                                </span>
                                            </td>
                                            <td>{note.academicYear ? `Year ${note.academicYear}` : '-'}</td>
                                            <td>{new Date(note.created_at).toLocaleDateString()}</td>
                                            <td className="text-end">
                                                <div className="d-flex gap-2 justify-content-end">
                                                    <button 
                                                        className="btn btn-sm btn-light border text-primary"
                                                        onClick={() => {
                                                            const url = note.file_url?.startsWith('http') ? note.file_url : `http://localhost/uni_core_proj_01/backend/${note.file_url}`;
                                                            if (url) window.open(url, '_blank');
                                                        }}
                                                        title="View Note"
                                                    >
                                                        <Download size={14} />
                                                    </button>
                                                    <button 
                                                        className="btn btn-sm btn-outline-danger"
                                                        onClick={() => setDeleteNoteID(note.noteID)}
                                                        title="Delete Note"
                                                    >
                                                        <Trash2 size={14} />
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
            )}

            {/* Delete Confirmation Modal */}
            {deleteNoteID && (
                <div className="modal show d-block" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border-0 shadow">
                            <div className="modal-header border-0 pb-0">
                                <h5 className="fw-bold text-danger">Delete Note</h5>
                                <button type="button" className="btn-close" onClick={() => setDeleteNoteID(null)}></button>
                            </div>
                            <div className="modal-body p-4">
                                <p>Are you sure you want to delete this note? This action cannot be undone.</p>
                                <div className="d-flex gap-2 justify-content-end mt-4">
                                    <button type="button" className="btn btn-light rounded-pill px-4" onClick={() => setDeleteNoteID(null)}>Cancel</button>
                                    <button type="button" className="btn btn-danger rounded-pill px-4" onClick={handleDeleteNote}>Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default NotesMonitoring;
