"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getNationalTotePools } from "../../../services/clubService";
import "./NationalTotePools.css";

const DEFAULT_TITLE = "National Tote Pools";

// Line markers used in the admin text
const CENTER_HEADING_LINE = /^#\s+/;      // "# Centered heading"
const UNDERLINE_HEADING_LINE = /^##\s+/;  // "## Underlined sub-heading"
const NUMBERED_LINE = /^\d+[.)]\s+/;      // "1. text"
const BULLET_LINE = /^-\s+/;              // "- text"
const POOL_ROW_LINE = /^>>\s*(.+?)\s*\|\s*(.+)$/; // ">> LABEL | text"
const BOLD_PART = /(\*\*[^*]+\*\*)/g;     // "**bold text**"

// Renders inline **bold** markers as <strong>
function renderInline(text) {
    return text.split(BOLD_PART).map((part, i) =>
        /^\*\*[^*]+\*\*$/.test(part) ? (
            <strong key={i}>{part.slice(2, -2)}</strong>
        ) : (
            part
        )
    );
}

// Groups consecutive paragraphs that start with a bold label ("**Win:** ...")
// into one "terms" block, so they render inside the termsBlock wrapper.
function groupTerms(blocks) {
    const out = [];

    blocks.forEach((block) => {
        if (block.type === "para" && block.text.startsWith("**")) {
            const last = out[out.length - 1];
            if (last && last.type === "terms") {
                last.items.push(block.text);
            } else {
                out.push({ type: "terms", items: [block.text] });
            }
            return;
        }
        out.push(block);
    });

    return out;
}

// Converts raw admin text into blocks
function parseBlocks(rawText) {
    const lines = String(rawText).replace(/\r\n?/g, "\n").split("\n");
    const blocks = [];
    let para = [];
    let list = null; // { ordered: boolean, items: string[] }

    const flushPara = () => {
        if (!para.length) return;
        blocks.push({ type: "para", text: para.join(" ").trim() });
        para = [];
    };

    const flushList = () => {
        if (!list) return;
        blocks.push({ type: "list", ordered: list.ordered, items: list.items });
        list = null;
    };

    const addListItem = (ordered, text) => {
        flushPara();
        if (!list || list.ordered !== ordered) {
            flushList();
            list = { ordered, items: [] };
        }
        list.items.push(text);
    };

    lines.forEach((line) => {
        const t = line.trim();

        if (!t) {
            flushPara();
            flushList();
            return;
        }

        if (UNDERLINE_HEADING_LINE.test(t)) {
            flushPara();
            flushList();
            blocks.push({ type: "underlineHeading", text: t.replace(UNDERLINE_HEADING_LINE, "") });
            return;
        }

        if (CENTER_HEADING_LINE.test(t)) {
            flushPara();
            flushList();
            blocks.push({ type: "centerHeading", text: t.replace(CENTER_HEADING_LINE, "") });
            return;
        }

        const row = t.match(POOL_ROW_LINE);
        if (row) {
            flushPara();
            flushList();
            blocks.push({ type: "poolRow", label: row[1], text: row[2] });
            return;
        }

        if (NUMBERED_LINE.test(t)) {
            addListItem(true, t.replace(NUMBERED_LINE, ""));
            return;
        }

        if (BULLET_LINE.test(t)) {
            addListItem(false, t.replace(BULLET_LINE, ""));
            return;
        }

        flushList();
        para.push(t);
    });

    flushPara();
    flushList();

    return groupTerms(blocks);
}

export default function NationalTotePools() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [blocks, setBlocks] = useState([]);

    useEffect(() => {
        let active = true;

        getNationalTotePools()
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
                            if (block.type === "centerHeading") {
                                return (
                                    <h2
                                        key={i}
                                        className={i === 0 ? "ntpCenterHeading" : "ntpCenterHeading ntpSpacedTop"}
                                    >
                                        {block.text}
                                    </h2>
                                );
                            }

                            if (block.type === "underlineHeading") {
                                return (
                                    <h3 key={i} className="ntpUnderlineHeading">
                                        {block.text}
                                    </h3>
                                );
                            }

                            if (block.type === "poolRow") {
                                return (
                                    <div key={i} className="ntpPoolRow">
                                        <span className="ntpPoolLabel">{block.label}</span>
                                        <span>: {block.text}</span>
                                    </div>
                                );
                            }

                            if (block.type === "terms") {
                                return (
                                    <div key={i} className="termsBlock">
                                        {block.items.map((item, j) => (
                                            <p key={j}>{renderInline(item)}</p>
                                        ))}
                                    </div>
                                );
                            }

                            if (block.type === "list") {
                                const items = block.items.map((item, j) => (
                                    <li key={j}>{renderInline(item)}</li>
                                ));

                                return block.ordered ? (
                                    <ol key={i} className="guideList">
                                        {items}
                                    </ol>
                                ) : (
                                    <ul key={i} className="ntpBulletList">
                                        {items}
                                    </ul>
                                );
                            }

                            return <p key={i}>{renderInline(block.text)}</p>;
                        })}
                    </div>
                </div>
            </div>
        </section>
    );
}