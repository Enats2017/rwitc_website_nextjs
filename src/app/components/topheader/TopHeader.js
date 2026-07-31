"use client";
import { useState, useRef, useEffect } from "react";
import Link from "next/link";
import "./TopHeader.css";
import { UPLOAD_URL } from "../../../services/api";
import {
    FaBars,
    FaTimes,
    FaCalendarAlt,
    FaChevronDown,
    FaPlay,
    FaHome,
    FaUniversity,
    FaHorseHead,
    FaTags,
    FaUsers,
    FaStar,
    FaHandshake,
    FaCloudDownloadAlt,
} from "react-icons/fa";

/* ---------- DESKTOP: nested submenu — CLICK ONLY, vertical ---------- */
function DesktopDropdownList({ items, level = 0 }) {
    const [openKey, setOpenKey] = useState(null);
    const [selectedLeaf, setSelectedLeaf] = useState(null);

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
                                className={`navDropdownLink navDropdownLinkParent ${openKey === key ? "active" : ""}`}
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
                    
                    <a    href={item.href}
                        className={`navDropdownLink ${selectedLeaf === key ? "active" : ""}`}
                        key={key}
                        onClick={() => setSelectedLeaf(key)}
                    >
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
    const [activeNav, setActiveNav] = useState("home");
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

    useEffect(() => {
        const handleOutsideClick = (e) => {
            if (!e.target.closest(".navDropdownWrap")) {
                setDesktopOpenDropdown(null);
            }
        };
        document.addEventListener("click", handleOutsideClick);
        return () => document.removeEventListener("click", handleOutsideClick);
    }, []);
    // FIX: reset mobile menu + all dropdown states when switching between
    // mobile <-> desktop widths, so nothing stays stuck open on resize
    useEffect(() => {
        const handleResize = () => {
            if (window.innerWidth > 992) {
                setMenuOpen(false);
                setOpenDropdown(null);
            } else {
                setDesktopOpenDropdown(null);
            }
        };
        window.addEventListener("resize", handleResize);
        return () => window.removeEventListener("resize", handleResize);
    }, []);
    // Nav items now carry their own icon for both desktop icon-row and mobile menu list
    const navItems = [
        // { label: "Home", key: "home", href: "/", icon: <FaHome /> },
        {
            label: "Club",
            key: "club",
            icon: <FaUniversity />,
            children: [
                { label: "About Us", href: "/club/about" },
                { label: "History", href: "/club/history" },
                {
                    label: "Committee",
                    key: "committee",
                    children: [
                        { label: "Managing Committee", href: "/club/committee/managing" },
                        { label: "Stewards", href: "/club/committee/stewards" },
                    ],
                },
                { label: "Contact", href: "/club/contact" },
            ],
        },
        {
            label: "Racing",
            key: "racing",
            icon: <FaHorseHead />,
            children: [
                {
                    label: "Race Card",
                    key: "race-card",
                    children: [
                        { label: "Today's Race Card", href: "/racing/race-card/today" },
                        { label: "Tomorrow's Race Card", href: "/racing/race-card/tomorrow" },
                        { label: "Race Card Archive", href: "/racing/race-card/archive" },
                    ],
                },
                { label: "Racing Fixtures", href: "/racing/fixtures" },
                { label: "Results", href: "/racing/results" },
            ],
        },
        {
            label: "Wagering",
            key: "wagering",
            icon: <FaTags />,
            children: [
                { label: "Tote Dividends", href: "/wagering/tote-dividends" },
                { label: "Betting Rules", href: "/wagering/betting-rules" },
                {
                    label: "Money Leaders",
                    key: "money-leaders",
                    children: [
                        { label: "Owners", href: "/wagering/money-leaders/owners" },
                        { label: "Jockeys", href: "/wagering/money-leaders/jockeys" },
                        { label: "Trainers", href: "/wagering/money-leaders/trainers" },
                    ],
                },
            ],
        },
        {
            label: "Membership",
            key: "membership",
            icon: <FaUsers />,
            children: [
                { label: "Membership Types", href: "/membership/types" },
                { label: "How to Apply", href: "/membership/apply" },
                { label: "Membership Fees", href: "/membership/fees" },
            ],
        },
        {
            label: "Experience",
            key: "experience",
            icon: <FaStar />,
            children: [
                { label: "Dining", href: "/experience/dining" },
                { label: "Events", href: "/experience/events" },
                { label: "Facilities", href: "/experience/facilities" },
            ],
        },
        {
            label: "Partners",
            key: "partners",
            icon: <FaHandshake />,
            children: [
                { label: "Sponsors", href: "/partners/sponsors" },
                { label: "Hospitality Partners", href: "/partners/hospitality" },
            ],
        },
        {
            label: "Download",
            key: "downloads",
            icon: <FaCloudDownloadAlt />,
            children: [
                { label: "Race Card PDF", href: "/downloads/race-card" },
                { label: "Membership Form", href: "/downloads/membership-form" },
                { label: "Annual Report", href: "/downloads/annual-report" },
            ],
        },
    ];

    return (
        <header className="header">

            {/* ROW 1: LOGO + BACKGROUND STRIP + CALENDAR/LIVE STREAM BUTTONS */}
            <div className="headerTopRow">

                {/* Mobile-only hamburger, sits left of the logo on small screens */}
                <button className="menuButton" aria-label="Menu" onClick={() => setMenuOpen(true)}>
                    <FaBars />
                </button>

                <Link href="/" className="brandLink">
                    <div className="logo">
                        <img src={`${UPLOAD_URL}/rwitc_logo_white.png`} alt="RWITC Logo" draggable="false" />
                    </div>
                    <div className="clubInfo">
                        <h1 className="clubNameShort">RWITC</h1>
                        <p className="clubNameFull">Royal Western India Turf Club Ltd.</p>
                    </div>
                </Link>

                <div className="headerTopActions">
                    <button className="calendarButton" aria-label="Calendar">
                        <FaCalendarAlt />
                        <span className="calendarButtonText">Calendar</span>
                    </button>

                   <a href="#" target="_blank" rel="noopener noreferrer" className="headerLiveBtn" aria-label="Watch Live Stream">
    <FaPlay className="headerLiveBtnIcon" />
    <span className="headerLiveBtnText">Live Stream</span>
    <span className="headerLiveBtnTextShort">Live</span>
</a>
                </div>

            </div>

            {/* ROW 2: ICON NAV ROW (desktop only) */}
            <nav className="navLinks">
                {navItems.map((item) =>
                    item.children ? (
                        <div
                            className="navDropdownWrap"
                            key={item.key}
                            onMouseEnter={() => handleDropdownEnter(item.key)}
                            onMouseLeave={handleDropdownLeave}
                        >
                            <button
                                type="button"
                                className={`navItem navItemDropdown ${activeNav === item.key ? "active" : ""}`}
                                onClick={() => {
                                    setActiveNav(item.key);
                                    setDesktopOpenDropdown(desktopOpenDropdown === item.key ? null : item.key);
                                }}
                            >
                                <span className="navItemIcon">{item.icon}</span>
                                {item.label}
                                <FaChevronDown className="navItemChevron" />
                            </button>
                            {desktopOpenDropdown === item.key && (
                                <DesktopDropdownList items={item.children} level={0} />
                            )}
                        </div>
                    ) : (
                        
                        <a    href={item.href}
                            className={`navItem ${activeNav === item.key ? "active" : ""}`}
                            key={item.key}
                            onClick={() => setActiveNav(item.key)}
                        >
                            <span className="navItemIcon">{item.icon}</span>
                            {item.label}
                        </a>
                    )
                )}
            </nav>

            {/* MOBILE SLIDE MENU */}
            <div className={`mobileMenu ${menuOpen ? "open" : ""}`}>
                <div className="mobileMenuHeader">
                    <div className="mobileMenuBrand">
                        <img src={`${UPLOAD_URL}/rwitc_logo_white.png`} alt="RWITC" className="mobileLogo" />
                        <div className="mobileClubInfo">
                            <h2 className="mobileClubNameShort">RWITC</h2>
                            <p className="mobileClubNameFull">Royal Western India Turf Club Ltd</p>
                        </div>
                    </div>
                    <button className="mobileCloseButton" aria-label="Close menu" onClick={() => setMenuOpen(false)}>
                        <FaTimes />
                    </button>
                </div>

                <div className="mobileMenuLinks">
                    {navItems.map((item) =>
                        item.children ? (
                            <div className="mobileDropdownWrap" key={item.key}>
                                <button type="button" className="mobileNavItem" onClick={() => toggleDropdown(item.key)}>
                                    <span className="mobileNavItemLeft">
                                        <span className="mobileNavItemIcon">{item.icon}</span>
                                        {item.label}
                                    </span>
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
                            
                            <a    href={item.href}
                                className="mobileNavItem"
                                key={item.key}
                                onClick={() => setMenuOpen(false)}
                            >
                                <span className="mobileNavItemLeft">
                                    <span className="mobileNavItemIcon">{item.icon}</span>
                                    {item.label}
                                </span>
                            </a>
                        )
                    )}
                </div>
            </div>

            {menuOpen && <div className="mobileOverlay" onClick={() => setMenuOpen(false)} />}

        </header>
    );
}