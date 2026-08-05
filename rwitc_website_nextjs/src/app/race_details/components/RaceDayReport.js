"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { getRaceDayReport } from "../../../services/raceDayReportService";
import "./RaceDayReport.css";

const ARCHIVE_STYLES_RACEDAY_REPORT = `
<style>
    html, body { overflow-x: hidden !important; }
    * { box-sizing: border-box; }
    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 24px 20px 40px;
        color: #000000;
        line-height: 1.5;
    }
    span, a { text-decoration: none; color: #333333; }

    p { margin: 0 0 12px; }
    b, strong { font-weight: 700; }
    u { text-underline-offset: 2px; }

    table { border-collapse: collapse; width: auto; max-width: 100%; margin: 14px 0; }
    th, td {
        padding: 8px 14px;
        border: 1px solid #000000;
        font-size: 13px;
        color: #222222;
        text-align: left;
        white-space: normal;
        vertical-align: middle;
    }
    th {
        background: #f2f2f2;
        font-weight: 700;
        text-align: center;
    }

    .MsoPlainText {
        margin: 0 0 10px;
        line-height: 1.6;
    }

    p.MsoPlainText {
        margin-bottom: 14px;
    }

    @media (max-width: 500px) {
        body { padding: 16px; }
        table, th, td { font-size: 11px; }
    }
</style>
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

    const hasNoHtml = !found || !rawHtml.trim();

    return (
        <section className="raceDayReportPage docPage">

            <div className="docBadgeWrap">
                <span className="docBadge">Raceday Report</span>
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

                {!loading && !error && !hasNoHtml && (
                    <iframe
                        className="docArchiveHtml"
                        srcDoc={ARCHIVE_STYLES_RACEDAY_REPORT + rawHtml}
                        title="Raceday Report"
                        sandbox="allow-same-origin"
                        scrolling="no"
                        onLoad={(e) => {
                            const iframe = e.target;
                            const doc = iframe.contentWindow?.document;
                            if (!doc) return;

                            const setHeight = () => {
                                iframe.style.height = doc.documentElement.scrollHeight + "px";
                            };

                            setHeight();
                            requestAnimationFrame(setHeight);
                            setTimeout(setHeight, 100);
                        }}
                    />
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