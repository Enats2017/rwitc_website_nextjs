"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getComeRacingServices } from "../../../services/Comeracingservicesservice";
import "./RaceCourseServicesOthers.css";

const PAGE_TITLE = "Race Course Services";

// Old content was plain text (one paragraph per line).
// If the saved text has no HTML tags, convert it to <p> blocks.
function toHtml(text) {
    const value = String(text || "").trim();
    if (!value) return "";
    if (/<[a-z][^>]*>/i.test(value)) return value;

    const escape = (s) =>
        s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

    return value
        .split(/\r?\n/)
        .map((l) => l.trim())
        .filter(Boolean)
        .map((l) => `<p>${escape(l)}</p>`)
        .join("");
}

export default function RaceCourseServicesOthers() {
    const [html, setHtml] = useState("");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        let active = true;

        async function load() {
            try {
                const data = await getComeRacingServices();
                if (!active) return;

                // new API -> data.content (HTML), old API -> data.paragraphs (array)
                const raw =
                    data?.content ??
                    (Array.isArray(data?.paragraphs) ? data.paragraphs.join("\n") : "");

                if (raw) setHtml(toHtml(raw));
            } catch (err) {
                if (active) {
                    setError(err.message || "Unable to load race course services details.");
                }
            } finally {
                if (active) setLoading(false);
            }
        }

        load();
        return () => {
            active = false;
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
                    {loading && <p className="aboutStatus">Loading...</p>}

                    {!loading && error && <p className="aboutStatus">{error}</p>}

                    {!loading && !error && !html && (
                        <p className="aboutStatus">Details will be updated soon.</p>
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