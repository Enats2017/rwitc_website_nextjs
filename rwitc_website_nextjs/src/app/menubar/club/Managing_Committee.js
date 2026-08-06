"use client";

import { FaHorseHead } from "react-icons/fa";
import "./Managing_Committee.css";

export default function ManagingCommittee() {

    const members = [
        "Mr. S. R. Sanas (Chairman)",
        "Mr. Jiyaji M. Bhosale",
        "Mr. Khushroo N. Dhunjibhoy",
        "Mr. Sunil G. Jhangiani",
        "Mr. Vikas Kharage, IAS (Govt. Nominee)",
        "Mr. Gautam P. Lala",
        "Ms. Manisha Patankar Mhaiskar, IAS (Govt. Nominee)",
        "Mr. Jaydev M. Mody",
        "Mr. Vijay B. Shirke",
        "Dr. Ram H Shroff",
        "Mr. Shiven Surendranath",
    ];

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Managing Committee of the Club</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            The management of the Club and the control over the funds and
                            property of the Club vests in the Committee consisting of 9 Club
                            Members elected by the Club Members in accordance with the
                            provisions contained in the Articles of Association of the Club.
                            In addition to this there are two Government Nominees on the
                            Committee who are usually the Additional Chief Secretary,
                            Government of Maharashtra, Home Department and the Additional
                            Chief Secretary, Government of Maharashtra, Revenue and Forests
                            Department. The Committee retires every year. The Chairman is
                            elected by the Members of the Managing Committee.
                        </p>

                        <p className="visionIntroLine">
                            The following are the Committee Members of the Club at present:
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