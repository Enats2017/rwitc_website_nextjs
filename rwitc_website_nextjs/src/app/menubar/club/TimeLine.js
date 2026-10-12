"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getHistory } from "../../../services/clubService";
import "./TimeLine.css";

const DEFAULT_TITLE = "Timeline";

function parseEvents(text) {
    return String(text)
        .split("\n")
        .map((line) => line.trim())
        .filter(Boolean)
        .map((line) => {
            const idx = line.indexOf("|");

            if (idx === -1) {
                return { year: "", text: line };
            }

            return {
                year: line.slice(0, idx).trim(),
                text: line.slice(idx + 1).trim(),
            };
        });
}

export default function TimeLine() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [events, setEvents] = useState([]);

    useEffect(() => {
        let active = true;

        getHistory("timeline")
            .then((data) => {
                if (!active || !data) return;

                if (data.title) setTitle(data.title);
                if (data.text) setEvents(parseEvents(data.text));
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
                                    <p className="timelineText">{event.text}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}