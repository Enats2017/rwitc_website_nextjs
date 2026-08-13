"use client";

import { useRef } from "react";
import { RWITC_UPLOAD_URL } from "../../../services/api";
import "./HorseRatings.css";

const RATINGS_FILE_URL = `${RWITC_UPLOAD_URL}/static/RATINGS.HTM`;

export default function HorseRatings() {
    const iframeRef = useRef(null);

    const handleIframeLoad = () => {
        try {
            const iframeDoc =
                iframeRef.current?.contentDocument ||
                iframeRef.current?.contentWindow?.document;

            if (!iframeDoc) return;

            const style = iframeDoc.createElement("style");
            style.innerHTML = `
                html, body {
                    display: flex;
                    justify-content: center;
                    margin: 0;
                    padding: 0;
                    scrollbar-width: none;
                    -ms-overflow-style: none;
                }
                html::-webkit-scrollbar,
                body::-webkit-scrollbar {
                    display: none;
                    width: 0;
                    height: 0;
                }
                body {
                    flex-direction: column;
                    align-items: center;
                }
                table {
                    margin: 0 auto;
                }
            `;
            iframeDoc.head.appendChild(style);
        } catch (err) {
            // cross-origin case: can't inject, silently ignore
            console.warn("Could not style iframe content:", err);
        }
    };

    return (
        <div className="horseRatingsPage">
            <div className="horseRatingsHeader">

                <a href={RATINGS_FILE_URL}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="horseRatingsDownloadBtn"
                >
                    Download
                </a>
            </div>

            <div className="horseRatingsContentWrapper">
                <iframe
                    ref={iframeRef}
                    className="horseRatingsFrame"
                    src={RATINGS_FILE_URL}
                    title="Ratings"
                    onLoad={handleIframeLoad}
                />
            </div>
        </div>
    );
}