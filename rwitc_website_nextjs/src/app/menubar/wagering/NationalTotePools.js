"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getNationalTotePools } from "../../../services/clubService";
import "./NationalTotePools.css";

// Fixed page heading (title field is removed from the admin panel)
const PAGE_TITLE = "National Tote Pools";

export default function NationalTotePools() {
    const [html, setHtml] = useState("");

    useEffect(() => {
        let active = true;

        getNationalTotePools()
            .then((data) => {
                if (!active || !data) return;
                if (data.text) setHtml(String(data.text));
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
                    <h1 className="aboutHeading">{PAGE_TITLE}</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">
                    {/* Content is sanitized on the server (admin panel) before saving */}
                    <div
                        className="aboutContent guideContent"
                        dangerouslySetInnerHTML={{ __html: html }}
                    />
                </div>
            </div>
        </section>
    );
}