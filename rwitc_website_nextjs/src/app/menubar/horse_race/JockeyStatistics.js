"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { API_URL } from "../../../services/api";
import "./JockeyStatistics.css";

function calcWinPercent(wins, totalMounts) {
    if (!totalMounts) return "0";
    const pct = (wins / totalMounts) * 100;
    return Number.isInteger(pct) ? String(pct) : pct.toFixed(2);
}

export default function JockeyStatistics() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        let cancelled = false;

        async function load() {
            try {
                const res = await fetch(`${API_URL}/jockey_statistics_get_api.php`);
                const json = await res.json();

                if (!res.ok || !json.success) {
                    throw new Error(json.error || "Unable to load jockey statistics");
                }
                if (!cancelled) setData(json.data);
            } catch (err) {
                if (!cancelled) setError("Unable to load jockey statistics.");
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
                    <h1 className="aboutHeading">Jockey's Statistics</h1>
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
                                    Jockey's Statistics as on {data.as_on}
                                </h2>
                            )}

                            {/* MODE 1: server ki ready-made HTML file */}
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
                                                <th className="colJockey">Jockey</th>
                                                <th>Wins</th>
                                                <th>Second</th>
                                                <th>Third</th>
                                                <th>Fourth</th>
                                                <th>Total Mounts</th>
                                                <th>Win %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {data.rows.map((row, index) => (
                                                <tr key={index}>
                                                    <td className="colJockey">{row.jockey}</td>
                                                    <td>{row.wins}</td>
                                                    <td>{row.second}</td>
                                                    <td>{row.third}</td>
                                                    <td>{row.fourth}</td>
                                                    <td>{row.totalMounts}</td>
                                                    <td>{calcWinPercent(row.wins, row.totalMounts)}</td>
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