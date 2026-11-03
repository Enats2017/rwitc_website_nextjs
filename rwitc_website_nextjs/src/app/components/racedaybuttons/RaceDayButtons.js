"use client";

import { useEffect, useState } from "react";
import { FaPlay } from "react-icons/fa";
import { getRaceDayStatus } from "../../../services/mediaService";
import "./RaceDayButtons.css";

export default function RaceDayButtons() {
    const [status, setStatus] = useState(null);
    const [failed, setFailed] = useState(false);

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

    // Race day: only these 3 buttons
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

    const NOTICE_BUTTONS = (Array.isArray(status?.notices) ? status.notices : [])
        .filter((n) => n && n.href)
        .map((n) => ({
            key: `notice-${n.id}`,
            text: n.text,
            href: n.href,
            video: !!n.video,
        }));

    const buttons = isRaceDay ? LIVE_BUTTONS : NOTICE_BUTTONS;

    if (buttons.length === 0) return null;

    return (
        <section className="raceDayWrap">
            <div className="raceDayRow">
                {buttons.map((btn) => (
                    <a
                        key={btn.key || btn.text}
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