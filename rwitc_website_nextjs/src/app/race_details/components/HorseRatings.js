"use client";

import "./HorseRatings.css";

const RATINGS_FILE_URL = "/rwitc_upload/static/RATINGS.HTM";

export default function HorseRatings() {
    return (
        <div className="horseRatingsPage">

            <div className="horseRatingsHeader">
                <a
                    href={RATINGS_FILE_URL}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="horseRatingsDownloadBtn"
                >
                    Download
                </a>
            </div>

            <div className="horseRatingsContentWrapper">
                <iframe
                    className="horseRatingsFrame"
                    title="Ratings"
                    src={RATINGS_FILE_URL}
                />
            </div>

        </div>
    );
}