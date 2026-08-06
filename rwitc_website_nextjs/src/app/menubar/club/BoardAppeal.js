"use client";

import { FaHorseHead } from "react-icons/fa";
import "./BoardAppeal.css";

export default function BoardAppeal() {

    const members = [
        "Mr. Shivlal R. Daga, Chairman",
        "Mr. Dilip P. Goculdas",
        "Mr. Asif Lampwala",
        "Ms. Zinia Lawyer",
        "Ms. Manisha Patankar Mhaiskar, I. A. S. (Govt. Nominee)",
        "Mr. Hoshang J. Nazir",
        "Mr. Gulamhusein A. Vahanvaty",
    ];

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Board of Appeal</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            The Board of Appeal deals with the appeals preferred against the
                            decision of the Stewards of the Club.
                        </p>

                        <p>
                            Its six members are elected by the Club members in accordance
                            with the Articles of Association of the Club. 1/3rd of the
                            members, i.e. two members, retire in rotation at each Annual
                            General Meeting and in their place two new members are elected by
                            Club members at the Annual General Meeting.
                        </p>

                        <p>
                            In addition, there is a Government nominee on the Board of
                            Appeal, usually the Additional Chief Secretary, Government of
                            Maharashtra, Home Department.
                        </p>

                        <p className="visionIntroLine">
                            The following are members of the Board of Appeal at present:
                        </p>

                        <ul className="memberList">
                            {members.map((name, index) => (
                                <li key={index}>{name}</li>
                            ))}
                        </ul>

                    </div>

                </div>

            </div>

        </section>

    );

}