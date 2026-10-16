"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getBettingChannels } from "../../../services/clubService";
import "./BettingChannels.css";

const DEFAULT_TITLE = "Tote betting and backing with the Bookmakers";

// Line markers used in the admin text
const HEADING_LINE = /^##\s+/;       // "## Sub heading"
const NUMBERED_LINE = /^\d+[.)]\s+/; // "1. text"
const BOLD_PART = /(\*\*[^*]+\*\*)/g; // "**bold text**"

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

// Converts raw admin text into blocks: heading, list and paragraphs
function parseBlocks(rawText) {
    const lines = String(rawText).replace(/\r\n?/g, "\n").split("\n");
    const blocks = [];
    let para = [];
    let list = null;

    const flushPara = () => {
        if (!para.length) return;
        blocks.push({ type: "para", text: para.join(" ").trim() });
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

        if (NUMBERED_LINE.test(t)) {
            flushPara();
            list = list || [];
            list.push(t.replace(NUMBERED_LINE, ""));
            return;
        }

        flushList();
        para.push(t);
    });

    flushPara();
    flushList();

    return blocks;
}

export default function BettingChannels() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [blocks, setBlocks] = useState([]);

    useEffect(() => {
        let active = true;

        getBettingChannels()
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
                                    <ol key={i} className="guideList">
                                        {block.items.map((item, j) => (
                                            <li key={j}>{renderInline(item)}</li>
                                        ))}
                                    </ol>
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