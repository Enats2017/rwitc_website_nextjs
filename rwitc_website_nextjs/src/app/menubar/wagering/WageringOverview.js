"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getWageringOverview } from "../../../services/clubService";
import "./WageringOverview.css";

// Shown when no custom title is set in the admin panel
const DEFAULT_TITLE = "Beginner's luck in Racing";

const LEAD = /^##\s+(.*)$/; // "## Highlighted lead line"

// Splits the admin text into blocks separated by blank lines.
// A block starting with "## " is the highlighted lead line, everything else is a paragraph.
function parseBlocks(text) {
    return String(text)
        .split(/\n\s*\n/)
        .map((b) => b.trim())
        .filter(Boolean)
        .map((block) => {
            const joined = block
                .split("\n")
                .map((l) => l.trim())
                .filter(Boolean)
                .join(" ");

            const m = joined.match(LEAD);

            if (m) {
                return { type: "lead", text: m[1].trim() };
            }

            return { type: "p", text: joined };
        });
}

export default function WageringOverview() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [blocks, setBlocks] = useState([]);

    useEffect(() => {
        let active = true;

        getWageringOverview()
            .then((data) => {
                if (!active || !data) return;

                if (data.title) setTitle(data.title);
                if (data.text) setBlocks(parseBlocks(data.text));
            })
            .catch(() => {});

        return () => {
            active = false;
        };
    }, []);

    return (
        <section className="aboutPage">
            <div className="aboutContainer">
                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">{title}</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">
                    <div className="aboutContent">
                        {blocks.map((block, i) => (
                            <p
                                key={i}
                                className={
                                    block.type === "lead" ? "wageringLeadLine" : undefined
                                }
                            >
                                {block.text}
                            </p>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}