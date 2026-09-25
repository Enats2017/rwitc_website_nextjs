"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { API_URL } from "../../../services/api";
import "./JockeyRidingWeight.css";

function splitInHalf(arr) {
    const mid = Math.ceil(arr.length / 2);
    return [arr.slice(0, mid), arr.slice(mid)];
}

function SimpleWeightTable({ rows }) {
    return (
        <table className="jrwTable">
            <thead>
                <tr>
                    <th className="colNo">Sr. No.</th>
                    <th className="colName">NAME</th>
                    <th className="colWeight">Lowest riding weight</th>
                </tr>
            </thead>
            <tbody>
                {rows.map((row) => (
                    <tr key={row.no}>
                        <td className="colNo">{row.no}</td>
                        <td className="colName">{row.name}</td>
                        <td className="colWeight">{row.weight}</td>
                    </tr>
                ))}
            </tbody>
        </table>
    );
}

export default function JockeyRidingWeight() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        let cancelled = false;

        async function load() {
            try {
                const res = await fetch(`${API_URL}/jockey_riding_weight_get_api.php`);
                const json = await res.json();

                if (!res.ok || !json.success) {
                    throw new Error(json.error || "Unable to load riding weight data");
                }
                if (!cancelled) setData(json.data);
            } catch (err) {
                if (!cancelled) setError("Unable to load riding weight data.");
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        load();
        return () => { cancelled = true; };
    }, []);

    // rows aane par category ke hisaab se group karo (JSON mode ke liye)
    const aLicensed = data?.rows?.filter((r) => r.category === "A") || [];
    const apprentice = data?.rows?.filter((r) => r.category === "APPRENTICE") || [];
    const bLicensed = data?.rows?.filter((r) => r.category === "B") || [];
    const [aLeft, aRight] = splitInHalf(aLicensed);

    return (
        <section className="aboutPage">
            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Jockey's Riding Weight</h1>
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
                            {/* MODE 1: server ki ready-made HTML file (RIDINGWEIGHT.HTM) */}
                            {data.mode === "html" && (
                                <div
                                    className="statsTableWrap statsHtml"
                                    dangerouslySetInnerHTML={{ __html: data.html }}
                                />
                            )}

                            {/* MODE 2: DB rows */}
                            {data.mode === "json" && (
                                <>
                                    {data.as_on && (
                                        <h2 className="statsSubHeading">
                                            Jockey's Riding Weight as on {data.as_on}
                                        </h2>
                                    )}

                                    {aLicensed.length > 0 && (
                                        <>
                                            <div className="jrwSectionBar">
                                                <span>"A" Licenced Jockeys</span>
                                            </div>
                                            <div className="jrwTwoColWrap">
                                                <div className="jrwTableWrap">
                                                    <SimpleWeightTable rows={aLeft} />
                                                </div>
                                                <div className="jrwTableWrap">
                                                    <SimpleWeightTable rows={aRight} />
                                                </div>
                                            </div>
                                        </>
                                    )}

                                    {apprentice.length > 0 && (
                                        <>
                                            <div className="jrwSectionBar">
                                                <span>Apprentice (†) / Allowance Claiming Jockeys</span>
                                            </div>
                                            <div className="jrwTableWrap">
                                                <table className="jrwTable">
                                                    <thead>
                                                        <tr>
                                                            <th className="colNo">No.</th>
                                                            <th className="colName">NAME</th>
                                                            <th className="colWeight">Lowest riding weight</th>
                                                            <th className="colAllowance">Allowance Entitle</th>
                                                            <th className="colWinners">Total Winners</th>
                                                            <th className="colTrainer">Master Trainer</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        {apprentice.map((row) => (
                                                            <tr key={row.no}>
                                                                <td className="colNo">{row.no}</td>
                                                                <td className="colName">{row.name}</td>
                                                                <td className="colWeight">{row.weight}</td>
                                                                <td className="colAllowance">{row.allowance}</td>
                                                                <td className="colWinners">{row.winners}</td>
                                                                <td className="colTrainer">{row.trainer}</td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </>
                                    )}

                                    {bLicensed.length > 0 && (
                                        <>
                                            <div className="jrwSectionBar">
                                                <span>"B" Licenced Jockeys</span>
                                            </div>
                                            <div className="jrwTableWrap jrwTableNarrow">
                                                <SimpleWeightTable rows={bLicensed} />
                                            </div>
                                        </>
                                    )}
                                </>
                            )}
                        </>
                    )}

                </div>
            </div>
        </section>
    );
}