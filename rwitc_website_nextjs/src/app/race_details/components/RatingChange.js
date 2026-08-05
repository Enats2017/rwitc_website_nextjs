"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getRatingChange } from "../../../services/ratingChangeService";
import "./RatingChange.css";

const ARCHIVE_STYLES_RATING_CHANGE = `
<style>
    * { box-sizing: border-box; }
    body {
        font-family: Arial;
        margin: 0;
        padding: 24px 20px 40px;
    }
    span, a { text-decoration: none; color: #333333; }

    /* The archive markup lays each pair of horses out as two
       divs (23% / 77% width) inside a .row — flex is what turns
       that into the two-column grid seen on the live site. */
    .row {
        display: flex;
        flex-wrap: wrap;
        row-gap: 6px;
    }

    /* Give each name/rating entry the same breathing room the
       live site has — inline font-size stays as-is, we just add
       spacing and bump readability slightly. */
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

    @media (max-width: 500px) {
        body { padding: 16px; }
        .row > div {
            width: 100% !important;
        }
    }
</style>
`;

export default function RatingChange() {

    const searchParams = useSearchParams();
    const date = searchParams.get("date");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [found, setFound] = useState(false);
    const [message, setMessage] = useState(null);
    const [rawHtml, setRawHtml] = useState("");

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

                const data = await getRatingChange(date);

                setFound(data.found);
                setMessage(data.message);
                setRawHtml(data.html || "");

            } catch (err) {

                console.error("Rating Change Error:", err);
                setError("Unable to load rating change for this date.");

            } finally {

                setLoading(false);

            }

        }

        loadRatingChange();

    }, [date]);

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
                        <p>{message || "No rating change found for this date."}</p>
                    </div>
                )}

                {!loading && !error && !hasNoHtml && (
                    <iframe
                        className="docArchiveHtml"
                        srcDoc={ARCHIVE_STYLES_RATING_CHANGE + rawHtml}
                        title="Rating Change"
                        sandbox="allow-same-origin"
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

            </div>

        </section>
    );
}