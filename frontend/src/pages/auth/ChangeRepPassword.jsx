import React, { useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { CheckCircle, XCircle } from 'lucide-react';
import api from '../../services/api';
import logo from '../../assets/logo.jpg';

const ChangeRepPassword = () => {
    const location = useLocation();
    const navigate = useNavigate();
    
    // Get the user ID from the router state
    const userId = location.state?.userId;
    
    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [loading, setLoading] = useState(false);

    // Redirect to login if accessed directly without user ID
    React.useEffect(() => {
        if (!userId) {
            navigate('/login');
        }
    }, [userId, navigate]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setSuccess('');

        if (password.length < 6) {
            setError('Password must be at least 6 characters long.');
            return;
        }

        if (password !== confirmPassword) {
            setError('Passwords do not match.');
            return;
        }

        setLoading(true);

        try {
            const response = await api.post('/auth.php?action=force-change-rep-password', {
                user_id: userId,
                new_password: password
            });

            if (response.data.status === 'success') {
                setSuccess('Password updated successfully! Redirecting to login...');
                setTimeout(() => {
                    navigate('/login', { 
                        state: { passwordReset: true } 
                    });
                }, 3000);
            } else {
                setError(response.data.message || 'Failed to update password.');
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Server error. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    if (!userId) return null;

    return (
        <div className="auth-bg">
            <div className="auth-card d-flex flex-column" style={{ maxWidth: '450px', width: '100%', padding: '2rem' }}>
                <div className="text-center mb-4">
                    <img src={logo} alt="UniCore Logo" style={{ height: '60px', marginBottom: '1rem', objectFit: 'contain' }} />
                    <h4 className="fw-bold text-dark mb-1">Set Your Password</h4>
                    <p className="text-muted small">Please set a secure password for your Course Representative account before continuing.</p>
                </div>

                {error && (
                    <div className="alert alert-danger py-2 small d-flex align-items-center gap-2">
                        <XCircle size={16} className="flex-shrink-0" />
                        <span>{error}</span>
                    </div>
                )}

                {success && (
                    <div className="alert alert-success py-2 small d-flex align-items-center gap-2">
                        <CheckCircle size={16} className="flex-shrink-0" />
                        <span>{success}</span>
                    </div>
                )}

                <form onSubmit={handleSubmit}>
                    <div className="mb-3">
                        <label className="form-label">New Password</label>
                        <input
                            type="password"
                            className="form-control"
                            placeholder="At least 6 characters"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            required
                        />
                    </div>
                    <div className="mb-4">
                        <label className="form-label">Confirm New Password</label>
                        <input
                            type="password"
                            className="form-control"
                            placeholder="Re-enter password"
                            value={confirmPassword}
                            onChange={(e) => setConfirmPassword(e.target.value)}
                            required
                        />
                    </div>
                    <button 
                        type="submit" 
                        className="btn btn-primary w-100 py-2 fw-bold"
                        disabled={loading || success}
                    >
                        {loading ? (
                            <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        ) : null}
                        {loading ? 'Processing...' : 'Update Password & Login'}
                    </button>
                </form>
            </div>
        </div>
    );
};

export default ChangeRepPassword;
