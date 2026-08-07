"use client";

import { FaHorseHead } from "react-icons/fa";
import "./JockeyStatistics.css";

// DUMMY DATA — replace this array with the API response later.
// Each object's shape should match exactly what the API will return.
const jockeyData = [
    { jockey: "A. Sandesh", wins: 5, second: 3, third: 2, fourth: 1, totalMounts: 17 },
    { jockey: "P. Trevor", wins: 3, second: 3, third: 1, fourth: 1, totalMounts: 14 },
    { jockey: "Vivek G.", wins: 3, second: 0, third: 2, fourth: 0, totalMounts: 6 },
    { jockey: "Akshay Kumar", wins: 2, second: 2, third: 3, fourth: 1, totalMounts: 9 },
    { jockey: "Yash Narredu", wins: 2, second: 2, third: 0, fourth: 1, totalMounts: 6 },
    { jockey: "Suraj Narredu", wins: 2, second: 0, third: 0, fourth: 0, totalMounts: 2 },
    { jockey: "N. S. Parmar", wins: 1, second: 3, third: 0, fourth: 0, totalMounts: 7 },
    { jockey: "Antony Raj S.", wins: 1, second: 2, third: 2, fourth: 1, totalMounts: 8 },
    { jockey: "Ramswarup", wins: 1, second: 1, third: 1, fourth: 1, totalMounts: 10 },
    { jockey: "Neeraj Rawal", wins: 1, second: 1, third: 1, fourth: 0, totalMounts: 4 },
    { jockey: "K. Nazil", wins: 1, second: 1, third: 0, fourth: 2, totalMounts: 10 },
    { jockey: "Aditya Waydande", wins: 1, second: 0, third: 1, fourth: 0, totalMounts: 5 },
    { jockey: "S. Siddharth", wins: 1, second: 0, third: 0, fourth: 2, totalMounts: 9 },
    { jockey: "Bhawani Singh", wins: 1, second: 0, third: 0, fourth: 0, totalMounts: 2 },
    { jockey: "Shrikant Kamble", wins: 0, second: 2, third: 0, fourth: 0, totalMounts: 6 },
    { jockey: "J. Chinoy", wins: 0, second: 1, third: 2, fourth: 1, totalMounts: 6 },
    { jockey: "A. Omkar", wins: 0, second: 1, third: 1, fourth: 1, totalMounts: 7 },
    { jockey: "Haridas Gore", wins: 0, second: 1, third: 0, fourth: 1, totalMounts: 6 },
    { jockey: "Prashant P. Dhebe", wins: 0, second: 1, third: 0, fourth: 1, totalMounts: 4 },
    { jockey: "S. J. Sunil", wins: 0, second: 1, third: 0, fourth: 0, totalMounts: 5 },
    { jockey: "C. S. Jodha", wins: 0, second: 0, third: 3, fourth: 4, totalMounts: 10 },
    { jockey: "T. S. Jodha", wins: 0, second: 0, third: 2, fourth: 3, totalMounts: 11 },
    { jockey: "Kirtish Bhagat", wins: 0, second: 0, third: 2, fourth: 0, totalMounts: 3 },
    { jockey: "A. S. Peter", wins: 0, second: 0, third: 1, fourth: 1, totalMounts: 7 },
    { jockey: "Amyn Merchant", wins: 0, second: 0, third: 1, fourth: 0, totalMounts: 7 },
    { jockey: "D. R. Shubham", wins: 0, second: 0, third: 0, fourth: 1, totalMounts: 2 },
    { jockey: "K. Pranil", wins: 0, second: 0, third: 0, fourth: 1, totalMounts: 3 },
    { jockey: "P. S. Kaviraj", wins: 0, second: 0, third: 0, fourth: 1, totalMounts: 3 },
    { jockey: "R. Ajinkya", wins: 0, second: 0, third: 0, fourth: 0, totalMounts: 7 },
    { jockey: "Akshay Gaikwad", wins: 0, second: 0, third: 0, fourth: 0, totalMounts: 4 },
    { jockey: "Avinash Paswan", wins: 0, second: 0, third: 0, fourth: 0, totalMounts: 9 },
    { jockey: "Bharat Singh", wins: 0, second: 0, third: 0, fourth: 0, totalMounts: 5 },
    { jockey: "Dashrath Singh", wins: 0, second: 0, third: 0, fourth: 0, totalMounts: 1 },
    { jockey: "N. Bhosale", wins: 0, second: 0, third: 0, fourth: 0, totalMounts: 3 },
    { jockey: "P. Vinod", wins: 0, second: 0, third: 0, fourth: 0, totalMounts: 1 },
    { jockey: "S. Mosin", wins: 0, second: 0, third: 0, fourth: 0, totalMounts: 6 },
];

// DUMMY DATE — replace with the API's "as on" date later.
const asOnDate = "02/08/2026";

function calcWinPercent(wins, totalMounts) {
    if (!totalMounts) return "0";
    const pct = (wins / totalMounts) * 100;
    return Number.isInteger(pct) ? String(pct) : pct.toFixed(2);
}

export default function JockeyStatistics() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Jockey's Statistics</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <h2 className="statsSubHeading">
                        Jockey's Statistics as on {asOnDate}
                    </h2>

                    <div className="statsTableWrap">
                        <table className="statsTable">
                            <thead>
                                <tr>
                                    <th className="colJockey">Jockey</th>
                                    <th>Wins</th>
                                    <th>Second</th>
                                    <th>Third</th>
                                    <th>Fourth</th>
                                    <th>Total Mounts</th>
                                    <th>Win %</th>
                                </tr>
                            </thead>
                            <tbody>
                                {jockeyData.map((row, index) => (
                                    <tr key={index}>
                                        <td className="colJockey">{row.jockey}</td>
                                        <td>{row.wins}</td>
                                        <td>{row.second}</td>
                                        <td>{row.third}</td>
                                        <td>{row.fourth}</td>
                                        <td>{row.totalMounts}</td>
                                        <td>{calcWinPercent(row.wins, row.totalMounts)}</td>
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