import React, { useState, useMemo } from 'react';
import { useNavigate, Navigate, useLocation, Link } from 'react-router-dom';
import api from '../../services/api';
import { Lock, CheckCircle, XCircle, Eye, EyeOff, ArrowLeft } from 'lucide-react';

const ResetPassword = () => {
    const [step, setStep] = useState(1); // 1: Enter OTP, 2: Enter new password
    const [otp, setOtp] = useState('');
    const [resetToken, setResetToken] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');
    const [loading, setLoading] = useState(false);

    const location = useLocation();
    const navigate = useNavigate();

    if (!location.state?.userId) {
        return <Navigate to="/forgot-password" replace />;
    }

    const { userId, email } = location.state;

    // Real-time password match
    const passwordMatch = useMemo(() => {
        if (!confirmPassword) return null;
        return newPassword === confirmPassword;
    }, [newPassword, confirmPassword]);

    // Step 1: Verify the OTP
    const handleVerifyOtp = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await api.post('/auth.php?action=verify-reset-otp', {
                user_id: userId,
                otp
            });

            if (response.data.status === 'success') {
                setResetToken(response.data.data.reset_token);
                setStep(2);
                setSuccess('OTP verified! Now set your new password.');
            } else {
                setError(response.data.message);
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Invalid OTP, please enter a valid OTP.');
        } finally {
            setLoading(false);
        }
    };

    // Step 2: Set new password
    const handleResetPassword = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');
        setSuccess('');

        if (newPassword.length < 6) {
            setError('Password must be at least 6 characters long.');
            setLoading(false);
            return;
        }

        if (newPassword !== confirmPassword) {
            setError('Passwords do not match.');
            setLoading(false);
            return;
        }

        try {
            const response = await api.post('/auth.php?action=reset-password', {
                user_id: userId,
                reset_token: resetToken,
                new_password: newPassword,
                confirm_password: confirmPassword
            });

            if (response.data.status === 'success') {
                navigate('/login', { state: { passwordReset: true } });
            } else {
                setError(response.data.message);
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to reset password. Please try again.');
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
                            <Lock size={30} />
                        </div>
                        <h3 className="fw-bold text-dark mb-1">
                            {step === 1 ? 'Enter Verification Code' : 'Set New Password'}
                        </h3>
                        <p className="text-muted small">
                            {step === 1
                                ? <>We sent a code to <span className="fw-semibold text-dark">{email}</span></>
                                : 'Choose a strong password for your account'
                            }
                        </p>
                    </div>

                    {success && (
                        <div className="alert alert-success py-2 small d-flex align-items-center gap-2">
                            <CheckCircle size={16} className="flex-shrink-0" />
                            <span>{success}</span>
                        </div>
                    )}

                    {error && (
                        <div className="alert alert-danger py-2 small d-flex align-items-center gap-2">
                            <XCircle size={16} className="flex-shrink-0" />
                            <span>{error}</span>
                        </div>
                    )}

                    {step === 1 ? (
                        /* Step 1: OTP Entry */
                        <form onSubmit={handleVerifyOtp}>
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
                                <div className="form-hint text-center mt-2">
                                    Enter the 6-digit code sent to your email. For testing, check <code>backend/otp_log.txt</code>
                                </div>
                            </div>
                            <button type="submit" className="btn btn-primary btn-lg w-100" disabled={loading || otp.length !== 6}>
                                {loading ? <span className="spinner-border spinner-border-sm me-2"></span> : null}
                                Verify Code
                            </button>
                        </form>
                    ) : (
                        /* Step 2: New Password */
                        <form onSubmit={handleResetPassword}>
                            <div className="mb-3">
                                <label className="form-label">New Password</label>
                                <div className="position-relative">
                                    <input
                                        type={showPassword ? 'text' : 'password'}
                                        className="form-control form-control-lg"
                                        placeholder="Minimum 6 characters"
                                        value={newPassword}
                                        onChange={(e) => { setNewPassword(e.target.value); setError(''); }}
                                        required
                                        autoFocus
                                    />
                                    <button
                                        type="button"
                                        className="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3 text-muted"
                                        onClick={() => setShowPassword(!showPassword)}
                                        tabIndex={-1}
                                        style={{ textDecoration: 'none' }}
                                    >
                                        {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                                    </button>
                                </div>
                            </div>

                            <div className="mb-4">
                                <label className="form-label">Confirm New Password</label>
                                <div className="position-relative">
                                    <input
                                        type={showConfirm ? 'text' : 'password'}
                                        className="form-control form-control-lg"
                                        placeholder="Re-enter your new password"
                                        value={confirmPassword}
                                        onChange={(e) => { setConfirmPassword(e.target.value); setError(''); }}
                                        required
                                    />
                                    <button
                                        type="button"
                                        className="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3 text-muted"
                                        onClick={() => setShowConfirm(!showConfirm)}
                                        tabIndex={-1}
                                        style={{ textDecoration: 'none' }}
                                    >
                                        {showConfirm ? <EyeOff size={18} /> : <Eye size={18} />}
                                    </button>
                                </div>
                                {passwordMatch !== null && (
                                    <div className={`password-match ${passwordMatch ? 'match' : 'no-match'}`}>
                                        {passwordMatch ? (
                                            <><CheckCircle size={14} /> Passwords match</>
                                        ) : (
                                            <><XCircle size={14} /> Passwords do not match</>
                                        )}
                                    </div>
                                )}
                            </div>

                            <button type="submit" className="btn btn-primary btn-lg w-100" disabled={loading || passwordMatch === false}>
                                {loading ? <span className="spinner-border spinner-border-sm me-2"></span> : null}
                                Reset Password
                            </button>
                        </form>
                    )}

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

export default ResetPassword;
