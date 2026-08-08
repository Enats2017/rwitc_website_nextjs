"use client";

import { FaHorseHead } from "react-icons/fa";
import "./ClubMembershipPrivileges.css";

export default function ClubMembershipPrivileges() {

    const privileges = [
        "Entry into Members Enclosure exclusively for Members and their guests only.",
        "Preference in allocation of private boxes.",
        "Concession in rates of hiring of lawns (one year after becoming a member) for personal functions, use of helipad.",
        "Exclusive use of the facilities at the Turf Club House, Pune (including residential).",
        "Use of the Club House at the Mumbai Race Course.",
        "Discount at Gallops and Keiba Restaurants at Mahalaxmi. Discounted rates at the Fitness Centre. Discounts at proposed additions on food and beverage / other facilities.",
        "Invitations to major racing events at the Club.",
    ];

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Club Member Privileges</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <h3 className="guideSubHeading">Importance of being a Member</h3>

                        <p>
                            Being a member of the Royal Western India Turf Club, Ltd.,
                            one of the oldest and prestigious institutions in the
                            country, is a privilege and a status symbol.
                        </p>

                        <p>
                            The membership of the Club is not restricted to people based
                            in Mumbai and Pune or ardent race goers but is drawn from a
                            cross section of the society spread over the length and
                            breadth of the country.
                        </p>

                        <p>
                            The members of the Club enjoy special privileges not only on
                            race days and but also while making use of other facilities
                            offered by the Club.
                        </p>

                        <p>
                            There are several categories of members.
                        </p>

                        <h3 className="guideSubHeading">Privileges enjoyed by Members</h3>

                        <ul className="memberList">
                            {privileges.map((item, index) => (
                                <li key={index}>{item}</li>
                            ))}
                        </ul>

                        <h2 className="cmpCenterHeading">
                            AFFILIATED CLUBS WITH RWITC, LTD.
                        </h2>

                    </div>

                </div>

            </div>

        </section>

    );

}