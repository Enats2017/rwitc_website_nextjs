"use client";

import { FaHorseHead } from "react-icons/fa";
import "./PuneRaceCourse.css";

export default function PuneRaceCourse() {

    const points = [
        "Built in 1830.",
        "Total area: 118.5 acres.",
        "Additional stabling at Empress Gardens (one km from the Race Course)",
        "Racing from July to October.",
        "Highlights: The Pune Derby, The RWITC Gold Cup, Independence Cup, Southern Command Cup and many more.",
        "Venue for the annual sales of 2-year olds in February.",
    ];

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">The Pune Race Course</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <ul className="memberList">
                            {points.map((item, index) => (
                                <li key={index}>{item}</li>
                            ))}
                        </ul>

                    </div>

                </div>

            </div>

        </section>

    );

}