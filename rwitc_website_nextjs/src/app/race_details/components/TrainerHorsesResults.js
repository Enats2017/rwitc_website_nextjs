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

    function cleanText(text) {
        return decodeHtmlFully(text || "")
            .replace(/[\s\u200B\uFEFF]+/g, " ")
            .trim();
    }

    function norm(text) {
        return (text || "").replace(/\s+/g, " ").trim().toLowerCase();
    }

    // "A, B, C" -> "A, B & C"
    function joinWithAnd(list) {
        if (list.length <= 1) return list.join("");
        if (list.length === 2) return `${list[0]} & ${list[1]}`;
        return `${list.slice(0, -1).join(", ")} & ${list[list.length - 1]}`;
    }

    // Shows RACE_CARD_NAME (in order) and attaches representatives from OWNERSHIP.
    // If OWNERSHIP ends with "&'s", a "'s" is added at the very end of the final text.
    //
    // RACE_CARD_NAME: "Company1, Owner, Owner, Company2, Owner"
    // OWNERSHIP:      "RepA & RepB rep.  Company1, Owner, Owner, RepC rep.  Company2, Owner &'s"
    // Output:         "RepA & RepB rep. Company1, Owner, Owner, RepC rep. Company2 & Owner's"
    function formatOwnership(horse) {
        const raceCard = cleanText(horse.RACE_CARD_NAME)
            .split(",")
            .map(s => s.trim())
            .filter(Boolean);

        const ownershipRaw = cleanText(horse.OWNERSHIP);

        // Check if OWNERSHIP ends with "&'s" (possessive marker)
        const possessiveRegex = /\s*&\s*['\u2018\u2019\u02BC\u2032`\u00B4]\s*s\s*$/i;
        const hasPossessive = possessiveRegex.test(ownershipRaw);
        const possessive = hasPossessive ? "'s" : "";

        const ownership = ownershipRaw.replace(possessiveRegex, "").trim();

        // No race card name: show cleaned OWNERSHIP as fallback
        if (!raceCard.length) {
            const items = ownership.split(",").map(s => s.trim()).filter(Boolean);
            return joinWithAnd(items) + possessive;
        }

        // repFor[index in raceCard] = representative text for that company
        const repFor = {};

        const segs = ownership.split(/\s+rep\.\s*/i);

        if (segs.length > 1) {
            const items = segs.map(s => s.split(",").map(x => x.trim()).filter(Boolean));
            const last = segs.length - 1;

            // Find each company (first item after every "rep.") inside the race card list
            const compIdx = [];
            let ok = true;

            for (let s = 1; s <= last; s++) {
                const company = items[s][0] || "";
                const idx = raceCard.findIndex(
                    (n, j) => norm(n) === norm(company) && !compIdx.includes(j)
                );
                if (idx === -1) {
                    ok = false;
                    break;
                }
                compIdx.push(idx);
            }

            if (ok) {
                for (let s = 0; s < last; s++) {
                    // Items of this segment that can contain owners + the reps of the next company
                    const list = s === 0 ? items[0] : items[s].slice(1);

                    // How many owners are in this segment (taken from race card order)
                    const ownersCount = s === 0
                        ? compIdx[0]
                        : compIdx[s] - compIdx[s - 1] - 1;

                    const reps = list.slice(ownersCount);

                    if (reps.length) {
                        repFor[compIdx[s]] = reps.join(", ");
                    }
                }
            }
        }

        const parts = raceCard.map((name, j) =>
            repFor[j] ? `${repFor[j]} rep. ${name}` : name
        );

        return joinWithAnd(parts) + possessive;
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

                                        const ownershipText = formatOwnership(horse);

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
                                                <td className="thAlignLeft">{ownershipText}</td>
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