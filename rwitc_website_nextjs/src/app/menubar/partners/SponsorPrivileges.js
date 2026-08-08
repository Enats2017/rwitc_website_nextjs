"use client";

import { FaHorseHead } from "react-icons/fa";
import "./SponsorPrivileges.css";

export default function SponsorPrivileges() {

    const privileges = [
        "Conduct contests of skill and award prizes to the public to generate interest",
        "Advertise on the CCTV transmission at all centers (free of charge).",
        "Have full rights for on-site branding across the stands.",
        "Name the race to suit its preference.",
        "Have its CEO / nominee present the trophy.",
        "Be entitled to the free use of lawns above a certain value of sponsorship",
        "Arrange for live entertainment at race time or before or after the event.",
        "Promote the race via mailers/press.",
        "Have access to the Club's 7000+ membership data.",
        "Have reserved parking and seating for its guests.",
        "Have free promotion on the rwitc.com website with links to other racing websites.",
        "Get coverage on a major television network at special rates (with select races telecast live).",
    ];

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">The Privileges of a Sponsor</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <h3 className="guideSubHeading">A sponsor can</h3>

                        <ul className="memberList">
                            {privileges.map((item, index) => (
                                <li key={index}>{item}</li>
                            ))}
                        </ul>

                        <p>
                            <strong>Note:</strong> The Mumbai Racing Season runs from
                            mid-November to end-April and racing is on most Sundays and
                            Thursdays. In Pune, the season is from August to October,
                            with racing on all Saturdays and Sundays.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}