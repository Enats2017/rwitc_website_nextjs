"use client";

import { FaHorseHead } from "react-icons/fa";
import "./Structure.css";

export default function Structure() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Structure</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            The management of the Club, its finances and property is the
                            responsibility of the 9-member Managing Committee. Club members
                            elect these 9 Committee members as laid down in the Articles of
                            Association of the Club. There are, in addition, two Government
                            nominees on the Committee - usually the Additional Chief
                            Secretary, Government of Maharashtra, Home Department and the
                            Additional Chief Secretary, Government of Maharashtra, Revenue and
                            Forests Department. The Committee retires every year. Fresh
                            elections take place on the third Thursday of December. The
                            Chairman is elected by the members of the Managing Committee.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}