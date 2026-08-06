"use client";

import { FaHorseHead } from "react-icons/fa";
import "./ResponsibleGambling.css";

export default function ResponsibleGambling() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Responsible Gambling</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            The Club whole-heartedly subscribes to and supports the
                            principles of responsible gambling.
                        </p>

                        <p>
                            Children under the age of 18 years are not permitted to place
                            Tote bets or to enter the bookmaker' ring in the Mahalaxmi and
                            Pune Race Courses.
                        </p>

                        <p>
                            Punters are advised to exercise extreme caution and restraint in
                            placing bets, always remember that wagering on races is only a
                            game – not a way to get rich quick. Spend only the amount one
                            can afford to lose. Do not place bets when stressed, upset or
                            depressed. Do not borrow money to wager. Do not try to recover
                            your losses with a single big bet.
                        </p>

                        <p>
                            Please avoid like the plague touts claiming access to
                            "privileged information".
                        </p>

                        <p>
                            Please do not patronise illegal bookmakers at any time or for
                            any reason.
                        </p>

                        <p>
                            Please cooperate with the anti-corruption and security staff
                            who are responsible for curbing illegal betting and bookmaking.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}