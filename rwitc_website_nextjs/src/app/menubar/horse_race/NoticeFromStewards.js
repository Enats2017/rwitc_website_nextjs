"use client";

import Link from "next/link";
import { FaHorseHead } from "react-icons/fa";
import { NEWS_URL } from "../../../services/api";
import "./NoticeFromStewards.css";

// DUMMY DATA — replace this array with the API response later.
// type: "pdf"  -> opens the file in a new tab (link should be a NEWS_URL PDF path)
// type: "page" -> navigates to the in-app detail screen using its id
const noticesData = [
    {
        id: 61,
        date: "13-01-17",
        title: "Re: 5 kilogram claiming apprentice jockeys permitted to carry and use a whip",
        type: "page",
    },
    {
        id: 60,
        date: "25-12-16",
        title: "Re: Penalty for improved performance within a short span of time",
        type: "page",
    },
    {
        id: 59,
        date: "16-12-16",
        title: "Re: Amendment To Clause 6 Of Appendix J, Clause (vi) (b) Of Appendix A And Clause (vi) (b) Of Appendix H of The Rules Of Racing Of The Club",
        type: "page",
    },
    {
        id: 58,
        date: "14-07-16",
        title: "Revision of fines - Use of Whip",
        type: "page",
    },
    {
        id: 57,
        date: "10-07-16",
        title: "Testing for Cobalt",
        type: "page",
    },
];

export default function NoticeFromStewards() {

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
                                {noticesData.map((row, index) => (
                                    <tr key={index}>
                                        <td className="colDate">{row.date}</td>
                                        <td className="colTitle">

                                            {row.type === "pdf" ? (
                                                
                                                <a    href={row.link}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="noticeLink"
                                                >
                                                    {row.title}
                                                </a>
                                            ) : (
                                                <Link
                                                    href={`/menubar?type=steward-notice&id=${row.id}`}
                                                    className="noticeLink"
                                                >
                                                    {row.title}
                                                </Link>
                                            )}

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