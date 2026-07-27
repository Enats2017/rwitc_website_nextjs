"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getDeclarations } from "../../../services/declarationsService";
import "./Declarations.css";

export default function Declarations() {

    const searchParams = useSearchParams();
    const date = searchParams.get("date");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [dayNarrative, setDayNarrative] = useState("");
    const [races, setRaces] = useState([]);
    const [pools, setPools] = useState([]);

    useEffect(() => {

        async function loadDeclarations() {

            if (!date) {
                setError("No date selected.");
                setLoading(false);
                return;
            }

            try {

                setLoading(true);
                setError(null);

                const data = await getDeclarations(date);

                setDayNarrative(data.dayNarrative);
                setRaces(data.races);
                setPools(data.pools);

            } catch (err) {

                console.error("Declarations Error:", err);
                setError("Unable to load declarations for this date.");

            } finally {

                setLoading(false);

            }

        }

        loadDeclarations();

    }, [date]);

    return (
        <section className="declarationsPage">

            <div className="declarationsHero">
    <h1>Declarations</h1>
    {dayNarrative && (
        <p className="declarationsNarrative">{dayNarrative}</p>
    )}
    <p className="declarationsHint">
        Check the final field for today's races
    </p>
</div>

            <div className="declarationsContainer">

                {loading && (
                    <div className="stateBox">
                        <div className="loader" />
                        <p>Loading declarations…</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="stateBox stateError">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && races.length === 0 && (
                    <div className="stateBox">
                        <p>No declarations found for this date.</p>
                    </div>
                )}

                {!loading && !error && races.map((race, idx) => (

                    <div className="raceBlock" key={idx}>

                        <div className="raceBlockHeader">
                            <div className="raceBlockHeaderLeft">
                                <span className="raceIndex">
                                    {String(race.race_no ?? idx + 1).padStart(2, "0")}
                                </span>
                                <div>
                                    <h2>{race.race_name}</h2>
                                    {race.narrative && (
                                        <p className="raceNarrative">{race.narrative}</p>
                                    )}
                                </div>
                            </div>

                            <div className="raceBlockHeaderRight">
                                {race.distance && (
                                    <span className="raceTag">{race.distance}m</span>
                                )}
                                {race.division && (
                                    <span className="raceTag">{race.division}</span>
                                )}
                                {race.race_time && (
                                    <span className="raceTag">{race.race_time}</span>
                                )}
                                {race.foreign_jockeys_eligible && (
                                    <span className="raceTag raceTagAccent">
                                        Foreign Jockeys Eligible
                                    </span>
                                )}
                            </div>
                        </div>

                        {race.weight_notes && race.weight_notes.length > 0 && (
                            <div className="weightNotes">
                                {race.weight_notes.map((note, nIdx) => (
                                    <p key={nIdx}>{note}</p>
                                ))}
                            </div>
                        )}

                        <div className="tableWrap">
                            <table className="horsesTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Horse</th>
                                        <th>Wt</th>
                                        <th>Rating</th>
                                        <th>Trainer</th>
                                        <th>Jockey</th>
                                        <th>Horse Wt</th>
                                        <th>Shoe</th>
                                        <th>Draw</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {race.horses && race.horses.map((horse, hIdx) => (
                                        <tr key={`${idx}-${hIdx}`}>
                                            <td className="orderCell">{horse.card_no}</td>
                                            <td className="horseNameCell">
                                                {horse.horse?.name}
                                                {horse.horse?.sire && (
                                                    <span className="breedingCell">
                                                        {" "}{horse.horse.sire}
                                                        {horse.horse?.dam ? `-${horse.horse.dam}` : ""}
                                                        {horse.horse?.dam_nationality
                                                            ? ` (${horse.horse.dam_nationality})`
                                                            : ""}
                                                    </span>
                                                )}
                                            </td>
                                            <td>{horse.weight ?? "-"}</td>
                                            <td>{horse.rating ?? "NR"}</td>
                                            <td>{horse.trainer?.name}</td>
                                            <td>
                                                {horse.jockey?.name}
                                                {horse.jockey?.allowance && (
                                                    <span className="allowanceTag">
                                                        {" "}(-{horse.jockey.allowance})
                                                    </span>
                                                )}
                                            </td>
                                            <td>{horse.horse_weight ?? "-"}</td>
                                            <td>{horse.shoe ?? "-"}</td>
                                            <td>{horse.draw ?? "-"}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                    </div>

                ))}

                {!loading && !error && pools.length > 0 && (
                    <div className="poolsBlock">
                        <h3>Pools</h3>
                        <div className="poolsGrid">
                            {pools.map((pool, pIdx) => (
                                <div className="poolCard" key={pIdx}>
                                    <span className="poolName">{pool.name}</span>
                                    <span className="poolRaces">{pool.races}</span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

            </div>

        </section>
    );
}               