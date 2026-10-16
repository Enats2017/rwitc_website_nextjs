"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getPartnersOpportunities } from "../../../services/clubService";
import "./PartnersOpportunities.css";

const DEFAULT_TITLE = "Sponsorships At The RWITC Ltd";

// Line marker used in the admin text: "## Recap of some key facts:"
const LEAD_LINE = /^##\s+/;

// Converts raw admin text into blocks: paragraphs and lead lines
function parseBlocks(rawText) {
    const lines = String(rawText).replace(/\r\n?/g, "\n").split("\n");
    const blocks = [];
    let para = [];

    const flushPara = () => {
        if (!para.length) return;
        blocks.push({ type: "para", text: para.join(" ").trim() });
        para = [];
    };

    lines.forEach((line) => {
        const t = line.trim();

        if (!t) {
            flushPara();
            return;
        }

        if (LEAD_LINE.test(t)) {
            flushPara();
            blocks.push({ type: "lead", text: t.replace(LEAD_LINE, "") });
            return;
        }

        para.push(t);
    });

    flushPara();

    return blocks;
}

export default function PartnersOpportunities() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [blocks, setBlocks] = useState([]);

    useEffect(() => {
        let active = true;

        getPartnersOpportunities()
            .then((data) => {
                if (!active || !data?.text) return;

                if (data.title) setTitle(data.title);
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
                            block.type === "lead" ? (
                                <p key={i} className="poLeadLine">
                                    {block.text}
                                </p>
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