import React, { useState, useContext } from 'react';
import { useLocation, useNavigate, Navigate } from 'react-router-dom';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';
import { ShieldCheck, XCircle } from 'lucide-react';

const OTPVerification = () => {
    const [otp, setOtp] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    
    const location = useLocation();
    const navigate = useNavigate();
    const { login } = useContext(AuthContext);

    if (!location.state?.userId) {
        return <Navigate to="/login" replace />;
    }

    const { userId, email } = location.state;

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await api.post('/auth.php?action=verify-otp', {
                user_id: userId,
                otp
            });

            if (response.data.status === 'success') {
                login(response.data.data.token, response.data.data.user);
                navigate('/dashboard');
            } else {
                setError(response.data.message);
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Invalid or expired OTP. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="auth-bg">
            <div className="auth-card" style={{ maxWidth: '420px', width: '100%' }}>
                <div className="card-body p-5">
                    <div className="text-center mb-4">
                        <div className="icon-badge success">
                            <ShieldCheck size={30} />
                        </div>
                        <h3 className="fw-bold text-dark mb-1">Verify Your Email</h3>
                        <p className="text-muted small">
                            We sent a 6-digit verification code to<br/>
                            <span className="fw-semibold text-dark">{email}</span>
                        </p>
                    </div>

                    <div className="alert alert-warning py-2 small mb-3">
                        <strong>First-time login:</strong> Please check your email for the OTP code. For local testing, check <code>backend/otp_log.txt</code>.
                    </div>

                    {error && (
                        <div className="alert alert-danger py-2 small d-flex align-items-center gap-2">
                            <XCircle size={16} className="flex-shrink-0" />
                            <span>{error}</span>
                        </div>
                    )}

                    <form onSubmit={handleSubmit}>
                        <div className="mb-4">
                            <input 
                                type="text" 
                                className="form-control form-control-lg text-center fw-bold fs-4 tracking-widest" 
                                placeholder="------"
                                maxLength="6"
                                value={otp}
                                onChange={(e) => { setOtp(e.target.value.replace(/\D/g, '')); setError(''); }}
                                required 
                                autoFocus
                            />
                            <div className="form-hint text-center mt-2">Enter the 6-digit code from your email</div>
                        </div>
                        <button type="submit" className="btn btn-primary btn-lg w-100 mb-3" disabled={loading || otp.length !== 6}>
                            {loading ? <span className="spinner-border spinner-border-sm me-2"></span> : null}
                            Verify & Continue
                        </button>
                    </form>

                    <div className="text-center mt-3">
                        <span className="text-muted small">Didn't receive the code? </span>
                        <button className="btn btn-link text-primary text-decoration-none small fw-medium p-0" onClick={() => navigate('/login')}>
                            Go back to login
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default OTPVerification;
