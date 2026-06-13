import React, { useState, useContext } from 'react';
import { useNavigate, Link, useLocation } from 'react-router-dom';
import api from '../../services/api';
import { AuthContext } from '../../context/AuthContext';
import { LogIn, XCircle, CheckCircle } from 'lucide-react';

const Login = () => {
    const [role, setRole] = useState('student');
    const [enrollmentNo, setEnrollmentNo] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const navigate = useNavigate();
    const location = useLocation();
    const { login } = useContext(AuthContext);

    // Show success message if redirected from registration or password reset
    const registrationSuccess = location.state?.registrationSuccess;
    const verifiedSuccess = location.state?.verifiedSuccess;
    const passwordReset = location.state?.passwordReset;

    const getIdLabel = () => {
        switch (role) {
            case 'admin': return 'Admin ID';
            case 'staff': return 'Staff ID';
            case 'rep': return 'Rep ID';
            case 'student':
            default: return 'Enrollment Number';
        }
    };

    const getIdPlaceholder = () => {
        switch (role) {
            case 'admin': return 'e.g. ADMIN001';
            case 'staff': return 'e.g. STF001';
            case 'rep': return 'e.g. UWU/CST/21/0042';
            case 'student':
            default: return 'e.g. UWU/CST/21/0042';
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            const response = await api.post('/auth.php?action=login', {
                enrollment_no: enrollmentNo, // Backend checks this against all IDs
                password
            });

            if (response.data.status === 'success') {
                const { verified } = response.data.data;

                if (verified) {
                    // User is already verified — login directly, no OTP needed
                    login(response.data.data.token, response.data.data.user);
                    if (response.data.data.user.role === 'admin') {
                        navigate('/admin');
                    } else {
                        navigate('/dashboard');
                    }
                } else {
                    // First-time login — redirect to OTP verification
                    navigate('/otp', {
                        state: {
                            userId: response.data.data.user_id,
                            email: response.data.data.email
                        }
                    });
                }
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
                        <div className="icon-badge primary">
                            <LogIn size={30} />
                        </div>
                        <h3 className="fw-bold text-dark mb-1">Welcome to UniCore</h3>
                        <p className="text-muted small">Sign in to your university account</p>
                    </div>

                    {registrationSuccess && (
                        <div className="alert alert-success py-2 small d-flex align-items-center gap-2">
                            <CheckCircle size={16} className="flex-shrink-0" />
                            <span>Account created successfully! Sign in to verify your email.</span>
                        </div>
                    )}

                    {verifiedSuccess && (
                        <div className="alert alert-success py-2 small d-flex align-items-center gap-2">
                            <CheckCircle size={16} className="flex-shrink-0" />
                            <span>Email verified successfully! Please sign in.</span>
                        </div>
                    )}

                    {passwordReset && (
                        <div className="alert alert-success py-2 small d-flex align-items-center gap-2">
                            <CheckCircle size={16} className="flex-shrink-0" />
                            <span>Password reset successfully! Sign in with your new password.</span>
                        </div>
                    )}

                    {error && (
                        <div className="alert alert-danger py-2 small d-flex align-items-center gap-2">
                            <XCircle size={16} className="flex-shrink-0" />
                            <span>{error}</span>
                        </div>
                    )}

                    <form onSubmit={handleSubmit}>
                        <div className="mb-3">
                            <label className="form-label">I am a</label>
                            <select 
                                className="form-select form-select-lg"
                                value={role}
                                onChange={(e) => setRole(e.target.value)}
                            >
                                <option value="student">Student</option>
                                <option value="staff">Staff Member</option>
                                <option value="rep">Course Representative</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div className="mb-3">
                            <label className="form-label">{getIdLabel()}</label>
                            <input
                                type="text"
                                className="form-control form-control-lg"
                                placeholder={getIdPlaceholder()}
                                value={enrollmentNo}
                                onChange={(e) => { setEnrollmentNo(e.target.value); setError(''); }}
                                required
                            />
                            <div className="form-hint">Your unique university enrollment or staff ID</div>
                        </div>
                        <div className="mb-3">
                            <label className="form-label">Password</label>
                            <input
                                type="password"
                                className="form-control form-control-lg"
                                placeholder="••••••••"
                                value={password}
                                onChange={(e) => { setPassword(e.target.value); setError(''); }}
                                required
                            />
                        </div>

                        {/* Forgot Password Link */}
                        <div className="text-end mb-3">
                            <Link to="/forgot-password" className="text-primary text-decoration-none small fw-medium">
                                Forgot Password?
                            </Link>
                        </div>

                        <button type="submit" className="btn btn-primary btn-lg w-100 mb-3" disabled={loading}>
                            {loading ? <span className="spinner-border spinner-border-sm me-2"></span> : null}
                            Sign In
                        </button>
                    </form>

                    <div className="text-center mt-4">
                        <span className="text-muted small">Don't have an account? </span>
                        <Link to="/register" className="text-primary text-decoration-none fw-medium small">Register here</Link>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Login;
