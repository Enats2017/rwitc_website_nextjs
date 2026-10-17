"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getPartnersOverview } from "../../../services/clubService";
import "./PartnersOverview.css";

// Matches numbered lines like "1. text" or "1) text"
const NUMBERED_LINE = /^\d+[.)]\s+/;

const escapeHtml = (s) =>
    s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

// Old content was plain text (blank line = new paragraph, "1. text" lines = numbered list).
// If the saved text has no HTML tags, convert it to <p> / <ol> blocks.
function toHtml(rawText) {
    const value = String(rawText || "").trim();
    if (!value) return "";
    if (/<[a-z][^>]*>/i.test(value)) return value;

    return value
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
                const items = lines
                    .map((l) => `<li>${escapeHtml(l.replace(NUMBERED_LINE, ""))}</li>`)
                    .join("");
                return `<ol>${items}</ol>`;
            }

            return `<p>${escapeHtml(lines.join(" "))}</p>`;
        })
        .join("");
}

export default function PartnersOverview() {
    const [html, setHtml] = useState("");

    useEffect(() => {
        let active = true;

        getPartnersOverview()
            .then((data) => {
                if (!active || !data?.text) return;
                setHtml(toHtml(data.text));
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
                    {/* Content is sanitized on the server (admin panel) before saving */}
                    {html && (
                        <div
                            className="aboutContent guideContent"
                            dangerouslySetInnerHTML={{ __html: html }}
                        />
                    )}
                </div>
            </div>
        </section>
    );
}
