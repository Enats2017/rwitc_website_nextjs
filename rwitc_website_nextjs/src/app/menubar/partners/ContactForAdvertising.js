"use client";

import { FaHorseHead, FaUser, FaPhoneAlt, FaEnvelope } from "react-icons/fa";
import "./ContactForAdvertising.css";

export default function ContactForAdvertising() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">For Advertising &amp; Sponsorships Contact</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="contactBlock">

                        <div className="contactIcon">
                            <FaUser />
                        </div>

                        <div className="contactDetails">

                            <h3 className="contactName">Mr. Niranjan Singh</h3>
                            <p className="contactRole">Secretary</p>

                            <div className="contactLine">
                                <FaPhoneAlt className="contactLineIcon" />
                                <span>022-20842550 Extn No- 101</span>
                            </div>

                            <div className="contactLine">
                                <FaEnvelope className="contactLineIcon" />
                                <a href="mailto:secretary@rwitc.com">secretary@rwitc.com</a>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </section>

    );

}