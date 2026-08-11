"use client";
import { useState, useRef, useEffect } from "react";
import Link from "next/link";
import "./TopHeader.css";
import { UPLOAD_URL, NEWS_URL, NEW_URL, RWITC_UPLOAD_URL, STATIC_URL } from "../../../services/api";
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
    FaImages,
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
                    <a href={item.href}
                        target={item.target || undefined}
                        rel={item.target === "_blank" ? "noopener noreferrer" : undefined}
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
                    <a href={item.href}
                        target={item.target || undefined}
                        rel={item.target === "_blank" ? "noopener noreferrer" : undefined}
                        className="mobileDropdownLink"
                        key={key}
                        onClick={onNavigate}
                    >
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
    const [hoverNav, setHoverNav] = useState(null);
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
                { label: "About RWITC", href: "menubar?type=about" },
                { label: "Vision & Mission", href: "menubar?type=vision-mission" },
                {
                    label: "Organization & Management",
                    key: "organization-management",
                    children: [
                        { label: "Structure", href: "menubar?type=structure" },
                        { label: "Managing Committee", href: "menubar?type=managing-committee" },
                        { label: "Stewards of the Club", href: "menubar?type=stewardsclub" },
                        { label: "Board of Appeal", href: "menubar?type=boardappeal" },
                        { label: "Working Group", href: `${NEWS_URL}/WORKINGGROUPS2023-24.pdf`, target: "_blank" },
                    ],
                },
                {
                    label: "History",
                    key: "history",
                    children: [
                        { label: "Timeline/Major Events", href: "menubar?type=timeline" },
                        { label: "Bequeathing a Colonial Legacy", href: "menubar?type=bequeathingcoloniallegacy" },
                    ],
                },
                {
                    label: "Charities",
                    key: "charities",
                    children: [
                        { label: "Charity Race Days", href: "menubar?type=charityracedays" },
                    ],
                },
                { label: "Contributing to the Community", href: "menubar?type=contributingcommunity" },
                { label: "Responsible Gambling", href: "menubar?type=responsible-gambling" },
                { label: "Careers", href: "menubar?type=careersclub" },
                { label: "Feedback", href: "suggestion" },
                { label: "Email to Chairman", href: "contact" },
                { label: "MGT-7", href: `${NEW_URL}/RWITC_MGT-7.pdf`, target: "_blank" },
                { label: "Register of Directors", href: `${NEW_URL}/Register_of_Directors.pdf`, target: "_blank" },
                { label: "Register of Contracts - I", href: `${NEW_URL}/Register_of_Contracts-I.pdf`, target: "_blank" },
                { label: "Register of Contracts - II", href: `${NEW_URL}/Register_of_Contracts-Part-II.pdf`, target: "_blank" },
                { label: "Notice for the 110th AGM", href: `${NEWS_URL}/AGM_Notice_18_09_2025.pdf`, target: "_blank" },
                { label: "110th Annual Report", href: `${NEWS_URL}/110th_Clubs_Annual_Report_March_31_2025.pdf`, target: "_blank" },
                { label: "Scrutinizer's Report (AGM)", href: `${NEWS_URL}/Scrutinizers_Report.pdf`, target: "_blank" },
                { label: "EGM Notice", href: `${RWITC_UPLOAD_URL}/EGM_Notice_30_01_2026.pdf`, target: "_blank" },
                { label: "Proceedings of EGM", href: `${RWITC_UPLOAD_URL}/Meeting.mp4`, target: "_blank" },
                { label: "Scrutinizer's Report (EGM)", href: `${NEWS_URL}/Scrutinisers_Report-EGM.pdf`, target: "_blank" },
            ],
        },
        {
            label: "Horse Racing",
            key: "racing",
            icon: <FaHorseHead />,
            children: [
                { label: "Medication Rules 2024", href: `${NEW_URL}/Medication Rules.pdf`, target: "_blank" },
                { label: "Beginners Guide", href: "menubar?type=beginners-guide" },
                { label: "Sweepstake Entries", href: "race_details?type=sweepstakes" },
                { label: "Rules of Racing", href: `${NEW_URL}/rulesofracing2025.pdf`, target: "_blank" },
                { label: "Racing Calendar", href: `${STATIC_URL}/racingCalendar.pdf`, target: "_blank" },
                { label: "Jockey's Statistics", href: "menubar?type=jockeystatistics" },
                { label: "Jockey's Riding Weight", href: "menubar?type=jockeyridingweight" },
                { label: "Trainer's Statistics", href: "menubar?type=trainerstatistics" },
                { label: "Memorandum & Articles of Association", href: `${NEW_URL}/memorandum2025.pdf`, target: "_blank" },
                { label: "Notice From Stewards", href: "menubar?type=noticefromstewards" },
                { label: "Ready Reckoner", href: "menubar?type=readyreckoner" },
                { label: "Body Weight of Horses", href: "menubar?type=bodyweighthorse" },
                { label: "Record Timings", href: "menubar?type=recordtimings" },
                { label: "Standard Timings", href: `${NEW_URL}/standard_timings.pdf`, target: "_blank" },
                { label: "Saddle Cloth Numbers", href: "menubar?type=saddleclothnumbers" },

            ],
        },
        {
            label: "Wagering",
            key: "wagering",
            icon: <FaTags />,
            children: [
                { label: "Overview", href: "menubar?type=wageringoverview" },
                { label: "Beginners luck in Racing", href: "menubar?type=beginnersluckracing" },
                { label: "Wagering Terms", href: "menubar?type=wageringterms" },
                { label: "Betting Pools", href: "menubar?type=betting-pools" },
                { label: "National Tote Pools", href: "menubar?type=national-tote-pools" },
                { label: "Betting Channels", href: "menubar?type=betting-channels" },
                { label: "Deduction Norms", href: "menubar?type=deduction-norms" },
            ],
        },
        {
            label: "Membership",
            key: "membership",
            icon: <FaUsers />,
            children: [
                { label: "Overview", href: "menubar?type=membership-overview" },
                { label: "Club Membership Privileges", href: "menubar?type=club-membership-privileges" },
                { label: "Categories", href: "menubar?type=membership-categories" },
                { label: "Affiliated Clubs with RWITC", href: `${NEWS_URL}/Affiliation.pdf`, target: "_blank" },
            ],
        },
        {
            label: "Come Racing",
            key: "comeracing",
            icon: <FaStar />,
            children: [
                { label: "Overview", href: "menubar?type=comeracing-overview" },
                { label: "Mumbai Race Course", href: "menubar?type=mumbai-race-course" },
                { label: "Pune Race Course", href: "menubar?type=pune-race-course" },
                { label: "How to get there", href: "menubar?type=how-to-get-there" },
                { label: "Race Course Services & Others", href: "menubar?type=race-course-services-others" },
            ],
        },
        {
            label: "Partners",
            key: "partners",
            icon: <FaHandshake />,
            children: [
                { label: "Overview", href: "menubar?type=partners-overview" },
                { label: "Sponsor's Privileges", href: "menubar?type=sponsor-privileges" },
                { label: "Advertising & Sponsorship Opportunities", href: "menubar?type=partners-opportunities" },
                { label: "Contact Us", href: "menubar?type=partners-contact" },
            ],
        },
        {
            label: "Download",
            key: "downloads",
            icon: <FaCloudDownloadAlt />,
            children: [
                { label: "Forms", href: "menubar?type=application-forms" },
                { label: "Chart", href: `${NEW_URL}/aplication_forms/CHART.pdf`, target: "_blank" },
                { label: "Prospectus", href: `${NEW_URL}/aplication_forms/PROSPECTUS.pdf`, target: "_blank" },
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
                        <span className="calendarButtonText">Racing Fixtures</span>
                    </button>

                    <Link href="/race_details?type=photos" className="calendarButton" aria-label="Gallery">
                        <FaImages />
                        <span className="calendarButtonText">Gallery</span>
                    </Link>

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
                            onMouseEnter={() => {
                                setHoverNav(item.key);
                                handleDropdownEnter(item.key);
                            }}
                            onMouseLeave={() => {
                                setHoverNav(null);
                                handleDropdownLeave();
                            }}
                        >
                            <button
                                type="button"
                                className={`navItem navItemDropdown ${hoverNav
                                    ? hoverNav === item.key
                                        ? "active"
                                        : ""
                                    : activeNav === item.key
                                        ? "active"
                                        : ""
                                    }`}
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

                        <a href={item.href}
                            className={`navItem ${hoverNav
                                ? hoverNav === item.key
                                    ? "active"
                                    : ""
                                : activeNav === item.key
                                    ? "active"
                                    : ""
                                }`}
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

                            <a href={item.href}
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