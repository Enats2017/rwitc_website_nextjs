"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getDeductionNorms } from "../../../services/clubService";
import "./DeductionNorms.css";

// Fixed page heading (title field is removed from the admin panel)
const PAGE_TITLE = "Deduction Norms";

// Wrap every <table> in a scroll container so wide tables never break the layout
function wrapTables(raw) {
    return String(raw)
        .replace(/<table/gi, '<div class="tableScroll"><table')
        .replace(/<\/table>/gi, "</table></div>");
}

export default function DeductionNorms() {
    const [html, setHtml] = useState("");

    useEffect(() => {
        let active = true;

        getDeductionNorms()
            .then((data) => {
                if (!active || !data || data.exists === false) return;
                if (data.text) setHtml(wrapTables(data.text));
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