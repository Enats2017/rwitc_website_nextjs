"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getOrganisation } from "../../../services/clubService";
import "./Managing_Committee.css";

const DEFAULT_TITLE = "Managing Committee of the Club";
const BULLET = /^\s*(?:-|•)\s+/;
function parseBlocks(text) {
    return String(text)
        .split(/\n\s*\n/)
        .map((b) => b.trim())
        .filter(Boolean)
        .map((block) => {
            const lines = block
                .split("\n")
                .map((l) => l.trim())
                .filter(Boolean);

            if (lines.length > 0 && lines.every((l) => BULLET.test(l))) {
                return {
                    type: "list",
                    items: lines.map((l) => l.replace(BULLET, "")),
                };
            }

            return { type: "p", text: lines.join(" ") };
        });
}

export default function ManagingCommittee() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [blocks, setBlocks] = useState([]);

    useEffect(() => {
        let active = true;

        getOrganisation("managing_committee")
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
                        {blocks.map((block, i) =>
                            block.type === "list" ? (
                                <ul key={i} className="memberList">
                                    {block.items.map((item, j) => (
                                        <li key={j}>{item}</li>
                                    ))}
                                </ul>
                            ) : (
                                <p
                                    key={i}
                                    className={
                                        blocks[i + 1]?.type === "list"
                                            ? "visionIntroLine"
                                            : undefined
                                    }
                                >
                                    {block.text}
                                </p>
                            )
                        )}
                    </div>
                </div>
            </div>
        </section>
    );
}