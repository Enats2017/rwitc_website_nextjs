"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { FaHorseHead } from "react-icons/fa";
// import { API_URL } from "../../../services/api";
import { SITE_URL } from "../../../services/api";
import "./NoticeFromStewards.css";

function toDisplayDate(dateStr) {
    const d = new Date(dateStr);
    if (isNaN(d)) return "";
    return d.toLocaleDateString("en-GB", { day: "2-digit", month: "2-digit", year: "2-digit" });
}

export default function NoticeFromStewards() {

    const [notices, setNotices] = useState([]);

    useEffect(() => {
        let cancelled = false;

        async function loadList() {
            try {
                // const res = await fetch(`${API_URL}/stewardsReport.php?api=list`);
                const res = await fetch(`${SITE_URL}/horseracing/stewardsReport.php?api=list`);
                const json = await res.json();
                if (!cancelled) setNotices(json.data || []);
            } catch (err) {
                if (!cancelled) setNotices([]);
            }
        }

        loadList();
        return () => { cancelled = true; };
    }, []);

    return (
        <section className="aboutPage">
            <div className="aboutContainer">
                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Stewards Notices</h1>
                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">
                    <div className="statsTableWrap">
                        <table className="statsTable">
                            <thead>
                                <tr>
                                    <th className="colDate">Date</th>
                                    <th className="colTitle">Title</th>
                                </tr>
                            </thead>
                            <tbody>
                                {notices.map((row) => (
                                    <tr key={row.id}>
                                        <td className="colDate">{toDisplayDate(row.racedate)}</td>
                                        <td className="colTitle">
                                            <Link
                                                href={`/menubar?type=steward-notice&id=${row.id}`}
                                                className="noticeLink"
                                            >
                                                {row.title}
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    );
}