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

    function decodeHtml(text) {
        const textarea = document.createElement("textarea");
        textarea.innerHTML = text;
        return textarea.value;
    }

    // Decode HTML entities repeatedly until the text stops changing
    function decodeHtmlFully(text) {
        let prev = text;

        for (let i = 0; i < 5; i++) {
            const next = decodeHtml(prev);
            if (next === prev) break;
            prev = next;
        }

        return prev;
    }

    function formatOwnership(horse) {
        let text = decodeHtmlFully(horse.OWNERSHIP || "")
            .replace(/\s*&\s*(['\u2018\u2019\u02BC\u2032`\u00B4])\s*s\b/gi, "$1s")
            .replace(/[\s\u200B\uFEFF]+(['\u2018\u2019\u02BC\u2032`\u00B4])s\b/gi, "$1s")
            .replace(/\s{2,}/g, " ")
            .trim();
        const commaCount = (text.match(/,/g) || []).length;
        if (commaCount >= 2) {
            const lastComma = text.lastIndexOf(",");
            const head = text.slice(0, lastComma).trim();
            const tail = text.slice(lastComma + 1).trim();

            if (tail && !tail.startsWith("&")) {
                text = `${head} & ${tail}`;
            }
        }

        return text;
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
                                    {horses.map((horse, i) => {

                                        // TEMP DEBUG: check raw ownership value in browser console (F12). Remove after the issue is fixed.
                                        console.log(JSON.stringify(horse.OWNERSHIP));

                                        return (
                                            <tr key={horse.HORSESEQ}>
                                                <td>{i + 1}</td>
                                                <td className="thAlignLeft">
                                                    <Link href={`/race_details?type=performanceProfile&as_values=${encodeURIComponent(horse.HORSENM)}&horseseq=${horse.HORSESEQ}`}>
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
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}

                </div>
            </div>
        </section>
    );
}