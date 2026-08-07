"use client";

import { FaHorseHead } from "react-icons/fa";
import "./TrainerStatistics.css";

// DUMMY DATA — replace this array with the API response later.
const trainerData = [
    { trainer: "Adhirajsingh Jodha", wins: 4, second: 4, third: 2, fourth: 3, totalRunners: 18 },
    { trainer: "P. Shroff", wins: 4, second: 1, third: 4, fourth: 2, totalRunners: 14 },
    { trainer: "M. Narredu", wins: 4, second: 0, third: 1, fourth: 1, totalRunners: 14 },
    { trainer: "Faisal A. Abbas", wins: 3, second: 0, third: 1, fourth: 0, totalRunners: 15 },
    { trainer: "Imtiaz A. Sait", wins: 2, second: 4, third: 1, fourth: 3, totalRunners: 12 },
    { trainer: "M. K. Jadhav", wins: 1, second: 4, third: 0, fourth: 0, totalRunners: 8 },
    { trainer: "Aman Altaf Hussain", wins: 1, second: 3, third: 2, fourth: 1, totalRunners: 14 },
    { trainer: "Dallas Todywalla", wins: 1, second: 2, third: 1, fourth: 0, totalRunners: 9 },
    { trainer: "Deepesh Narredu", wins: 1, second: 1, third: 0, fourth: 0, totalRunners: 5 },
    { trainer: "Hosidar Daji", wins: 1, second: 1, third: 0, fourth: 1, totalRunners: 6 },
    { trainer: "Shazaan Shah", wins: 1, second: 0, third: 1, fourth: 0, totalRunners: 11 },
    { trainer: "Himmat Singh", wins: 1, second: 0, third: 0, fourth: 0, totalRunners: 1 },
    { trainer: "Karthik Ganapathy", wins: 1, second: 0, third: 0, fourth: 0, totalRunners: 4 },
    { trainer: "Narendra Lagad", wins: 0, second: 1, third: 6, fourth: 3, totalRunners: 26 },
    { trainer: "P. S. Chouhan", wins: 0, second: 1, third: 2, fourth: 2, totalRunners: 17 },
    { trainer: "Bezan Chenoy", wins: 0, second: 1, third: 1, fourth: 0, totalRunners: 7 },
    { trainer: "Prasanna Kumar P.", wins: 0, second: 1, third: 0, fourth: 0, totalRunners: 1 },
    { trainer: "Sanjay Kolse", wins: 0, second: 1, third: 0, fourth: 1, totalRunners: 5 },
    { trainer: "Nosher Cama", wins: 0, second: 0, third: 1, fourth: 0, totalRunners: 2 },
    { trainer: "Behram Cama", wins: 0, second: 0, third: 1, fourth: 2, totalRunners: 4 },
    { trainer: "Sangramsinh N. Joshi", wins: 0, second: 0, third: 1, fourth: 2, totalRunners: 8 },
    { trainer: "Altamaash A. Ahmed", wins: 0, second: 0, third: 0, fourth: 0, totalRunners: 2 },
    { trainer: "Nirad Karanjawala", wins: 0, second: 0, third: 0, fourth: 0, totalRunners: 3 },
    { trainer: "Subhag Singh", wins: 0, second: 0, third: 0, fourth: 0, totalRunners: 5 },
    { trainer: "Vinesh", wins: 0, second: 0, third: 0, fourth: 0, totalRunners: 1 },
    { trainer: "Vijay Kasbekar", wins: 0, second: 0, third: 0, fourth: 0, totalRunners: 2 },
    { trainer: "Rehanullah Khan", wins: 0, second: 0, third: 0, fourth: 1, totalRunners: 2 },
    { trainer: "S. Waheed", wins: 0, second: 0, third: 0, fourth: 1, totalRunners: 3 },
    { trainer: "Nazzak B Chenoy", wins: 0, second: 0, third: 0, fourth: 2, totalRunners: 6 },
];

// DUMMY DATE — replace with the API's "as on" date later.
const asOnDate = "02/08/2026";

function calcWinPercent(wins, totalRunners) {
    if (!totalRunners) return "0";
    const pct = (wins / totalRunners) * 100;
    return Number.isInteger(pct) ? String(pct) : pct.toFixed(2);
}

export default function TrainerStatistics() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Trainer's Statistics</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <h2 className="statsSubHeading">
                        Trainer's Statistics as on {asOnDate}
                    </h2>

                    <div className="statsTableWrap">
                        <table className="statsTable">
                            <thead>
                                <tr>
                                    <th className="colTrainer">Trainer</th>
                                    <th>Wins</th>
                                    <th>Second</th>
                                    <th>Third</th>
                                    <th>Fourth</th>
                                    <th>Total Runners</th>
                                    <th>Win %</th>
                                </tr>
                            </thead>
                            <tbody>
                                {trainerData.map((row, index) => (
                                    <tr key={index}>
                                        <td className="colTrainer">{row.trainer}</td>
                                        <td>{row.wins}</td>
                                        <td>{row.second}</td>
                                        <td>{row.third}</td>
                                        <td>{row.fourth}</td>
                                        <td>{row.totalRunners}</td>
                                        <td>{calcWinPercent(row.wins, row.totalRunners)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

        </section>

    );

}