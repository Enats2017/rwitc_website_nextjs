"use client";

import { useState, useEffect } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getMembershipImportance } from "../../../services/Membershipimportanceservice";
import "./MembershipOverview.css";

const PAGE_TITLE = "Importance of being a Member";

// Old content was plain text (blank line = new paragraph).
// If the saved text has no HTML tags, convert it to <p> blocks.
function toHtml(text) {
    const value = String(text || "").trim();
    if (!value) return "";
    if (/<[a-z][^>]*>/i.test(value)) return value;

    const escape = (s) =>
        s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

    return value
        .split(/\n\s*\n/)
        .map((p) => p.trim())
        .filter(Boolean)
        .map((p) => `<p>${escape(p).replace(/\n/g, "<br>")}</p>`)
        .join("");
}

export default function MembershipOverview() {
    const [html, setHtml] = useState("");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        let cancelled = false;

        (async () => {
            try {
                const data = await getMembershipImportance();
                if (cancelled) return;

                if (data?.error) {
                    setError(data.error);
                } else if (data?.exists && data.text) {
                    setHtml(toHtml(data.text));
                }
            } catch (e) {
                if (!cancelled) setError("Unable to load content.");
            }

            if (!cancelled) setLoading(false);
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