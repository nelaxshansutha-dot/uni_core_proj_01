import React, { useState, useEffect, useRef, useContext } from 'react';
import { useLocation, useNavigate, Navigate } from 'react-router-dom';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';
import { ShieldCheck, XCircle, CheckCircle, RefreshCw } from 'lucide-react';

const OTP_EXPIRY_SECONDS = 120; // 2 minutes
const RESEND_COOLDOWN_SECONDS = 30;

const OTPVerification = () => {
    const [otp, setOtp] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    // Countdown timer: counts down from 120s (2 min)
    const [timeLeft, setTimeLeft] = useState(OTP_EXPIRY_SECONDS);
    const [otpExpired, setOtpExpired] = useState(false);

    // Resend cooldown: user must wait 30s before resending again
    const [resendCooldown, setResendCooldown] = useState(0);
    const [resendLoading, setResendLoading] = useState(false);
    const [resendSuccess, setResendSuccess] = useState('');

    const expiryTimerRef = useRef(null);
    const cooldownTimerRef = useRef(null);

    const location = useLocation();
    const navigate = useNavigate();
    const { login } = useContext(AuthContext);

    if (!location.state?.email) {
        return <Navigate to="/login" replace />;
    }

    const { userId, email, otpDebug } = location.state;

    // OTP expiry countdown
    useEffect(() => {
        expiryTimerRef.current = setInterval(() => {
            setTimeLeft(prev => {
                if (prev <= 1) {
                    clearInterval(expiryTimerRef.current);
                    setOtpExpired(true);
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);
        return () => clearInterval(expiryTimerRef.current);
    }, []);

    // Resend cooldown countdown
    useEffect(() => {
        if (resendCooldown <= 0) return;
        cooldownTimerRef.current = setInterval(() => {
            setResendCooldown(prev => {
                if (prev <= 1) {
                    clearInterval(cooldownTimerRef.current);
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);
        return () => clearInterval(cooldownTimerRef.current);
    }, [resendCooldown]);

    const formatTime = (secs) => {
        const m = Math.floor(secs / 60).toString().padStart(2, '0');
        const s = (secs % 60).toString().padStart(2, '0');
        return `${m}:${s}`;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await api.post('/auth/verify-otp', {
                user_id: userId,
                otp
            });

            if (response.data.success) {
                clearInterval(expiryTimerRef.current);
                navigate('/login', { state: { verifiedSuccess: true } });
            } else {
                setError(response.data.message || 'Invalid OTP.');
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Invalid OTP, please enter a valid OTP.');
        } finally {
            setLoading(false);
        }
    };

    const handleResend = async () => {
        setResendLoading(true);
        setResendSuccess('');
        setError('');

        try {
            await api.post('/auth/resend-otp', { user_id: userId });

            // Reset expiry timer and expiry state
            clearInterval(expiryTimerRef.current);
            setOtp('');
            setOtpExpired(false);
            setTimeLeft(OTP_EXPIRY_SECONDS);
            expiryTimerRef.current = setInterval(() => {
                setTimeLeft(prev => {
                    if (prev <= 1) {
                        clearInterval(expiryTimerRef.current);
                        setOtpExpired(true);
                        return 0;
                    }
                    return prev - 1;
                });
            }, 1000);

            setResendSuccess('A new OTP has been sent to your email!');
            setTimeout(() => setResendSuccess(''), 5000);
            setResendCooldown(RESEND_COOLDOWN_SECONDS);
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to resend OTP. Please try again.');
        } finally {
            setResendLoading(false);
        }
    };

    const isOtpInputDisabled = otpExpired || loading;

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

                    {/* OTP Expiry Countdown */}
                    <div className={`d-flex align-items-center justify-content-center gap-2 mb-3 small fw-medium ${otpExpired ? 'text-danger' : timeLeft <= 30 ? 'text-warning' : 'text-muted'}`}>
                        <span
                            style={{
                                display: 'inline-block',
                                minWidth: '54px',
                                textAlign: 'center',
                                fontFamily: 'monospace',
                                fontSize: '1rem',
                                fontWeight: '700',
                                padding: '2px 8px',
                                borderRadius: '6px',
                                background: otpExpired ? '#fee2e2' : timeLeft <= 30 ? '#fef9c3' : '#f1f5f9',
                                color: otpExpired ? '#dc2626' : timeLeft <= 30 ? '#d97706' : '#475569'
                            }}
                        >
                            {formatTime(timeLeft)}
                        </span>
                        <span>{otpExpired ? 'OTP expired' : 'remaining'}</span>
                    </div>

                    <div className="alert alert-warning py-2 small mb-3">
                        <strong>First-time login:</strong> Please check your email for the OTP code check
                    </div>

                    {resendSuccess && (
                        <div className="alert alert-success py-2 small d-flex align-items-center gap-2">
                            <CheckCircle size={16} className="flex-shrink-0" />
                            <span>{resendSuccess}</span>
                        </div>
                    )}

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
                                disabled={isOtpInputDisabled}
                            />
                            <div className="form-hint text-center mt-2">Enter the 6-digit code from your email</div>
                        </div>
                        <button
                            type="submit"
                            className="btn btn-primary btn-lg w-100 mb-2"
                            disabled={loading || otp.length !== 6 || otpExpired}
                        >
                            {loading ? <span className="spinner-border spinner-border-sm me-2"></span> : null}
                            Verify &amp; Continue
                        </button>
                    </form>

                    {/* Resend OTP */}
                    <div className="text-center mt-2 mb-1">
                        <button
                            className="btn btn-outline-secondary btn-sm w-100 d-flex align-items-center justify-content-center gap-2"
                            onClick={handleResend}
                            disabled={resendLoading || resendCooldown > 0}
                        >
                            {resendLoading
                                ? <span className="spinner-border spinner-border-sm"></span>
                                : <RefreshCw size={15} />
                            }
                            {resendCooldown > 0
                                ? `Resend OTP (wait ${resendCooldown}s)`
                                : 'Resend OTP'
                            }
                        </button>
                    </div>

                    <div className="text-center mt-3">
                        <span className="text-muted small">Wrong account? </span>
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

