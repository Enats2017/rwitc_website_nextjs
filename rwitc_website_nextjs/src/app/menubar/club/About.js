"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getAboutRwitc } from "../../../services/clubService";
import "./About.css";

export default function About() {
    const [paragraphs, setParagraphs] = useState([]);

    useEffect(() => {
        let active = true;

        getAboutRwitc()
            .then((data) => {
                if (!active || !data?.text) return;

                const list = String(data.text)
                    .split(/\n\s*\n/)
                    .map((p) => p.trim())
                    .filter(Boolean);

                setParagraphs(list);
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
                    <h1 className="aboutHeading">About RWITC</h1>

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