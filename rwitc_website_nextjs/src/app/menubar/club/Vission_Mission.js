"use client";

import { FaHorseHead } from "react-icons/fa";
import "./Vission_Mission.css";

export default function VissionMission() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">The RWITC Vision &amp; Mission Statement</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            RWITC is renowned as the premier racing club in India offering
                            facilities matching the best in the world. It is also the home of
                            the Indian Classics introduced first in 1943 and modelled on the
                            British originals – Classics that are truly national in character.
                        </p>

                        <p className="visionIntroLine">
                            The following in brief is the Club's vision and mission statement:
                        </p>

                        <ul className="visionList">

                            <li>
                                To ensure quality in its race programmes, racing surfaces,
                                racing environment and conduct as behoves one of Asia's most
                                famous race courses and home to the five Indian Classics.
                            </li>

                            <li>
                                To ensure that race courses at Mumbai and Pune continue to be
                                maintained as world-class racing venues so as to measure up to
                                RWITC's reputation as one of the leading race Clubs in Asia.
                            </li>

                            <li>
                                To set the highest standards in the organization and
                                administration of the sport.
                            </li>

                            <li>
                                To provide superior amenities and up-to-date facilities to its
                                racing patrons and members by way of the quality of
                                entertainment, infrastructure and betting facilities.
                            </li>

                            <li>
                                To be totally transparent in every aspect of its working and to
                                be always owner- as well as punter-friendly.
                            </li>

                            <li>
                                To maximize returns from its racing and non-racing activities.
                            </li>

                            <li>
                                To ensure for its sponsors optimum returns on investment.
                            </li>

                            <li>
                                To make horse racing a clean and family-oriented sport.
                            </li>

                            <li>
                                To provide the best working environment to its staff.
                            </li>

                            <li>
                                To contribute its bit toward social causes and be responsive to
                                the needs of the society in to the best of its abilities.
                            </li>

                        </ul>

                    </div>

                </div>

            </div>

        </section>

    );

}