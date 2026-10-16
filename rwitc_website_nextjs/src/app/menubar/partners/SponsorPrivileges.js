"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getSponsorPrivileges } from "../../../services/clubService";
import "./SponsorPrivileges.css";

const DEFAULT_TITLE = "The Privileges of a Sponsor";

// Line markers used in the admin text
const HEADING_LINE = /^##\s+/;       // "## A sponsor can"
const BULLET_LINE = /^[-*•]\s+/;     // "- item"
const NOTE_PREFIX = /^note:\s*/i;    // "Note: text"

// Converts raw admin text into blocks: heading, list, note, para
function parseBlocks(rawText) {
    const lines = String(rawText).replace(/\r\n?/g, "\n").split("\n");
    const blocks = [];
    let para = [];
    let list = null;

    const flushPara = () => {
        if (!para.length) return;
        const text = para.join(" ").trim();
        if (NOTE_PREFIX.test(text)) {
            blocks.push({ type: "note", text: text.replace(NOTE_PREFIX, "") });
        } else {
            blocks.push({ type: "para", text });
        }
        para = [];
    };

    const flushList = () => {
        if (!list) return;
        blocks.push({ type: "list", items: list });
        list = null;
    };

    lines.forEach((line) => {
        const t = line.trim();

        if (!t) {
            flushPara();
            flushList();
            return;
        }

        if (HEADING_LINE.test(t)) {
            flushPara();
            flushList();
            blocks.push({ type: "heading", text: t.replace(HEADING_LINE, "") });
            return;
        }

        if (BULLET_LINE.test(t)) {
            flushPara();
            list = list || [];
            list.push(t.replace(BULLET_LINE, ""));
            return;
        }

        flushList();
        para.push(t);
    });

    flushPara();
    flushList();

    return blocks;
}

export default function SponsorPrivileges() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [blocks, setBlocks] = useState([]);

    useEffect(() => {
        let active = true;

        getSponsorPrivileges()
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
                        {blocks.map((block, i) => {
                            if (block.type === "heading") {
                                return (
                                    <h3 key={i} className="guideSubHeading">
                                        {block.text}
                                    </h3>
                                );
                            }

                            if (block.type === "list") {
                                return (
                                    <ul key={i} className="memberList">
                                        {block.items.map((item, j) => (
                                            <li key={j}>{item}</li>
                                        ))}
                                    </ul>
                                );
                            }

                            if (block.type === "note") {
                                return (
                                    <p key={i}>
                                        <strong>Note:</strong> {block.text}
                                    </p>
                                );
                            }

                            return <p key={i}>{block.text}</p>;
                        })}
                    </div>
                </div>
            </div>
        </section>
    );
}