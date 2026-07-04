import React, { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { Menu, X } from 'lucide-react';
import './HomeNavbar.css';

const HomeNavbar = () => {
  const [scrolled, setScrolled] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);
  const location = useLocation();

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const navLinks = [
    { label: 'About', to: '#features', isScroll: true },
    { label: 'How it Works', to: '#how-it-works', isScroll: true },

  ];

  const handleNavClick = (e, link) => {
    if (link.isScroll) {
      e.preventDefault();
      if (location.pathname !== '/') {
        window.location.href = '/' + link.to;
      } else {
        const element = document.getElementById(link.to.substring(1));
        if (element) {
          element.scrollIntoView({ behavior: 'smooth' });
        }
      }
      setMenuOpen(false);
    } else {
      setMenuOpen(false);
    }
  };

  return (
    <header className={`home-navbar ${scrolled ? 'scrolled' : ''}`}>
      <div className="navbar-inner container-fluid px-4 px-md-5">
        {/* Brand */}
        <Link to="/" className="navbar-brand-link" onClick={() => setMenuOpen(false)}>
          <img src="/logo.jpeg" alt="UniCore Logo" className="brand-logo-img" />
          <span className="brand-name">UniCore</span>
        </Link>

        {/* Desktop Nav */}
        <nav className="desktop-nav">
          {navLinks.map((link) => (
            link.isScroll ? (
              <a
                key={link.to}
                href={link.to}
                onClick={(e) => handleNavClick(e, link)}
                className="nav-text-link"
              >
                {link.label}
              </a>
            ) : (
              <Link
                key={link.to}
                to={link.to}
                className={`nav-text-link ${location.pathname === link.to ? 'active' : ''}`}
              >
                {link.label}
              </Link>
            )
          ))}
          <div className="nav-divider" />
          <Link to="/login" className="btn-nav-signin">
            Sign In
          </Link>
          <Link to="/register" className="btn-nav-register">
            Register
          </Link>
        </nav>

        {/* Mobile Menu Toggle */}
        <button
          className="mobile-menu-toggle"
          onClick={() => setMenuOpen(!menuOpen)}
          aria-label="Toggle navigation"
        >
          {menuOpen ? <X size={22} /> : <Menu size={22} />}
        </button>
      </div>

      {/* Mobile Dropdown Menu */}
      <div className={`mobile-menu ${menuOpen ? 'open' : ''}`}>
        {navLinks.map((link) => (
          link.isScroll ? (
            <a
              key={link.to}
              href={link.to}
              className="mobile-nav-link"
              onClick={(e) => handleNavClick(e, link)}
            >
              {link.label}
            </a>
          ) : (
            <Link
              key={link.to}
              to={link.to}
              className="mobile-nav-link"
              onClick={() => setMenuOpen(false)}
            >
              {link.label}
            </Link>
          )
        ))}
        <div className="mobile-auth-row">
          <Link to="/login" className="btn-nav-signin" onClick={() => setMenuOpen(false)}>
            Sign In
          </Link>
          <Link to="/register" className="btn-nav-register" onClick={() => setMenuOpen(false)}>
            Register
          </Link>
        </div>
      </div>
    </header>
  );
};

export default HomeNavbar;
