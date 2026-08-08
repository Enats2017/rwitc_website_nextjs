"use client";

import { FaHorseHead } from "react-icons/fa";
import "./HowToGetThere.css";

export default function HowToGetThere() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">How To Get There</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <div className="mapBlock">

                            <h3 className="mapLabel">Mumbai Race Course</h3>

                            <div className="mapFrameWrap">
                                <iframe
                                    src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d60378.851459649566!2d72.74879291787695!3d18.945632096977132!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0xb6ce08c6304dcc32!2sMahalakshmi%20Race%20Course!5e0!3m2!1sen!2sin!4v1664623453048!5m2!1sen!2sin"
                                    style={{ border: 0, width: "100%", height: "100%" }}
                                    allowFullScreen=""
                                    loading="lazy"
                                    referrerPolicy="no-referrer-when-downgrade"
                                    title="Mumbai Race Course Map"
                                ></iframe>
                            </div>

                            
                            <a    href="https://goo.gl/maps/UQ8HyecL3DhwVnbC7"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="mapLargerLink"
                            >
                                View Larger Map
                            </a>

                        </div>

                        <div className="mapDivider"></div>

                        <div className="mapBlock">

                            <h3 className="mapLabel">Pune Race Course</h3>

                            <div className="mapFrameWrap">
                                <iframe
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d60535.06790450531!2d73.82671053124999!3d18.508929500000008!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bc2c0354aa4bebd%3A0xe172c323435d288a!2sRoyal%20Western%20India%20Turf%20Club%20Limited!5e0!3m2!1sen!2sin!4v1664623938652!5m2!1sen!2sin"
                                    style={{ border: 0, width: "100%", height: "100%" }}
                                    allowFullScreen=""
                                    loading="lazy"
                                    referrerPolicy="no-referrer-when-downgrade"
                                    title="Pune Race Course Map"
                                ></iframe>
                            </div>

                            
                            <a    href="https://goo.gl/maps/ZgiCx2Tozavyrqih7"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="mapLargerLink"
                            >
                                View Larger Map
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </section>

    );

}