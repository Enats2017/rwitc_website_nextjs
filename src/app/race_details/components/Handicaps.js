"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getHandicaps } from "../../../services/handicapsService";
import "./Handicaps.css";

export default function Handicaps() {

    const searchParams = useSearchParams();
    const date = searchParams.get("date");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [meeting, setMeeting] = useState(null);
    const [races, setRaces] = useState([]);

    useEffect(() => {

        async function loadHandicaps() {

            if (!date) {
                setError("No date selected.");
                setLoading(false);
                return;
            }

            try {

                setLoading(true);
                setError(null);

                const data = await getHandicaps(date);

                setMeeting(data.meeting);
                setRaces(data.races);

            } catch (err) {

                console.error("Handicaps Error:", err);
                setError("Unable to load handicaps for this date.");

            } finally {

                setLoading(false);

            }

        }

        loadHandicaps();

    }, [date]);

    return (
        <section className="handicapsPage">

           <div className="handicapsHero">
    <h1>Handicaps</h1>
    {meeting && <p className="handicapsMeeting">{meeting}</p>}
    <p className="handicapsHint">
        Click on a horse to know its Performance Profile @ RWITC
    </p>
</div>

            <div className="handicapsContainer">

                {loading && (
                    <div className="stateBox">
                        <div className="loader" />
                        <p>Loading handicaps…</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="stateBox stateError">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && races.length === 0 && (
                    <div className="stateBox">
                        <p>No handicaps data found for this date.</p>
                    </div>
                )}

                {!loading && !error && races.map((race, idx) => (

                    <div className="raceBlock" key={race.srno || idx}>

                        <div className="raceBlockHeader">
                            <div className="raceBlockHeaderLeft">
                                <span className="raceIndex">
                                    {String(idx + 1).padStart(2, "0")}
                                </span>
                                <div>
                                    <h2>{race.race_name}</h2>
                                    {race.narrent && (
                                        <p className="raceNarrative">{race.narrent}</p>
                                    )}
                                </div>
                            </div>

                            <div className="raceBlockHeaderRight">
                                {race.distance && (
                                    <span className="raceTag">
                                        {race.distance}m
                                    </span>
                                )}
                                {race.grade ? (
    <span className="raceTag">
        {race.grade}
    </span>
) : null}
                                {race.foreign_jockeys_eligible && (
                                    <span className="raceTag raceTagAccent">
                                        Foreign Jockeys Eligible
                                    </span>
                                )}
                            </div>
                        </div>

                        {race.weight_note && (
                            <p className="weightNote">{race.weight_note}</p>
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
        <th>Breeding</th>
        <th>Trainer</th>
    </tr>
</thead>
                                <tbody>
                                    {race.horses && race.horses.map((horse, hIdx) => (
                                        <tr key={horse.horseseq || hIdx}>
                                            <td className="orderCell">{horse.order}</td>
<td className="horseNameCell">{horse.name}</td>
<td>{horse.color}/{horse.sex}</td>
<td>{horse.age ?? "-"}</td>
<td>{horse.weight ?? "-"}</td>
                                            <td>{horse.rating ?? "NR"}</td>
                                            <td className="breedingCell">{horse.breeding}</td>
                                            <td>{horse.trainer}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {(race.ss_ban?.length > 0 ||
                            race.vo_ban?.length > 0 ||
                            race.mk_ban?.length > 0) && (
                            <div className="banNotes">
                                {race.ss_ban?.length > 0 && (
                                    <p><strong>SS Ban:</strong> {race.ss_ban.join(", ")}</p>
                                )}
                                {race.vo_ban?.length > 0 && (
                                    <p><strong>Vet Ban:</strong> {race.vo_ban.join(", ")}</p>
                                )}
                                {race.mk_ban?.length > 0 && (
                                    <p><strong>MK Ban:</strong> {race.mk_ban.join(", ")}</p>
                                )}
                            </div>
                        )}

                    </div>

                ))}

            </div>

        </section>
    );
}