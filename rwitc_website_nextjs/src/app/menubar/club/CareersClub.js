"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getCareers } from "../../../services/clubService";
import "./CareersClub.css";

// Shown when no custom title is set in the admin panel
const DEFAULT_TITLE = "Career Opportunities in Racing";

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

export default function CareersClub() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [html, setHtml] = useState("");

    useEffect(() => {
        let active = true;

        getCareers()
            .then((data) => {
                if (!active || !data) return;

                if (data.title) setTitle(data.title);
                if (data.text) setHtml(toHtml(data.text));
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
