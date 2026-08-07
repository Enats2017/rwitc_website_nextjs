"use client";

import { FaHorseHead } from "react-icons/fa";
import "./ReadyReckoner.css";

// DUMMY DATA — replace this array with the API response later.
const readyReckonerData = [
    { weight: "49.00", I: 80, II: 60, III: 40, IV: 20, V: 4 },
    { weight: "49.50", I: 81, II: 61, III: 41, IV: 21, V: 5 },
    { weight: "50.00", I: 82, II: 62, III: 42, IV: 22, V: 6 },
    { weight: "50.50", I: 83, II: 63, III: 43, IV: 23, V: 7 },
    { weight: "51.00", I: 84, II: 64, III: 44, IV: 24, V: 8 },
    { weight: "51.50", I: 85, II: 65, III: 45, IV: 25, V: 9 },
    { weight: "52.00", I: 86, II: 66, III: 46, IV: 26, V: 10 },
    { weight: "52.50", I: 87, II: 67, III: 47, IV: 27, V: 11 },
    { weight: "53.00", I: 88, II: 68, III: 48, IV: 28, V: 12 },
    { weight: "53.50", I: 89, II: 69, III: 49, IV: 29, V: 13 },
    { weight: "54.00", I: 90, II: 70, III: 50, IV: 30, V: 14 },
    { weight: "54.50", I: 91, II: 71, III: 51, IV: 31, V: 15 },
    { weight: "55.00", I: 92, II: 72, III: 52, IV: 32, V: 16 },
    { weight: "55.50", I: 93, II: 73, III: 53, IV: 33, V: 17 },
    { weight: "56.00", I: 94, II: 74, III: 54, IV: 34, V: 18 },
    { weight: "56.50", I: 95, II: 75, III: 55, IV: 35, V: 19 },
    { weight: "57.00", I: 96, II: 76, III: 56, IV: 36, V: 20 },
    { weight: "57.50", I: 97, II: 77, III: 57, IV: 37, V: 21 },
    { weight: "58.00", I: 98, II: 78, III: 58, IV: 38, V: 22 },
    { weight: "58.50", I: 99, II: 79, III: 59, IV: 39, V: 23 },
    { weight: "59.00", I: 100, II: 80, III: 60, IV: 40, V: 24 },
    { weight: "59.50", I: 101, II: 81, III: 61, IV: 41, V: 25 },
    { weight: "60.00", I: 102, II: 82, III: 62, IV: 42, V: 26 },
    { weight: "60.50", I: 103, II: 83, III: 63, IV: 43, V: 27 },
    { weight: "61.00", I: 104, II: 84, III: 64, IV: 44, V: 28 },
    { weight: "61.50", I: 105, II: 85, III: 65, IV: 45, V: 29 },
    { weight: "62.00", I: 106, II: 86, III: 66, IV: 46, V: 30 },
];

const readyReckonerNotes = [
    "Ratings are in points where 1 point equals 0.5 kg.",
    "The minimum Top Weight is 59 kgs with Bottom Weight being 49 kgs.",
    "Starting rating mark for 3 year olds is 30 points for Colts & Geldings and 27 points for Fillies.",
];

const readyReckonerDate = "Mumbai: November 10, 2022";

export default function ReadyReckoner() {

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Ready Reckoner</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="reckonerTitleBlock">
                        <p className="reckonerClubName">Royal Western India Turf Club Ltd.</p>
                        <p className="reckonerSubTitle">Ready Reckoner</p>
                    </div>

                    <div className="statsTableWrap">
                        <table className="statsTable reckonerTable">
                            <thead>
                                <tr>
                                    <th className="colWeight" rowSpan={2}>
                                        Weight (Kg)
                                    </th>
                                    <th colSpan={5}>Class</th>
                                </tr>
                                <tr>
                                    <th>I Rating</th>
                                    <th>II Rating</th>
                                    <th>III Rating</th>
                                    <th>IV Rating</th>
                                    <th>V Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                {readyReckonerData.map((row, index) => (
                                    <tr key={index}>
                                        <td className="colWeight">{row.weight}</td>
                                        <td>{row.I}</td>
                                        <td>{row.II}</td>
                                        <td>{row.III}</td>
                                        <td>{row.IV}</td>
                                        <td>{row.V}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="reckonerNotes">
                        {readyReckonerNotes.map((note, index) => (
                            <p key={index}>{note}</p>
                        ))}
                    </div>

                    <p className="reckonerDate">{readyReckonerDate}</p>

                </div>

            </div>

        </section>

    );

}