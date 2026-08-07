"use client";

import { FaHorseHead } from "react-icons/fa";
import "./BettingChannels.css";

export default function BettingChannels() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Tote betting and backing with the Bookmakers</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            A punter can place his bets with either the officially
                            operated totalisator pools where your bet can be as little
                            as Rs 10 or with the legal permitted bookmakers operating at
                            the club premises. To back with the bookmakers, the minimum
                            bet that one needs to do is higher than with the totalisator
                            pools
                        </p>

                        <p>
                            The club has set hundreds of tote booths where one can place
                            their bets. The club also offers incentives to those backing
                            with the club operated tote pools by way of giving various
                            bumper prizes which can also include a Mercedes car on the
                            Indian Derby day.
                        </p>

                        <p>
                            Apart from the race course, the club also operates several
                            outside betting centres where one can place a bet on the
                            tote pools which is linked to the club's overall pool.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}