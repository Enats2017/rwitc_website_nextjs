"use client";

import { FaHorseHead } from "react-icons/fa";
import "./NationalTotePools.css";

export default function NationalTotePools() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">National Tote Pools</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <h2 className="ntpCenterHeading">
                            Enjoy the benefits of Common dividends across all the race courses in INDIA
                        </h2>

                        <p>
                            Patrons betting into a commingled pool can bet with more
                            confidence as bigger pools offer more stable and potentially
                            better dividends both online &amp; On-Course, across all racing
                            centres.
                        </p>

                        <p>
                            Tote System is being upgraded with new software to run with
                            new national tote rules.
                        </p>

                        <h3 className="ntpUnderlineHeading">We are adding three new pools:</h3>

                        <ul className="ntpBulletList">
                            <li>THP (Third Horse Pool)-Nominate a horse to finish 3rd in a race.</li>
                            <li>SHOW Pool - Nominate a horse to finish 1st or 2nd place in a race.</li>
                            <li>MINI-JACKPOT POOL-Nominate Horse to finish 1st in Four different legs (races) designated by Club for Mini-Jackpot pool. Ticket with correctly nominated winners will be eligible for a 100% dividend.</li>
                        </ul>

                        <h3 className="ntpUnderlineHeading">Changes in Existing Pool Rules:</h3>

                        <ul className="ntpBulletList">
                            <li>Tanala pool will pay 100% Dividend, no consolation dividend will be paid. You have to nominate horses to come 1st, 2nd &amp; 3rd position in correct order only.</li>
                            <li>Exacta pool will now pay 100% Dividend You have to nominate horses to finish 1st, 2nd, 3rd and 4th in correct order.</li>
                            <li>Common deduction rates will be applied uniformly across all racing centres.</li>
                        </ul>

                        <p>
                            Pool's investment of the host club's will be merged with the
                            investments of all the other racing centres in India namely-
                            Bangalore, Delhi, Kolkata, Hyderabad, Mysore, Madras, Ooty &amp;
                            RWITC (Mumbai &amp; Pune), and a common dividend is paid across
                            all centres.
                        </p>

                        <h2 className="ntpCenterHeading ntpSpacedTop">
                            Pools available in Combined National Tote
                        </h2>

                        <div className="ntpPoolRow">
                            <span className="ntpPoolLabel">SINGLE LEG POOLS</span>
                            <span>: WIN, PLACE, SHP, THP, AND SHOW.</span>
                        </div>

                        <div className="ntpPoolRow">
                            <span className="ntpPoolLabel">EXOTIC POOLS</span>
                            <span>: FORECAST, QUINELLA, TANALA, AND EXACTA.</span>
                        </div>

                        <div className="ntpPoolRow">
                            <span className="ntpPoolLabel">MULTILEG POOLS</span>
                            <span>: JACKPOT, MINI-JACKPOT, TREBLE.</span>
                        </div>

                        <h2 className="ntpCenterHeading ntpSpacedTop">NATIONAL TOTE POOLS</h2>

                        <h3 className="ntpUnderlineHeading">SINGLE RACE POOLS</h3>

                        <div className="termsBlock">

                            <p>
                                <strong>Win (Minimum bet: Rs.10/- only):</strong> When you
                                bet a horse to WIN, you get dividend only if your horse
                                finishes first.
                            </p>

                            <p>
                                <strong>Place (Minimum bet: Rs.10/- only):</strong> Pick a
                                horse to finish 1st, 2nd or 3rd (in races with 7 or more
                                runners). With more than 4 or upto 6 runners, your choice
                                must finish 1st or 2nd.
                            </p>

                            <p>
                                <strong>Second Horse Pool (Minimum bet: Rs.10/- only):</strong>
                                {" "}Pick a horse to finish 2nd only.
                            </p>

                            <p>
                                <strong>Third Horse Pool (Minimum bet: Rs.10/- only):</strong>
                                {" "}Pick a horse to finish 3rd only.
                            </p>

                            <p>
                                <strong>Show Horse Pool (Minimum bet: Rs.10/- only):</strong>
                                {" "}Pick a horse to finish 1st or 2nd position in a Races.
                            </p>

                            <p>
                                <strong>Forecast Pool (Minimum bet: Rs.10/- only):</strong>
                                {" "}Pick two horses to finish 1st and 2nd in correct order.
                            </p>

                            <p>
                                <strong>Quinella Pool (Minimum bet: Rs.10/- only):</strong>
                                {" "}Pick two horses to finish 1st and 2nd or 2nd and 1st
                                irrespective of order.
                            </p>

                            <p>
                                <strong>Tanala Pool (Minimum bet: Rs.10/- only):</strong>
                                {" "}Pick 3 horses to finish 1st, 2nd or 3rd in the exact
                                order or buy a combination ticket where your first three
                                choices finish in the exact 1-2-3 order to earn the 100%
                                dividend.
                            </p>

                            <p>
                                <strong>Exacta Pool (Minimum bet: Rs.10/- only):</strong>
                                {" "}Pick four horses to finish 1st, 2nd, 3rd and 4th in
                                correct order.
                            </p>

                        </div>

                        <h3 className="ntpUnderlineHeading">MULITLEG RACE POOLS</h3>

                        <div className="termsBlock">

                            <p>
                                <strong>Treble Pool (Minimum bet: Rs.10/- only):</strong>
                                {" "}Select the winners of the three races designated by the
                                Club for the Treble Pool at the declaration stage. You can
                                either buy a single-digit ticket (e.g., 1-8-5) or a
                                combination ticket (e.g., 1/6-7/8-5). If you get the first
                                three winners correctly, you get share in the 100% dividend
                                amount.
                            </p>

                            <p>
                                <strong>Mini Jackpot (Minimum bet: Rs.10/- only):</strong>
                                {" "}Select the winners of the four races designated by the
                                Club for the Mini Jackpot Pool at the declaration stage. You
                                can either buy a single-digit ticket (e.g., 1-8-5-2) or a
                                combination ticket (e.g., 1/6-7/8-5-2). If you get the first
                                four winners correctly, you get share in the 100% dividend
                                amount.
                            </p>

                            <p>
                                <strong>Jackpot (Minimum bet: Rs.10/- only):</strong> Select
                                the winners of the five races designated by the Club for the
                                Jackpot Pool at the declaration stage. You can either buy a
                                single-digit ticket (e.g., 1-8-5-2-12) or a combination
                                ticket (e.g., 1/6-7/8-5-2-6/12). If you get the first four
                                winners correct, you get share in the consolation dividend
                                of 30%. If you get all 5 legs right, you get share in the
                                70% dividend amount.
                            </p>

                            <p>
                                <strong>Super Jackpot (Minimum bet: Rs.5/- only):</strong>
                                {" "}Select the winners of the Six races designated by the
                                Club for the Super Jackpot Pool at the declaration stage.
                                You can either buy a single-digit ticket (e.g.,
                                1-8-5-2-12-3) or a combination ticket (e.g.,
                                1/6-7/8-5-2-6/12-3). If you get the first five winners
                                correct, you get share in the consolation dividend of 30%.
                                If you get all Six legs right, you get share in the 70%
                                dividend amount.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        </section>

    );

}