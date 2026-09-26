"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { getRaceDayStatus } from "../../../services/mediaService";
import "./RaceDayButtons.css";

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL;

const NOTICE_BUTTONS = [
    // {
    //     text: "Notice for the 111th AGM",
    //     href: `${SITE_URL}/staticpages/news/AGM_Notice_17.09.2026.pdf`,
    // },
    // {
    //     text: "111th Annual Report",
    //     href: `${SITE_URL}/staticpages/news/111th_Annual_Report%202025-26.pdf`,
    // },
    {
        text: "Proceedings of 111th AGM",
        href: `${SITE_URL}/2026_AGM.mp4`,
    },
    {
        text: "Block your weekend in Pune",
        href: `${SITE_URL}/Block_your_weekend_in_Pune.mp4`,
    },
];

export default function RaceDayButtons() {
    const [status, setStatus] = useState(null);

    useEffect(() => {
        let active = true;

        getRaceDayStatus().then((data) => {
            if (active) setStatus(data);
        });

        return () => {
            active = false;
        };
    }, []);

    if (!status) {
        return (
            <section className="raceDayWrap">
                <div className="raceDayRow raceDayLoading" />
            </section>
        );
    }

    return (
        <section className="raceDayWrap">
            <div className="raceDayRow">
                {NOTICE_BUTTONS.map((btn) => (
                    <a
                        key={btn.text}
                        href={btn.href}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="raceDayBtn"
                    >
                        {btn.text}
                    </a>
                ))}
            </div>
        </section>
    );
}