"use client";

import { useEffect, useState } from "react";
import { FaPlay, FaTimes } from "react-icons/fa";
import { getRaceDayStatus } from "../../../services/mediaService";
import "./RaceDayButtons.css";

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL;

// Ye race day pe kabhi nahi dikhenge.
const NOTICE_BUTTONS = [
    // {
    //     text: "Notice for the 111th AGM",
    //     href: `${SITE_URL}/staticpages/news/AGM_Notice_17.09.2026.pdf`,
    //     video: false,
    // },

    // {
    //     text: "111th Annual Report",
    //     href: `${SITE_URL}/staticpages/news/111th_Annual_Report%202025-26.pdf`,
    //     video: false,
    // },

    {
        text: "Proceedings of 111th AGM",
        href: "https://erpuat.rwitc.com/rwitc_website/MEDIA/AGM2026-720p.mp4",
        video: true,
    },

    // {
    //     text: "Block your weekend in Pune",
    //     href: `${SITE_URL}/Block_your_weekend_in_Pune.mp4`,
    //     video: true,
    // },
];

export default function RaceDayButtons() {
    const [status, setStatus] = useState(null);
    const [failed, setFailed] = useState(false);
    const [selectedVideo, setSelectedVideo] = useState(null);

    useEffect(() => {
        let active = true;

        getRaceDayStatus()
            .then((data) => {
                if (!active) return;

                if (data) {
                    setStatus(data);
                } else {
                    setFailed(true);
                }
            })
            .catch(() => {
                if (active) setFailed(true);
            });

        return () => {
            active = false;
        };
    }, []);

    if (!status && !failed) {
        return (
            <section className="raceDayWrap">
                <div className="raceDayRow raceDayLoading" />
            </section>
        );
    }

    const isRaceDay = !!status?.raceDay;

    // RACE DAY ke sirf 3 buttons
    const LIVE_BUTTONS = isRaceDay
        ? [
              {
                  text: "Live Result",
                  href: `/race_details?type=raceResults&date=${status.today}`,
                  video: false,
              },
              {
                  text: "Media Tips",
                  href: status.mediaTipsUrl,
                  video: false,
              },
              {
                  text: "Updates",
                  href: status.updatesUrl,
                  video: false,
              },
          ]
        : [];

    const buttons = isRaceDay ? LIVE_BUTTONS : NOTICE_BUTTONS;

    if (buttons.length === 0) return null;

    return (
        <>
            <section className="raceDayWrap">
                <div className="raceDayRow">
                    {buttons.map((btn) =>
                        btn.video ? (
                            <button
                                key={btn.text}
                                type="button"
                                className="raceDayBtn"
                                onClick={() => setSelectedVideo(btn.href)}
                            >
                                <FaPlay className="videoPlayIcon" />
                                {btn.text}
                            </button>
                        ) : (
                            <a
                                key={btn.text}
                                href={btn.href}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="raceDayBtn"
                            >
                                {btn.text}
                            </a>
                        )
                    )}
                </div>
            </section>

            {selectedVideo && (
                <div
                    className="videoModal"
                    onClick={() => setSelectedVideo(null)}
                >
                    <div
                        className="videoModalContent"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <button
                            type="button"
                            className="videoModalClose"
                            onClick={() => setSelectedVideo(null)}
                            aria-label="Close video"
                        >
                            <FaTimes />
                        </button>

                        <video
                            controls
                            playsInline
                            width="100%"
                            src={selectedVideo}
                        />
                    </div>
                </div>
            )}
        </>
    );
}