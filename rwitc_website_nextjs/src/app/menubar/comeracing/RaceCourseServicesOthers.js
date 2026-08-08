"use client";

import { FaHorseHead } from "react-icons/fa";
import "./RaceCourseServicesOthers.css";

export default function RaceCourseServicesOthers() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Race Course Race Course Services</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <h2 className="rcsSubHeading">
                        Making it a pleasurable outing for the enthusiasts
                    </h2>

                    <div className="aboutContent">

                        <p>
                            The Royal Western India Turf Club has provided a number of
                            facilities for race goers to have a good day at the race
                            course. Apart from exclusive seating facility for members,
                            the needs of general public are also catered to in great
                            detail. For those buying the lawn badge and coming with
                            their family, there is a huge park which has facilities for
                            children to engage themselves in recreational activities.
                        </p>

                        <p>
                            The RWITC premises are also a veritable gourmet's delight.
                            Food Lovers can feast themselves on the multiple choices
                            that are available. Beginning with South India's delicacies
                            to the all-India favourite, the Biryani, the menu goes far
                            beyond the traditional. Tastiest sandwiches, pastries et al
                            are available. The hugely popular Chaats and Paani Puri
                            stalls provide variety and spice.
                        </p>

                        <p>
                            For the members, there is also the Mini Club House,
                            complete with all the facilities that are a feature of any
                            good Service Club.
                        </p>

                        <p>
                            On important race days, race goers will have the added
                            pleasure of watching live fashion shows, enjoy food
                            festivals, life style events that all contribute to make
                            for an enjoyable day out at racing.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}