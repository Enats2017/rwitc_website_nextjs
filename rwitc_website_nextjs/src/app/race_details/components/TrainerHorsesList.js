"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { FaHorseHead } from "react-icons/fa6";
import { getTrainers } from "../../../services/TrainerhorsesService";
import "./Trainerhorses.css";

export default function TrainerHorsesList() {

    const router = useRouter();

    const [trainers, setTrainers] = useState([]);
    const [loadingTrainers, setLoadingTrainers] = useState(true);
    const [trainersError, setTrainersError] = useState(null);

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

    function handleTrainerClick(trainerName) {
        router.push(`/race_details?type=trainerHorsesResults&trainer=${encodeURIComponent(trainerName)}`);
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
                                        className="thItem"
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
            </div>
        </section>
    );
}