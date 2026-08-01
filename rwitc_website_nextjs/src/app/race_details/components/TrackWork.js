"use client";

import { useState } from "react";
import {
    FaCalendarAlt,
    FaSearch,
    FaChevronUp,
    FaChevronDown,
    FaFlag,
} from "react-icons/fa";
import { GiHorseHead } from "react-icons/gi";
import "./TrackWork.css";

// TODO: replace with real API data once the trackwork endpoint is ready.
// Two tracks share the exact same shape — "Select Track" radio switches
// which one is displayed, per the requested feedback. Heading always
// stays "Inner Sand Track" regardless of the selection.
const TRACKWORK_DATA = {
    inner: {
        distances: [
            {
                id: "600",
                label: "600 METRES",
                horses: [
                    { id: 1, name: "Axlrod", jockey: "T.S.Jodha", time: "600/35." },
                ],
            },
            {
                id: "800",
                label: "800 METRES",
                horses: [
                    { id: 1, name: "Diego Garcia", jockey: "Shrikant", time: "55, 600/41." },
                    { id: 2, name: "Tropical Paradise", jockey: "Parmar", time: "57, 600/42.", tag: "Pair moved together." },
                    { id: 3, name: "Midnight Express", jockey: "Parmar", time: "51, 600/37." },
                    { id: 4, name: "Latios", jockey: "Prasad", time: "53, 600/39." },
                    { id: 5, name: "Liam", jockey: "Omkar", time: "56, 600/41." },
                    { id: 6, name: "Field Of Gold", jockey: "Shubham", time: "55, 600/40." },
                    { id: 7, name: "Monterey", jockey: "C.S.Jodha", withHorse: "Cashel King", withJockey: "Neeraj", time: "54, 600/41.", tag: "Pair level." },
                    { id: 8, name: "Styx", jockey: "T.S.Jodha", time: "55, 600/41." },
                ],
            },
            {
                id: "1000",
                label: "1000 METRES",
                horses: [
                    { id: 1, name: "Thalassa", jockey: "Umesh", time: "1-08, 800/54, 600/41." },
                    { id: 2, name: "Baille", jockey: "Gore", withHorse: "Dreams Come True", withJockey: "V.Walker", time: "1-09, 800/52, 600/38.", tag: "Former finished ahead." },
                    { id: 3, name: "Queen Of Beauties", jockey: "Akshay G", time: "1-07, 800/50, 600/38." },
                    { id: 4, name: "Desert Diamond", jockey: "Amyn", withHorse: "Echo", withJockey: "Umesh", time: "1-09, 800/54, 600/42.", tag: "Former finished ahead." },
                    { id: 5, name: "Escondio", jockey: "Mosin", time: "1-10, 800/54, 600/40." },
                    { id: 6, name: "Seneca", jockey: "Sandesh", time: "1-09, 800/54, 600/40." },
                    { id: 7, name: "Amazing Ruler", jockey: "Amyn", withHorse: "Undercover", withJockey: "Umesh", time: "1-09, 800/51, 600/38.", tag: "Pair level." },
                    { id: 8, name: "Endurance", jockey: "Yash", withHorse: "Dream Alliance", withJockey: "C.S.Jodha", time: "1-11, 800/56, 600/43.", tag: "Former finished ahead." },
                    { id: 9, name: "Gold Bar", jockey: "Ramswarup", time: "1-07, 800/52, 600/39." },
                    { id: 10, name: "Scaramouche", jockey: "T.S.Jodha", time: "1-08, 800/53, 600/40." },
                    { id: 11, name: "Mansa Musa", jockey: "T.S.Jodha", time: "1-08, 800/53, 600/39." },
                ],
            },
            {
                id: "1200",
                label: "1200 METRES",
                horses: [
                    { id: 1, name: "Fighton", jockey: "P.Dhebe", time: "1-23, 1000/1-07, 800/51, 600/39." },
                    { id: 2, name: "Age Of Reason", jockey: "Neeraj", time: "1-26, 1000/1-11, 800/56, 600/42." },
                    { id: 3, name: "Bee Magical", jockey: "Yash", withHorse: "Minari", withJockey: "Neeraj", time: "1-21, 1000/1-08, 800/54, 600/41.", tag: "Former finished ahead." },
                    { id: 4, name: "Pinnacle", jockey: "Yash", time: "1-20, 1000/1-06, 800/52, 600/40." },
                    { id: 5, name: "Break Point", jockey: "Kaviraj", time: "1-26, 1000/1-09, 800/52, 600/40." },
                ],
            },
        ],
        bannedMockRace: {
            title: "BANNED MOCK RACE – 1200 METRES",
            entries: "BIG BAY (S.J.Sunil), SHAMARA (P.Dhebe), FUNNY BUNNY (T.S.Jodha), SABALENKA (Akshay G), G VALENTINO (Shrikant), EXOTIC STAR (S.Mosin), JURACAN (C.S.Jodha), RENAISSANCE (A.S.Peter), DANCING STAR (J.Chinoy), ESTEBAN (Avinash), RAFAEL – Wdrn. : won by 7 lgths in 1-10.525 secs.",
        },
        openMockRace: {
            title: "OPEN MOCK RACE – 1200 METRES",
            entries: "EARTH (H.Gore), NEBULA (T.S.Jodha), FOXY (P.Vinod), COFFEE AT ELEVEN (Prasad), GOLDEN AXL (J.Chinoy), ENRICH (Suraj N), MIAMI VICE (Parmar), RED ROSE (Akshay G), INQUILAB (P.Dhebe), AQUILIUS (Siddharth), SON OF A GUN – Wdrn. : won by Nose in 1-11.291 secs.",
        },
    },
    outer: {
        distances: [
            {
                id: "600",
                label: "600 METRES",
                horses: [
                    { id: 1, name: "Sample Horse A", jockey: "R.Kumar", time: "600/36." },
                ],
            },
            {
                id: "800",
                label: "800 METRES",
                horses: [
                    { id: 1, name: "Sample Horse B", jockey: "S.Mehta", time: "56, 600/40." },
                ],
            },
        ],
        bannedMockRace: {
            title: "BANNED MOCK RACE – 1200 METRES",
            entries: "No banned mock race recorded for the outer sand track on this date.",
        },
        openMockRace: {
            title: "OPEN MOCK RACE – 1200 METRES",
            entries: "No open mock race recorded for the outer sand track on this date.",
        },
    },
};

