"use client";

import { FaHorseHead } from "react-icons/fa";
import "./MembershipCategories.css";

export default function MembershipCategories() {

    const categories = [
        "Club Membership (the only category with voting rights)",
        "Life Membership",
        "Stand Membership (presently discontinued)",
        "Lady Stand Membership",
        "Invitee",
        "Service Membership",
        "Temporary Membership",
        "Local Membership of the Turf Club House at Pune",
        "Local Corporate Membership of the Turf Club House at Pune",
        "Short Term Associates",
    ];

    const strength = [
        "Club Members - 1684",
        "Life Members - 5784",
        "Stand Members - 1465",
        "Lady Stand Members - 1368",
        "Invitee Members - 108",
        "Local Members (Turf Club House, Pune)- 511",
        "Total - 10920",
    ];

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Categories of Members</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <div className="mcPlainList">
                            {categories.map((item, index) => (
                                <p key={index}>{item}</p>
                            ))}
                        </div>

                        <h3 className="guideSubHeading">Present Membership Strength</h3>

                        <div className="mcPlainList">
                            {strength.map((item, index) => (
                                <p key={index}>{item}</p>
                            ))}
                        </div>

                    </div>

                </div>

            </div>

        </section>

    );

}