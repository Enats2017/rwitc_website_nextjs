"use client";

import { useState, useEffect } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getComeRacingMumbai } from "../../../services/Comeracingmumbaiservice";
import "./MumbaiRaceCourse.css";

const PAGE_TITLE = "The Mahalaxmi Race Course";

// Old content was plain text (one point per line).
// If the saved text has no HTML tags, convert it to a <ul> list.
function toHtml(text) {
    const value = String(text || "").trim();
    if (!value) return "";
    if (/<[a-z][^>]*>/i.test(value)) return value;

    const escape = (s) =>
        s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

    const items = value
        .split(/\r?\n/)
        .map((l) => l.trim())
        .filter(Boolean)
        .map((l) => `<li>${escape(l)}</li>`)
        .join("");

    return items ? `<ul>${items}</ul>` : "";
}

export default function MumbaiRaceCourse() {
    const [html, setHtml] = useState("");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        let cancelled = false;

        (async () => {
            try {
                const data = await getComeRacingMumbai();
                if (cancelled) return;

                // new API -> data.content (HTML), old API -> data.points (array)
                const raw =
                    data?.content ??
                    (Array.isArray(data?.points) ? data.points.join("\n") : "");

                if (raw) {
                    setHtml(toHtml(raw));
                }
            } catch (e) {
                if (!cancelled) {
                    setError(e.message || "Unable to load content.");
                }
            } finally {
                if (!cancelled) {
                    setLoading(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, []);

    return (
        <section className="aboutPage">
            <div className="aboutContainer">
                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">{PAGE_TITLE}</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">
                    {loading && <p className="aboutStatus">Loading…</p>}

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