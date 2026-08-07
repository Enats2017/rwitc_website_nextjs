"use client";

import { FaHorseHead } from "react-icons/fa";
import "./DeductionNorms.css";

const winDeductionTable = [
    { odds: "If the Current Odds are EVENS or ODDS ON", percent: "50%" },
    { odds: "If the Current Odds are above EVENS and under 5/2", percent: "30%" },
    { odds: "If the Current Odds are 5/2 (2.50 to 1) and under 5/1", percent: "20%" },
    { odds: "If the Current Odds are 5/1 (5 to 1) and under 7/1", percent: "10%" },
    { odds: "If the Current Odds are 7/1 and above", percent: "NIL" },
];

const placeDeductionTable = [
    { odds: "If the Current Odds are EVENS or ODDS ON", percent: "50%" },
    { odds: "If the Current Odds are above EVENS and under 5/2", percent: "30%" },
    { odds: "If the Current Odds are 5/2 (2.50 to 1) and under 5/1", percent: "20%" },
    { odds: "If the Current Odds are 5/1 (5 to 1) and under 7/1", percent: "10%" },
    { odds: "If the Current Odds are 7/1 and above", percent: "NIL" },
];

export default function DeductionNorms() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Deduction Norms</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        <h2 className="dnMainHeading">
                            Revised rules for deduction made by bookmakers/Fixed Odds
                            Betting for withdrawn horses
                        </h2>

                        <h3 className="dnUnderlineHeading">(A) Deduction for WIN bets</h3>

                        <p>
                            The percentage rate of deduction on WIN bets with bookmakers
                            as well as with Fixed Odds Betting in case of
                            withdrawn/non-starter horse/s will be as under:-
                        </p>

                        <div className="statsTableWrap">
                            <table className="statsTable">
                                <thead>
                                    <tr>
                                        <th>Odds on withdrawn Horse/s at the time of Withdrawal</th>
                                        <th className="colPercent">Percentage of deductions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {winDeductionTable.map((row, index) => (
                                        <tr key={index}>
                                            <td className="colOdds">{row.odds}</td>
                                            <td className="colPercent">{row.percent}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <h3 className="dnUnderlineHeading">(B) Deduction for PLACE bets</h3>

                        <p>
                            The percentage rate of deduction on PLACE bets with
                            bookmakers as well as with Fixed Odds Betting in case of
                            withdrawn/non-starter horse/s would be as under:-
                        </p>

                        <div className="statsTableWrap">
                            <table className="statsTable">
                                <thead>
                                    <tr>
                                        <th>Odds on withdrawn Horse/s at the time of Withdrawal</th>
                                        <th className="colPercent">Percentage of deductions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {placeDeductionTable.map((row, index) => (
                                        <tr key={index}>
                                            <td className="colOdds">{row.odds}</td>
                                            <td className="colPercent">{row.percent}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <p>
                            <strong>(C)</strong> The procedure for selection of
                            bookmakers whose betting sheets would be considered for
                            determining the percentage of deduction on WIN and PLACE in
                            case of withdrawal of a horse/s shall be as under:-
                        </p>

                        <ol className="dnRomanList">
                            <li>
                                For the purpose of determining the current odds of
                                withdrawn/non-starter horse, Auditors will take into
                                consideration last recorded entry (closing odds) of 10
                                bookmakers with the highest collection on the previous
                                race day and arrive at average odds.
                            </li>
                            <li>
                                If on any race day there are less than 10 bookmakers
                                operating then the Auditors would consider the betting
                                sheets of all the bookmakers operating.
                            </li>
                            <li>
                                For WIN - Minimum 50% of the 10 selected bookmakers
                                should have received betting on the withdrawn/non-starter
                                horse, failing which there would be no deduction.
                            </li>
                            <li>
                                For PLACE - Minimum 30% of the bookmakers should have
                                received betting on the withdrawn/non-starter horse/s in
                                which case there will be deduction. However, in the
                                event, of a horse which is quoted at odds of 2.5:1 or
                                less for WIN being withdrawn and there is no recorded
                                PLACE bet with the bookmakers on the withdrawn horse then
                                the calculation of deduction for PLACE bets would be 1/4th
                                of the WIN odds.
                            </li>
                            <li>
                                In the case of more than one horse being withdrawn, then
                                each deduction will be worked out separately subject to
                                the condition that if the total percentage of deduction
                                works out to be more than 50% for WIN and PLACE, the sum
                                total of the rate of deduction of all the horses
                                withdrawn shall not exceed 50% for WIN and PLACE.
                            </li>
                            <li>
                                In exceptional circumstances when there is a very short
                                price runner which is withdrawn and where there is no
                                recorded entry of a bet, then deduction shall be
                                announced in consultation with the representatives of
                                bookmakers and the Club's Officials. In case of
                                disagreement between the Bookmakers and the Club's
                                Officials, the decision of the Stewards shall be final
                                and binding.
                            </li>
                            <li>
                                For Mumbai Races the odds of the bookmakers operating at
                                the Mumbai Race Course will be considered as stated
                                above.
                            </li>
                            <li>
                                For Pune Races the odds of the bookmakers operating at
                                the Pune Race Course will be considered as stated above.
                            </li>
                            <li>
                                For all inter-venue betting days, the odds of bookmakers
                                operating only at the Pune Race Course will be considered
                                as stated above.
                            </li>
                        </ol>

                        <p>
                            <strong>(D)</strong> In respect of place bets, in cases of
                            the number of runners being reduced from 8 or more to less
                            than 8, the Bookmakers will pay for 3 places if the
                            horse/s is/are withdrawn after the "announcements" of the
                            results of the previous race. It was clarified that the
                            relevant announcement would be the first announcement, i.e.
                            "The result of the (number of the race) race horse
                            number..." and would not be dependent upon the result of
                            the photo finishes or objections. Should any horse be
                            withdrawn or declared a non-starter after having been
                            declared to start, it shall not affect the number of places
                            to be paid.
                        </p>

                        <p>
                            Two places will be paid when there are four or less than
                            eight horses declared to start and three places will be
                            paid when there are eight or more horses declared to start.
                        </p>

                        <p>
                            Should any horse which was declared to start is
                            "withdrawn" or declared a "non-starter" in a race, after
                            the bookmakers have started to accept bets for that
                            particular race, an announcement of the withdrawal of such
                            horse/s shall be made on the Public Address System.
                        </p>

                        <p>
                            On such an announcement being made concerning the
                            withdrawal of a horse/s, the Bookmakers will immediately
                            draw a line every time under all the last bets accepted by
                            them upto the time of the announcement of the withdrawal of
                            each horse. Any bets accepted by the bookmakers after the
                            drawing of such line and entered below such line will
                            denote that the bets have been laid after the announcement
                            of the respective withdrawal/s of a horse, as the case may
                            be. The bookmakers will then use a GREEN Colour pen to
                            write out the Cards they issue to cash punters in order to
                            denote that these bets have been accepted after the
                            withdrawal and that no deduction on such bets will be
                            permitted to be made by the Bookmakers. Credit punters must
                            ensure that bets laid by them after the withdrawal
                            announcement is made are recorded below the line drawn in
                            the Bookmakers Betting Sheet to indicate that such bets
                            have been accepted after the withdrawal of the horse.
                        </p>

                        <p>
                            In the event of the withdrawal of one or more runners in
                            circumstances which would lead to only one runner and
                            therefore a "Walk over" all bets on the race will be void.
                            The race will be considered a "Walk over" for the purpose
                            of settling bets. Similarly for place bets, if the field is
                            reduced to three or less runners, all PLACE bets on the
                            race will be void.
                        </p>

                        <p>
                            Notwithstanding anything contained in these rules, it will
                            be open to the Secretary to announce a higher or a lower
                            percentage of reduction in any of the cases mentioned in
                            this Rule, and so there may be different percentages of
                            reduction in respect of different bets, and in that case,
                            the reduction will be made in accordance with such
                            announcement and the same will be final and binding on all
                            parties concerned.
                        </p>

                        <p className="dnSignOff">
                            <strong>Mumbai: 25th April 2009</strong>
                        </p>

                    </div>

                </div>

            </div>

        </section>

    );

}