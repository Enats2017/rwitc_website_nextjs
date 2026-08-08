"use client";

import { FaHorseHead } from "react-icons/fa";
import "./ComeRacingOverview.css";

export default function ComeRacingOverview() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Enjoy the thrills and spill</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            A day at the races either at Mahalaxmi or in Pune is always
                            full of fun. The Club plans its racing programme in such a
                            way that everyone will have something more to enjoy than
                            just backing a horse or cheering a winner. The variety
                            entertainment programmes, life style events, contest of
                            skills, bumper prizes and other add-ons make it a day to
                            remember and cherish. RWITC is the only turf club in the
                            country to actively encourage people to bring their
                            children thus making it a family day of leisure &amp;
                            entertainment.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}