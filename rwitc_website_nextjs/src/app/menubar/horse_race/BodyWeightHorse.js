"use client";

import { useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import "./BodyWeightHorse.css";

// DUMMY DATA — replace this array with the API response later.
// Each weight cell can be a plain value, or an object { value, extra }
// where "extra" is the second (red) reading shown next to some entries.
const weightColumns = [
    "B1", "B2", "B3", "B4", "B5", "B6", "B7", "B8", "B9", "B10",
    "B11", "B12", "B13", "B14", "B15", "B16", "B17", "B18", "B19", "B20",
];

const bodyWeightData = [
    { horse: "ABSOLUTE EMPEROR", weights: { B1: "5(450)" } },
    { horse: "ABSOLUTE MAJESTY", weights: { B1: "7(483)" } },
    { horse: "ADMIRABLE", weights: { B1: "4(459)" } },
    { horse: "ADONIS", weights: { B1: "7(483)" } },
    { horse: "AEON FLUX", weights: { B1: "7(409)" } },
    { horse: "AGATHA", weights: { B1: "5(452)" } },
    { horse: "ALEXANDRIA", weights: { B1: "13(474)" } },
    { horse: "ALGONQUIN", weights: { B1: "5(519)", B2: { value: "20", extra: "20;(463)" } } },
    { horse: "ALIZE'S TOILE", weights: { B1: "5(481)" } },
    { horse: "ALPHA STRIKE", weights: { B1: "19(441)" } },
    { horse: "AMAZING RULER", weights: { B1: "23(524)" } },
    { horse: "AMELIA EARHART", weights: { B1: "12(499)" } },
    { horse: "ANSE RAPHAEL", weights: { B1: "19(441)" } },
    { horse: "ANTINO", weights: { B1: "19(427)" } },
    { horse: "ARMITAGE", weights: { B1: "1(445)" } },
    { horse: "ARDAVAN", weights: { B1: "22(454)" } },
    { horse: "ASHWA BRAZIL", weights: { B1: "4(482)", B2: { value: "14", extra: "14;(498)" } } },
    { horse: "ASHWA GYPSY", weights: { B1: "4(430)" } },
    { horse: "AVANTE", weights: { B1: "8(461)", B2: { value: "18", extra: "18;(469)" } } },
    { horse: "AXILROD", weights: { B1: "14(519)" } },
    { horse: "AZALEA", weights: { B1: "25(439)" } },
    { horse: "BEAST MODE", weights: { B1: "6(470)" } },
    { horse: "BEE MAGICAL", weights: { B1: "9(470)" } },
    { horse: "BELIEVE", weights: { B1: "5(498)" } },
    { horse: "BISHOP", weights: { B1: "9(438)" } },
    { horse: "BLUE EYED GIRL", weights: { B1: "12(585)" } },
    { horse: "BLUE JET", weights: { B1: "12(474)" } },
    { horse: "BOHEMIAN RHAPSODY", weights: { B1: "18(412)" } },
    { horse: "BRAVO ZULU", weights: { B1: "16(458)" } },
    { horse: "BRIGHT BUTTON", weights: { B1: "5(482)" } },
];

export default function BodyWeightHorse() {
    const [searchValue, setSearchValue] = useState("");
    const [filteredData, setFilteredData] = useState(bodyWeightData);

    const handleSubmit = (e) => {
        e.preventDefault();
        const query = searchValue.trim().toLowerCase();

        if (!query) {
            setFilteredData(bodyWeightData);
            return;
        }

        setFilteredData(
            bodyWeightData.filter((row) =>
                row.horse.toLowerCase().includes(query)
            )
        );
    };

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Body Weight of Horses</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <form className="horseSearchBar" onSubmit={handleSubmit}>
                        <label className="horseSearchLabel" htmlFor="horseSearchInput">
                            Search By Horse Name
                        </label>

                        <div className="horseSearchInputWrap">
                            <input
                                id="horseSearchInput"
                                type="text"
                                placeholder="Enter Horsename Here"
                                value={searchValue}
                                onChange={(e) => setSearchValue(e.target.value)}
                            />
                            <button type="submit">Submit</button>
                        </div>
                    </form>

                    <p className="weightNote">
                        Body Weight of Horses are shown in{" "}
                        <span className="weightNoteRed">(RED)</span>
                    </p>

                    <div className="statsTableWrap">
                        <table className="statsTable weightTable">
                            <thead>
                                <tr>
                                    <th className="colHorse">HORSES</th>
                                    {weightColumns.map((col) => (
                                        <th key={col}>{col}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {filteredData.length > 0 ? (
                                    filteredData.map((row, index) => (
                                        <tr key={index}>
                                            <td className="colHorse">{row.horse}</td>
                                            {weightColumns.map((col) => {
                                                const cell = row.weights[col];

                                                if (!cell) {
                                                    return <td key={col}></td>;
                                                }

                                                if (typeof cell === "object") {
                                                    return (
                                                        <td key={col}>
                                                            {cell.value}
                                                            {cell.extra && (
                                                                <span className="weightExtra">
                                                                    {cell.extra}
                                                                </span>
                                                            )}
                                                        </td>
                                                    );
                                                }

                                                return <td key={col}>{cell}</td>;
                                            })}
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td className="noResults" colSpan={weightColumns.length + 1}>
                                            No horses found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                </div>

            </div>

        </section>

    );

}