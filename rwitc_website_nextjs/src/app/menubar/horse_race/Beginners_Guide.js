"use client";

import { FaHorseHead } from "react-icons/fa";
import "./Beginners_Guide.css";

export default function BeginnersGuide() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Beginner's Guide</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <h3 className="guideSubHeading">How to bet: a beginner's guide</h3>
                        <p>
                            You can bet either on the Totes or Totalisators or in the
                            bookmakers' ring. One prerequisite of betting is that you must
                            be 18 years or older.
                        </p>

                        <h3 className="guideSubHeading">Betting on the Totes</h3>
                        <p>
                            You can go to a manned Tote window, buy a cash voucher from a
                            Tote Service Outlet, or call the roving operator with a
                            hand-held computer. You can either bet at variable Tote odds or
                            at fixed Tote odds. With the fixed Tote odds, your winning
                            amount are at the odds prevailing at the time you make your
                            wager are guaranteed and printed on the ticket.
                        </p>

                        <p>
                            <strong>Win (Minimum bet: Rs.10/- only):</strong> Pick a horse
                            to finish 1st.
                        </p>

                        <p>
                            <strong>Place (Minimum bet: Rs.10/- only):</strong> Pick a
                            horse to finish 1st, 2nd or 3rd (in races with 8 runners) or
                            1st, 2nd, 3rd or 4th (in races with 12 or more runners). With 7
                            or fewer runners, your choice must finish 1st or 2nd.
                        </p>

                        <p>
                            <strong>Accumulator Win (Minimum bet: Rs.10/- only):</strong>
                            {" "}This is a multi-race bet in which the winnings are
                            subsequently wagered on each succeeding nominated race.
                        </p>

                        <p>
                            <strong>Accumulator Place (Minimum bet: Rs.10/- only):</strong>
                            {" "}As above for place.
                        </p>

                        <p>
                            <strong>Kenchi:</strong> A permutation of the Accumulator Win
                            or Place wager accepted for a chosen group of 3, 4 or 5 races.
                            The good news with Kenchi is that the chances of your getting
                            back some money are better than with a straight Accumulator.
                            For the minimum unit of Rs.10/-, a 3-race Kenchi requires you
                            to invest Rs.40/-; a 4-race Kenchi, Rs.110/-; and a 5-race
                            Kenchi, Rs.260/-.
                        </p>

                        <p>
                            <strong>Forecast Pool (Minimum bet: Rs.10/- only):</strong>
                            {" "}Pick two horses to finish 1st and 2nd.
                        </p>

                        <p>
                            <strong>Quinella Pool (Minimum bet: Rs.10/- only):</strong>
                            {" "}Just like the Forecast Pool except you win if your choices
                            finish 1st and 2nd or 2nd and 1st. In other words, so long
                            both your chosen horses figure in the first two, the order of
                            finish does not matter.
                        </p>

                        <p>
                            <strong>Second Horse Pool (Minimum bet: Rs.10/- only):</strong>
                            {" "}Pick a horse to come 2nd only.
                        </p>

                        <p>
                            <strong>Tanala Pool (Minimum bet: Rs.10/- only):</strong> Pick
                            3 horses to finish 1st, 2nd or 3rd in the exact order or buy a
                            combination ticket where your first three choices finish in
                            the exact 1-2-3 order to earn the 70% dividend. The
                            consolation 30% dividend is paid on the ticket where the order
                            for the second and third place gets interchanged; in other
                            words, 1-3-2 instead of 1-2-3.
                        </p>

                        <p>
                            <strong>Jackpot (Minimum bet: Rs.10/- only):</strong> Select
                            the winners of the five races designated by the Club for the
                            Jackpot Pool at the declaration stage. You can either buy a
                            single-digit ticket (e.g., 1-8-5-2-12) or a combination ticket
                            (e.g., 1/6-7/8-5-2-6/12). If you get the first four winners
                            correct, you share in the consolation dividend of 30%. If you
                            get all 5 legs right, you share in the 70% dividend amount.
                        </p>

                        <p>
                            <strong>Super Jackpot (Minimum bet: Rs.5/- only):</strong>
                            {" "}RWITC has a Super Jackpot pool with six legs or six races
                            designated for the Super Jackpot Pool at the declaration
                            stage. You can buy either a combination or a single ticket. If
                            you nominate the winners of the first five legs, you share in
                            the 30% dividend amount. If you get all 6 legs right, you
                            share in the 70% dividend amount.
                        </p>

                        <p>
                            <strong>Note:</strong> At the Tote window, please have the
                            following information ready for the operator:
                        </p>

                        <ol className="guideList">
                            <li>The race meeting (i.e., the centre at which the race is about to run in case of main and subsidiary betting)</li>
                            <li>The race number</li>
                            <li>The amount of money you want to bet</li>
                            <li>The type of bet (Win, Place, Each way, Forecast Pool, Quinella Pool, Second Horse Pool, Tanala Pool, Jackpot Pool or Super Jackpot Pool)</li>
                            <li>Number(s) of the horse(s).</li>
                        </ol>

                        <h3 className="guideSubHeading">How To Bet With A Bookmaker</h3>

                        <p>
                            <strong>Win (Minimum bet: Rs.100/- plus taxes):</strong> Pick
                            a horse to finish 1st.
                        </p>

                        <p>
                            <strong>Place (Minimum bet: Rs.100/- plus taxes):</strong>
                            {" "}Pick a horse to place.
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}