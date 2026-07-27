"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getAcceptance } from "../../../services/acceptanceService";
import "./Acceptance.css";

export default function Acceptance() {

    const searchParams = useSearchParams();
    const date = searchParams.get("date");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [dayNarrative, setDayNarrative] = useState("");
    const [races, setRaces] = useState([]);
    const [pools, setPools] = useState([]);

    useEffect(() => {

        async function loadAcceptance() {

            if (!date) {
                setError("No date selected.");
                setLoading(false);
                return;
            }

            try {

                setLoading(true);
                setError(null);

                const data = await getAcceptance(date);

                setDayNarrative(data.dayNarrative);
                setRaces(data.races);
                setPools(data.pools);

            } catch (err) {

                console.error("Acceptance Error:", err);
                setError("Unable to load acceptances for this date.");

            } finally {

                setLoading(false);

            }

        }

        loadAcceptance();

    }, [date]);

    return (
        <section className="acceptancePage">

            <div className="acceptanceHero">
    <h1>Acceptances</h1>
    {dayNarrative && (
        <p className="acceptanceNarrative">{dayNarrative}</p>
    )}
    <p className="acceptanceHint">
        Click on a horse to view its entry details
    </p>
</div>

            <div className="acceptanceContainer">

                {loading && (
                    <div className="stateBox">
                        <div className="loader" />
                        <p>Loading acceptances…</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="stateBox stateError">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && races.length === 0 && (
                    <div className="stateBox">
                        <p>No acceptance data found for this date.</p>
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
                                    {race.narration && (
                                        <p className="raceNarrative">{race.narration}</p>
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
                                {race.is_void && (
                                    <span className="raceTag raceTagVoid">VOID</span>
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
        <th>Color/Sex</th>
        <th>Age</th>
        <th>Weight</th>
        <th>Rating</th>
        <th>Trainer</th>
    </tr>
</thead>
                                <tbody>
                                    {race.horses && race.horses.map((horse, hIdx) => (
                                        <tr key={horse.horse_seq || hIdx}>
                                            <td className="orderCell">{hIdx + 1}</td>
<td className="horseNameCell">
    {horse.horse_name}
    {horse.sire && (
        <span className="breedingCell">
            {horse.sire}
            {horse.dam ? `-${horse.dam}` : ""}
            {horse.dam_nationality
                ? ` (${horse.dam_nationality})`
                : ""}
        </span>
    )}
</td>
<td>{horse.color}/{horse.sex}</td>
<td>{horse.age ?? "-"}</td>
<td>{horse.weight ?? "-"}</td>
<td>{horse.rating ?? "NR"}</td>
<td>{horse.trainer_name}</td>
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