import React from 'react';
import { Link } from 'react-router-dom';
import { 
  BookOpen, 
  Users, 
  ShoppingBag, 
  Search, 
  ArrowRight, 
  FileText, 
  MapPin, 
  Sparkles
} from 'lucide-react';
import './Home.css';

const Home = () => {
  const scrollToFeatures = (e) => {
    e.preventDefault();
    const element = document.getElementById('features');
    if (element) {
      element.scrollIntoView({ behavior: 'smooth' });
    }
  };

  return (
    <div className="home-container">
      {/* Hero Section */}
      <section className="hero-section d-flex align-items-center">
        <div className="container hero-content">
          <div className="row align-items-center">
            <div className="col-lg-7 text-center text-lg-start">
              <div className="badge-glow mb-3">
                <Sparkles size={16} className="me-2 text-warning animate-pulse" />
                <span>Introducing UniCore v1.0</span>
              </div>
              <h1 className="hero-title">
                Your Smart Campus <br />
                <span className="gradient-text">Companion</span>
              </h1>
              <p className="hero-subtitle">
                Navigate university life with ease. UniCore brings together
                Marketplace, Lost & Found, Shared Notes, and Peer Learning into
                one seamless dashboard.
              </p>
              <div className="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                <Link to="/register" className="btn btn-primary-custom text-decoration-none d-flex align-items-center gap-2">
                  Get Started Now <ArrowRight size={18} />
                </Link>
                <a href="#features" onClick={scrollToFeatures} className="btn btn-outline-custom text-decoration-none">
                  See How It Works
                </a>
              </div>
            </div>
            <div className="col-lg-5 text-center mt-5 mt-lg-0 position-relative">
              {/* Visual geometric blobs for premium look */}
              <div className="hero-blob hero-blob-1"></div>
              <div className="hero-blob hero-blob-2"></div>
              
              {/* Interactive preview UI container */}
              <div className="hero-glass-card">
                <div className="glass-header d-flex gap-1 mb-3">
                  <span className="dot dot-red"></span>
                  <span className="dot dot-yellow"></span>
                  <span className="dot dot-green"></span>
                </div>
                <div className="glass-content text-start">
                  <div className="glass-item mb-2 d-flex align-items-center gap-2">
                    <div className="icon-circle bg-purple-soft"><ShoppingBag size={16} /></div>
                    <span className="text-muted-soft text-xs">Marketplace: Calculus Textbook • $20</span>
                  </div>
                  <div className="glass-item mb-2 d-flex align-items-center gap-2">
                    <div className="icon-circle bg-yellow-soft"><Search size={16} /></div>
                    <span className="text-muted-soft text-xs">Lost & Found: Car Keys near Library</span>
                  </div>
                  <div className="glass-item d-flex align-items-center gap-2">
                    <div className="icon-circle bg-green-soft"><BookOpen size={16} /></div>
                    <span className="text-muted-soft text-xs">Notes Hub: CS101 Lecture Notes</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Features Section */}
      <section id="features" className="features-section">
        <div className="container">
          <div className="text-center mb-5">
            <h2 className="section-title">Everything You Need to Succeed</h2>
            <p className="section-subtitle text-muted-soft max-w-2xl mx-auto">
              We've built specialized tools for every facet of your university journey, from finding lost keys to acing that final exam.
            </p>
          </div>

          <div className="row g-4 mt-2">
            {/* Campus Marketplace */}
            <div className="col-md-6 col-lg-3">
              <div className="feature-card h-100 d-flex flex-column justify-content-between">
                <div>
                  <div className="feature-icon-wrapper marketplace-icon">
                    <ShoppingBag size={28} />
                  </div>
                  <h3 className="feature-title">Campus Marketplace</h3>
                  <p className="feature-desc">
                    Buy and sell textbooks, dorm furniture, and tech with verified students from your university.
                  </p>
                </div>
                <div className="mt-4 pt-2">
                  <Link to="/marketplace" className="btn btn-sm btn-action-card w-100 d-flex align-items-center justify-content-center gap-2 text-decoration-none">
                    Explore Marketplace <ArrowRight size={14} />
                  </Link>
                </div>
              </div>
            </div>

            {/* Lost & Found */}
            <div className="col-md-6 col-lg-3">
              <div className="feature-card h-100 d-flex flex-column justify-content-between">
                <div>
                  <div className="feature-icon-wrapper lostfound-icon">
                    <Search size={28} />
                  </div>
                  <h3 className="feature-title">Lost & Found</h3>
                  <p className="feature-desc">
                    Instantly post and search for lost items with geolocation tagging and photo verification.
                  </p>
                </div>
                <div className="mt-4 pt-2">
                  {/* User representations stack */}
                  <div className="d-flex align-items-center justify-content-between bg-dark-glass p-2 rounded-xl">
                    <div className="avatar-stack">
                      <div className="avatar-circle">JD</div>
                      <div className="avatar-circle">AS</div>
                      <div className="avatar-circle more-count">+12</div>
                    </div>
                    <div className="d-flex align-items-center text-xs text-muted-soft gap-1">
                      <MapPin size={12} className="text-danger" />
                      <span>Active nearby</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Notes Hub */}
            <div className="col-md-6 col-lg-3">
              <div className="feature-card h-100 d-flex flex-column justify-content-between">
                <div>
                  <div className="feature-icon-wrapper notes-icon">
                    <BookOpen size={28} />
                  </div>
                  <h3 className="feature-title">Notes Hub</h3>
                  <p className="feature-desc">
                    Share and access high-quality lecture notes and study guides curated by top-performing students.
                  </p>
                </div>
                <div className="mt-4 pt-2">
                  {/* Document tag file representation */}
                  <div className="d-flex align-items-center gap-2 bg-dark-glass p-2 rounded-xl border-dashed">
                    <FileText size={18} className="text-info" />
                    <span className="text-xs font-semibold truncate text-muted-soft">Bio_Lecture_04.pdf</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Peer Learning Networks */}
            <div className="col-md-6 col-lg-3">
              <div className="feature-card h-100 d-flex flex-column justify-content-between">
                <div>
                  <div className="feature-icon-wrapper peer-icon">
                    <Users size={28} />
                  </div>
                  <h3 className="feature-title">Peer Learning Networks</h3>
                  <p className="feature-desc">
                    Connect with study partners, form project groups, or find mentors in your specific department.
                  </p>
                </div>
                <div className="mt-4 pt-2">
                  {/* Buttons/pills representation */}
                  <div className="d-flex flex-wrap gap-1">
                    <span className="pill-badge pill-purple">Study Groups</span>
                    <span className="pill-badge pill-blue">Mentorship</span>
                    <span className="pill-badge pill-green">Workshops</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Onboarding Section */}
      <section className="onboarding-section">
        <div className="container">
          <div className="text-center mb-5">
            <h2 className="section-title">Get Started in Minutes</h2>
            <p className="section-subtitle text-muted-soft max-w-2xl mx-auto">
              Designed for the busy student. No complicated setups, just direct access to your campus ecosystem.
            </p>
          </div>

          <div className="row g-4 justify-content-center">
            {/* Step 1 */}
            <div className="col-md-4">
              <div className="step-card text-center">
                <div className="step-number">1</div>
                <h3 className="step-title">Join Your Univ</h3>
                <p className="step-desc">
                  Sign up with your university email to get instant access to your verified local community.
                </p>
              </div>
            </div>

            {/* Step 2 */}
            <div className="col-md-4">
              <div className="step-card text-center">
                <div className="step-number">2</div>
                <h3 className="step-title">Personalize Feed</h3>
                <p className="step-desc">
                  Select your major, year, and interests to see the most relevant marketplace listings and notes.
                </p>
              </div>
            </div>

            {/* Step 3 */}
            <div className="col-md-4">
              <div className="step-card text-center">
                <div className="step-number">3</div>
                <h3 className="step-title">Engage & Grow</h3>
                <p className="step-desc">
                  Start trading, sharing, and collaborating with peers to make the most of your student years.
                </p>
              </div>
            </div>
          </div>

          <div className="text-center mt-5">
            <Link to="/onboarding-guide" className="onboarding-link text-decoration-none d-inline-flex align-items-center gap-1">
              Read the onboarding guide <ArrowRight size={16} />
            </Link>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="cta-section text-center text-white">
        <div className="container">
          <div className="cta-box glass-card p-5 rounded-3xl mx-auto max-w-4xl">
            <h2 className="cta-title">
              Join your university community today
            </h2>
            <p className="cta-desc text-muted-soft max-w-2xl mx-auto mb-4">
              Over 10,000 students are already using UniCore to simplify their academic journey. Are you ready to optimize your campus life?
            </p>
            <div className="d-flex flex-wrap justify-content-center gap-3 mt-4">
              <Link to="/register" className="btn btn-primary-custom text-decoration-none">
                Sign Up Free
              </Link>
              <Link to="/contact" className="btn btn-outline-custom text-decoration-none">
                Contact Sales
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="home-footer">
        <div className="container">
          <p className="mb-0 text-muted-soft text-sm">
            © {new Date().getFullYear()} UniCore. Designed for university students.
          </p>
        </div>
      </footer>
    </div>
  );
};

export default Home;
