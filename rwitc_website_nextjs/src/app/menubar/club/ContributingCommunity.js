"use client";

import { FaHorseHead } from "react-icons/fa";
import "./ContributingCommunity.css";

export default function ContributingCommunity() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Contributing to the Community</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            As a socially responsible and responsive corporate citizen,
                            RWITC hosts several special race days the proceeds of which are
                            donated to charity. A part of its budget is earmarked for such
                            contributions. The Club members have also helped it to raise
                            significant amount of money on special occasions and in
                            distress situations such as the Gujarat Earthquake.
                        </p>

                        <p>
                            The Club commemorates people who have made a special
                            contribution to the society by naming certain races after them.
                            A recent example was the races honouring the 26/11 martyrs in
                            Mumbai including the Major General Hooda Trophy. The Club also
                            contributed towards a Fund to help the injured in the attacks..
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}