"use client";

import { FaHorseHead } from "react-icons/fa";
import "./RecordTimings.css";

// DUMMY DATA — replace with the API response later.
// Each Mumbai distance can have one or more record entries (rowspan
// handles the multi-entry distances like 1400 / 1600 Metres).
// A line can contain **bold** text — rendered as <strong>.
const mumbaiTimings = [
    {
        distance: "1000 Metres",
        entries: [
            { lines: ["Esperanza 50 kgs", "(16/02/25)", "56.513 secs."] },
        ],
    },
    {
        distance: "1100 Metres",
        entries: [
            { lines: ["American Eagle 59.5 kgs", "(30/03/26)", "1 m 03.573 secs"] },
        ],
    },
    {
        distance: "1200 Metres",
        entries: [
            { lines: ["Market King 58.5 kgs", "(17/12/23)", "1 m.08.657 secs"] },
        ],
    },
    {
        distance: "1400 Metres",
        entries: [
            {
                lines: [
                    "Royal Mysore 58.5 kgs",
                    "(06/04/24)",
                    "1 m. 21.083 secs **(Mile Chute)**",
                ],
            },
            {
                lines: [
                    "Miracle 54.5 kgs",
                    "(14/03/21)",
                    "1 m. 23.159 secs",
                    "**(Back Stretch)** Since Nov. 2017",
                ],
            },
        ],
    },
    {
        distance: "1600 Metres",
        entries: [
            {
                lines: [
                    "Zachary 56 kgs",
                    "(07/03/15)",
                    "1 m. 33.96 secs **(Mile Chute)**",
                ],
            },
            {
                lines: [
                    "Zuccaro 53.5 kgs",
                    "(15/03/26)",
                    "1 m. 33.964 secs",
                    "**(Back Stretch)** Since Nov. 2016",
                ],
            },
        ],
    },
    {
        distance: "1800 Metres",
        entries: [
            { lines: ["Diego Rivera 59 kgs", "(01/03/09)", "1 m. 47.51 secs"] },
        ],
    },
    {
        distance: "2000 Metres",
        entries: [
            { lines: ["Baychimo 57 kgs", "(10/01/26)", "1 ms. 59.252 secs"] },
        ],
    },
    {
        distance: "2200 Metres",
        entries: [
            { lines: ["Game Plan 59 kgs", "(12/03/98)", "2 ms. 17.8 secs"] },
        ],
    },
    {
        distance: "2400 Metres",
        entries: [
            { lines: ["Juliette 58.5 kgs", "(02/04/23)", "2 ms. 26.729 secs"] },
        ],
    },
    {
        distance: "2800 Metres",
        entries: [
            { lines: ["Mystical 57 kgs", "(26/03/06)", "2 ms. 54.06 secs"] },
        ],
    },
    {
        distance: "3000 Metres",
        entries: [
            { lines: ["Arabian Prince 59 kgs", "(05/03/11)", "3 ms. 09.98 secs"] },
        ],
    },
];

