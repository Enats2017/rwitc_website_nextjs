"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getSponsorPrivileges } from "../../../services/clubService";
import "./SponsorPrivileges.css";

const DEFAULT_TITLE = "The Privileges of a Sponsor";

// Line markers used in the OLD plain-text content
const HEADING_LINE = /^##\s+/;       // "## A sponsor can"
const BULLET_LINE = /^[-*•]\s+/;     // "- item"
const NOTE_PREFIX = /^note:\s*/i;    // "Note: text"

const escapeHtml = (s) =>
    s.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");

// Old content was plain text. If the saved text has no HTML tags,
// convert it to HTML (heading / list / note / paragraph).
function toHtml(rawText) {
    const value = String(rawText || "").trim();
    if (!value) return "";
    if (/<[a-z][^>]*>/i.test(value)) return value;

    const lines = value.replace(/\r\n?/g, "\n").split("\n");
    let html = "";
    let para = [];
    let list = [];

    const flushPara = () => {
        if (!para.length) return;
        const text = para.join(" ").trim();
        if (NOTE_PREFIX.test(text)) {
            html += `<p><strong>Note:</strong> ${escapeHtml(text.replace(NOTE_PREFIX, ""))}</p>`;
        } else {
            html += `<p>${escapeHtml(text)}</p>`;
        }
        para = [];
    };

    const flushList = () => {
        if (!list.length) return;
        html += `<ul>${list.map((i) => `<li>${escapeHtml(i)}</li>`).join("")}</ul>`;
        list = [];
    };

    lines.forEach((line) => {
        const t = line.trim();

        if (!t) {
            flushPara();
            flushList();
            return;
        }

        if (HEADING_LINE.test(t)) {
            flushPara();
            flushList();
            html += `<h2>${escapeHtml(t.replace(HEADING_LINE, ""))}</h2>`;
            return;
        }

        if (BULLET_LINE.test(t)) {
            flushPara();
            list.push(t.replace(BULLET_LINE, ""));
            return;
        }

        flushList();
        para.push(t);
    });

    flushPara();
    flushList();

    return html;
}

export default function SponsorPrivileges() {
    const [title, setTitle] = useState(DEFAULT_TITLE);
    const [html, setHtml] = useState("");

    useEffect(() => {
        let active = true;

        getSponsorPrivileges()
            .then((data) => {
                if (!active || !data?.text) return;

                if (data.title) setTitle(data.title);
                setHtml(toHtml(data.text));
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
