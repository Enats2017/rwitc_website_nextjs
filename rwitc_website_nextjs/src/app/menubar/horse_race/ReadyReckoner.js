"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getPdfLinks } from "../../../services/pdfService";
import "./ReadyReckoner.css";

// Fallback data, used only when no file is uploaded yet or the file cannot be loaded.
// Same values as the old hardcoded table (49.00 kg to 62.00 kg, step 0.5).
function buildFallbackRows() {
    const rows = [];
    for (let i = 0; i <= 26; i++) {
        rows.push({
            weight: (49 + i * 0.5).toFixed(2),
            I: 80 + i,
            II: 60 + i,
            III: 40 + i,
            IV: 20 + i,
            V: 4 + i,
        });
    }
    return rows;
}

const FALLBACK = {
    club: "Royal Western India Turf Club Ltd.",
    subtitle: "Ready Reckoner",
    date: "Mumbai: November 10, 2022",
    notes: [
        "Ratings are in points where 1 point equals 0.5 kg.",
        "The minimum Top Weight is 59 kgs with Bottom Weight being 49 kgs.",
        "Starting rating mark for 3 year olds is 30 points for Colts & Geldings and 27 points for Fillies.",
    ],
    rows: buildFallbackRows(),
};

// Decodes the file bytes. Tries UTF-8 first, falls back to windows-1252 for old text files.
function decodeBytes(buffer) {
    const utf8 = new TextDecoder("utf-8").decode(buffer);
    if (utf8.indexOf("\uFFFD") === -1) {
        return utf8;
    }
    return new TextDecoder("windows-1252").decode(buffer);
}

// Parses the uploaded text file.
// Lines like "CLUB: ...", "SUBTITLE: ...", "DATE: ...", "NOTE: ..." are the text parts.
// Every other non-empty line is a table row: weight, I, II, III, IV, V
function parseReckonerText(text) {
    const result = { club: "", subtitle: "", date: "", notes: [], rows: [] };
    const lines = text.replace(/^\uFEFF/, "").split(/\r?\n/);

    lines.forEach((rawLine) => {
        const line = rawLine.trim();
        if (!line) { return; }

        const meta = line.match(/^(CLUB|SUBTITLE|DATE|NOTE)\s*:\s*(.*)$/i);
        if (meta) {
            const key = meta[1].toUpperCase();
            const value = meta[2].trim();
            if (key === "CLUB") { result.club = value; }
            else if (key === "SUBTITLE") { result.subtitle = value; }
            else if (key === "DATE") { result.date = value; }
            else if (key === "NOTE" && value) { result.notes.push(value); }
            return;
        }

        const parts = line.split(/[,\t|]+/).map((p) => p.trim());
        if (parts.length >= 6) {
            result.rows.push({
                weight: parts[0],
                I: parts[1],
                II: parts[2],
                III: parts[3],
                IV: parts[4],
                V: parts[5],
            });
        }
    });

    return result;
}

export default function ReadyReckoner() {
    const [data, setData] = useState(null);

    useEffect(() => {
        let cancelled = false;

        async function load() {
            try {
                // Latest uploaded file link (same service the menu uses)
                const links = await getPdfLinks();
                const url = links["ready_reckoner"];

                if (!url || typeof url !== "string") {
                    if (!cancelled) setData(FALLBACK);
                    return;
                }

                const res = await fetch(url);
                if (!res.ok) { throw new Error("Failed to load file"); }

                const parsed = parseReckonerText(decodeBytes(await res.arrayBuffer()));

                // If the file has no valid table rows, show the fallback instead of an empty table
                if (parsed.rows.length === 0) {
                    if (!cancelled) setData(FALLBACK);
                    return;
                }

                if (!cancelled) {
                    setData({
                        club: parsed.club || FALLBACK.club,
                        subtitle: parsed.subtitle || FALLBACK.subtitle,
                        date: parsed.date,
                        notes: parsed.notes,
                        rows: parsed.rows,
                    });
                }
            } catch (err) {
                if (!cancelled) setData(FALLBACK);
            }
        }

        load();
        return () => { cancelled = true; };
    }, []);

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Ready Reckoner</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    {!data && <p style={{ textAlign: "center" }}>Loading...</p>}

                    {data && (
                        <>
                            <div className="reckonerTitleBlock">
                                <p className="reckonerClubName">{data.club}</p>
                                <p className="reckonerSubTitle">{data.subtitle}</p>
                            </div>

                            <div className="statsTableWrap">
                                <table className="statsTable reckonerTable">
                                    <thead>
                                        <tr>
                                            <th className="colWeight" rowSpan={2}>
                                                Weight (Kg)
                                            </th>
                                            <th colSpan={5}>Class</th>
                                        </tr>
                                        <tr>
                                            <th>I Rating</th>
                                            <th>II Rating</th>
                                            <th>III Rating</th>
                                            <th>IV Rating</th>
                                            <th>V Rating</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {data.rows.map((row, index) => (
                                            <tr key={index}>
                                                <td className="colWeight">{row.weight}</td>
                                                <td>{row.I}</td>
                                                <td>{row.II}</td>
                                                <td>{row.III}</td>
                                                <td>{row.IV}</td>
                                                <td>{row.V}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div className="reckonerNotes">
                                {data.notes.map((note, index) => (
                                    <p key={index}>{note}</p>
                                ))}
                            </div>

                            {data.date && <p className="reckonerDate">{data.date}</p>}
                        </>
                    )}

                </div>

            </div>

        </section>

    );

}