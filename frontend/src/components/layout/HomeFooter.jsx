import React from 'react';
import { Link } from 'react-router-dom';
import { Mail, Phone, MapPin, Globe, MessageCircle, Link2 } from 'lucide-react';
import './HomeFooter.css';

const HomeFooter = () => {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="home-footer-wrapper">
      <div className="footer-top">
        <div className="container">
          <div className="row g-5">
            {/* Brand Column */}
            <div className="col-lg-4 col-md-6">
              <div className="footer-brand">
                <img src="/logo.jpeg" alt="UniCore Logo" className="footer-logo-img" />
                <span className="footer-brand-name">UniCore</span>
              </div>
              <p className="footer-tagline">
                Your all-in-one smart campus platform — built for students and staff to connect, collaborate, and thrive together.
              </p>
              <div className="footer-social-row">
                <a href="#" className="footer-social-btn" aria-label="Social">
                  <MessageCircle size={16} />
                </a>
                <a href="#" className="footer-social-btn" aria-label="Website">
                  <Globe size={16} />
                </a>
                <a href="#" className="footer-social-btn" aria-label="Links">
                  <Link2 size={16} />
                </a>
              </div>
            </div>

            {/* Quick Links */}
            <div className="col-lg-2 col-md-6 col-6">
              <h4 className="footer-col-title">Platform</h4>
              <ul className="footer-link-list">
                <li><Link to="/marketplace">Marketplace</Link></li>
                <li><Link to="/lost-items">Lost &amp; Found</Link></li>
                <li><Link to="/notes">Notes Hub</Link></li>
                <li><Link to="/peer-learning">Peer Learning</Link></li>
              </ul>
            </div>

            {/* Company */}
            <div className="col-lg-2 col-md-6 col-6">
              <h4 className="footer-col-title">Company</h4>
              <ul className="footer-link-list">
                <li><Link to="/about">About</Link></li>
                <li><a href="#contact">Contact</a></li>
                <li><Link to="/register">Register</Link></li>
                <li><Link to="/login">Sign In</Link></li>
              </ul>
            </div>

            {/* Contact Info */}
            <div className="col-lg-4 col-md-6">
              <h4 className="footer-col-title">Contact Us</h4>
              <ul className="footer-contact-list">
                <li>
                  <div className="contact-icon"><Mail size={14} /></div>
                  <span>support@unicore.edu</span>
                </li>
                <li>
                  <div className="contact-icon"><Phone size={14} /></div>
                  <span>+94 11 234 5678</span>
                </li>
                <li>
                  <div className="contact-icon"><MapPin size={14} /></div>
                  <span>University Campus, Sri Lanka</span>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      {/* Bottom Bar */}
      <div className="footer-bottom">
        <div className="container">
          <div className="footer-bottom-inner">
            <p className="footer-copy">
              © {currentYear} UniCore. All rights reserved. Built for university students &amp; staff.
            </p>
            <div className="footer-legal-links">
              <a href="#">Privacy Policy</a>
              <a href="#">Terms of Use</a>
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
};

export default HomeFooter;
