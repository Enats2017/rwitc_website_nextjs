"use client";

import { FaHorseHead } from "react-icons/fa";
import "./MumbaiRaceCourse.css";

export default function MumbaiRaceCourse() {

    const points = [
        "Modelled on the Melbourne Race Course.",
        "Built in 1883 on land facing the sea originally donated by Sir Cusrow N Wadia. Now on perpetual lease from the MCGM.",
        "Length of the Race Track: 2400 metres.",
        "Racing from November to April.",
        "Home to the 5 Indian Classics (Indian Derby on the first Sunday of February).",
        "Highlights: 1000 Guineas, 2000 Guineas, Oaks, Derby, St Leger, Poonawalla Multimillion and many more.",
        "Grandstand now designated a heritage structure.",
    ];

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">The Mahalaxmi Race Course</h1>

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