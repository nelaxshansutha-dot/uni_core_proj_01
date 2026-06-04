import React, { useState, useEffect, useContext } from 'react';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';
import { Upload, BookOpen, Download, FileText } from 'lucide-react';

const Notes = () => {
    const { user } = useContext(AuthContext);
    const [notes, setNotes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    
    const [filters, setFilters] = useState({ course_code: '', year: '', semester: '' });
    
    const [formData, setFormData] = useState({
        title: '',
        description: '',
        course_code: '',
        year: '',
        semester: ''
    });
    const [file, setFile] = useState(null);

    const fetchNotes = async () => {
        setLoading(true);
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const res = await api.get(`/notes.php?${queryParams}`);
            if (res.data.status === 'success') {
                setNotes(res.data.data);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchNotes();
    }, [filters]);

    const handleFilterChange = (e) => {
        setFilters({ ...filters, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        const data = new FormData();
        Object.keys(formData).forEach(key => data.append(key, formData[key]));
        if (file) {
            data.append('file', file);
        }

        try {
            const res = await api.post('/notes.php', data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            if (res.data.status === 'success') {
                setShowModal(false);
                setFormData({ title: '', description: '', course_code: '', year: '', semester: '' });
                setFile(null);
                fetchNotes();
            }
        } catch (err) {
            console.error(err);
        }
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 className="fw-bold text-dark mb-1">Notes Sharing</h3>
                    <p className="text-muted m-0">Access and share course materials</p>
                </div>
                {['staff', 'rep', 'admin'].includes(user?.role) && (
                    <button className="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-4" onClick={() => setShowModal(true)}>
                        <Upload size={18} />
                        Upload Notes
                    </button>
                )}
            </div>

            {/* Filters */}
            <div className="card border-0 shadow-sm mb-4">
                <div className="card-body p-3">
                    <div className="row g-3">
                        <div className="col-md-4">
                            <input type="text" className="form-control" name="course_code" placeholder="Course Code (e.g. CS101)" value={filters.course_code} onChange={handleFilterChange} />
                        </div>
                        <div className="col-md-4">
                            <input type="number" className="form-control" name="year" placeholder="Year" value={filters.year} onChange={handleFilterChange} />
                        </div>
                        <div className="col-md-4">
                            <input type="number" className="form-control" name="semester" placeholder="Semester" value={filters.semester} onChange={handleFilterChange} />
                        </div>
                    </div>
                </div>
            </div>

            {loading ? (
                <div className="text-center mt-5"><div className="spinner-border text-primary"></div></div>
            ) : (
                <div className="row g-4">
                    {notes.length === 0 ? (
                        <div className="col-12 text-center text-muted py-5">
                            <BookOpen size={48} className="mb-3 opacity-50" />
                            <h5>No notes found for this filter</h5>
                        </div>
                    ) : (
                        notes.map(note => (
                            <div className="col-md-6 col-lg-4" key={note.id}>
                                <div className="card h-100 border-0 shadow-sm">
                                    <div className="card-body p-4 d-flex flex-column">
                                        <div className="d-flex align-items-start gap-3 mb-3">
                                            <div className="bg-primary bg-opacity-10 text-primary p-3 rounded">
                                                <FileText size={24} />
                                            </div>
                                            <div>
                                                <h5 className="fw-bold m-0">{note.title}</h5>
                                                <span className="badge bg-secondary mt-1">{note.course_code}</span>
                                            </div>
                                        </div>
                                        <p className="text-muted small flex-grow-1">{note.description}</p>
                                        <div className="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                            <div className="text-secondary small">
                                                By: {note.enrollment_no}
                                            </div>
                                            <a href={`http://localhost/uni_core_proj_01/backend/${note.file_url}`} target="_blank" rel="noreferrer" className="btn btn-sm btn-outline-primary rounded-pill d-flex align-items-center gap-1">
                                                <Download size={14} /> Download
                                            </a>
                                        </div>
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
                                <h5 className="fw-bold">Upload Notes</h5>
                                <button type="button" className="btn-close" onClick={() => setShowModal(false)}></button>
                            </div>
                            <div className="modal-body p-4">
                                <form onSubmit={handleSubmit}>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">Title</label>
                                        <input type="text" className="form-control" value={formData.title} onChange={e => setFormData({...formData, title: e.target.value})} required />
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">Course Code</label>
                                        <input type="text" className="form-control" value={formData.course_code} onChange={e => setFormData({...formData, course_code: e.target.value})} required />
                                    </div>
                                    <div className="row g-3 mb-3">
                                        <div className="col-6">
                                            <label className="form-label text-muted small fw-bold">Year</label>
                                            <input type="number" className="form-control" value={formData.year} onChange={e => setFormData({...formData, year: e.target.value})} required />
                                        </div>
                                        <div className="col-6">
                                            <label className="form-label text-muted small fw-bold">Semester</label>
                                            <input type="number" className="form-control" value={formData.semester} onChange={e => setFormData({...formData, semester: e.target.value})} required />
                                        </div>
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">Description</label>
                                        <textarea className="form-control" rows="2" value={formData.description} onChange={e => setFormData({...formData, description: e.target.value})}></textarea>
                                    </div>
                                    <div className="mb-4">
                                        <label className="form-label text-muted small fw-bold">PDF File</label>
                                        <input type="file" className="form-control" accept=".pdf" onChange={e => setFile(e.target.files[0])} required />
                                    </div>
                                    <div className="d-flex gap-2 justify-content-end">
                                        <button type="button" className="btn btn-light rounded-pill px-4" onClick={() => setShowModal(false)}>Cancel</button>
                                        <button type="submit" className="btn btn-primary rounded-pill px-4" disabled={!file}>Upload</button>
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

export default Notes;
