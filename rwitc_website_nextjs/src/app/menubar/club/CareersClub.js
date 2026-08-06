"use client";

import { FaHorseHead } from "react-icons/fa";
import "./CareersClub.css";

export default function CareersClub() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Career Opportunities in Racing</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            The activity of racing provides innumerable opportunities for
                            those seeking to make a career in this Sport of Kings. At the
                            administrative level, there is scope for young and enthusiastic
                            people with talent to make their foray in several areas such as
                            working as racing officials such as Stipendiary Stewards,
                            Veterinary doctors, race day officials, computer operators etc.
                            Racing is a labor intensive industry which gives wide scope for
                            employment opportunities directly through the club and also
                            through the several license holders who are part of its
                            activity in related jobs..
                        </p>

                        <p>
                            Racing also provides opportunities for those with a penchant
                            for riding to take a career as a jockey. This of course is
                            demanding because a person wanting to be a jockey should do so
                            at a young age, have the required physical attributes, with
                            light weight. There are also opportunities to become race horse
                            trainers. These two jobs are highly skilled jobs for which only
                            some are suitabl. All in all, racing triggers a huge economic
                            activity with multiple career options.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}