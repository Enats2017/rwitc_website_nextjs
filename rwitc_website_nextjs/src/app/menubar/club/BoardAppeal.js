"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getOrganisation } from "../../../services/clubService";
import "./BoardAppeal.css";

const DEFAULT_TITLE = "Board of Appeal";
const BULLET = /^\s*(?:-|•)\s+/;

const escapeHtml = (s) =>
    s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

// Old content was plain text:
// - a blank line separates blocks
// - a block where every line starts with "- " becomes a bullet list
// - any other block becomes a paragraph
// If the saved text already contains HTML tags, it is returned unchanged.
function toHtml(text) {
    const value = String(text || "").trim();
    if (!value) return "";
    if (/<[a-z][^>]*>/i.test(value)) return value;

    return value
        .split(/\n\s*\n/)
        .map((b) => b.trim())
        .filter(Boolean)
        .map((block) => {
            const lines = block
                .split("\n")
                .map((l) => l.trim())
                .filter(Boolean);

            if (lines.length === 0) return "";

            if (lines.every((l) => BULLET.test(l))) {
                const items = lines
                    .map((l) => `<li>${escapeHtml(l.replace(BULLET, ""))}</li>`)
                    .join("");
                return `<ul>${items}</ul>`;
            }

            return `<p>${escapeHtml(lines.join(" "))}</p>`;
        })
        .join("");
}

export default function BoardAppeal() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [html, setHtml] = useState("");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        let active = true;

        (async () => {
            try {
                const data = await getOrganisation("board_of_appeal");
                if (!active || !data) return;

                if (data.title) setTitle(data.title);

                // API returns the saved column as data.text (HTML or old plain text)
                const raw = data.text ?? data.content ?? "";
                if (raw) setHtml(toHtml(raw));
            } catch (err) {
                if (active) setError(err.message || "Unable to load content.");
            } finally {
                if (active) setLoading(false);
            }
        })();

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
                    {loading && <p className="aboutStatus">Loading...</p>}

                    {!loading && error && <p className="aboutStatus">{error}</p>}

                    {!loading && !error && !html && (
                        <p className="aboutStatus">Content will be updated soon.</p>
                    )}

                    {/* Content is sanitized on the server (admin panel) before saving */}
                    {!loading && !error && html && (
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