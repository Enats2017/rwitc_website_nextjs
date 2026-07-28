"use client";
import { useState } from "react";
import "./TopHeader.css";
import { FaBars, FaTimes, FaCalendarAlt, FaChevronDown } from "react-icons/fa";

export default function TopHeader() {
    const [menuOpen, setMenuOpen] = useState(false);
    const [openDropdown, setOpenDropdown] = useState(null);

    const toggleDropdown = (name) => {
        setOpenDropdown(openDropdown === name ? null : name);
    };

    const navItems = [
        { label: "Home", href: "/" },
        { label: "About Us", href: "/about" },
        {
            label: "Races",
            key: "races",
            children: [
                { label: "Race Card", href: "/races/race-card" },
                { label: "Racing Fixtures", href: "/races/fixtures" },
                { label: "Results", href: "/races/results" },
            ],
        },
        {
            label: "Horses",
            key: "horses",
            children: [
                { label: "Rating of all Horses", href: "/horses/rating" },
                { label: "Trainerwise Horses", href: "/horses/trainerwise" },
                { label: "Performance Profile", href: "/horses/performance" },
            ],
        },
        {
            label: "Information",
            key: "information",
            children: [
                { label: "Tote Dividends", href: "/information/tote-dividends" },
                { label: "Money Leaders", href: "/information/money-leaders" },
                { label: "Indian Stud Book", href: "/information/stud-book" },
            ],
        },
        {
            label: "Media",
            key: "media",
            children: [
                { label: "Video Archives", href: "/media/videos" },
                { label: "Gallery", href: "/media/gallery" },
                { label: "News Room", href: "/media/news" },
            ],
        },
        { label: "Contact", href: "/contact" },
    ];

    return (
        <header className="header">
            <div className="header__wrapper">

                {/* LEFT SIDE - LOGO */}
                <a href="/" className="brandLink">
                    <div className="logo">
                        <img src="/image/rwitc_logo_white.png" alt="RWITC Logo" draggable="false" />
                    </div>
                    <div className="clubInfo">
                        <h2>Royal Western India<br />Turf Club Ltd.</h2>
                    </div>
                </a>

                {/* RIGHT SIDE GROUP - NAV + ICON + LIVE BUTTON (all together on the right) */}
                <div className="rightGroup">

                    {/* NAV LINKS (desktop) */}
                    <nav className="navLinks">
                        {navItems.map((item) => (
                            item.children ? (
                                <div
                                    className="navDropdownWrap"
                                    key={item.key}
                                >
                                    <button
                                        type="button"
                                        className="navItem navItemDropdown"
                                        onClick={() => toggleDropdown(item.key)}
                                    >
                                        {item.label}
                                        <FaChevronDown className="chevronIcon" />
                                    </button>
                                    <div className="navDropdownMenu">
                                        {item.children.map((child) => (
                                            <a href={child.href} className="navDropdownLink" key={child.label}>
                                                {child.label}
                                            </a>
                                        ))}
                                    </div>
                                </div>
                            ) : (
                                <a href={item.href} className="navItem" key={item.label}>
                                    {item.label}
                                </a>
                            )
                        ))}
                    </nav>

                    <div className="header__actions">

                        <button
                            className="calendarButton"
                            aria-label="Calendar"
                        >
                            <FaCalendarAlt />
                        </button>

                        <a
                            href="https://youtube.com/@rwitcltd9390/shorts"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="liveStreamButton"
                        >
                            Watch Live Stream
                        </a>

                        <button
                            className="menuButton"
                            aria-label="Menu"
                            onClick={() => setMenuOpen(true)}
                        >
                            <FaBars />
                        </button>

                    </div>

                </div>

            </div>

            {/* MOBILE SLIDE MENU */}
            <div className={`mobileMenu ${menuOpen ? "open" : ""}`}>
                <div className="mobileMenuHeader">
                    <img src="/image/rwitc_logo_white.png" alt="RWITC" className="mobileLogo" />
                    <button
                        className="mobileCloseButton"
                        aria-label="Close menu"
                        onClick={() => setMenuOpen(false)}
                    >
                        <FaTimes />
                    </button>
                </div>

                <div className="mobileMenuLinks">
                    {navItems.map((item) => (
                        item.children ? (
                            <div className="mobileDropdownWrap" key={item.key}>
                                <button
                                    type="button"
                                    className="mobileNavItem"
                                    onClick={() => toggleDropdown(item.key)}
                                >
                                    {item.label}
                                    <FaChevronDown
                                        className={`chevronIcon ${openDropdown === item.key ? "rotated" : ""}`}
                                    />
                                </button>
                                {openDropdown === item.key && (
                                    <div className="mobileDropdownMenu">
                                        {item.children.map((child) => (
                                            <a href={child.href} className="mobileDropdownLink" key={child.label}>
                                                {child.label}
                                            </a>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ) : (

                            <a href={item.href}
                                className="mobileNavItem"
                                key={item.label}
                                onClick={() => setMenuOpen(false)}
                            >
                                {item.label}
                            </a>
                        )
                    ))}


                    <a href="https://youtube.com/@rwitcltd9390/shorts"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="mobileLiveStreamButton"
                    >
                        Watch Live Stream
                    </a>
                </div>
            </div>

            {/* OVERLAY */}
            {menuOpen && (
                <div className="mobileOverlay" onClick={() => setMenuOpen(false)} />
            )}

        </header>
    );
}