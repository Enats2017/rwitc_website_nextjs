"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getBodyWeightHorses } from "../../../services/bodyWeightHorseService";
import "./BodyWeightHorse.css";

const weightColumns = [
    "R1", "R2", "R3", "R4", "R5", "R6", "R7", "R8", "R9", "R10",
    "R11", "R12", "R13", "R14", "R15", "R16", "R17", "R18", "R19", "R20",
];

export default function BodyWeightHorse() {
    const [searchValue, setSearchValue] = useState("");
    const [bodyWeightData, setBodyWeightData] = useState([]);
    const [filteredData, setFilteredData] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        let cancelled = false;

        const fetchBodyWeightData = async () => {
            try {
                const horses = await getBodyWeightHorses();

                // Column position = run order (R1 = first run of the season)
                const result = horses.map((item) => {
                    const weights = {};

                    item.runs.slice(0, weightColumns.length).forEach((run, index) => {
                        weights[weightColumns[index]] = `${run.raceno}(${run.weight})`;
                    });

                    return {
                        horse: item.horse,
                        weights,
                    };
                });

                if (!cancelled) {
                    setBodyWeightData(result);
                    setFilteredData(result);
                }
            } catch (error) {
                console.error("Body Weight Data Error :", error);
            } finally {
                if (!cancelled) setLoading(false);
            }
        };

        fetchBodyWeightData();

        return () => {
            cancelled = true;
        };
    }, []);

    const applySearch = (value) => {
        const query = value.trim().toLowerCase();

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

    const handleSubmit = (e) => {
        e.preventDefault();
        applySearch(searchValue);
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

                                {loading ? (

                                    <tr>
                                        <td
                                            className="noResults"
                                            colSpan={weightColumns.length + 1}
                                        >
                                            Loading...
                                        </td>
                                    </tr>

                                ) : filteredData.length > 0 ? (

                                    filteredData.map((row) => (

                                        <tr key={row.horse}>

                                            <td className="colHorse">
                                                {row.horse}
                                            </td>

                                            {weightColumns.map((col) => {

                                                const cell = row.weights[col];

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
                                            colSpan={weightColumns.length + 1}
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