"use client";

import { useEffect, useState } from "react";
import { SITE_URL } from "../../../services/api";
import "./HorseRatings.css";

const RATINGS_PAGE_URL = `${SITE_URL}/horseRatings.php?content=1`;

export default function HorseRatings() {
    const [ratingsHtml, setRatingsHtml] = useState("");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        const fetchRatings = async () => {
            try {
                setLoading(true);
                setError("");

                const response = await fetch(RATINGS_PAGE_URL, {
                    cache: "no-store",
                });

                if (!response.ok) {
                    throw new Error(
                        `Failed to load ratings. Status: ${response.status}`
                    );
                }

                const html = await response.text();

                if (!html.trim()) {
                    throw new Error(
                        "Ratings content could not be found."
                    );
                }

                setRatingsHtml(html);
            } catch (err) {
                console.error("Error loading ratings:", err);
                setError("Unable to load ratings.");
            } finally {
                setLoading(false);
            }
        };

        fetchRatings();
    }, []);

    const handleDownload = () => {
        if (!ratingsHtml) {
            return;
        }

        const downloadHtml = `
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Ratings of all horses</title>
                </head>
                <body>
                    ${ratingsHtml}
                </body>
            </html>
        `;

        const blob = new Blob(
            [downloadHtml],
            { type: "text/html;charset=utf-8", }
        );

        const url = URL.createObjectURL(blob);

        const link = document.createElement("a");
        link.href = url;
        link.download = "RATINGS.HTM";

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        URL.revokeObjectURL(url);
    };

    return (
        <div className="horseRatingsPage">
            <div className="horseRatingsHeader">
                <button
                    type="button"
                    onClick={handleDownload}
                    className="horseRatingsDownloadBtn"
                    disabled={!ratingsHtml || loading}
                >
                    Download
                </button>
            </div>

            <div className="horseRatingsContentWrapper">
                {loading && (
                    <div className="horseRatingsLoading">
                        Loading ratings...
                    </div>
                )}

                {!loading && error && (
                    <div className="horseRatingsError">
                        {error}
                    </div>
                )}

                {!loading && !error && ratingsHtml && (
                    <iframe
                        className="horseRatingsFrame"
                        title="Ratings"
                        srcDoc={ratingsHtml}
                    />
                )}
            </div>
        </div>
    );
}