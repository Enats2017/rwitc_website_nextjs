"use client";

import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { FaHorseHead, FaArrowLeft } from "react-icons/fa";
import "./StewardsReport.css";

// DUMMY DATA — replace with an API call (fetch by id) later.
// Each entry's key is the notice's id.
const noticeDetails = {
    61: {
        heading: "RE: 5 KILOGRAM CLAIMING APPRENTICE JOCKEYS PERMITTED TO CARRY AND USE A WHIP",
        to: "Trainers/ Jockeys.",
        body: [
            "The Stewards of the Club at their meeting held on 12th January 2017 have decided that Apprentice Jockeys, claiming 5 kilograms Allowance in races, will henceforth be permitted to carry and use a whip during the Track Work and Mock races.",
            "This Notice supersedes all other previous Notices in this regard.",
        ],
        signOff: "Assistant Secretary,",
        place: "Mumbai: January 13, 2017.",
    },
    60: {
        heading: "RE: PENALTY FOR IMPROVED PERFORMANCE WITHIN A SHORT SPAN OF TIME",
        to: "Trainers/ Jockeys.",
        body: [
            "Content for this notice will be added here.",
        ],
        signOff: "Assistant Secretary,",
        place: "Mumbai: December 25, 2016.",
    },
    59: {
        heading: "RE: AMENDMENT TO CLAUSE 6 OF APPENDIX J, CLAUSE (vi) (b) OF APPENDIX A AND CLAUSE (vi) (b) OF APPENDIX H OF THE RULES OF RACING OF THE CLUB",
        to: "Trainers/ Jockeys.",
        body: [
            "Content for this notice will be added here.",
        ],
        signOff: "Assistant Secretary,",
        place: "Mumbai: December 16, 2016.",
    },
     58: {
        heading: "Revision of fines - Use of Whip",
        to: "Revision of fines - Use of Whip",
        body: [
            "Content for this notice will be added here.",
        ],
        signOff: "Assistant Secretary,",
        place: "Mumbai: December 16, 2016.",
    },
    57: {
        heading: "TESTING FOR COBALT",
        to: "Trainers/ Jockeys.",
        body: [
            "Content for this notice will be added here.",
        ],
        signOff: "Assistant Secretary,",
        place: "Mumbai: July 10, 2016.",
    },
};

export default function StewardsReport() {

    const searchParams = useSearchParams();
    const id = searchParams.get("id");
    const notice = noticeDetails[id];

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

                    <Link href="/menubar?type=noticefromstewards" className="noticeBackLink">
                        <FaArrowLeft /> Back
                    </Link>

                    {!notice ? (

                        <div className="noticeNotFound">
                            Notice not found.
                        </div>

                    ) : (

                        <div className="noticeDetailWrap">

                            <h2 className="noticeClubName">ROYAL WESTERN INDIA TURF CLUB, LTD</h2>
                            <p className="noticeLabel">NOTICE</p>

                            <p className="noticeTo">To: {notice.to}</p>

                            <h3 className="noticeHeading">{notice.heading}</h3>

                            <div className="noticeBody">
                                {notice.body.map((para, index) => (
                                    <p key={index}>{para}</p>
                                ))}
                            </div>

                            <div className="noticeSignOff">
                                <p>Sd/-</p>
                                <p>{notice.signOff}</p>
                                <p>ROYAL WESTERN INDIA TURF CLUB, LTD</p>
                                <p>{notice.place}</p>
                            </div>

                        </div>

                    )}

                </div>

            </div>

        </section>

    );

}