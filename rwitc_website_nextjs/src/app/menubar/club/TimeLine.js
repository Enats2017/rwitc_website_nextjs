"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getHistory } from "../../../services/clubService";
import "./TimeLine.css";

const DEFAULT_TITLE = "Timeline";

const escapeHtml = (s) =>
    s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

// Plain text of an HTML snippet (used for the year)
function textOf(html) {
    const doc = new DOMParser().parseFromString(html, "text/html");
    return (doc.body.textContent || "").trim();
}

// Saved content is HTML (one paragraph = one event, "Year | Text").
// Old content was plain text (one line = one event), so that is supported too.
function parseEvents(raw) {
    const value = String(raw || "").trim();
    if (!value) return [];

    let lines;

    if (/<[a-z][^>]*>/i.test(value)) {
        const doc = new DOMParser().parseFromString(value, "text/html");
        const blocks = Array.from(doc.body.querySelectorAll("p, li"));
        lines = blocks.length
            ? blocks.map((b) => b.innerHTML)
            : [doc.body.innerHTML];
    } else {
        lines = value.split("\n").map((l) => escapeHtml(l));
    }

    return lines
        .map((l) => l.trim())
        .filter((l) => textOf(l) !== "")
        .map((line) => {
            const idx = line.indexOf("|");

            if (idx === -1) {
                return { year: "", html: line };
            }

            return {
                year: textOf(line.slice(0, idx)),
                html: line.slice(idx + 1).trim(),
            };
        });
}

export default function TimeLine() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [events, setEvents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        let active = true;

        (async () => {
            try {
                const data = await getHistory("timeline");
                if (!active || !data) return;

                if (data.title) setTitle(data.title);

                // API returns the saved column as data.text (HTML or old plain text)
                const raw = data.text ?? data.content ?? "";
                if (raw) setEvents(parseEvents(raw));
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

                    {!loading && !error && events.length === 0 && (
                        <p className="aboutStatus">Content will be updated soon.</p>
                    )}

                    {!loading && !error && events.length > 0 && (
                        <div className="timelineWrap">
                            {events.map((event, index) => (
                                <div className="timelineItem" key={index}>
                                    <div className="timelineMarker">
                                        <span className="timelineDot"></span>
                                    </div>

                                    <div className="timelineContent">
                                        {event.year && (
                                            <span className="timelineYear">{event.year}</span>
                                        )}
                                        {/* Content is sanitized on the server (admin panel) before saving */}
                                        <div
                                            className="timelineText"
                                            dangerouslySetInnerHTML={{ __html: event.html }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}