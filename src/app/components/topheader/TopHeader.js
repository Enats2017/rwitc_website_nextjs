"use client";
import { useState, useRef } from "react";
import Link from "next/link";
import "./TopHeader.css";
import { UPLOAD_URL } from "../../../services/api";
import { FaBars, FaTimes, FaCalendarAlt, FaChevronDown, FaPlay } from "react-icons/fa";

/* ---------- DESKTOP: nested submenu — CLICK ONLY, vertical ---------- */
function DesktopDropdownList({ items, level = 0 }) {
    const [openKey, setOpenKey] = useState(null);

    const handleToggle = (key) => {
        setOpenKey(openKey === key ? null : key);
    };

    return (
        <div className={level === 0 ? "navDropdownMenu open" : "navSubMenu open"}>
            {items.map((item) => {
                const key = item.key || item.label;
                if (item.children) {
                    return (
                        <div className="navDropdownItemWrap" key={key}>
                            <button
                                type="button"
                                className="navDropdownLink navDropdownLinkParent"
                                onClick={() => handleToggle(key)}
                            >
                                {item.label}
                                <FaChevronDown className={`subChevron ${openKey === key ? "open" : ""}`} />
                            </button>
                            {openKey === key && (
                                <DesktopDropdownList items={item.children} level={level + 1} />
                            )}
                        </div>
                    );
                }
                return (
                    <a href={item.href} className="navDropdownLink" key={key}>
                        {item.label}
                    </a>
                );
            })}
        </div>
    );
}

/* ---------- MOBILE: recursive accordion (click-only, vertical nested) ---------- */
function MobileDropdownList({ items, onNavigate }) {
    const [openKeys, setOpenKeys] = useState({});

    const toggle = (key) => {
        setOpenKeys((prev) => ({ ...prev, [key]: !prev[key] }));
    };

    return (
        <div className="mobileDropdownInner">
            {items.map((item) => {
                const key = item.key || item.label;
                if (item.children) {
                    return (
                        <div className="mobileDropdownWrap" key={key}>
                            <button
                                type="button"
                                className="mobileDropdownLink mobileDropdownParent"
                                onClick={() => toggle(key)}
                            >
                                {item.label}
                                <FaChevronDown className={`chevronIcon ${openKeys[key] ? "rotated" : ""}`} />
                            </button>
                            <div className={`mobileSubMenu ${openKeys[key] ? "open" : ""}`}>
                                <MobileDropdownList items={item.children} onNavigate={onNavigate} />
                            </div>
                        </div>
                    );
                }
                return (
                    <a href={item.href} className="mobileDropdownLink" key={key} onClick={onNavigate}>
                        {item.label}
                    </a>
                );
            })}
        </div>
    );
}

export default function TopHeader() {
    const [menuOpen, setMenuOpen] = useState(false);
    const [openDropdown, setOpenDropdown] = useState(null);
    const [desktopOpenDropdown, setDesktopOpenDropdown] = useState(null);
    const closeTimerRef = useRef(null);

    const toggleDropdown = (name) => {
        setOpenDropdown(openDropdown === name ? null : name);
    };

    const handleDropdownEnter = (name) => {
        if (closeTimerRef.current) {
            clearTimeout(closeTimerRef.current);
            closeTimerRef.current = null;
        }
        setDesktopOpenDropdown(name);
    };

    const handleDropdownLeave = () => {
        closeTimerRef.current = setTimeout(() => {
            setDesktopOpenDropdown(null);
        }, 150);
    };

    const navItems = [
    { label: "The Club", href: "/rwitc-website" },
    { label: "Horse Racing", href: "/rwitc-website" },
    { label: "Betting &\nEntertainment", href: "/rwitc-website" },
    { label: "Membership", href: "/rwitc-website" },
    { label: "Come Racing", href: "/rwitc-website" },
    { label: "Advertising &\nSponsership", href: "/rwitc-website" },
    { label: "Downloads", href: "/rwitc-website" },
];

    return (
        <header className="header">
            <div className="header__wrapper">

                <Link href="/" className="brandLink">
                    <div className="logo">
                        <img src={`${UPLOAD_URL}/rwitc_logo_white.png`} alt="RWITC Logo" draggable="false" />
                    </div>
                    <div className="clubInfo">
                        <h2>Royal Western India Turf Club Ltd.</h2>
                    </div>
                </Link>

                <div className="rightGroup">

                    <nav className="navLinks">
                        {navItems.map((item) => (
                            item.children ? (
                                <div
                                    className="navDropdownWrap"
                                    key={item.key}
                                    onMouseEnter={() => handleDropdownEnter(item.key)}
                                    onMouseLeave={handleDropdownLeave}
                                >
                                    <button
                                        type="button"
                                        className="navItem navItemDropdown"
                                        onClick={() => setDesktopOpenDropdown(desktopOpenDropdown === item.key ? null : item.key)}
                                    >
                                        {item.label}
                                        <FaChevronDown className={`chevronIcon ${desktopOpenDropdown === item.key ? "open" : ""}`} />
                                    </button>
                                    {desktopOpenDropdown === item.key && (
                                        <DesktopDropdownList items={item.children} level={0} />
                                    )}
                                </div>
                            ) : (
                                <a href={item.href} className="navItem" key={item.label}>
                                    {item.label}
                                </a>
                            )
                        ))}
                    </nav>

                    <div className="header__actions">

                        <button className="calendarButton" aria-label="Calendar">
                            <FaCalendarAlt />
                        </button>

                        <a href="#" target="_blank" rel="noopener noreferrer" className="headerLiveBtn" aria-label="Watch Live Stream">
                            <FaPlay className="headerLiveBtnIcon" />
                            <span className="headerLiveBtnText">Watch Live Stream</span>
                        </a>

                        <button className="menuButton" aria-label="Menu" onClick={() => setMenuOpen(true)}>
                            <FaBars />
                        </button>

                    </div>

                </div>

            </div>

            {/* MOBILE SLIDE MENU */}
            <div className={`mobileMenu ${menuOpen ? "open" : ""}`}>
                <div className="mobileMenuHeader">
                    <img src={`${UPLOAD_URL}/rwitc_logo_white.png`} alt="RWITC" className="mobileLogo" />
                    <button className="mobileCloseButton" aria-label="Close menu" onClick={() => setMenuOpen(false)}>
                        <FaTimes />
                    </button>
                </div>

                <div className="mobileMenuLinks">
                    {navItems.map((item) => (
                        item.children ? (
                            <div className="mobileDropdownWrap" key={item.key}>
                                <button type="button" className="mobileNavItem" onClick={() => toggleDropdown(item.key)}>
                                    {item.label}
                                    <FaChevronDown className={`chevronIcon ${openDropdown === item.key ? "rotated" : ""}`} />
                                </button>
                                <div className={`mobileDropdownMenu ${openDropdown === item.key ? "open" : ""}`}>
                                    <MobileDropdownList
                                        items={item.children}
                                        onNavigate={() => setMenuOpen(false)}
                                    />
                                </div>
                            </div>
                        ) : (
                            <a href={item.href} className="mobileNavItem" key={item.label} onClick={() => setMenuOpen(false)}>
                                {item.label}
                            </a>
                        )
                    ))}

                    <a href="#" target="_blank" rel="noopener noreferrer" className="mobileLiveStreamButton">
                        Watch Live Stream
                    </a>
                </div>
            </div>

            {menuOpen && <div className="mobileOverlay" onClick={() => setMenuOpen(false)} />}

        </header>
    );
}