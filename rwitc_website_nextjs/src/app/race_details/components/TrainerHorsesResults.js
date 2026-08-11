"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import { getTrainerHorses } from "../../../services/TrainerhorsesService";
import "./Trainerhorses.css";

export default function TrainerHorsesResults() {

    const searchParams = useSearchParams();
    const trainerName = searchParams.get("trainer") || "";

    const [horses, setHorses] = useState([]);
    const [loadingHorses, setLoadingHorses] = useState(true);
    const [horsesError, setHorsesError] = useState(null);

    useEffect(() => {

        async function loadHorses() {
            setLoadingHorses(true);
            setHorsesError(null);

            const data = await getTrainerHorses(trainerName);

            if (data === null) {
                setHorses([]);
                setHorsesError("Unable to load horses for this trainer. Please try again.");
                setLoadingHorses(false);
                return;
            }

            if (!data.horses.length) {
                setHorsesError("No horses found for this trainer.");
            }

            setHorses(data.horses);
            setLoadingHorses(false);
        }

        if (trainerName) {
            loadHorses();
        } else {
            setLoadingHorses(false);
            setHorsesError("No trainer selected.");
        }

    }, [trainerName]);

    function formatOwnership(horse) {
        return [horse.OWNERSHIP, horse.OWNERSHIP1, horse.OWNERSHIP2, horse.OWNERSHIP3]
            .map((part) => (part || "").trim())
            .filter(Boolean)
            .join(" ");
    }

    return (
        <section className="trainerHorsesSection">
            <div className="thWrap">
                <div className="thResultsCard">

                    <div className="thResultsHeader">
                        <h2 className="thResultsTitle">{trainerName}</h2>
                        <p className="thResultsSub">Click on a horse to know its Performance Profile @ RWITC</p>
                    </div>

                    {loadingHorses ? (
                        <div className="thState">
                            <div className="thLoader" />
                            <p>Loading horses…</p>
                        </div>
                    ) : horsesError ? (
                        <div className="thState">
                            <p>{horsesError}</p>
                        </div>
                    ) : (
                        <div className="thTableWrap">
                            <table className="thTable">
                                <thead>
                                    <tr>
                                        <th>Sr. No.</th>
                                        <th>Horse</th>
                                        <th>Rating</th>
                                        <th>Description</th>
                                        <th>Sire-Dam</th>
                                        <th>Ownership</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {horses.map((horse, i) => (
                                        <tr key={horse.HORSESEQ}>
                                            <td>{i + 1}</td>
                                            <td className="thAlignLeft">

                                                <Link href={`/race_details/?type=performanceProfile&as_values=${encodeURIComponent(horse.HORSENM)}&horseseq=${horse.HORSESEQ}`}
                                                >
                                                    {horse.HORSENM}
                                                </Link>
                                            </td>
                                            <td>{horse.RATING}</td>
                                            <td>{horse.COLOR} {horse.SEX} {horse.AGE}</td>
                                            <td className="thAlignLeft">
                                                <p className="thSire">{horse.SIRE}-</p>
                                                <Link
                                                    href={`/race_details/?type=foalRecords&mareName=${encodeURIComponent(horse.DAM)}&damnat=${horse.DAMNAT || ""}`}
                                                >
                                                    {horse.DAM} {horse.DAMNAT ? `[${horse.DAMNAT}]` : ""}
                                                </Link>
                                            </td>
                                            <td className="thAlignLeft">{formatOwnership(horse)}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}

                </div>
            </div>
        </section>
    );
}