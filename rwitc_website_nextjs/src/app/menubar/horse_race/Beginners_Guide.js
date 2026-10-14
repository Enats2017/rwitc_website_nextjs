"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getBeginnersGuide } from "../../../services/clubService";
import "./Beginners_Guide.css";

// Shown when no custom title is set in the admin panel
const DEFAULT_TITLE = "Beginner's Guide";

const HEADING = /^##\s+(.*)$/;      // "## Sub heading"
const ORDERED = /^\d+[.)]\s+(.*)$/; // "1. Item"

// Turns the admin text into blocks:
// - "## text"          -> sub-heading
// - "1. text" lines    -> numbered list
// - anything else      -> paragraph (blank line ends a paragraph)
function parseBlocks(text) {
    const blocks = [];
    let para = [];
    let list = [];

    const flushPara = () => {
        if (para.length) {
            blocks.push({ type: "p", text: para.join(" ") });
            para = [];
        }
    };

    const flushList = () => {
        if (list.length) {
            blocks.push({ type: "ol", items: list });
            list = [];
        }
    };

    String(text)
        .split("\n")
        .forEach((raw) => {
            const line = raw.trim();

            if (!line) {
                flushPara();
                flushList();
                return;
            }

            const h = line.match(HEADING);
            if (h) {
                flushPara();
                flushList();
                blocks.push({ type: "h", text: h[1].trim() });
                return;
            }

            const o = line.match(ORDERED);
            if (o) {
                flushPara();
                list.push(o[1].trim());
                return;
            }

            flushList();
            para.push(line);
        });

    flushPara();
    flushList();

    return blocks;
}

// Renders **bold** parts as <strong> without using innerHTML
function renderInline(text) {
    return String(text)
        .split(/(\*\*[^*]+\*\*)/g)
        .map((part, i) =>
            part.length > 4 && part.startsWith("**") && part.endsWith("**") ? (
                <strong key={i}>{part.slice(2, -2)}</strong>
            ) : (
                part
            )
        );
}

export default function BeginnersGuide() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [blocks, setBlocks] = useState([]);

    useEffect(() => {
        let active = true;

        getBeginnersGuide()
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
                        {blocks.map((block, i) => {
                            if (block.type === "h") {
                                return (
                                    <h3 key={i} className="guideSubHeading">
                                        {block.text}
                                    </h3>
                                );
                            }

                            if (block.type === "ol") {
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