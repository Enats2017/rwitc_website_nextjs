"use client";

import { FaHorseHead } from "react-icons/fa";
import "./StewardsClub.css";

export default function StewardsClub() {

    const stewards = [
        "Dr. Ram H. Shroff (Chairman)",
        "Lt. Gen. Rajesh Pushkar, AVSM, VSM, GOC-in-C, Southern Command",
        "Mr. Jiyaji M. Bhosale",
        "Mr. Khushroo N. Dhunjibhoy",
        "Mr. Amitesh Kumar, IPS",
        "Mr. Sunil G. Jhangiani",
        "Mr. Gautam P. Lala",
        "Mr. S. R. Sanas",
        "Mr. Sanjay D. Shah",
        "Mr. Vijay B. Shirke",
        "Mr. Sanjeev Kumar Singhal, IPS",
        "Mr. Rustom H. Vakil",
    ];

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Stewards of the Club</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            The Stewards are responsible for the conduct of racing and have
                            jurisdiction over all racing matters. The Chairman of the Stewards
                            is chosen by the Stewards of the Club.
                        </p>

                        <p>
                            After the annual elections of the Managing Committee, the
                            Committee nominates nine Club members of the Club to serve as the
                            Stewards of the Club for the period of its own tenure. There are
                            two Government nominees as additional Stewards of the Club,
                            usually the Commissioner/Joint Commissioner of Police, Mumbai/Pune
                            and the Director General, Anti-Corruption Bureau. In addition, the
                            Club invites the GOC-in-Chief, Southern Command, Pune, to be a
                            Steward of the Club.
                        </p>

                        <p className="visionIntroLine">
                            The following are the Stewards of the Club at present:
                        </p>

                        <ul className="memberList">
                            {stewards.map((name, index) => (
                                <li key={index}>{name}</li>
                            ))}
                        </ul>

                    </div>

                </div>

            </div>

        </section>

    );

}