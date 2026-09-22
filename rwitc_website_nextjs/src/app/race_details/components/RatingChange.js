"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getRatingChange } from "../../../services/ratingChangeService";
import "./RatingChange.css";

const ARCHIVE_STYLES_RATING_CHANGE = `
<style>
    * { box-sizing: border-box; }

    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 24px 20px 40px;
        color: #333333;
        background: #ffffff;
    }

    span, a {
        text-decoration: none;
        color: #333333;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        row-gap: 6px;
    }

    .row > div {
        padding: 2px 10px 2px 0;
        line-height: 1.7;
        font-size: 12.5px !important;
    }

    .MsoPlainText {
        margin: 0 0 10px;
        line-height: 1.6;
    }

    p.MsoPlainText {
        margin-bottom: 14px;
    }

    table {
        max-width: 100%;
    }

    img {
        max-width: 100%;
        height: auto;
    }

    @media (max-width: 500px) {
        body {
            padding: 16px;
        }

        .row > div {
            width: 100% !important;
        }

        table {
            width: 100% !important;
        }
    }
</style>
`;

export default function RatingChange() {
    const searchParams = useSearchParams();

    const date = searchParams.get("date");

    const type = searchParams.get("type") || "rating_change";
    const raceType = searchParams.get("race_type") || "post_race";

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [found, setFound] = useState(false);
    const [message, setMessage] = useState(null);
    const [rawHtml, setRawHtml] = useState("");
    const [downloadFile, setDownloadFile] = useState(null);
    const [downloadAvailable, setDownloadAvailable] = useState(false);

    useEffect(() => {
        async function loadRatingChange() {
            if (!date) {
                setError("No date selected.");
                setLoading(false);
                return;
            }

            try {
                setLoading(true);
                setError(null);

                const data = await getRatingChange(
                    date,
                    type,
                    raceType
                );

                setFound(data.found);
                setMessage(data.message);
                setRawHtml(data.html || "");
                setDownloadFile(data.downloadFile || null);
                setDownloadAvailable(
                    data.downloadAvailable || false
                );
            } catch (err) {
                console.error("Rating Change Error:", err);
                setError(
                    "Unable to load rating change for this date."
                );
            } finally {
                setLoading(false);
            }
        }

        loadRatingChange();
    }, [date, type, raceType]);

    const hasNoHtml = !found || !rawHtml.trim();

    return (
        <section className="ratingChangePage docPage">
            <div className="docBadgeWrap">
                <span className="docBadge">Rating Change</span>
            </div>

            <div className="docContainer">
                {loading && (
                    <div className="docStateBox">
                        <div className="docLoader" />
                        <p>Loading rating change…</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="docStateBox docStateError">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && hasNoHtml && (
                    <div className="docStateBox">
                        <p>
                            {message ||
                                "No rating change found for this date."}
                        </p>
                    </div>
                )}

                {!loading && !error && !hasNoHtml && (
                    <>
                        <div
                            style={{
                                display: "flex",
                                justifyContent: "flex-end",
                                marginBottom: "12px",
                            }}
                        >
                            {downloadAvailable && downloadFile && (
                                <button
                                    type="button"
                                    onClick={() => {
                                        window.open(
                                            downloadFile,
                                            "_blank",
                                            "noopener,noreferrer"
                                        );
                                    }}
                                    style={{
                                        padding: "8px 14px",
                                        border: "1px solid #ccc",
                                        borderRadius: "4px",
                                        background: "#fff",
                                        cursor: "pointer",
                                    }}
                                >
                                    Download / Open HTML
                                </button>
                            )}
                        </div>

                        <iframe
                            className="docArchiveHtml"
                            srcDoc={
                                ARCHIVE_STYLES_RATING_CHANGE +
                                rawHtml
                            }
                            title="Rating Change"
                            sandbox="allow-same-origin"
                            onLoad={(e) => {
                                const iframe = e.target;
                                const doc =
                                    iframe.contentWindow?.document;

                                if (!doc) return;

                                const setHeight = () => {
                                    iframe.style.height =
                                        doc.documentElement
                                            .scrollHeight + "px";
                                };

                                setHeight();
                                requestAnimationFrame(setHeight);
                                setTimeout(setHeight, 100);
                            }}
                        />
                    </>
                )}
            </div>
        </section>
    );
}