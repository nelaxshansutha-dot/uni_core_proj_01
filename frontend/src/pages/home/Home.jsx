import React from 'react';
import { Link } from 'react-router-dom';
import { BookOpen, Users, ShoppingBag, Search } from 'lucide-react';
import './Home.css';

const Home = () => {
    return (
        <div className="home-container">
            {/* Hero Section */}
            <section className="hero-section d-flex align-items-center">
                <div className="container hero-content">
                    <div className="row align-items-center min-vh-50">
                        <div className="col-lg-8 mx-auto text-center">
                            <h1 className="hero-title">Welcome to UniCore</h1>
                            <p className="hero-subtitle">
                                The ultimate hub for university students. Connect, learn, trade, and find what you need—all in one place.
                            </p>
                            <div className="d-flex gap-3 justify-content-center">
                                <Link to="/login" className="btn btn-primary-custom text-decoration-none">
                                    Login
                                </Link>
                                <Link to="/register" className="btn btn-outline-custom text-decoration-none">
                                    Get Started
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Features Section */}
            <section className="features-section">
                <div className="container">
                    <h2 className="section-title">Everything You Need</h2>
                    <div className="row g-4 mt-2">
                        {/* Feature 1 */}
                        <div className="col-md-6 col-lg-3">
                            <div className="feature-card">
                                <div className="feature-icon-wrapper">
                                    <BookOpen size={36} />
                                </div>
                                <h3 className="feature-title">Notes Sharing</h3>
                                <p className="feature-desc">
                                    Access and share well-organized study materials with your peers effortlessly.
                                </p>
                            </div>
                        </div>
                        
                        {/* Feature 2 */}
                        <div className="col-md-6 col-lg-3">
                            <div className="feature-card">
                                <div className="feature-icon-wrapper">
                                    <Users size={36} />
                                </div>
                                <h3 className="feature-title">Peer Learning</h3>
                                <p className="feature-desc">
                                    Collaborate on projects, find study partners, and learn together in a thriving community.
                                </p>
                            </div>
                        </div>

                        {/* Feature 3 */}
                        <div className="col-md-6 col-lg-3">
                            <div className="feature-card">
                                <div className="feature-icon-wrapper">
                                    <ShoppingBag size={36} />
                                </div>
                                <h3 className="feature-title">Marketplace</h3>
                                <p className="feature-desc">
                                    Buy and sell textbooks, electronics, and other essentials within the campus.
                                </p>
                            </div>
                        </div>

                        {/* Feature 4 */}
                        <div className="col-md-6 col-lg-3">
                            <div className="feature-card">
                                <div className="feature-icon-wrapper">
                                    <Search size={36} />
                                </div>
                                <h3 className="feature-title">Lost & Found</h3>
                                <p className="feature-desc">
                                    Lost something? Found an item? Quickly report and reunite belongings on campus.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <footer className="home-footer">
                <div className="container">
                    <p className="mb-0">© {new Date().getFullYear()} UniCore. Designed for university students.</p>
                </div>
            </footer>
        </div>
    );
};

export default Home;
