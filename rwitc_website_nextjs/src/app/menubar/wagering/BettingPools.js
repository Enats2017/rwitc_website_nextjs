"use client";

import { FaHorseHead } from "react-icons/fa";
import "./BettingPools.css";

export default function BettingPools() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">How to bet on Tote</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <p>
                            You can go to a manned Tote window each time or purchase a
                            cash voucher from a Tote Service Outlet or call the operator
                            who has a hand held computer. You can either bet on tote odds
                            which is variable or bet on fixed tote odds.
                        </p>

                        <div className="termsBlock">

                            <p>
                                <strong>Win:</strong> Pick a horse to finish 1st.
                            </p>

                            <p>
                                <strong>Place:</strong> Pick a horse to be 1st, 2nd or
                                3rd. In races with 7 or less runners you must be 1st or
                                2nd.
                            </p>

                            <p>
                                <strong>SHP:</strong> The horse you nominate should
                                finish in the second position only for you to get the
                                dividend.
                            </p>

                            <p>
                                <strong>Forecast:</strong> You have to nominate the
                                horses finishing first and second in the correct order.
                            </p>

                            <p>
                                <strong>Quinella:</strong> You can nominate the horses
                                finishing first and second in any order.
                            </p>

                            <p>
                                <strong>Treble:</strong> You have to nominate winners of
                                three races earmarked for this pool. You have the
                                option to buy a combination ticket.
                            </p>

                            <p>
                                <strong>Exacta:</strong> Pick four horses to finish
                                among the first four in the correct order. If you buy a
                                combination ticket, you are a winner if you have the
                                first four finishers in your combined pool.
                            </p>

                            <p>
                                <strong>Trinella/tanala:</strong> Pick 3 horses to
                                finish 1st, 2nd or 3rd in correct order or buy a
                                combination ticket where you need to have the first
                                three finishers in your ticket.
                            </p>

                            <p>
                                <strong>Jackpot:</strong> Your aim is to select the
                                winners of the five races nominated for the jackpot
                                pool. The races which are considered for jackpot pools
                                are announced well in advance by the club at the time
                                of declaration itself. You can either buy a single
                                ticket or through a combination ticket.
                            </p>

                            <p>
                                <strong>Super Jackpot:</strong> The RWITC conducts a
                                super jackpot pool which involves nominating winners in
                                six legs either through combination or through a
                                single ticket which should nominate winners of all
                                races selected for this purpose.
                            </p>

                        </div>

                        <h3 className="guideSubHeading">
                            While betting at the tote windows, bear the following in mind:
                        </h3>

                        <ul className="guideList guideListPlain">
                            <li>The race meeting</li>
                            <li>The race number</li>
                            <li>Amount of money</li>
                            <li>Type of bet</li>
                            <li>Number of the horse</li>
                        </ul>

                        <p>
                            After placing the bet you will receive a ticket with all
                            the details on it. Make sure they are correct. When your
                            horse wins present the ticket to collect your winnings.
                        </p>

                        <h3 className="guideSubHeading">How To Bet On The Bookmakers</h3>

                        <div className="termsBlock">

                            <p>
                                <strong>Win:</strong> Pick a horse to finish 1st.
                            </p>

                            <p>
                                <strong>Each way:</strong> Win and Place bet. The place
                                price is 1/4 or 1/5 of the win price. Only in terms
                                races, the place odds are at the discretion of the
                                bookmaker.
                            </p>

                            <p>
                                <strong>Without the favourite:</strong> With this bet
                                you get reduced odds but two chances to win. Quite
                                often, horses are withdrawn at the gate due to unruly
                                behaviour. If the horse withdrawn happens to be a
                                fancied runner, there will be cut in the dividends.
                            </p>

                        </div>

                        <ol className="guideList">
                            <li>If your horse wins the race.</li>
                            <li>If your horse finishes 2nd to the favourite.</li>
                        </ol>

                    </div>

                </div>

            </div>

        </section>

    );

}