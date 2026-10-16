"use client";

import { Fragment, useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getDeductionNorms } from "../../../services/clubService";
import "./DeductionNorms.css";

const DEFAULT_TITLE = "Deduction Norms";
const DEFAULT_COL1 = "Odds on withdrawn Horse/s at the time of Withdrawal";
const DEFAULT_COL2 = "Percentage of deductions";

// Line markers used in the admin text
const MAIN_HEADING_LINE = /^#\s+/;        // "# Main heading"
const UNDERLINE_HEADING_LINE = /^##\s+/;  // "## Underlined sub-heading"
const NUMBERED_LINE = /^\d+[.)]\s+/;      // "1. text"
const BULLET_LINE = /^-\s+/;              // "- text"
const SIGNOFF_LINE = /^>>\s+(.+)$/;       // ">> Mumbai: 25th April 2009"
const BOLD_PART = /(\*\*[^*]+\*\*)/g;     // "**bold text**"

// Renders inline **bold** markers as <strong>
function renderInline(text) {
    return String(text).split(BOLD_PART).map((part, i) =>
        /^\*\*[^*]+\*\*$/.test(part) ? (
            <strong key={i}>{part.slice(2, -2)}</strong>
        ) : (
            part
        )
    );
}

// Splits a plain text into paragraphs (blank line = new paragraph)
function splitParas(text) {
    return String(text || "")
        .replace(/\r\n?/g, "\n")
        .split(/\n\s*\n/)
        .map((p) => p.replace(/\s*\n\s*/g, " ").trim())
        .filter(Boolean);
}

// Converts the raw admin text into blocks
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

        if (MAIN_HEADING_LINE.test(t)) {
            flushPara();
            flushList();
            blocks.push({ type: "mainHeading", text: t.replace(MAIN_HEADING_LINE, "") });
            return;
        }

        const signOff = t.match(SIGNOFF_LINE);
        if (signOff) {
            flushPara();
            flushList();
            blocks.push({ type: "signOff", text: signOff[1] });
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

    return blocks;
}

export default function DeductionNorms() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [header, setHeader] = useState("");
    const [tables, setTables] = useState([]);
    const [blocks, setBlocks] = useState([]);

    useEffect(() => {
        let active = true;

        getDeductionNorms()
            .then((data) => {
                if (!active || !data || data.exists === false) return;

                if (data.title) setTitle(data.title);
                setHeader(data.header || "");
                setTables(Array.isArray(data.tables) ? data.tables : []);
                setBlocks(data.text ? parseBlocks(data.text) : []);
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
                        {header && <h2 className="dnMainHeading">{header}</h2>}

                        {tables.map((t, i) => (
                            <Fragment key={i}>
                                {t.heading && (
                                    <h3 className="dnUnderlineHeading">{t.heading}</h3>
                                )}

                                {splitParas(t.text).map((p, j) => (
                                    <p key={j}>{renderInline(p)}</p>
                                ))}

                                {Array.isArray(t.rows) && t.rows.length > 0 && (
                                    <div className="statsTableWrap">
                                        <table className="statsTable">
                                            <thead>
                                                <tr>
                                                    <th>{t.col1 || DEFAULT_COL1}</th>
                                                    <th className="colPercent">{t.col2 || DEFAULT_COL2}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {t.rows.map((row, k) => (
                                                    <tr key={k}>
                                                        <td className="colOdds">{row.c1}</td>
                                                        <td className="colPercent">{row.c2}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                            </Fragment>
                        ))}

                        {blocks.map((block, i) => {
                            if (block.type === "mainHeading") {
                                return (
                                    <h2 key={i} className="dnMainHeading">
                                        {block.text}
                                    </h2>
                                );
                            }

                            if (block.type === "underlineHeading") {
                                return (
                                    <h3 key={i} className="dnUnderlineHeading">
                                        {block.text}
                                    </h3>
                                );
                            }

                            if (block.type === "signOff") {
                                return (
                                    <p key={i} className="dnSignOff">
                                        <strong>{block.text}</strong>
                                    </p>
                                );
                            }

                            if (block.type === "list") {
                                const items = block.items.map((item, j) => (
                                    <li key={j}>{renderInline(item)}</li>
                                ));

                                return block.ordered ? (
                                    <ol key={i} className="dnRomanList">
                                        {items}
                                    </ol>
                                ) : (
                                    <ul key={i} className="guideList">
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