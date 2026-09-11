"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import Link from "next/link";
import { getFoalRecords } from "../../../services/TrainerhorsesService";
import "./Trainerhorses.css";

export default function FoalRecords() {

    const searchParams = useSearchParams();
    const mareName = searchParams.get("mareName") || "";
    const damnat = searchParams.get("damnat") || "";

    const [foals, setFoals] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {

        async function loadFoals() {

            setLoading(true);
            setError(null);

            if (!mareName) {
                setError("No Mare Selected. Please select Mare from race card.");
                setLoading(false);
                return;
            }

            const data = await getFoalRecords(mareName, damnat);

            if (data === null) {
                setFoals([]);
                setError("Unable to load foal records. Please try again.");
                setLoading(false);
                return;
            }

            if (!data.foals.length) {
                setError(
                    `No Foals found for Mare ${mareName}${data.damNat ? " [" + data.damNat + "]" : ""}`
                );
            }

            setFoals(data.foals);
            setLoading(false);
        }

        loadFoals();

    }, [mareName, damnat]);

    const first = foals[0];

    const msirenat = first?.MSIRENAT ? `[${first.MSIRENAT}]` : "";
    const mdamnat = first?.MDAMNAT ? `[${first.MDAMNAT}]` : "";
    const marenatDisp = first?.MARENAT ? `[${first.MARENAT}]` : "";

    return (
        <section className="trainerHorsesSection">
            <div className="thWrap">
                <div className="thResultsCard">

                    {loading ? (
                        <div className="thState">
                            <div className="thLoader" />
                            <p>Loading foal records…</p>
                        </div>
                    ) : error ? (
                        <div className="thState">
                            <p>{error}</p>
                        </div>
                    ) : (
                        <>
                            <div className="thResultsHeader">
                                <h2 className="thResultsTitle">
                                    Foals of {first.MARENAME}{marenatDisp} ({first.MARESIRE} {msirenat} - {first.MAREDAM} {mdamnat})
                                </h2>
                            </div>

                            <div className="thTableWrap">
                                <table className="thTable">
                                    <thead>
                                        <tr>
                                            <th>Year of Foal</th>
                                            <th>Desc</th>
                                            <th>Horse</th>
                                            <th>Wins</th>
                                            <th>Stakes Won (Rs)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foals.map((foal, i) => (
                                            <tr key={i}>
                                                <td>{foal.YROFFLNG}</td>
                                                <td>{foal.HORSECOLOR} {foal.HORSESEX}</td>
                                                <td className="thAlignLeft">
                                                    <Link
                                                        href={`/race_details?type=performanceProfile&as_values=${encodeURIComponent(foal.HORSE_NAME)}`}
                                                    >
                                                        <b>{foal.HORSE_NAME}</b>
                                                    </Link>
                                                </td>
                                                <td>{foal.WIN}</td>
                                                <td>{foal.STAKES == 0 ? "-" : foal.STAKES}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <p style={{ fontSize: "13px", marginTop: "16px" }}>
                                The above data has been collated from the records maintained by the Stud Book Authority of India and is as on 31st July 2026. It does not include details of siblings abroad or Indian horses&apos; performances abroad.
                            </p>
                        </>
                    )}

                </div>
            </div>
        </section>
    );
}