"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getPartnersOverview } from "../../../services/clubService";
import "./PartnersOverview.css";

// Matches numbered lines like "1. text" or "1) text"
const NUMBERED_LINE = /^\d+[.)]\s+/;

// Converts raw admin text into blocks: paragraphs and numbered lists
function parseBlocks(rawText) {
    return String(rawText)
        .replace(/\r\n?/g, "\n")
        .split(/\n\s*\n/)
        .map((block) => block.trim())
        .filter(Boolean)
        .map((block) => {
            const lines = block
                .split("\n")
                .map((l) => l.trim())
                .filter(Boolean);

            const isList = lines.length > 0 && lines.every((l) => NUMBERED_LINE.test(l));

            if (isList) {
                return {
                    type: "list",
                    items: lines.map((l) => l.replace(NUMBERED_LINE, "")),
                };
            }

            return { type: "para", text: lines.join(" ") };
        });
}

export default function PartnersOverview() {
    const [blocks, setBlocks] = useState([]);

    useEffect(() => {
        let active = true;

        getPartnersOverview()
            .then((data) => {
                if (!active || !data?.text) return;
                setBlocks(parseBlocks(data.text));
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
                    <h1 className="aboutHeading">Overview</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">
                    <div className="aboutContent">
                        {blocks.map((block, i) =>
                            block.type === "list" ? (
                                <ol key={i} className="spNumberedList">
                                    {block.items.map((item, j) => (
                                        <li key={j}>{item}</li>
                                    ))}
                                </ol>
                            ) : (
                                <p key={i}>{block.text}</p>
                            )
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}