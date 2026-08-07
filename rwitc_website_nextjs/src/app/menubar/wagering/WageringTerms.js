"use client";

import { FaHorseHead } from "react-icons/fa";
import "./WageringTerms.css";

export default function WageringTerms() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Terms About Wagering</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <h3 className="guideSubHeading">Evens or Even Money</h3>
                        <p>
                            When your stake equals your winnings, e.g., an investment of
                            Rs 10 fetching a return of another Rs 10, totaling to Rs 20
                            including the investment.
                        </p>

                        <h3 className="guideSubHeading">Odds-on</h3>
                        <p>
                            When the returns are less than your investment. For example,
                            if a horse you back is at 80/100, a bet of Rs 100 will return
                            Rs 180 which includes your investment.
                        </p>

                        <h3 className="guideSubHeading">Starting Price</h3>
                        <p>
                            The odds offered on a horse at the time of the start of the
                            race and at which bets are settled by the official
                            bookmakers.
                        </p>

                        <h3 className="guideSubHeading">Place</h3>
                        <p>
                            First, second or third position at the finish.
                        </p>

                        <h3 className="guideSubHeading">Place Bet</h3>
                        <p>
                            Wager on a horse to finish first, second or third. Place
                            bets are given upto two places if there are 4 or more
                            runners; upto three places if there are 8 or more runners.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}