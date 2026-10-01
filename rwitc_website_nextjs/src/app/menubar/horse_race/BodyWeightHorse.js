"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getBodyWeightHorses } from "../../../services/bodyWeightHorseService";
import "./BodyWeightHorse.css";

const weightColumns = [
    "B1", "B2", "B3", "B4", "B5", "B6", "B7", "B8", "B9", "B10",
    "B11", "B12", "B13", "B14", "B15", "B16", "B17", "B18", "B19", "B20",
];

export default function BodyWeightHorse() {
    const [searchValue, setSearchValue] = useState("");
    const [bodyWeightData, setBodyWeightData] = useState([]);
    const [filteredData, setFilteredData] = useState([]);

    useEffect(() => {
        const fetchBodyWeightData = async () => {
            try {
                const data = await getBodyWeightHorses();

                const formattedData = data.reduce((acc, item) => {
                    if (!acc[item.horsenm]) {
                        acc[item.horsenm] = {
                            horse: item.horsenm,
                            weights: {},
                        };
                    }

                    acc[item.horsenm].weights[`B${item.raceno}`] =
                        `${item.raceno}(${item.HORSEWT || "NR"})`;

                    return acc;
                }, {});

                const result = Object.values(formattedData);

                setBodyWeightData(result);
                setFilteredData(result);
            } catch (error) {
                console.error("Body Weight Data Error :", error);
            }
        };

        fetchBodyWeightData();
    }, []);

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
                    <h1 className="aboutHeading">
                        Body Weight of Horses
                    </h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>

                        <FaHorseHead className="dividerIcon" />

                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <form
                        className="horseSearchBar"
                        onSubmit={handleSubmit}
                    >
                        <label
                            className="horseSearchLabel"
                            htmlFor="horseSearchInput"
                        >
                            Search By Horse Name
                        </label>

                        <div className="horseSearchInputWrap">

                            <input
                                id="horseSearchInput"
                                type="text"
                                placeholder="Enter Horsename Here"
                                value={searchValue}
                                onChange={(e) =>
                                    setSearchValue(e.target.value)
                                }
                            />

                            <button type="submit">
                                Submit
                            </button>

                        </div>
                    </form>

                    <p className="weightNote">
                        Body Weight of Horses are shown in{" "}
                        <span className="weightNoteRed">
                            (RED)
                        </span>
                    </p>

                    <div className="statsTableWrap">

                        <table className="statsTable weightTable">

                            <thead>
                                <tr>

                                    <th className="colHorse">
                                        HORSES
                                    </th>

                                    {weightColumns.map((col) => (
                                        <th key={col}>
                                            {col}
                                        </th>
                                    ))}

                                </tr>
                            </thead>

                            <tbody>

                                {filteredData.length > 0 ? (

                                    filteredData.map((row, index) => (

                                        <tr key={index}>

                                            <td className="colHorse">
                                                {row.horse}
                                            </td>

                                            {weightColumns.map((col) => {

                                                const cell =
                                                    row.weights[col];

                                                if (!cell) {
                                                    return (
                                                        <td key={col}></td>
                                                    );
                                                }

                                                return (
                                                    <td
                                                        key={col}
                                                        className="weightValue"
                                                    >
                                                        {cell}
                                                    </td>
                                                );
                                            })}

                                        </tr>

                                    ))

                                ) : (

                                    <tr>

                                        <td
                                            className="noResults"
                                            colSpan={
                                                weightColumns.length + 1
                                            }
                                        >
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