"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getHistory } from "../../../services/clubService";
import "./BequeathingColonialLegacy.css";

const DEFAULT_TITLE = "Bequeathing a colonial legacy";

export default function BequeathingColonialLegacy() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [paragraphs, setParagraphs] = useState([]);

    useEffect(() => {
        let active = true;

        getHistory("bequeathing_legacy")
            .then((data) => {
                if (!active || !data) return;

                if (data.title) setTitle(data.title);

                if (data.text) {
                    const list = String(data.text)
                        .split(/\n\s*\n/)
                        .map((p) => p.trim())
                        .filter(Boolean);

                    setParagraphs(list);
                }
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
                        {paragraphs.map((text, i) => (
                            <p key={i}>{text}</p>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}