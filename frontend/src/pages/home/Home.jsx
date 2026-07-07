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
  Sparkles,
  CheckCircle,
  Mail,
  Phone,
  Clock,
} from 'lucide-react';
import HomeNavbar from '../../components/layout/HomeNavbar';
import HomeFooter from '../../components/layout/HomeFooter';
import './Home.css';

const FEATURES = [
  {
    icon: <ShoppingBag size={26} />,
    iconClass: 'icon-marketplace',
    title: 'Campus Marketplace',
    desc: 'Buy and sell textbooks, electronics, and dorm essentials with verified students from your university.',
    action: { label: 'Browse Listings', to: '/marketplace' },
  },
  {
    icon: <Search size={26} />,
    iconClass: 'icon-lost',
    title: 'Lost Items',
    desc: 'Report or search for lost items with photo uploads and campus location tagging.',
    action: { label: 'View Items', to: '/lost-items' },
  },
  {
    icon: <BookOpen size={26} />,
    iconClass: 'icon-notes',
    title: 'Notes Hub',
    desc: 'Access and share high-quality lecture notes, study guides, and resources across departments.',
    action: { label: 'Explore Notes', to: '/notes' },
  },
  {
    icon: <Users size={26} />,
    iconClass: 'icon-peer',
    title: 'Peer Learning',
    desc: 'Find study partners, form project teams, and connect with mentors in your field.',
    action: { label: 'Get Connected', to: '/peer-learning' },
  },
];

const STEPS = [
  {
    number: '01',
    title: 'Create Your Account',
    desc: 'Register with your university email to join your verified campus community instantly.',
  },
  {
    number: '02',
    title: 'Set Up Your Profile',
    desc: 'Choose your role, department, and interests to personalise your experience.',
  },
  {
    number: '03',
    title: 'Connect & Collaborate',
    desc: 'Start trading, sharing notes, and growing your academic network right away.',
  },
];

const HIGHLIGHTS = [
  'Verified university accounts only',
  'Secure and private communications',
  'Mobile-friendly across all devices',
  'Real-time notifications & updates',
];

const Home = () => {
  const scrollToFeatures = (e) => {
    e.preventDefault();
    document.getElementById('features')?.scrollIntoView({ behavior: 'smooth' });
  };

  return (
    <div className="home-page">
      <HomeNavbar />

      {/* ── Hero ── */}
      <section className="hero-section">
        <div className="hero-glow glow-1" />
        <div className="hero-glow glow-2" />

        <div className="container hero-container">
         
          

          <h1 className="hero-title">
            Your Smart Campus <br />
            <span className="gradient-text">Companion</span>
          </h1>

          <p className="hero-subtitle">
            Simplify your university experience with UniCore. Access Lost Items, Marketplace, Notes Sharing, and Peer Learning Request. 
            all in one powerful, easy-to-use platform.
          </p>

          <div className="hero-actions">
            <Link to="/register" className="btn-cta-primary">
              Get Started  <ArrowRight size={17} />
            </Link>
            <a href="#features" onClick={scrollToFeatures} className="btn-cta-ghost">
              See Features
            </a>
          </div>

          {/* Highlight pills */}
          <div className="hero-highlights">
            {HIGHLIGHTS.map((h) => (
              <div key={h} className="hero-highlight-pill">
                <CheckCircle size={13} className="pill-check" />
                <span>{h}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── Features ── */}
      <section id="features" className="features-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">Everything You Need</h2>
            <p className="section-subtitle">
              Purpose-built tools for every part of your university journey, all in one place.
            </p>
          </div>

          <div className="features-grid">
            {FEATURES.map((feat) => (
              <div key={feat.title} className="feature-card">
                <div className={`feature-icon ${feat.iconClass}`}>{feat.icon}</div>
                <h3 className="feature-card-title">{feat.title}</h3>
                <p className="feature-card-desc">{feat.desc}</p>
                <Link to={feat.action.to} className="feature-card-link">
                  {feat.action.label} <ArrowRight size={14} />
                </Link>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── How It Works ── */}
      <section id="how-it-works" className="how-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">Get Started in Minutes</h2>
            <p className="section-subtitle">
              Simple onboarding, no complicated setup — just log in and start using UniCore.
            </p>
          </div>

          <div className="steps-grid">
            {STEPS.map((step, idx) => (
              <div key={step.number} className="step-card">
                <div className="step-num">{step.number}</div>
                {idx < STEPS.length - 1 && <div className="step-connector" />}
                <h3 className="step-title">{step.title}</h3>
                <p className="step-desc">{step.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── Contact Us ── */}
      <section id="contact" className="contact-section">
        <div className="container">
          <div className="section-header">
            <h2 className="section-title">Need More Help?</h2>
            <p className="section-subtitle">
              We're here to support you. Reach out to our admin team using any of the contact methods below.
            </p>
          </div>

          <div className="contact-grid">
            <div className="contact-card">
              <div className="contact-icon"><Mail size={24} /></div>
              <h3 className="contact-title">Send us an email</h3>
              <p className="contact-desc">admin@unicore.uwu.ac.lk</p>
              <p className="contact-subdesc">We typically reply within 24 hours.</p>
            </div>
            
            <div className="contact-card">
              <div className="contact-icon"><Phone size={24} /></div>
              <h3 className="contact-title">Call us</h3>
              <p className="contact-desc">+94 55 222 6622</p>
              <p className="contact-subdesc">Available 8am-4pm, Monday to Friday.</p>
            </div>

            <div className="contact-card">
              <div className="contact-icon"><Clock size={24} /></div>
              <h3 className="contact-title">Admin Office Hours</h3>
              <p className="contact-desc">8:00 AM - 4:00 PM</p>
              <p className="contact-subdesc">University premises, Weekdays only.</p>
            </div>
          </div>
        </div>
      </section>

      {/* ── CTA Banner ── */}
      <section className="cta-section">
        <div className="container">
          <div className="cta-box">
            <div className="cta-glow" />
            <h2 className="cta-title">Join your campus community today</h2>
            <p className="cta-desc">
              Thousands of students and staff are already using UniCore to simplify their academic life.
            </p>
            <div className="cta-actions">
              <Link to="/register" className="btn-cta-primary">
                Sign Up  <ArrowRight size={16} />
              </Link>

            </div>
          </div>
        </div>
      </section>

      <HomeFooter />
    </div>
  );
};

export default Home;
