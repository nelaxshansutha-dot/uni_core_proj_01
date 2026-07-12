import React, { useState, useEffect, useContext } from 'react';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';
import { Upload, BookOpen, Download, FileText, Search } from 'lucide-react';

const Notes = () => {
    const { user } = useContext(AuthContext);
    const [notes, setNotes] = useState([]);
    const [loading, setLoading] = useState(true);
    const [showModal, setShowModal] = useState(false);
    
    const [filters, setFilters] = useState({ courseUnitID: '' });
    
    const [formData, setFormData] = useState({
        title: '',
        description: '',
        courseUnitID: '',
        academicYear: '1',
        noteType: 'notes'
    });
    const [file, setFile] = useState(null);

    const fetchNotes = async () => {
        setLoading(true);
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const res = await api.get(`/notes?${queryParams}`);
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
        fetchNotes();
    }, []);

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
            const res = await api.post('/notes', data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
            if (res.data.success) {
                setShowModal(false);
                setFormData({ title: '', description: '', courseUnitID: '', academicYear: '1', noteType: 'notes' });
                setFile(null);
                fetchNotes();
            }
        } catch (err) {
            console.error(err);
        }
    };

    // Grouping notes by Course -> Year
    const groupedNotes = notes.reduce((acc, note) => {
        const courseName = note.courseUniName || note.courseUnitID || 'Other Modules';
        const year = note.academicYear || 'Unknown Year';
        
        if (!acc[courseName]) acc[courseName] = {};
        if (!acc[courseName][year]) acc[courseName][year] = [];
        
        acc[courseName][year].push(note);
        return acc;
    }, {});

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 className="fw-bold text-dark mb-1">Notes Sharing</h3>
                    <p className="text-muted m-0">Access and share course materials</p>
                </div>
                {user && (
                    <button className="btn btn-primary d-flex align-items-center gap-2 rounded-pill px-4" onClick={() => setShowModal(true)}>
                        <Upload size={18} />
                        Upload Notes
                    </button>
                )}
            </div>

            {/* Filters */}
            <div className="card border-0 shadow-sm mb-4">
                <div className="card-body p-3">
                    <div className="row g-3 align-items-center">
                        <div className="col-md-9">
                            <div className="input-group">
                                <span className="input-group-text bg-white border-end-0"><Search size={18} className="text-muted" /></span>
                                <input type="text" className="form-control border-start-0 ps-0" name="courseUnitID" placeholder="Search by Course Unit (e.g. CS101)" value={filters.courseUnitID} onChange={handleFilterChange} />
                            </div>
                        </div>
                        <div className="col-md-3">
                            <button className="btn btn-dark w-100" onClick={fetchNotes}>
                                Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {loading ? (
                <div className="text-center mt-5"><div className="spinner-border text-primary"></div></div>
            ) : (
                <div className="notes-grid-container">
                    {Object.keys(groupedNotes).length === 0 ? (
                        <div className="text-center text-muted py-5 mt-4">
                            <BookOpen size={48} className="mb-3 opacity-50" />
                            <h5>No notes found for this filter</h5>
                            <p className="small">You are only seeing notes for your enrolled course.</p>
                        </div>
                    ) : (
                        Object.keys(groupedNotes).map(courseName => (
                            <div key={courseName} className="mb-5">
                                <h4 className="fw-bold mb-3">{courseName}</h4>
                                <div className="row g-3">
                                    {Object.keys(groupedNotes[courseName]).sort((a,b) => a-b).map(year => (
                                        <div className="col-sm-6 col-md-4 col-lg-3" key={`${courseName}-${year}`}>
                                            <div className="card border shadow-sm h-100 rounded-3">
                                                <div className="card-body p-3">
                                                    <h6 className="fw-bold mb-3">{year == 1 ? '1st Year' : year == 2 ? '2nd Year' : year == 3 ? '3rd Year' : year == 4 ? '4th Year' : `${year} Year`}</h6>
                                                    <div className="d-flex flex-wrap gap-2">
                                                        {groupedNotes[courseName][year].map(note => {
                                                            let label = 'Notes';
                                                            if (note.noteType === 'past_paper') label = 'Paper';
                                                            if (note.noteType === 'scheme') label = 'Scheme';
                                                            
                                                            return (
                                                                <a 
                                                                    key={note.noteID} 
                                                                    href={`http://localhost/uni_core_proj_01/backend/${note.file_url}`} 
                                                                    target="_blank" 
                                                                    rel="noreferrer" 
                                                                    className="btn btn-sm btn-light border d-flex align-items-center gap-1"
                                                                    title={note.title}
                                                                >
                                                                    <Download size={12} className="text-primary" /> {label}
                                                                </a>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
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
                                <h5 className="fw-bold">Upload Course Material</h5>
                                <button type="button" className="btn-close" onClick={() => setShowModal(false)}></button>
                            </div>
                            <div className="modal-body p-4">
                                <form onSubmit={handleSubmit}>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">Title (Optional)</label>
                                        <input type="text" className="form-control" value={formData.title} onChange={e => setFormData({...formData, title: e.target.value})} placeholder="e.g., Midterm Paper" />
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label text-muted small fw-bold">Course Code</label>
                                        <input type="text" className="form-control" value={formData.courseUnitID} onChange={e => setFormData({...formData, courseUnitID: e.target.value})} required placeholder="e.g., CST101" />
                                    </div>
                                    <div className="row g-3 mb-3">
                                        <div className="col-6">
                                            <label className="form-label text-muted small fw-bold">Academic Year</label>
                                            <select className="form-select" value={formData.academicYear} onChange={e => setFormData({...formData, academicYear: e.target.value})} required>
                                                <option value="1">1st Year</option>
                                                <option value="2">2nd Year</option>
                                                <option value="3">3rd Year</option>
                                                <option value="4">4th Year</option>
                                            </select>
                                        </div>
                                        <div className="col-6">
                                            <label className="form-label text-muted small fw-bold">Material Type</label>
                                            <select className="form-select" value={formData.noteType} onChange={e => setFormData({...formData, noteType: e.target.value})} required>
                                                <option value="notes">Notes</option>
                                                <option value="past_paper">Past Paper</option>
                                                <option value="scheme">Marking Scheme</option>
                                            </select>
                                        </div>
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
