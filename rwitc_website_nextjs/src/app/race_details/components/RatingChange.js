"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getRatingChange } from "../../../services/ratingChangeService";
import { formatArchiveHtml, handleArchiveIframeLoad } from "../../../utils/archiveHtmlHelper";
import "./RatingChange.css";
import { FaHorseHead } from "react-icons/fa";

/*
 * Styles injected INSIDE the archive iframe.
 *
 * NOTE: the old "@media (max-width: 500px)" block was removed on purpose.
 * The iframe now keeps a minimum width (see RatingChange.css) inside a
 * scroll wrapper, so the content looks exactly like the desktop version
 * and the user swipes sideways on phones, same as the Race Result page.
 */
const ARCHIVE_STYLES_RATING_CHANGE = `
<style>
    * { box-sizing: border-box; }

    html, body { margin: 0; padding: 0; }

    body {
        font-family: Arial, sans-serif;
        padding: 24px 20px 40px;
        color: #333333;
        background: #ffffff;
        word-wrap: break-word;
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

            <div className="aboutTitleWrap">
                <h1 className="aboutHeading">Rating Change</h1>
                <div className="sectionDivider">
                    <span className="dividerLine dividerLineLeft"></span>
                    <FaHorseHead className="dividerIcon" />
                    <span className="dividerLine dividerLineRight"></span>
                </div>
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
                        {downloadAvailable && downloadFile && (
                            <div className="docActionRow">
                                <button
                                    type="button"
                                    className="docOpenBtn"
                                    onClick={() => {
                                        window.open(
                                            downloadFile,
                                            "_blank",
                                            "noopener,noreferrer"
                                        );
                                    }}
                                >
                                    Download / Open HTML
                                </button>
                            </div>
                        )}

                        {/* The iframe sits in a scroll wrapper so on phones the
                            content keeps its real layout and scrolls sideways
                            instead of breaking. */}
                        <div className="docArchiveScroll">
                            <iframe
                                className="docArchiveHtml"
                                srcDoc={formatArchiveHtml(
                                    ARCHIVE_STYLES_RATING_CHANGE,
                                    rawHtml
                                )}
                                title="Rating Change"
                                sandbox="allow-same-origin allow-scripts allow-top-navigation allow-forms"
                                scrolling="no"
                                style={{ width: "100%", border: "none" }}
                                onLoad={(e) => {
                                    handleArchiveIframeLoad(e);
                                    const doc = e.target.contentDocument;
                                    if (!doc) return;
                                    doc.querySelectorAll("a, button, span").forEach((el) => {
                                        if (el.textContent.trim().toLowerCase() === "back") {
                                            el.style.cursor = "pointer";
                                            el.addEventListener("click", (ev) => {
                                                ev.preventDefault();
                                                window.history.back();
                                            });
                                        }
                                    });
                                }}
                            />
                        </div>
                    </>
                )}
            </div>
        </section>
    );
}