"use client";

import { useEffect, useState } from "react";
import { FaPlay } from "react-icons/fa";
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
        href: `${SITE_URL}/2026_AGM.mp4`,
        video: true,
    },

    {
        text: "Block your weekend in Pune",
        href: `${SITE_URL}/Block_your_weekend_in_Pune.mp4`,
        video: true,
    },
];

export default function RaceDayButtons() {
    const [status, setStatus] = useState(null);
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        let active = true;

        getRaceDayStatus()
            .then((data) => {
                if (!active) return;
                if (data) setStatus(data);
                else setFailed(true);
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
        <section className="raceDayWrap">
            <div className="raceDayRow">
                {buttons.map((btn) => (
                    <a
                        key={btn.text}
                        href={btn.href}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="raceDayBtn"
                    >
                        {btn.video && <FaPlay className="videoPlayIcon" />}
                        {btn.text}
                    </a>
                ))}
            </div>
        </section>
    );
}