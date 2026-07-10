import React, { useState, useEffect } from 'react';
import { BookOpen, FileText, CheckCircle, XCircle } from 'lucide-react';
import api from '../../services/api';

const NotesMonitoring = () => {
    const [notes, setNotes] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Fetch notes to monitor (placeholder)
        setLoading(false);
    }, []);

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
                    <p className="text-muted mb-0">Monitor and manage shared notes</p>
                </div>
            </div>

            {notes.length === 0 ? (
                <div className="text-center py-5">
                    <FileText size={48} className="text-secondary mb-3 opacity-50" />
                    <h5 className="text-secondary">No notes to monitor</h5>
                    <p className="text-muted">There are currently no notes awaiting review or monitoring.</p>
                </div>
            ) : (
                <div className="card shadow-sm border-0">
                    <div className="card-body">
                        {/* List of notes would go here */}
                    </div>
                </div>
            )}
        </div>
    );
};

export default NotesMonitoring;
