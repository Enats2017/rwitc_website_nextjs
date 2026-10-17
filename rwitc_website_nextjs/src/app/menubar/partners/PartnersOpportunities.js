"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import DOMPurify from "isomorphic-dompurify";
import { getPartnersOpportunities } from "../../../services/clubService";
import "./PartnersOpportunities.css";

const DEFAULT_TITLE = "Sponsorships At The RWITC Ltd";

const HAS_HTML = /<\/?[a-z][\s\S]*>/i;
const LEAD_LINE = /^##\s+/;

const escapeHtml = (str) =>
    str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;");

// Old entries were plain text. Convert them to HTML so they still display correctly.
function legacyTextToHtml(rawText) {
    return String(rawText)
        .replace(/\r\n?/g, "\n")
        .split(/\n\s*\n/)
        .map((block) => block.trim())
        .filter(Boolean)
        .map((block) =>
            LEAD_LINE.test(block)
                ? `<h3>${escapeHtml(block.replace(LEAD_LINE, ""))}</h3>`
                : `<p>${escapeHtml(block).replace(/\n/g, "<br />")}</p>`
        )
        .join("");
}

export default function PartnersOpportunities() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [html, setHtml] = useState("");

    useEffect(() => {
        let active = true;

        getPartnersOpportunities()
            .then((data) => {
                if (!active || !data?.text) return;

                if (data.title) setTitle(data.title);

                const raw = HAS_HTML.test(data.text)
                    ? data.text
                    : legacyTextToHtml(data.text);

                setHtml(DOMPurify.sanitize(raw));
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
                    <div
                        className="aboutContent cmpRichContent"
                        dangerouslySetInnerHTML={{ __html: html }}
                    />
                </div>
            </div>
        </section>
    );
}