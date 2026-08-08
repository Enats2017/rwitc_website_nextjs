"use client";

import { FaHorseHead } from "react-icons/fa";
import "./MembershipOverview.css";

export default function MembershipOverview() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Importance of being a Member</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            It is a privilege to be a Member of the Royal Western India
                            Turf Club, Ltd. as being part of the premier institution is
                            considered a status symbol. The membership of the club is
                            not restricted to those who are based in Mumbai and Pune.
                            Members of the club are drawn from the length and breadth of
                            the country. The membership is drawn from a cross section of
                            the society, with the best known names in the country being
                            part of its membership.
                        </p>

                        <p>
                            The members of the club enjoy special privileges not only on
                            race days and but also while making use of other facilities
                            offered by the club.
                        </p>

                        <p>
                            The membership of the club is sought after by not only
                            those who are ardent race goers but by others as well
                            simply because being a member of one of the oldest and
                            prestigious institutions in the country carries its own
                            charm. There are several categories of members.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}