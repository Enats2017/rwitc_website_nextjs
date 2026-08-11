"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa6";
import Link from "next/link";
import { getTrainers, getTrainerHorses } from "../../../services/TrainerhorsesService";
import "./Trainerhorses.css";

export default function TrainerHorses() {

    const [trainers, setTrainers] = useState([]);
    const [loadingTrainers, setLoadingTrainers] = useState(true);
    const [trainersError, setTrainersError] = useState(null);

    const [activeTrainer, setActiveTrainer] = useState(null);
    const [horses, setHorses] = useState([]);
    const [loadingHorses, setLoadingHorses] = useState(false);
    const [horsesError, setHorsesError] = useState(null);

    useEffect(() => {

        async function loadTrainers() {
            setLoadingTrainers(true);
            const data = await getTrainers();

            if (data === null) {
                setTrainersError("Unable to load trainers. Please refresh the page.");
                setTrainers([]);
            } else {
                setTrainers(data);
                setTrainersError(null);
            }

            setLoadingTrainers(false);
        }

        loadTrainers();

    }, []);

    async function handleTrainerClick(trainerName) {

        setActiveTrainer(trainerName);
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

    function formatOwnership(horse) {
        return [horse.OWNERSHIP, horse.OWNERSHIP1, horse.OWNERSHIP2, horse.OWNERSHIP3]
            .map((part) => (part || "").trim())
            .filter(Boolean)
            .join(" ");
    }

    return (
        <section className="trainerHorsesSection">

            <div className="thWrap">

                <div className="thCard">

                    <div className="thHeader">
                        <span className="thHeaderIcon">
                            <FaHorseHead />
                        </span>
                        <h1 className="thHeaderTitle">Trainer Wise Horses In Training</h1>
                    </div>

                    <div className="thBody">

                        {loadingTrainers ? (
                            <div className="thState">
                                <div className="thLoader" />
                                <p>Loading trainers…</p>
                            </div>
                        ) : trainersError ? (
                            <div className="thState">
                                <p>{trainersError}</p>
                            </div>
                        ) : trainers.length === 0 ? (
                            <div className="thState">
                                <p>No trainers found.</p>
                            </div>
                        ) : (
                            <div className="thGrid">
                                {trainers.map((t, index) => (
                                    <button
                                        key={t.TRAINERNM}
                                        className={
                                            "thItem" +
                                            (activeTrainer === t.TRAINERNM ? " thItemActive" : "")
                                        }
                                        onClick={() => handleTrainerClick(t.TRAINERNM)}
                                    >
                                        <span className="thBadge">{index + 1}</span>
                                        <span className="thName">{t.TRAINERNM}</span>
                                    </button>
                                ))}
                            </div>
                        )}

                    </div>

                </div>

                {activeTrainer && (
                    <div className="thResultsCard">

                        <div className="thResultsHeader">
                            <h2 className="thResultsTitle">{activeTrainer}</h2>
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
                                                    <Link
                                                        href={`/race_details?type=performanceProfile&as_values=${encodeURIComponent(horse.HORSENM)}&horseseq=${horse.HORSESEQ}`}
                                                    >
                                                        {horse.HORSENM}
                                                    </Link>
                                                </td>
                                                <td>{horse.RATING}</td>
                                                <td>{horse.COLOR} {horse.SEX} {horse.AGE}</td>
                                                <td className="thAlignLeft">
                                                    <p className="thSire">{horse.SIRE}-</p>
                                                    <Link
                                                        href={`/race_details?type=foalRecords&mareName=${encodeURIComponent(horse.DAM)}&damnat=${horse.DAMNAT || ""}`}
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
                )}

            </div>

        </section>
    );
}