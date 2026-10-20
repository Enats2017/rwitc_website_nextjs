"use client";

import { Fragment, useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getRecordTimings } from "../../../services/recordTimingsService";
import "./RecordTimings.css";

/* 2025-02-16 -> 16/02/25 */
function formatDate(iso) {
    const m = String(iso || "").match(/^(\d{4})-(\d{2})-(\d{2})/);
    return m ? `${m[3]}/${m[2]}/${m[1].slice(2)}` : "";
}

/* "50.0" -> "50", "59.5" -> "59.5" */
function formatWeight(w) {
    const n = Number(w);
    return Number.isFinite(n) ? String(n) : "";
}

/* 0 min -> "56.513 secs."   |   1 min -> "1 m 03.573 secs" */
function formatTime(min, sec) {
    const m = parseInt(min, 10) || 0;
    return m > 0 ? `${m} m ${sec} secs` : `${sec} secs.`;
}

/* Notes: (bracket wala hissa) bold, baaki normal. Plain text, koi HTML inject nahi hota. */
function Notes({ text }) {
    if (!text) return null;
    const parts = String(text).split(/(\([^)]*\))/g).filter(Boolean);
    return (
        <span className="entryLine">
            {parts.map((p, i) =>
                p.startsWith("(") ? <strong key={i}>{p}</strong> : <Fragment key={i}>{p}</Fragment>
            )}
        </span>
    );
}

function Entry({ e }) {
    return (
        <div className="recordEntry">
            <span className="entryName">
                {e.horse_name} {formatWeight(e.weight)} kgs
            </span>
            <span className="entryLine">({formatDate(e.race_date)})</span>
            <span className="entryLine">{formatTime(e.time_min, e.time_sec)}</span>
            <Notes text={e.notes} />
        </div>
    );
}

function groupByDistance(list) {
    const map = {};
    list.forEach((e) => {
        if (!map[e.distance]) map[e.distance] = [];
        map[e.distance].push(e);
    });
    return Object.keys(map)
        .map(Number)
        .sort((a, b) => a - b)
        .map((d) => ({ distance: d, items: map[d] }));
}

/* MUMBAI: Distance | Indian Horses */
function MumbaiTable({ rows }) {
    const groups = groupByDistance(rows);

    return (
        <div className="statsTableWrap">
            <table className="statsTable">
                <thead>
                    <tr>
                        <th className="colDistance">Distance<br />(About)</th>
                        <th>Indian Horses</th>
                    </tr>
                </thead>
                <tbody>
                    {groups.map((g) =>
                        g.items.map((e, i) => (
                            <tr key={e.id}>
                                {i === 0 && (
                                    <td className="colDistance" rowSpan={g.items.length}>
                                        {g.distance} Metres
                                    </td>
                                )}
                                <td className="colEntry">
                                    <Entry e={e} />
                                </td>
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}

/* PUNE: Distance | Main Track | Monsoon Track */
function PuneTable({ main, monsoon }) {
    const mainMap = {};
    const monsoonMap = {};
    groupByDistance(main).forEach((g) => { mainMap[g.distance] = g.items; });
    groupByDistance(monsoon).forEach((g) => { monsoonMap[g.distance] = g.items; });

    const distances = Array.from(
        new Set([...Object.keys(mainMap), ...Object.keys(monsoonMap)].map(Number))
    ).sort((a, b) => a - b);

    const renderCell = (items) =>
        items && items.length
            ? items.map((e) => <Entry key={e.id} e={e} />)
            : "-";

    return (
        <div className="statsTableWrap">
            <table className="statsTable">
                <thead>
                    <tr>
                        <th className="colDistance">Distance<br />(About)</th>
                        <th>Main Track</th>
                        <th>Monsoon Track</th>
                    </tr>
                </thead>
                <tbody>
                    {distances.map((d) => (
                        <tr key={d}>
                            <td className="colDistance">{d} Metres</td>
                            <td className="colEntry">{renderCell(mainMap[d])}</td>
                            <td className="colEntry">{renderCell(monsoonMap[d])}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export default function RecordTimings() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        let cancelled = false;

        async function load() {
            try {
                const result = await getRecordTimings();
                if (!cancelled) setData(result);
            } catch (err) {
                if (!cancelled) setError("Unable to load record timings.");
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        load();
        return () => { cancelled = true; };
    }, []);

    const mumbai = (data && data.mumbai && data.mumbai.main) || [];
    const puneMain = (data && data.pune && data.pune.main) || [];
    const puneMonsoon = (data && data.pune && data.pune.monsoon) || [];

    const hasMumbai = mumbai.length > 0;
    const hasPune = puneMain.length > 0 || puneMonsoon.length > 0;

    return (
        <section className="aboutPage">
            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Record Timings</h1>
                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    {loading && <p className="statsMsg">Loading...</p>}
                    {!loading && error && <p className="statsMsg statsMsgError">{error}</p>}

                    {!loading && !error && !hasMumbai && !hasPune && (
                        <p className="statsMsg">No record timings available yet.</p>
                    )}

                    {!loading && !error && hasMumbai && (
                        <>
                            <h2 className="recordSubHeading">
                                Best Timings By Indian Horses On The Mumbai Race Track
                            </h2>
                            <MumbaiTable rows={mumbai} />
                        </>
                    )}

                    {!loading && !error && hasPune && (
                        <>
                            <h2 className={`recordSubHeading ${hasMumbai ? "recordSubHeadingSpaced" : ""}`}>
                                Best Timings By Indian Horses On The Pune Race Track
                            </h2>
                            <PuneTable main={puneMain} monsoon={puneMonsoon} />
                        </>
                    )}

                </div>
            </div>
        </section>
    );
}