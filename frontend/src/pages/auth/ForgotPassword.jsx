import React, { useState } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import api from '../../services/api';
import { KeyRound, XCircle, ArrowLeft } from 'lucide-react';

const ForgotPassword = () => {
    const [email, setEmail] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await api.post('/auth/forgot-password', { email });

            if (response.data.status === 'success') {
                navigate('/reset-password', {
                    state: {
                        userId: response.data.data.user_id,
                        email: response.data.data.email
                    }
                });
            } else {
                setError(response.data.message);
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Something went wrong. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="auth-bg">
            <div className="auth-card" style={{ maxWidth: '420px', width: '100%' }}>
                <div className="card-body p-5">
                    <div className="text-center mb-4">
                        <div className="icon-badge warning">
                            <KeyRound size={30} />
                        </div>
                        <h3 className="fw-bold text-dark mb-1">Forgot Password?</h3>
                        <p className="text-muted small">
                            Enter your registered email address and we'll send you a verification code to reset your password.
                        </p>
                    </div>

                    {error && (
                        <div className="alert alert-danger py-2 small d-flex align-items-center gap-2">
                            <XCircle size={16} className="flex-shrink-0" />
                            <span>{error}</span>
                        </div>
                    )}

                    <form onSubmit={handleSubmit}>
                        <div className="mb-4">
                            <label className="form-label">Email Address</label>
                            <input 
                                type="email" 
                                className="form-control form-control-lg" 
                                placeholder="Enter your registered email"
                                value={email}
                                onChange={(e) => { setEmail(e.target.value); setError(''); }}
                                required 
                                autoFocus
                            />
                            <div className="form-hint">The email you used during registration</div>
                        </div>

                        <button type="submit" className="btn btn-primary btn-lg w-100 mb-3" disabled={loading}>
                            {loading ? <span className="spinner-border spinner-border-sm me-2"></span> : null}
                            Send Reset Code
                        </button>
                    </form>

                    <div className="text-center mt-4">
                        <Link to="/login" className="text-primary text-decoration-none small fw-medium d-inline-flex align-items-center gap-1">
                            <ArrowLeft size={14} />
                            Back to Sign In
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ForgotPassword;
