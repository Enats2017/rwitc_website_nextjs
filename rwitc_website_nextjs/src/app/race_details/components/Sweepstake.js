"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getSweepstakes } from "../../../services/sweepstakeService";
import "./Sweepstake.css";

export default function Sweepstake() {

    const searchParams = useSearchParams();
    const id = searchParams.get("id");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [mode, setMode] = useState("list");
    const [sweepstakes, setSweepstakes] = useState([]);
    const [sweepstake, setSweepstake] = useState(null);

    useEffect(() => {

        async function loadSweepstakes() {

            try {

                setLoading(true);
                setError(null);

                const data = await getSweepstakes(id);

                setMode(data.mode);
                setSweepstakes(data.sweepstakes || []);
                setSweepstake(data.sweepstake || null);

            } catch (err) {

                console.error("Sweepstake Error:", err);
                setError("Unable to load sweepstakes.");

            } finally {

                setLoading(false);

            }

        }

        loadSweepstakes();

    }, [id]);

    const isDetailMode = mode === "detail";
    const hasNoList = !isDetailMode && sweepstakes.length === 0;
    const hasNoDetail = isDetailMode && !sweepstake;

    return (
        <section className="sweepstakePage docPage">

            <div className="docBadgeWrap">
                <span className="docBadge">Sweep Stakes</span>
            </div>

            <div className="docContainer">

                {loading && (
                    <div className="docStateBox">
                        <div className="docLoader" />
                        <p>Loading sweepstakes…</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="docStateBox docStateError">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && hasNoList && (
                    <div className="docStateBox">
                        <p>No sweepstakes found.</p>
                    </div>
                )}

                {!loading && !error && isDetailMode && hasNoDetail && (
                    <div className="docStateBox">
                        <p>Sweepstake not found.</p>
                    </div>
                )}

                {/* LIST MODE — Date + Race table, same as the live page */}
                {!loading && !error && !isDetailMode && !hasNoList && (
                    <div className="docTableWrap">
                        <table className="docTable sweepstakeTable">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Race</th>
                                </tr>
                            </thead>
                            <tbody>
                                {sweepstakes.map((item) => (
                                    <tr key={item.id}>
                                        <td>{item.formatted_date}</td>
                                        <td className="alignLeft">
                                            <a
                                                href={item.file_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="sweepstakeTitle"
                                            >
                                                {item.title}
                                            </a>
                                            <br />
                                            {item.comments && (
                                                <span className="sweepstakeComments">
                                                    ({item.comments})
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* DETAIL MODE — used only if ?id= is passed directly */}
                {!loading && !error && isDetailMode && sweepstake && (
                    <div className="docStateBox sweepstakeDetail">
                        <p>
                            <a
                                href={sweepstake.file_url}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {sweepstake.title}
                            </a>
                        </p>
                        <p>
                            {sweepstake.formatted_date}
                            {sweepstake.comments && <> — {sweepstake.comments}</>}
                        </p>
                    </div>
                )}

            </div>

        </section>
    );
}