const puneTimings = [
    {
        distance: "1000 Metres",
        main: { lines: ["Stringsofmyheart 52.5 kgs", "(14/10/12)", "58.14 secs."] },
        monsoon: { lines: ["Harvey 55 kgs", "(10/09/17)", "56.771 secs."] },
    },
    {
        distance: "1100 Metres",
        main: { lines: ["Indiscretion 52.5 kgs", "(25/10/98)", "1 m 05.64 secs"] },
        monsoon: { lines: ["Abhicandra 55.5 kgs", "(21/09/2025)", "1 m 04.396 secs"] },
    },
    {
        distance: "1200 Metres",
        main: { lines: ["Evatina 55.5 kgs", "(15/10/06)", "1 m 08.83 secs"] },
        monsoon: { lines: ["Enigma 58.5 kgs", "(16/10/22)", "1 m 06.509 secs"] },
    },
    {
        distance: "1400 Metres",
        main: { lines: ["Sovereign Power 49.5 kgs", "(29/10/06)", "1 m 22.57 secs"] },
        monsoon: { lines: ["Kildare 59 kgs", "(24/11/19)", "1 m 23.099 secs"] },
    },
    {
        distance: "1600 Metres",
        main: { lines: ["Live Legend 49 kgs", "(10/09/06)", "1 m 36.74 secs"] },
        monsoon: { lines: ["Chopin 49 kgs", "(05/09/23)", "1 m 36.027 secs"] },
    },
    {
        distance: "1800 Metres",
        main: { lines: ["Dancing Dynamite 62 kgs", "(30/08/08)", "1 m 49.70 secs"] },
        monsoon: { lines: ["Arcadia 52.5 kgs", "(28/11/21)", "1 m 48.877 secs"] },
    },
    {
        distance: "2000 Metres",
        main: { lines: ["Yana 51 kgs", "(24/08/08)", "2 ms 01.45 secs"] },
        monsoon: { lines: ["Duke Of Tuscany 57 kgs", "(02/11/25)", "2 ms 02.242 secs"] },
    },
    {
        distance: "2400 Metres",
        main: { lines: ["Personified 59 kgs", "(27/09/08)", "2 ms 28.78 secs"] },
        monsoon: { lines: ["Dyf 56.5 kgs", "(05/09/23)", "2 ms 27.843 secs"] },
    },
    {
        distance: "2800 Metres",
        main: { lines: ["Macchupicchu 57 kgs", "(25/09/11)", "2 ms 57.30 secs"] },
        monsoon: { lines: ["Mathaiyus 57 kgs", "(24/09/17)", "2 ms 53.880 secs"] },
    },
    {
        distance: "3200 Metres",
        main: { lines: ["Jonty Rhodes 48.5 kgs", "(22/10/00)", "3 ms 26.64 secs"] },
        monsoon: { lines: ["Magneto 52 kgs", "(12/10/24)", "3 ms 23.728 secs"] },
    },
];

// Renders a single line of text, turning **bold** segments into <strong>.
function renderLine(line, key) {
    const parts = line.split("**");

    return (
        <span className="recordLine" key={key}>
            {parts.map((part, i) =>
                i % 2 === 1 ? <strong key={i}>{part}</strong> : part
            )}
        </span>
    );
}

function EntryCell({ entry }) {
    return (
        <td className="colEntry">
            <span className="entryName">{entry.lines[0]}</span>
            {entry.lines.slice(1).map((line, i) => (
                <span className="entryLine" key={i}>
                    {renderLine(line, i)}
                </span>
            ))}
        </td>
    );
}

export default function RecordTimings() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Record Timings</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <h2 className="recordSubHeading">
                        Best Timings By Indian Horses On The Mumbai Race Track
                    </h2>

                    <div className="statsTableWrap">
                        <table className="statsTable recordTable">
                            <thead>
                                <tr>
                                    <th className="colDistance">Distance (About)</th>
                                    <th>Indian Horses</th>
                                </tr>
                            </thead>
                            <tbody>
                                {mumbaiTimings.map((row) =>
                                    row.entries.map((entry, entryIndex) => (
                                        <tr key={`${row.distance}-${entryIndex}`}>
                                            {entryIndex === 0 && (
                                                <td
                                                    className="colDistance"
                                                    rowSpan={row.entries.length}
                                                >
                                                    {row.distance}
                                                </td>
                                            )}
                                            <EntryCell entry={entry} />
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    <h2 className="recordSubHeading recordSubHeadingSpaced">
                        Best Timings By Indian Horses On The Pune Race Track
                    </h2>

                    <div className="statsTableWrap">
                        <table className="statsTable recordTable">
                            <thead>
                                <tr>
                                    <th className="colDistance">Distance (About)</th>
                                    <th>Main Track</th>
                                    <th>Monsoon Track</th>
                                </tr>
                            </thead>
                            <tbody>
                                {puneTimings.map((row) => (
                                    <tr key={row.distance}>
                                        <td className="colDistance">{row.distance}</td>
                                        <EntryCell entry={row.main} />
                                        <EntryCell entry={row.monsoon} />
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

        </section>

    );

}