"use client";

import { FaHorseHead } from "react-icons/fa";
import "./JockeyRidingWeight.css";

// DUMMY DATA — replace these arrays with the API response later.

const seasonLabel = "JOCKEYS LICENSED FOR THE SEASON 2026/27";
const asOfLabel = "(As of 28th July 2026)";

const aLicensedJockeys = [
    { no: 1, name: "A. Prakash", weight: "49" },
    { no: 2, name: "A. S. Peter", weight: "52" },
    { no: 3, name: "A. Sandesh", weight: "53" },
    { no: 4, name: "Akshay Gaikwad", weight: "55" },
    { no: 5, name: "Amyn Merchant", weight: "51" },
    { no: 6, name: "Bhawani Singh", weight: "55" },
    { no: 7, name: "C. S. Jodha", weight: "54" },
    { no: 8, name: "C. Umesh", weight: "52" },
    { no: 9, name: "D. R. Shubham", weight: "51.5" },
    { no: 10, name: "Dashrath Singh", weight: "52" },
    { no: 11, name: "H. G. Rathod", weight: "57" },
    { no: 12, name: "Haridas Gore", weight: "53.5" },
    { no: 13, name: "J. Chinoy", weight: "54" },
    { no: 14, name: "K. Nazil", weight: "50" },
    { no: 15, name: "K. Pranil", weight: "49" },
    { no: 16, name: "Kirtish Bhagat", weight: "52" },
    { no: 17, name: "N. B. Kuldeep", weight: "52" },
    { no: 18, name: "N. Bhosale", weight: "48" },
    { no: 19, name: "N. S. Parmar", weight: "49" },
    { no: 20, name: "Neeraj Rawal", weight: "50" },
    { no: 21, name: "P. S. Kaviraj", weight: "54" },
    { no: 22, name: "P. Trevor", weight: "53.5" },
    { no: 23, name: "P. Vinod", weight: "49" },
    { no: 24, name: "Prashant P. Dhebe", weight: "47" },
    { no: 25, name: "R. Ajinkya", weight: "54" },
    { no: 26, name: "S. A. Amit", weight: "53" },
    { no: 27, name: "S. G. Prasad", weight: "51" },
    { no: 28, name: "S. J. Sunil", weight: "53.5" },
    { no: 29, name: "S. Mosin", weight: "54" },
    { no: 30, name: "Shrikant Kamble", weight: "51" },
    { no: 31, name: "T. S. Jodha", weight: "53" },
    { no: 32, name: "Vishal N. Bunde", weight: "51" },
    { no: 33, name: "Vivek G.", weight: "53" },
    { no: 34, name: "Yash Narredu", weight: "53" },
];

const apprenticeJockeys = [
    { no: 1, name: "A. Omkar", weight: "45", allowance: "- 5 kg.", winners: 6, trainer: "Mr. Bezan Chenoy & Ms. Nazzak B. Chenoy" },
    { no: 2, name: "Aditya Waydande", weight: "45.5", allowance: "- 1.5 kg.", winners: 31, trainer: "Mr. Shazaan Shah" },
    { no: 3, name: "Avinash Paswan", weight: "49", allowance: "- 5 kg.", winners: 2, trainer: "Mr. Narendra Lagad" },
    { no: 4, name: "Bharat Singh", weight: "51", allowance: "- 5 kg.", winners: 6, trainer: "Mr. Karthik Ganapathy" },
    { no: 5, name: "Ramswarup", weight: "47", allowance: "- 3.5 kg.", winners: 15, trainer: "Mr. Adhirajsingh Jodha" },
    { no: 6, name: "S. Siddharth", weight: "46", allowance: "- 5 kg.", winners: 8, trainer: "Mr. P. Shroff" },
];

const bLicensedJockeys = [
    { no: 1, name: "A. Ashhad Asbar", weight: "52" },
    { no: 2, name: "Abhishek Mhatre", weight: "50" },
    { no: 3, name: "Akshay Kumar", weight: "51" },
    { no: 4, name: "Antony Raj S.", weight: "55" },
    { no: 5, name: "B. Nikhil", weight: "50" },
    { no: 6, name: "B. R. Kumar", weight: "53" },
    { no: 7, name: "P. Ajeeth Kumar", weight: "52" },
    { no: 8, name: "Shreyas Singh S.", weight: "51" },
    { no: 9, name: "Suraj Narredu", weight: "54" },
];

function splitInHalf(arr) {
    const mid = Math.ceil(arr.length / 2);
    return [arr.slice(0, mid), arr.slice(mid)];
}

function SimpleWeightTable({ rows }) {
    return (
        <table className="jrwTable">
            <thead>
                <tr>
                    <th className="colNo">Sr. No.</th>
                    <th className="colName">NAME</th>
                    <th className="colWeight">Lowest riding weight</th>
                </tr>
            </thead>
            <tbody>
                {rows.map((row) => (
                    <tr key={row.no}>
                        <td className="colNo">{row.no}</td>
                        <td className="colName">{row.name}</td>
                        <td className="colWeight">{row.weight}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

export default function JockeyRidingWeight() {

    const [aLeft, aRight] = splitInHalf(aLicensedJockeys);

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Jockey's Riding Weight</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    {/* SEASON BANNER */}
                    <div className="jrwSeasonBanner">
                        <h2>{seasonLabel}</h2>
                        <p>{asOfLabel}</p>
                    </div>

                    {/* A LICENSED JOCKEYS */}
                    <div className="jrwSectionBar">
                        <span>"A" Licenced Jockeys"</span>
                    </div>

                    <div className="jrwTwoColWrap">
                        <div className="jrwTableWrap">
                            <SimpleWeightTable rows={aLeft} />
                        </div>
                        <div className="jrwTableWrap">
                            <SimpleWeightTable rows={aRight} />
                        </div>
                    </div>

                    {/* APPRENTICE / ALLOWANCE CLAIMING */}
                    <div className="jrwSectionBar">
                        <span>" Apprentice (†) / Allowance Claiming Jockeys"</span>
                    </div>

                    <div className="jrwTableWrap">
                        <table className="jrwTable">
                            <thead>
                                <tr>
                                    <th className="colNo">No.</th>
                                    <th className="colName">NAME</th>
                                    <th className="colWeight">Lowest riding weight</th>
                                    <th className="colAllowance">Allowance Entitle</th>
                                    <th className="colWinners">Total Winners</th>
                                    <th className="colTrainer">Master Trainer</th>
                                </tr>
                            </thead>
                            <tbody>
                                {apprenticeJockeys.map((row) => (
                                    <tr key={row.no}>
                                        <td className="colNo">{row.no}</td>
                                        <td className="colName">{row.name}</td>
                                        <td className="colWeight">{row.weight}</td>
                                        <td className="colAllowance">{row.allowance}</td>
                                        <td className="colWinners">{row.winners}</td>
                                        <td className="colTrainer">{row.trainer}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {/* B LICENSED JOCKEYS */}
                    <div className="jrwSectionBar">
                        <span>" B" Licenced Jockeys"</span>
                    </div>

                    <div className="jrwTableWrap jrwTableNarrow">
                        <SimpleWeightTable rows={bLicensedJockeys} />
                    </div>

                </div>

            </div>

        </section>

    );

}