function toInputDate(dateStr) {
    if (!dateStr) return new Date().toISOString().slice(0, 10);
    const d = new Date(dateStr);
    if (isNaN(d)) return new Date().toISOString().slice(0, 10);
    return d.toISOString().slice(0, 10);
}

function toBadgeDate(dateStr) {
    const d = dateStr ? new Date(dateStr) : new Date();
    if (isNaN(d)) return "";
    return d
        .toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        })
        .toUpperCase();
}

export default function TrackWork() {
    const [inputDate, setInputDate] = useState(toInputDate());
    const [appliedDate, setAppliedDate] = useState(toInputDate());
    const [horseSearch, setHorseSearch] = useState("");
    const [selectedTrack, setSelectedTrack] = useState("inner");
    const [cardOpen, setCardOpen] = useState(true);
    const [openSections, setOpenSections] = useState({
        "600": true,
        "800": true,
        "1000": true,
        "1200": true,
    });

    const trackData = TRACKWORK_DATA[selectedTrack];

    const toggleSection = (id) => {
        setOpenSections((prev) => ({ ...prev, [id]: !prev[id] }));
    };

    const handleDateSearch = () => {
        setAppliedDate(inputDate);
        // TODO: trigger real API fetch by date here
    };

    const handleHorseSearch = () => {
        // TODO: trigger real API fetch by horse name here
    };

    const filterHorses = (horses) => {
        if (!horseSearch.trim()) return horses;
        const term = horseSearch.trim().toLowerCase();
        return horses.filter(
            (h) =>
                h.name.toLowerCase().includes(term) ||
                (h.withHorse && h.withHorse.toLowerCase().includes(term))
        );
    };

    return (
        <section className="trackWorkSection">
            <div className="trackWorkHeader">
                <h1 className="trackWorkTitle">Track Work</h1>
                <div className="trackWorkDivider">
                    <span className="dividerLine" />
                    <GiHorseHead className="horseIcon" />
                    <span className="dividerLine" />
                </div>
            </div>

            <div className="searchBarsRow">
                <div className="searchBarGroup">
                    <div className="dateInputWrap">
                        <FaCalendarAlt className="inputIcon" />
                        <input
                            type="date"
                            className="dateInput"
                            value={inputDate}
                            onChange={(e) => setInputDate(e.target.value)}
                        />
                    </div>
                    <button type="button" className="searchBtnDark" onClick={handleDateSearch}>
                        Search
                    </button>
                </div>

                <div className="searchBarGroup">
                    <div className="textInputWrap">
                        <FaSearch className="inputIcon" />
                        <input
                            type="text"
                            className="textInput"
                            placeholder="Search Trackwork By Horsename"
                            value={horseSearch}
                            onChange={(e) => setHorseSearch(e.target.value)}
                        />
                    </div>
                    <button type="button" className="searchBtnGold" onClick={handleHorseSearch}>
                        Search
                    </button>
                </div>
            </div>

            <div className="trackworkBadgeWrap">
                <span className="trackworkBadge">
                    Trackwork for {toBadgeDate(appliedDate)}
                </span>
            </div>

            <div className="trackWorkCard">
                <div
                    className="trackCardHeader"
                    onClick={() => setCardOpen((prev) => !prev)}
                >
                    <span className="trackCardHeaderLeft">
                        Inner Sand Track
                    </span>
                    {cardOpen ? <FaChevronUp /> : <FaChevronDown />}
                </div>

                {cardOpen && (
                    <>
                        <div className="selectTrackRow" onClick={(e) => e.stopPropagation()}>
                            <span className="selectTrackLabel">Select Track :</span>

                            <label className="radioOption">
                                <input
                                    type="radio"
                                    name="trackSelect"
                                    checked={selectedTrack === "inner"}
                                    onChange={() => setSelectedTrack("inner")}
                                />
                                <span className="radioCustom" />
                                Inner Sand Track
                            </label>

                            <label className="radioOption">
                                <input
                                    type="radio"
                                    name="trackSelect"
                                    checked={selectedTrack === "outer"}
                                    onChange={() => setSelectedTrack("outer")}
                                />
                                <span className="radioCustom" />
                                Outer Sand Track
                            </label>
                        </div>

                        {trackData.distances.map((section) => {
                            const filtered = filterHorses(section.horses);
                            if (horseSearch.trim() && filtered.length === 0) return null;

                            return (
                                <div className="distanceSection" key={section.id}>
                                    <div
                                        className="distanceHeader"
                                        onClick={() => toggleSection(section.id)}
                                    >
                                        <span className="distanceHeaderLeft">
                                            {section.label}
                                        </span>
                                        {openSections[section.id] ? (
                                            <FaChevronUp />
                                        ) : (
                                            <FaChevronDown />
                                        )}
                                    </div>

                                    {openSections[section.id] && (
                                        <ol className="horseList">
                                            {filtered.map((horse) => (
                                                <li className="horseRow" key={horse.id}>
                                                    <span className="horseName">
                                                        {horse.name}{" "}
                                                        <span className="jockeyName">
                                                            ({horse.jockey})
                                                        </span>
                                                        {horse.withHorse && (
                                                            <>
                                                                {" "}
                                                                {horse.withHorse}{" "}
                                                                <span className="jockeyName">
                                                                    ({horse.withJockey})
                                                                </span>
                                                            </>
                                                        )}
                                                    </span>

                                                    <span className="horseMeta">
                                                        <span className="horseTime">{horse.time}</span>
                                                        {horse.tag && (
                                                            <span className="horseTag">{horse.tag}</span>
                                                        )}
                                                    </span>
                                                </li>
                                            ))}
                                        </ol>
                                    )}
                                </div>
                            );
                        })}

                        <div className="mockRaceCard">
                            <div className="mockRaceHeader">
                                <FaFlag className="mockRaceIcon" />
                                {trackData.bannedMockRace.title}
                            </div>
                            <p className="mockRaceEntries">
                                {trackData.bannedMockRace.entries}
                            </p>
                        </div>

                        <div className="mockRaceCard">
                            <div className="mockRaceHeader">
                                <FaFlag className="mockRaceIcon" />
                                {trackData.openMockRace.title}
                            </div>
                            <p className="mockRaceEntries">
                                {trackData.openMockRace.entries}
                            </p>
                        </div>

                        <div className="bottomDivider">
                            <span className="dividerLine" />
                            <GiHorseHead className="horseIconGold" />
                            <span className="dividerLine" />
                        </div>
                    </>
                )}
            </div>
        </section>
    );
}