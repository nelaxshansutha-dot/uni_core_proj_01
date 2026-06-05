import loginBg from '../../assets/images/login.jpg';

/**
 * Wraps all auth pages with the login.jpg background.
 * Using an ES module import ensures Vite processes the image correctly
 * (hashed filename, optimized, works in both dev and production build).
 */
const AuthBackground = ({ children }) => (
    <div
        className="auth-bg"
        style={{ '--auth-bg-image': `url(${loginBg})` }}
    >
        {children}
    </div>
);

export default AuthBackground;
