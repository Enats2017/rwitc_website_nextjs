"use client";

import { useEffect, useState } from "react";

import { getHorseRatings } from "../../../services/horseRatingsService";

import "./HorseRatings.css";

function buildSrcDoc(html) {
    return (
        '<meta charset="UTF-8">' +
        '<meta name="viewport" content="width=device-width, initial-scale=1">' +
        html
    );
}

export default function HorseRatings() {
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const [html, setHtml] = useState("");
    const [downloadFile, setDownloadFile] = useState(null);
    const [downloadAvailable, setDownloadAvailable] = useState(false);

    useEffect(() => {
        let cancelled = false;

        async function loadRatings() {
            try {
                setLoading(true);
                setError(null);

                const data = await getHorseRatings();

                if (cancelled) return;

                setHtml(data.html || "");
                setDownloadFile(data.downloadFile || null);
                setDownloadAvailable(!!data.downloadAvailable);
            } catch (err) {
                console.error("Horse Ratings Error:", err);
                if (!cancelled) setError("Unable to load ratings.");
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        loadRatings();

        return () => {
            cancelled = true;
        };
    }, []);

    const hasNoHtml = !html.trim();

    return (
        <div className="horseRatingsPage">

            <div className="horseRatingsHeader">
                {!loading && !error && !hasNoHtml && downloadAvailable && downloadFile && (
                    <a
                        href={downloadFile}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="horseRatingsDownloadBtn"
                    >
                        Download
                    </a>
                )}
            </div>

            <div className="horseRatingsContentWrapper">

                {loading && (
                    <div className="horseRatingsMessage">Loading ratings...</div>
                )}

                {!loading && error && (
                    <div className="horseRatingsMessage">{error}</div>
                )}

                {!loading && !error && hasNoHtml && (
                    <div className="horseRatingsMessage">
                        Ratings are not available yet.
                    </div>
                )}

                {!loading && !error && !hasNoHtml && (
                    <iframe
                        className="horseRatingsFrame"
                        title="Ratings"
                        srcDoc={buildSrcDoc(html)}
                        sandbox="allow-same-origin"
                    />
                )}

            </div>

        </div>
    );
}