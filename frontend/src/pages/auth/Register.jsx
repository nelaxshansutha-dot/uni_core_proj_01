import React, { useState, useMemo } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import api from '../../services/api';
import { UserPlus, CheckCircle, XCircle, Eye, EyeOff, Phone, Mail } from 'lucide-react';
import authImage from '../../assets/auth_side_image.png';
import logo from '../../assets/logo.jpg';

const Register = () => {
    const [formData, setFormData] = useState({
        enrollment_no: '',
        email: '',
        phone_number: '',
        password: '',
        confirm_password: '',
        role: 'student',
        first_name: '',
        last_name: '',
        department: ''
    });
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);
    const navigate = useNavigate();

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
        if (error) setError('');
    };

    // Real-time password match check
    const passwordMatch = useMemo(() => {
        if (!formData.confirm_password) return null;
        return formData.password === formData.confirm_password;
    }, [formData.password, formData.confirm_password]);

    // Email domain validation
    const emailDomainValid = useMemo(() => {
        if (!formData.email) return null;
        if (formData.role === 'student') {
            return formData.email.toLowerCase().endsWith('@std.uwu.ac.lk');
        } else if (formData.role === 'staff') {
            return formData.email.toLowerCase().endsWith('@gmail.com');
        }
        return null;
    }, [formData.email, formData.role]);

    const emailHint = useMemo(() => {
        if (formData.role === 'student') {
            return 'Students must use their university email ending with @std.uwu.ac.lk';
        }
        return 'Staff must use a Gmail address ending with @gmail.com';
    }, [formData.role]);

    // Phone number validation — digits, +, spaces, hyphens only (7–15 digits)
    const phoneValid = useMemo(() => {
        if (!formData.phone_number) return null;
        return /^[+]?[0-9][\s\-]?([0-9][\s\-]?){6,14}$/.test(formData.phone_number.trim());
    }, [formData.phone_number]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        // Client-side validation
        if (!phoneValid) {
            setError('Please enter a valid phone number (digits only, 7–15 digits).');
            setLoading(false);
            return;
        }

        if (emailDomainValid === false) {
            const expectedDomain = formData.role === 'student' ? '@std.uwu.ac.lk' : '@gmail.com';
            setError(`Invalid email domain. ${formData.role === 'student' ? 'Students' : 'Staff'} must register with a ${expectedDomain} email address.`);
            setLoading(false);
            return;
        }

        if (formData.password.length < 6) {
            setError('Password must be at least 6 characters long.');
            setLoading(false);
            return;
        }

        if (formData.password !== formData.confirm_password) {
            setError('Passwords do not match. Please re-enter.');
            setLoading(false);
            return;
        }

        try {
            const response = await api.post('/auth.php?action=register', formData);
            if (response.data.status === 'success') {
                navigate('/otp', {
                    state: {
                        userId: response.data.data.user_id,
                        email: response.data.data.email
                    }
                });
            } else {
                setError(response.data.message);
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Registration failed. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="auth-bg">
            <div className="auth-card d-flex flex-column flex-md-row" style={{ maxWidth: '1000px', width: '100%', overflow: 'hidden', padding: 0 }}>
                {/* Image Side */}
                <div className="auth-side-image d-none d-md-flex align-items-end" style={{ flex: '1 1 45%', backgroundImage: `url(${authImage})` }}>
                    <div className="auth-side-content w-100 text-center">
                        <h4 className="fw-bold text-white mb-2">Join Our Community</h4>
                        <p className="text-white-50 small mb-0">Empowering students and staff with smart digital tools.</p>
                    </div>
                </div>

                {/* Form Side */}
                <div className="card-body p-4 p-sm-5 d-flex flex-column justify-content-center" style={{ flex: '1 1 55%' }}>
                    <div className="text-center mb-4">
                        <img src={logo} alt="UniCore Logo" style={{ height: '80px', marginBottom: '1rem', objectFit: 'contain' }} />
                        <h3 className="fw-bold text-dark mb-1">Create an Account</h3>
                        <p className="text-muted small">Join the UniCore university platform</p>
                    </div>

                    {error && (
                        <div className="alert alert-danger py-2 small d-flex align-items-center gap-2">
                            <XCircle size={16} className="flex-shrink-0" />
                            <span>{error}</span>
                        </div>
                    )}

                    <form onSubmit={handleSubmit}>
                        <div className="row g-3">
                            {/* Role selector */}
                            <div className="col-12">
                                <label className="form-label">I am a</label>
                                <select className="form-select" name="role" value={formData.role} onChange={handleChange}>
                                    <option value="student">Student</option>
                                    <option value="staff">Staff Member</option>
                                </select>
                                <div className="form-hint">Select your role at Uva Wellassa University</div>
                            </div>

                            {/* Name */}
                            <div className="col-md-6">
                                <label className="form-label">First Name</label>
                                <input type="text" className="form-control" name="first_name" placeholder="e.g. Kavindu" value={formData.first_name} onChange={handleChange} required />
                            </div>
                            <div className="col-md-6">
                                <label className="form-label">Last Name</label>
                                <input type="text" className="form-control" name="last_name" placeholder="e.g. Perera" value={formData.last_name} onChange={handleChange} required />
                            </div>

                            {/* Enrollment Number */}
                            <div className="col-12">
                                <label className="form-label">Enrollment Number / Staff ID</label>
                                <input type="text" className="form-control" name="enrollment_no" placeholder="e.g. UWU/CST/21/0042" value={formData.enrollment_no} onChange={handleChange} required />
                                <div className="form-hint">Your unique university enrollment or staff identification number</div>
                            </div>

                            {/* Email */}
                            <div className="col-12">
                                <label className="form-label">Email Address</label>
                                <input
                                    type="email"
                                    className={`form-control${emailDomainValid === false ? ' is-invalid' : emailDomainValid === true ? ' is-valid' : ''}`}
                                    name="email"
                                    placeholder={formData.role === 'student' ? 'name@std.uwu.ac.lk' : 'name@gmail.com'}
                                    value={formData.email}
                                    onChange={handleChange}
                                    required
                                />
                                {emailDomainValid === false ? (
                                    <div className="invalid-feedback d-flex align-items-center gap-1">
                                        <XCircle size={13} />
                                        {formData.role === 'student'
                                            ? 'Students must register with a @std.uwu.ac.lk email address.'
                                            : 'Staff must register with a @gmail.com email address.'}
                                    </div>
                                ) : emailDomainValid === true ? (
                                    <div className="valid-feedback d-flex align-items-center gap-1">
                                        <CheckCircle size={13} /> Valid email domain
                                    </div>
                                ) : (
                                    <div className="form-hint">{emailHint}</div>
                                )}
                            </div>

                            {/* Phone Number */}
                            <div className="col-12">
                                <label className="form-label">Phone Number</label>
                                <input
                                    type="tel"
                                    className={`form-control${phoneValid === false ? ' is-invalid' : phoneValid === true ? ' is-valid' : ''}`}
                                    name="phone_number"
                                    placeholder="e.g. +94 77 123 4567"
                                    value={formData.phone_number}
                                    onChange={handleChange}
                                    required
                                />
                                {phoneValid === false ? (
                                    <div className="invalid-feedback d-flex align-items-center gap-1">
                                        <XCircle size={13} /> Enter a valid phone number (digits only, 7–15 digits).
                                    </div>
                                ) : phoneValid === true ? (
                                    <div className="valid-feedback d-flex align-items-center gap-1">
                                        <CheckCircle size={13} /> Valid phone number
                                    </div>
                                ) : (
                                    <div className="form-hint">Enter your phone number (digits only)</div>
                                )}
                            </div>

                            {/* Password */}
                            <div className="col-12">
                                <label className="form-label">Password</label>
                                <div className="position-relative">
                                    <input
                                        type={showPassword ? 'text' : 'password'}
                                        className="form-control"
                                        name="password"
                                        placeholder="Minimum 6 characters"
                                        value={formData.password}
                                        onChange={handleChange}
                                        required
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

                            {/* Confirm Password */}
                            <div className="col-12">
                                <label className="form-label">Confirm Password</label>
                                <div className="position-relative">
                                    <input
                                        type={showConfirm ? 'text' : 'password'}
                                        className="form-control"
                                        name="confirm_password"
                                        placeholder="Re-enter your password"
                                        value={formData.confirm_password}
                                        onChange={handleChange}
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

                            {/* Department (Staff only) */}
                            {formData.role === 'staff' && (
                                <div className="col-12">
                                    <label className="form-label">Department</label>
                                    <input type="text" className="form-control" name="department" placeholder="e.g. Computer Science & Technology" value={formData.department} onChange={handleChange} required />
                                </div>
                            )}

                            {/* Submit */}
                            <div className="col-12 mt-3">
                                <button
                                    type="submit"
                                    className="btn btn-primary btn-lg w-100"
                                    disabled={loading || passwordMatch === false || emailDomainValid === false || phoneValid === false}
                                >
                                    {loading ? <span className="spinner-border spinner-border-sm me-2"></span> : null}
                                    Create Account
                                </button>
                            </div>
                        </div>
                    </form>

                    <div className="text-center mt-4">
                        <span className="text-muted small">Already have an account? </span>
                        <Link to="/login" className="text-primary text-decoration-none fw-medium small">Sign in</Link>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default Register;
