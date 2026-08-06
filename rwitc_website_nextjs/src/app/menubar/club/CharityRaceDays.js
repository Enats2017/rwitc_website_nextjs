"use client";

import { FaHorseHead } from "react-icons/fa";
import "./CharityRaceDays.css";

export default function CharityRaceDays() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Being Receptive to Social Causes</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            The Royal Western India Turf Club has responded to any crisis
                            by way of generous contributions. Apart from raising funds for
                            extraordinary situations like the Gujarat Earthquake, the turf
                            club has earmarked fifteen race days during the Season, the
                            proceeds of which will go to charity. The turf club contributes
                            in excess of Rs. 100 lakhs by way of charity.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}