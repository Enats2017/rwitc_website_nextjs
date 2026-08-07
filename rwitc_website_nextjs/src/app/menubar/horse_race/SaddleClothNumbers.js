"use client";

import { useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import "./SaddleClothNumbers.css";

// DUMMY DATA — replace this array with the API response later.
const saddleClothSections = [
    {
        clothLabel: "WHITE lettering on GREEN Cloth",
        generatedOn: "24-07-2026 10:30 AM",
        rows: [
            { saddle: 1, horse: "AXLROD", colorSex: "ch g", age: 6, sire: "Quasar", dame: "Shivalik Heroine", trainer: "Nosher Cama" },
            { saddle: 2, horse: "ABSOLUTE EMPEROR", colorSex: "b c", age: 3, sire: "Profitable", dame: "Partitia[GB]", trainer: "P. S. Chouhan" },
            { saddle: 3, horse: "ELOQUENT", colorSex: "dk b g", age: 6, sire: "Speaking of Which[IRE]", dame: "Auntie Kathryn[IRE]", trainer: "Shazaan Shah" },
            { saddle: 4, horse: "ADONIS", colorSex: "ch g", age: 7, sire: "Shifting Power[GB]", dame: "Ainra", trainer: "Vijay Kasbekar" },
            { saddle: 5, horse: "ABHYANKAR", colorSex: "ch g", age: 4, sire: "Planetaire[GB]", dame: "Conscience", trainer: "M. Narredu" },
            { saddle: 6, horse: "ABSOLUTE HONOUR", colorSex: "b (mtg) f", age: 3, sire: "Charmo[FR]", dame: "Advantage Logan", trainer: "P. S. Chouhan" },
            { saddle: 8, horse: "YOUREMANORBORNE", colorSex: "b f", age: 4, sire: "Moonlight Magic[GB]", dame: "To The Manor Born", trainer: "P. S. Chouhan" },
            { saddle: 9, horse: "GALLANT PRIDE", colorSex: "b c", age: 4, sire: "Roman Rule", dame: "Gallant Heart", trainer: "Nosher Cama" },
            { saddle: 10, horse: "MYSTIC FALCON", colorSex: "ch f", age: 5, sire: "Skyline[GB]", dame: "Falcon Wing", trainer: "M. Narredu" },
            { saddle: 11, horse: "SILVER CREST", colorSex: "gr g", age: 6, sire: "Crestwood[IRE]", dame: "Silver Belle", trainer: "Vijay Kasbekar" },
            { saddle: 12, horse: "CRIMSON TIDE", colorSex: "b f", age: 3, sire: "High Tide[USA]", dame: "Crimson Rose", trainer: "Shazaan Shah" },
            { saddle: 13, horse: "NOBLE VENTURE", colorSex: "b c", age: 4, sire: "Venture Capital", dame: "Noble Grace", trainer: "P. S. Chouhan" },
        ],
    },
    {
        clothLabel: "White lettering on RED Cloth",
        generatedOn: "24-07-2026 10:31 AM",
        rows: [
            { saddle: 1, horse: "REYNOSA", colorSex: "b c", age: 2, sire: "Top Class[USA]", dame: "Euphrates", trainer: "Adhirajsingh Jodha" },
            { saddle: 2, horse: "ADJUDICATE/2024", colorSex: "dk b c", age: 2, sire: "Win Legend[JPN]", dame: "Adjudicate", trainer: "Adhirajsingh Jodha" },
            { saddle: 3, horse: "STELLAR QUEST", colorSex: "b c", age: 2, sire: "Phoenix Tower[USA]", dame: "Jolie Rue[USA]", trainer: "Adhirajsingh Jodha" },
            { saddle: 4, horse: "SANTA CLARA/2024", colorSex: "b f", age: 2, sire: "Fiero[JPN]", dame: "Santa Clara", trainer: "Adhirajsingh Jodha" },
            { saddle: 5, horse: "NORTHERN COAST", colorSex: "b c", age: 2, sire: "Fiero[JPN]", dame: "Costa Rica", trainer: "Adhirajsingh Jodha" },
            { saddle: 6, horse: "JAZZ", colorSex: "ch f", age: 2, sire: "Win Legend[JPN]", dame: "Zana", trainer: "Adhirajsingh Jodha" },
            { saddle: 7, horse: "AMBER SKY", colorSex: "b f", age: 2, sire: "Top Class[USA]", dame: "Amber Light", trainer: "Adhirajsingh Jodha" },
            { saddle: 8, horse: "DESERT ROSE/2024", colorSex: "ch f", age: 2, sire: "Fiero[JPN]", dame: "Desert Bloom", trainer: "Adhirajsingh Jodha" },
            { saddle: 9, horse: "ROYAL EMBER", colorSex: "b c", age: 2, sire: "Win Legend[JPN]", dame: "Ember Glow", trainer: "Adhirajsingh Jodha" },
            { saddle: 10, horse: "MIDNIGHT PEARL", colorSex: "dk b f", age: 2, sire: "Phoenix Tower[USA]", dame: "Pearl Drop", trainer: "Adhirajsingh Jodha" },
            { saddle: 11, horse: "GOLDEN HORIZON", colorSex: "ch c", age: 2, sire: "Top Class[USA]", dame: "Horizon Line", trainer: "Adhirajsingh Jodha" },
            { saddle: 726, horse: "SORA", colorSex: "b f", age: 3, sire: "Leitir Mor[IRE]", dame: "Phantasmagoric[IRE]", trainer: "Aman Altaf Hussain" },
        ],
    },
    {
        clothLabel: "White lettering on BLUE Cloth",
        generatedOn: "24-07-2026 10:32 AM",
        rows: [
            { saddle: 2001, horse: "STARLIGHT SERENADE", colorSex: "b f", age: 3, sire: "Sporting Chance[GB]", dame: "Indian Empress", trainer: "Prasanna Kumar P." },
            { saddle: 2004, horse: "NOBLE TITAN", colorSex: "b c", age: 3, sire: "Arod[IRE]", dame: "Platts Tour", trainer: "Prasanna Kumar P." },
            { saddle: 2005, horse: "ALLSETTOGO/2024", colorSex: "b c", age: 2, sire: "Planetaire[GB]", dame: "Allsettogo", trainer: "Prasanna Kumar P." },
            { saddle: 2007, horse: "DAPPLE DANCER", colorSex: "b f", age: 3, sire: "Cougar Mountain[IRE]", dame: "Pointillist[IRE]", trainer: "Prasanna Kumar P." },
            { saddle: 2008, horse: "BRAVION", colorSex: "b g", age: 3, sire: "Sporting Chance[GB]", dame: "Invicta", trainer: "Prasanna Kumar P." },
            { saddle: 2009, horse: "BABUSHKA", colorSex: "b f", age: 2, sire: "Saamidd[GB]", dame: "Babushka", trainer: "Prasanna Kumar P." },
            { saddle: 2010, horse: "VELVET QUEEN/2024", colorSex: "b f", age: 2, sire: "Chinese Whisper[IRE]", dame: "Velvet Queen", trainer: "Prasanna Kumar P." },
            { saddle: 2014, horse: "ZUT ALORS/2024", colorSex: "b f", age: 2, sire: "Cougar Mountain[IRE]", dame: "Zut Alors", trainer: "Prasanna Kumar P." },
            { saddle: 291, horse: "MOTHER'S PRIDE/2024", colorSex: "ch f", age: 2, sire: "Western Aristocrat[USA]", dame: "Mother's Pride", trainer: "P. S. Chouhan" },
            { saddle: 292, horse: "EXPECT[GB] /2024", colorSex: "b f", age: 2, sire: "Western Aristocrat[USA]", dame: "Expect[GB]", trainer: "P. S. Chouhan" },
            { saddle: 2018, horse: "BLUE HORIZON", colorSex: "b c", age: 3, sire: "Arod[IRE]", dame: "Skyline View", trainer: "Prasanna Kumar P." },
            { saddle: 2021, horse: "ROYAL SAPPHIRE", colorSex: "b f", age: 2, sire: "Saamidd[GB]", dame: "Sapphire Dream", trainer: "Prasanna Kumar P." },
        ],
    },
];

const VISIBLE_COUNT = 10;

export default function SaddleClothNumbers() {
    const [expanded, setExpanded] = useState({});

    const toggleSection = (index) => {
        setExpanded((prev) => ({ ...prev, [index]: !prev[index] }));
    };

    return (
        <section className="aboutPage">
            <div className="aboutContainer">

                {/* FIXED HEADER — now matches the same heading style
                    used across the site (h1 + underline divider) */}
                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Saddle Cloth</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                {saddleClothSections.map((section, sectionIndex) => {
                    const isExpanded = !!expanded[sectionIndex];
                    const visibleRows = isExpanded
                        ? section.rows
                        : section.rows.slice(0, VISIBLE_COUNT);
                    const hasMore = section.rows.length > VISIBLE_COUNT;

                    return (
                        <div className="aboutCard saddleSection" key={sectionIndex}>

                            <div className="saddleTitleBlock">
                                <p className="saddleClubName">
                                    Royal Western India Turf Club, Ltd.
                                </p>
                                <p className="saddleSubTitle">Saddle Cloth Number Report</p>
                                <p className="saddleGeneratedOn">
                                    Generated On : {section.generatedOn}
                                </p>
                            </div>

                            <div className="statsTableWrap">
                                <table className="statsTable saddleTable">
                                    <thead>
                                        <tr>
                                            <th colSpan={7} className="clothLabelRow">
                                                {section.clothLabel}
                                            </th>
                                        </tr>
                                        <tr>
                                            <th className="colSaddle">Saddle</th>
                                            <th className="colHorseName">Horse Name</th>
                                            <th className="colColorSex">Color/Sex</th>
                                            <th className="colAge">Age</th>
                                            <th className="colSire">Sire Name</th>
                                            <th className="colDame">Dame Name</th>
                                            <th className="colTrainer">Trainer Name</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {visibleRows.map((row, rowIndex) => (
                                            <tr key={rowIndex}>
                                                <td className="colSaddle">{row.saddle}</td>
                                                <td className="colHorseName">{row.horse}</td>
                                                <td className="colColorSex">{row.colorSex}</td>
                                                <td className="colAge">{row.age}</td>
                                                <td className="colSire">{row.sire}</td>
                                                <td className="colDame">{row.dame}</td>
                                                <td className="colTrainer">{row.trainer}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {hasMore && (
                                <div className="viewMoreWrap">
                                    <button
                                        type="button"
                                        className="viewMoreBtn"
                                        onClick={() => toggleSection(sectionIndex)}
                                    >
                                        {isExpanded ? "View Less" : "View More"}
                                    </button>
                                </div>
                            )}

                        </div>
                    );
                })}

            </div>
        </section>
    );
}