"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { getRaceDayReport } from "../../../services/raceDayReportService";
import { formatArchiveHtml, handleArchiveIframeLoad } from "../../../utils/archiveHtmlHelper";
import "./RaceDayReport.css";
import { FaHorseHead } from "react-icons/fa";

/*
 * Styles + script injected INSIDE the archive iframe.
 * Iframe always fits the card width; text wraps; every table is
 * wrapped in .tableScroll so ONLY the table scrolls sideways.
 */
const ARCHIVE_STYLES_RACEDAY_REPORT = `
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; max-width: 100%; overflow-x: hidden; }
body { font-family: Arial, sans-serif; padding: 24px 20px 40px; color: #000000; line-height: 1.5; display: flow-root; }
span, a { text-decoration: none; color: #333333; }
p { margin: 0 0 12px; }
b, strong { font-weight: 700; }
u { text-underline-offset: 2px; }
table { border-collapse: collapse; width: auto; margin: 14px 0; }
th, td { padding: 8px 14px; border: 1px solid #000000; font-size: 13px; color: #222222; text-align: left; white-space: normal; vertical-align: middle; }
th { background: #f2f2f2; font-weight: 700; text-align: center; }
.MsoPlainText { margin: 0 0 10px; line-height: 1.6; }
p.MsoPlainText { margin-bottom: 14px; }
img { max-width: 100%; height: auto; }

/* MOBILE FIX: text wraps inside the card */
p, div, span, pre, .MsoPlainText { max-width: 100%; overflow-wrap: anywhere; }
pre { white-space: pre-wrap; }
@media (max-width: 600px) { body { padding: 12px 8px 24px; } }

/* MOBILE FIX: ONLY tables scroll */
.tableScroll { width: 100%; max-width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; }
.tableScroll table { margin: 14px 0; }
</style>

<script>
(function () {
    function wrapTables() {
        document.querySelectorAll("table").forEach(function (table) {
            if (table.parentElement && table.parentElement.classList.contains("tableScroll")) return;
            if (table.querySelector("table")) return;

            var wrap = document.createElement("div");
            wrap.className = "tableScroll";
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        });
    }

    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", wrapTables);
    else wrapTables();
})();
</script>
`;

export default function RaceDayReport() {

    const router = useRouter();
    const searchParams = useSearchParams();
    const date = searchParams.get("date");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [found, setFound] = useState(false);
    const [message, setMessage] = useState(null);
    const [dayLabel, setDayLabel] = useState(null);
    const [rawHtml, setRawHtml] = useState("");
    const [downloadFile, setDownloadFile] = useState(null);
    const [downloadAvailable, setDownloadAvailable] = useState(false);

    useEffect(() => {

        async function loadRaceDayReport() {

            if (!date) {
                setError("No date selected.");
                setLoading(false);
                return;
            }

            try {

                setLoading(true);
                setError(null);

                const data = await getRaceDayReport(date);

                setFound(data.found);
                setMessage(data.message);
                setDayLabel(data.dayLabel);
                setRawHtml(data.html || "");
                setDownloadFile(data.downloadFile);
                setDownloadAvailable(data.downloadAvailable);

            } catch (err) {

                console.error("Race Day Report Error:", err);
                setError("Unable to load race day report for this date.");

            } finally {

                setLoading(false);

            }

        }

        loadRaceDayReport();

    }, [date]);

    /*
     * Iframe onLoad: run helper, then keep the iframe height in sync.
     * Height = body.offsetHeight, updated only when the value changes,
     * so it can shrink and never loops or vibrates. Wide tables scroll
     * inside the iframe, so the iframe itself never needs to widen.
     */
    const handleReportIframeLoad = (e) => {
        handleArchiveIframeLoad(e);

        const iframe = e.target;
        const doc = iframe.contentWindow?.document;
        if (!doc || !doc.body) return;

        const resize = () => {
            if (!iframe.isConnected || !doc.body) return;

            const nextHeight = Math.ceil(doc.body.offsetHeight);
            if (nextHeight !== parseInt(iframe.style.height, 10)) {
                iframe.style.height = nextHeight + "px";
            }
        };

        resize();
        requestAnimationFrame(resize);

        doc.querySelectorAll("img").forEach((img) => {
            if (!img.complete) {
                img.addEventListener("load", resize);
                img.addEventListener("error", resize);
            }
        });

        if (iframe.contentWindow?.ResizeObserver) {
            new iframe.contentWindow.ResizeObserver(resize).observe(doc.body);
        }
        if (doc.fonts && doc.fonts.ready) {
            doc.fonts.ready.then(resize);
        }
    };

    const hasNoHtml = !found || !rawHtml.trim();

    return (
        <section className="raceDayReportPage docPage">

            <div className="aboutTitleWrap">
                <h1 className="aboutHeading">Raceday Report</h1>
                <div className="sectionDivider">
                    <span className="dividerLine dividerLineLeft"></span>
                    <FaHorseHead className="dividerIcon" />
                    <span className="dividerLine dividerLineRight"></span>
                </div>
            </div>

            <div className="docContainer">

                {!hasNoHtml && (
                    <a
                        className="docBackLink"
                        onClick={(e) => {
                            e.preventDefault();
                            router.back();
                        }}
                        href="#"
                    >
                        {/* Back */}
                    </a>
                )}

                {!hasNoHtml && (
                    <button
                        type="button"
                        className="docDownloadBtn"
                        onClick={() => {
                            if (downloadAvailable && downloadFile) {
                                window.open(downloadFile, "_blank", "noopener,noreferrer");
                            } else {
                                alert("No download file found for this date. Showing data below.");
                            }
                        }}
                    >
                        Download Raceday Report
                    </button>
                )}

                {!hasNoHtml && dayLabel && (
                    <div className="docHeader">
                        <p className="docClub">ROYAL WESTERN INDIA TURF CLUB.</p>
                        <h1 className="docWatermark">RACEDAY REPORT</h1>
                        <p className="docHint">{dayLabel}</p>
                    </div>
                )}

                {loading && (
                    <div className="docStateBox">
                        <div className="docLoader" />
                        <p>Loading race day report…</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="docStateBox docStateError">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && hasNoHtml && (
                    <div className="docStateBox">
                        <p>{message || "No race day report found for this date."}</p>
                    </div>
                )}

                {/* Iframe fits the card; only tables scroll inside it */}
                {!loading && !error && !hasNoHtml && (
                    <div className="docArchiveScroll">
                        <iframe
                            className="docArchiveHtml"
                            srcDoc={formatArchiveHtml(ARCHIVE_STYLES_RACEDAY_REPORT, rawHtml)}
                            title="Raceday Report"
                            sandbox="allow-same-origin allow-scripts allow-top-navigation allow-forms"
                            scrolling="no"
                            onLoad={handleReportIframeLoad}
                        />
                    </div>
                )}

                {!hasNoHtml && (
                    <a
                        className="docBackLink"
                        onClick={(e) => {
                            e.preventDefault();
                            router.back();
                        }}
                        href="#"
                    >
                        Back
                    </a>
                )}

            </div>

        </section>
    );
}