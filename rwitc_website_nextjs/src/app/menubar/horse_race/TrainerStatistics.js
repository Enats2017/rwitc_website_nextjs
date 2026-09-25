"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { API_URL } from "../../../services/api";
import "./TrainerStatistics.css";

function calcWinPercent(wins, totalRunners) {
    if (!totalRunners) return "0";
    const pct = (wins / totalRunners) * 100;
    return Number.isInteger(pct) ? String(pct) : pct.toFixed(2);
}

export default function TrainerStatistics() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        let cancelled = false;

        async function load() {
            try {
                const res = await fetch(`${API_URL}/trainer_statistics_get_api.php`);
                const json = await res.json();

                if (!res.ok || !json.success) {
                    throw new Error(json.error || "Unable to load trainer statistics");
                }
                if (!cancelled) setData(json.data);
            } catch (err) {
                if (!cancelled) setError("Unable to load trainer statistics.");
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        load();
        return () => { cancelled = true; };
    }, []);

    return (
        <section className="aboutPage">
            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Trainer's Statistics</h1>
                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    {loading && <p className="statsMsg">Loading...</p>}
                    {!loading && error && <p className="statsMsg statsMsgError">{error}</p>}

                    {!loading && !error && data && (
                        <>
                            {data.as_on && (
                                <h2 className="statsSubHeading">
                                    Trainer's Statistics as on {data.as_on}
                                </h2>
                            )}

                            {/* MODE 1: server ki ready-made HTML file (Trainer_statistics.html) */}
                            {data.mode === "html" && (
                                <div
                                    className="statsTableWrap statsHtml"
                                    dangerouslySetInnerHTML={{ __html: data.html }}
                                />
                            )}

                            {/* MODE 2: DB rows */}
                            {data.mode === "json" && (
                                <div className="statsTableWrap">
                                    <table className="statsTable">
                                        <thead>
                                            <tr>
                                                <th className="colTrainer">Trainer</th>
                                                <th>Wins</th>
                                                <th>Second</th>
                                                <th>Third</th>
                                                <th>Fourth</th>
                                                <th>Total Runners</th>
                                                <th>Win %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {data.rows.map((row, index) => (
                                                <tr key={index}>
                                                    <td className="colTrainer">{row.trainer}</td>
                                                    <td>{row.wins}</td>
                                                    <td>{row.second}</td>
                                                    <td>{row.third}</td>
                                                    <td>{row.fourth}</td>
                                                    <td>{row.totalRunners}</td>
                                                    <td>{calcWinPercent(row.wins, row.totalRunners)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </>
                    )}

                </div>
            </div>
        </section>
    );
}