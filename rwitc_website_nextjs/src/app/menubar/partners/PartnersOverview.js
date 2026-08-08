"use client";

import { FaHorseHead } from "react-icons/fa";
import "./PartnersOverview.css";

export default function PartnersOverview() {

    const factors = [
        "Restrictive government policies on tobacco and alcohol advertising.",
        "Escalating costs of media advertising.",
        "Rise in leisure pursuits and sporting events.",
        "The proven record of sponsorship.",
        "Greater media (especially electronic and digital) coverage of sponsored events.",
        "The loss of efficiency of traditional media advertising owing to (a) clutter and (b) zapping between channels when ads come on.",
    ];

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Overview</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            According to DM Sandler, David Shani and Dr DK Stotlar, the
                            insertion of advertisements in the 1896 Olympics Games
                            Official Programme and the products sampling rights bought
                            by Coca-Cola in the 1928 Olympics were the first instances
                            of sponsorship's use as a promotional tool in the modern
                            context.
                        </p>

                        <p>
                            D Jobber cites these chief contributory factors to the
                            increasing popularity of sports sponsorship:
                        </p>

                        <ol className="spNumberedList">
                            {factors.map((item, index) => (
                                <li key={index}>{item}</li>
                            ))}
                        </ol>

                        <p>
                            Sponsorship of RWITC racing events offers excellent
                            opportunities for marketers to catch the eye and the
                            imagination of target users. Racing is a lifestyle sport. It
                            has always been associated with the rich and the famous.
                            Once upon a time, the owners of the performers on the race
                            track, i.e., the horses, were the titled British
                            aristocracy. They were succeeded by the Maharajas and the
                            tycoons and the film stars. The socialites and the
                            celebrities continue in this role even today. Marketers of
                            all sizes can promote lifestyle brands effectively via
                            sponsorship.
                        </p>

                        <p>
                            One classic example of a long and mutually beneficial
                            sponsor-event association is the UB Group sponsorship of the
                            McDowell Indian Derby over the last two decades. Today, the
                            sponsorship of the Derby has taken the event to a different
                            level with stake money of Rs 2 crore on offer. The impact of
                            brand power propelled by sponsorship cannot be quantified,
                            however.
                        </p>

                        <p>
                            The Royal Western India Turf Club has created a record in
                            terms of the number of races that are sponsored which in
                            itself is indicative of the mutual benefit that accrues
                            because of a race being associated with a brand.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}