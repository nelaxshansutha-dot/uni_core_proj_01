import React, { useState, useMemo } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { CheckCircle, XCircle, Lock, Eye, EyeOff, ShieldCheck } from 'lucide-react';
import api from '../../services/api';
import logo from '../../assets/logo.jpg';

const ChangeRepPassword = () => {
    const location  = useLocation();
    const navigate  = useNavigate();

    // Passed from Login when is_first_login = true
    const userId = location.state?.userId;
    const repId  = location.state?.repId;

    const [password, setPassword]               = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [showPassword, setShowPassword]       = useState(false);
    const [showConfirm, setShowConfirm]         = useState(false);
    const [error, setError]                     = useState('');
    const [success, setSuccess]                 = useState('');
    const [loading, setLoading]                 = useState(false);

    // Redirect to login if accessed directly without user ID
    React.useEffect(() => {
        if (!userId) navigate('/login');
    }, [userId, navigate]);

    const passwordValid = useMemo(() => {
        if (!password) return null;
        return password.length >= 8;
    }, [password]);

    const passwordMatch = useMemo(() => {
        if (!confirmPassword) return null;
        return password === confirmPassword;
    }, [password, confirmPassword]);

    const strengthScore = useMemo(() => {
        let score = 0;
        if (password.length >= 8)  score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        return score;
    }, [password]);

    const strengthLabel = ['', 'Weak', 'Fair', 'Good', 'Strong'];
    const strengthColor = ['', 'danger', 'warning', 'info', 'success'];

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setSuccess('');

        if (!passwordValid) {
            setError('Password must be at least 8 characters long.');
            return;
        }
        if (!passwordMatch) {
            setError('Passwords do not match.');
            return;
        }

        setLoading(true);
        try {
            const response = await api.post('/auth/force-change-rep-password', {
                user_id:      userId,
                new_password: password
            });

            if (response.data.status === 'success') {
                setSuccess('Password set successfully! Redirecting to login…');
                setTimeout(() => {
                    navigate('/login', {
                        state: { passwordReset: true }
                    });
                }, 2500);
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
            <div
                className="auth-card d-flex flex-column"
                style={{ maxWidth: 460, width: '100%', padding: '2.5rem' }}
            >
                {/* Header */}
                <div className="text-center mb-4">
                    <img src={logo} alt="UniCore" style={{ height: 64, marginBottom: '1rem', objectFit: 'contain' }} />
                    <div
                        className="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                        style={{ width: 56, height: 56, background: 'linear-gradient(135deg,#4f46e5,#7c3aed)', display: 'flex' }}
                    >
                        <ShieldCheck size={28} color="white" />
                    </div>
                    <h4 className="fw-bold text-dark mb-1">Set Your New Password</h4>
                    <p className="text-muted small mb-0">
                        You have been assigned as a <strong>Course Representative</strong>.
                        Please set a secure password to continue.
                    </p>
                    {repId && (
                        <div className="mt-2">
                            <span className="badge bg-light text-dark border font-monospace">{repId}</span>
                        </div>
                    )}
                </div>

                {/* Info banner */}
                <div className="alert alert-primary d-flex align-items-start gap-2 py-2 mb-4 border-0 rounded-3 small">
                    <Lock size={16} className="flex-shrink-0 mt-1" />
                    <span>
                        This is a <strong>one-time step</strong>. After setting your password you will
                        be redirected to the login page. Use your <strong>Rep ID</strong> and new password to sign in.
                    </span>
                </div>

                {error && (
                    <div className="alert alert-danger d-flex align-items-center gap-2 py-2 small">
                        <XCircle size={16} className="flex-shrink-0" />
                        <span>{error}</span>
                    </div>
                )}
                {success && (
                    <div className="alert alert-success d-flex align-items-center gap-2 py-2 small">
                        <CheckCircle size={16} className="flex-shrink-0" />
                        <span>{success}</span>
                    </div>
                )}

                <form onSubmit={handleSubmit}>
                    {/* New Password */}
                    <div className="mb-3">
                        <label className="form-label fw-semibold small">New Password</label>
                        <div className="position-relative">
                            <input
                                type={showPassword ? 'text' : 'password'}
                                className={`form-control pe-5${passwordValid === false ? ' is-invalid' : passwordValid === true ? ' is-valid' : ''}`}
                                placeholder="At least 8 characters"
                                value={password}
                                onChange={e => setPassword(e.target.value)}
                                required
                            />
                            <button
                                type="button"
                                className="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3 text-muted p-0"
                                onClick={() => setShowPassword(!showPassword)}
                                tabIndex={-1}
                                style={{ textDecoration: 'none' }}
                            >
                                {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                            </button>
                        </div>

                        {/* Strength bar */}
                        {password && (
                            <div className="mt-2">
                                <div className="progress" style={{ height: 5 }}>
                                    <div
                                        className={`progress-bar bg-${strengthColor[strengthScore]}`}
                                        style={{ width: `${(strengthScore / 4) * 100}%`, transition: 'width 0.3s' }}
                                    />
                                </div>
                                <small className={`text-${strengthColor[strengthScore]} mt-1 d-block`}>
                                    {strengthScore > 0 ? `${strengthLabel[strengthScore]} password` : ''}
                                </small>
                            </div>
                        )}

                        <div className="form-text small mt-1">
                            Use 8+ characters. Mix uppercase, numbers &amp; symbols for a stronger password.
                        </div>
                    </div>

                    {/* Confirm Password */}
                    <div className="mb-4">
                        <label className="form-label fw-semibold small">Confirm Password</label>
                        <div className="position-relative">
                            <input
                                type={showConfirm ? 'text' : 'password'}
                                className={`form-control pe-5${passwordMatch === false ? ' is-invalid' : passwordMatch === true ? ' is-valid' : ''}`}
                                placeholder="Re-enter your password"
                                value={confirmPassword}
                                onChange={e => setConfirmPassword(e.target.value)}
                                required
                            />
                            <button
                                type="button"
                                className="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3 text-muted p-0"
                                onClick={() => setShowConfirm(!showConfirm)}
                                tabIndex={-1}
                                style={{ textDecoration: 'none' }}
                            >
                                {showConfirm ? <EyeOff size={18} /> : <Eye size={18} />}
                            </button>
                        </div>
                        {passwordMatch === false && (
                            <div className="text-danger small mt-1 d-flex align-items-center gap-1">
                                <XCircle size={13} /> Passwords do not match
                            </div>
                        )}
                        {passwordMatch === true && (
                            <div className="text-success small mt-1 d-flex align-items-center gap-1">
                                <CheckCircle size={13} /> Passwords match
                            </div>
                        )}
                    </div>

                    <button
                        type="submit"
                        className="btn btn-primary w-100 py-2 fw-bold rounded-pill"
                        disabled={loading || success !== '' || passwordMatch === false || !passwordValid}
                    >
                        {loading
                            ? <><span className="spinner-border spinner-border-sm me-2" />Setting password…</>
                            : <><ShieldCheck size={16} className="me-2" />Set Password &amp; Continue</>}
                    </button>
                </form>
            </div>
        </div>
    );
};

export default ChangeRepPassword;
