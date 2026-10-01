"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getRatingChange } from "../../../services/ratingChangeService";
import { formatArchiveHtml, handleArchiveIframeLoad } from "../../../utils/archiveHtmlHelper";
import "./RatingChange.css";
import { FaHorseHead } from "react-icons/fa";

/*
 * Styles + script injected INSIDE the archive iframe.
 * Iframe always fits the card width; text wraps; only wide
 * tables (if any) scroll sideways (wrapped in .tableScroll).
 */
const ARCHIVE_STYLES_RATING_CHANGE = `
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; max-width: 100%; overflow-x: hidden; }
body { font-family: Arial, sans-serif; padding: 24px 20px 40px; color: #333333; background: #ffffff; word-wrap: break-word; display: flow-root; }
span, a { text-decoration: none; color: #333333; }
.row { display: flex; flex-wrap: wrap; row-gap: 6px; }
.row > div { padding: 2px 10px 2px 0; line-height: 1.7; font-size: 12.5px !important; }
.MsoPlainText { margin: 0 0 10px; line-height: 1.6; }
p.MsoPlainText { margin-bottom: 14px; }
table { max-width: 100%; }
img { max-width: 100%; height: auto; }

/* MOBILE FIX: text wraps inside the card */
p, div, span, pre, .MsoPlainText { max-width: 100%; overflow-wrap: anywhere; }
pre { white-space: pre-wrap; }
@media (max-width: 600px) { body { padding: 12px 8px 24px; } }

/* MOBILE FIX: ONLY wide tables scroll */
.tableScroll { width: 100%; max-width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; margin-bottom: 12px; }
.tableScroll table { min-width: 640px; margin: 0 !important; }
</style>

<script>
(function () {
    function wrapTables() {
        document.querySelectorAll("table").forEach(function (table) {
            if (table.parentElement && table.parentElement.classList.contains("tableScroll")) return;
            if (table.querySelector("table")) return;

            var maxCells = 0;
            table.querySelectorAll("tr").forEach(function (tr) {
                if (tr.children.length > maxCells) maxCells = tr.children.length;
            });
            if (maxCells < 3) return;

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

                const data = await getRatingChange(date, type, raceType);

                setFound(data.found);
                setMessage(data.message);
                setRawHtml(data.html || "");
                setDownloadFile(data.downloadFile || null);
                setDownloadAvailable(data.downloadAvailable || false);
            } catch (err) {
                console.error("Rating Change Error:", err);
                setError("Unable to load rating change for this date.");
            } finally {
                setLoading(false);
            }
        }

        loadRatingChange();
    }, [date, type, raceType]);

    const hasNoHtml = !found || !rawHtml.trim();

    /*
     * Iframe onLoad: run helper, wire the Back link, then keep the iframe
     * height in sync. Height changes only when the value differs, so it
     * never loops or vibrates.
     */
    function handleRatingIframeLoad(e) {
        handleArchiveIframeLoad(e);

        const iframe = e.target;
        const doc = iframe.contentDocument;
        if (!doc || !doc.body) return;

        doc.querySelectorAll("a, button, span").forEach((el) => {
            if (el.textContent.trim().toLowerCase() === "back") {
                el.style.cursor = "pointer";
                el.addEventListener("click", (ev) => {
                    ev.preventDefault();
                    window.history.back();
                });
            }
        });

        const setHeight = () => {
            if (!iframe.isConnected || !doc.body) return;
            const next = Math.ceil(doc.body.offsetHeight);
            const current = parseInt(iframe.style.height, 10);
            if (next !== current) {
                iframe.style.height = next + "px";
            }
        };

        setHeight();
        requestAnimationFrame(setHeight);

        if (iframe.contentWindow?.ResizeObserver) {
            new iframe.contentWindow.ResizeObserver(setHeight).observe(doc.body);
        }
        if (doc.fonts && doc.fonts.ready) {
            doc.fonts.ready.then(setHeight);
        }
    }

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
                        <p>{message || "No rating change found for this date."}</p>
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
                                        window.open(downloadFile, "_blank", "noopener,noreferrer");
                                    }}
                                >
                                    Download / Open HTML
                                </button>
                            </div>
                        )}

                        {/* Iframe fits the card; only wide tables scroll inside it */}
                        <div className="docArchiveScroll">
                            <iframe
                                className="docArchiveHtml"
                                srcDoc={formatArchiveHtml(ARCHIVE_STYLES_RATING_CHANGE, rawHtml)}
                                title="Rating Change"
                                sandbox="allow-same-origin allow-scripts allow-top-navigation allow-forms"
                                scrolling="no"
                                style={{ width: "100%", border: "none" }}
                                onLoad={handleRatingIframeLoad}
                            />
                        </div>
                    </>
                )}
            </div>
        </section>
    );
}