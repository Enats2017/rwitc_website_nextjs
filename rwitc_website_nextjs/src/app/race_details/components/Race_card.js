"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getRaceCard } from "../../../services/racecardService";
import "./Race_card.css";

const ARCHIVE_STYLES_RACECARD = `
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial; margin: 0; padding: 12px; background: #ffffff; }
    span, a { display: inline-block; text-decoration: none; color: #c9c9c9; }
    img { vertical-align: middle; }
    h3 {
        font-family: 'Roboto Condensed', Arial, sans-serif;
        font-size: 32px;
        letter-spacing: 3px;
        color: #c9c9c9;
        text-align: center;
        margin: 10px 0;
    }
    .pageHeading { text-align: center; margin-bottom: 24px; }
    .subHeading {
        text-align: center;
        font-size: 13px;
        font-weight: bold;
        color: #111;
        margin: 4px 0;
    }

    /* Legacy "download" link — hidden, page already renders its own button. */
    .download { display: none !important; }

    /* Race-number quick-nav pills */
    .slider { text-align: center; margin: 20px 0; }
    .slider a.race_call {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        margin: 0 4px;
        border-radius: 50%;
        background: #16a34a;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 14px;
    }

    table { border-collapse: collapse; width: 100%; }
    .table { max-width: 100%; margin-bottom: 1rem; background-color: transparent; }
    .table-bordered { border: 1px solid #e2e2e2; }
    .table-bordered th, .table-bordered td { border: none; }

    /* ---- Race header bar (green) ---- */
.race_no_data {
    background: #16a34a !important;
    border-radius: 4px;
    margin: 36px 0 18px;
    overflow: hidden;
}

/* ---- Spacing between horse cards ---- */

td > table.infoTable[style*='box-shadow'] {
    margin: 18px 0 !important;
    padding: 24px 28px !important;
}

    .race_no_data th {
    background: transparent;
    color: #ffffff !important;
    text-align: left;
    padding: 12px 26px;
    font-size: 14px;
    line-height: 1.1;
    vertical-align: top;
    border: none !important;
}
.race_no_data th,
.race_no_data th span,
.race_no_data th * {
    color: #ffffff !important;
}

    .darkGrey { color: #ffffff !important; font-weight: bold; }
    .foreign_eligible2 span { display: block; margin: 2px 0; }

    /* ---- Horse card (grey box, background comes from inline style) ---- */
    
    .infoTable { width: 100%; }
    .infoTable td { border: 0; padding: 4px 6px; font-size: 13.5px; color: #222; font-weight: 700; }
    .horse_number_class { color: #111 !important; }
    .alignLeft { text-align: left !important; }
    .alignRight { text-align: right !important; }

    .infoTable img { border: 2px solid #16a34a !important; border-radius: 2px; }
    .infoTable td:has(> img) { text-align: right !important; }

    /* "View Runs" button */
    .view_perform { cursor: pointer; }
    .view_runs {
        display: inline-block;
        background: #16a34a;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 12px;
        padding: 6px 16px;
        border-radius: 4px;
        cursor: pointer;
    }
    .view_runs:hover { background: #12833b; }

    /* ---- Performance history table ---- */
    .perform_head td {
        background: #f7f7f7;
        font-weight: bold;
        font-size: 12px;
        padding: 8px;
        border: 1px solid #e2e2e2;
        text-align: center;
    }
    
    .perform_data td {
    font-size: 12px;
    padding: 8px;
    border: 1px solid #e2e2e2;
    text-align: center;
    font-weight: 700;
}

    /* ---- Pools table ---- */
    .poolsTable th {
        background: #16a34a;
        color: #ffffff !important;
        padding: 10px;
        text-align: left;
    }
    .poolsTable td {
        padding: 10px;
        border: 1px solid #e2e2e2;
    }

    @media (min-width: 500px) and (max-width: 2560px) {
        .show1 { display: none; }
    }

    @media (max-width: 500px) {
        td, th { font-size: 11px !important; }
        .perform_data {
            display: contents;
            border: 1px solid #cdced3 !important;
            border-radius: 12px;
            background: #cdced3 !important;
        }
        .perform_data td:before {
            content: attr(data-label);
            float: left;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
            width: 45%;
        }
    }
</style>
`;

export default function RaceCard() {

    const searchParams = useSearchParams();
    const date = searchParams.get("date");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [mode, setMode] = useState("json");
    const [rawHtml, setRawHtml] = useState("");
    const [dayLabel, setDayLabel] = useState(null);
    const [dayNarrative, setDayNarrative] = useState("");
    const [races, setRaces] = useState([]);
    const [pools, setPools] = useState([]);
    const [downloadFile, setDownloadFile] = useState(null);
    const [downloadAvailable, setDownloadAvailable] = useState(false);
    const [openRuns, setOpenRuns] = useState({});

    useEffect(() => {

        async function loadRaceCard() {

            if (!date) {
                setError("No date selected.");
                setLoading(false);
                return;
            }

            try {

                setLoading(true);
                setError(null);

                const data = await getRaceCard(date);

                setMode(data.mode || "json");
                setRawHtml(data.html || "");
                setDayLabel(data.dayLabel);
                setDayNarrative(data.dayNarrative);
                setRaces(data.races || []);
                setPools(data.pools || []);
                setDownloadFile(data.downloadFile);
                setDownloadAvailable(data.downloadAvailable);

            } catch (err) {

                console.error("Race Card Error:", err);
                setError("Unable to load race card for this date.");

            } finally {

                setLoading(false);

            }

        }

        loadRaceCard();

    }, [date]);

    const toggleRuns = (key) => {
        setOpenRuns((prev) => ({ ...prev, [key]: !prev[key] }));
    };

    const isHtmlMode = mode === "html";
    const hasNoHtml = isHtmlMode && !rawHtml.trim();
    const hasNoData = !isHtmlMode && races.length === 0;

    return (
        <section className="racecardPage docPage">

            <div className="docBadgeWrap">
                <span className="docBadge">Race Card</span>
            </div>

            <div className="docContainer">

                <button
                    type="button"
                    className="docDownloadBtn"
                    onClick={() => {
                        if (downloadAvailable && downloadFile) {
                            window.open(downloadFile, "_blank", "noopener,noreferrer");
                        } else {
                            alert("No download file found for this date. Showing data below.");
                        }
                    }}
                >
                    Download Race Card
                </button>

                {!isHtmlMode && (
                    <div className="docHeader">
                        <p className="docClub">ROYAL WESTERN INDIA TURF CLUB.</p>
                        {dayLabel && <p className="docMeeting">{dayLabel}</p>}
                        {dayNarrative && <p className="docMeeting">{dayNarrative}</p>}
                        <h1 className="docWatermark">RACE CARD</h1>
                        <p className="docHint">
                            Click on a horse to know its Performance Profile @ RWITC
                        </p>
                    </div>
                )}

                {loading && (
                    <div className="docStateBox">
                        <div className="docLoader" />
                        <p>Loading race card…</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="docStateBox docStateError">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && (hasNoData || hasNoHtml) && (
                    <div className="docStateBox">
                        <p>No race card found for this date.</p>
                    </div>
                )}

                {!loading && !error && isHtmlMode && !hasNoHtml && (
                   <iframe
    className="docArchiveHtml"
    srcDoc={ARCHIVE_STYLES_RACECARD + rawHtml}
    title="Race Card"
    sandbox="allow-same-origin"
    scrolling="no"
    onLoad={(e) => {
    const iframe = e.target;
    const doc = iframe.contentWindow?.document;
    if (!doc) return;


    doc.querySelectorAll(".race_no_data").forEach((el) => {
        el.querySelectorAll("th").forEach((th) => {
            th.style.setProperty("background", "#16a34a", "important");
        });
    
        el.querySelectorAll("span[style*='text-align:center']").forEach((span) => {
            span.style.textAlign = "left";
            span.style.display = "block";
            span.style.marginTop = "2px";
        });
        
        el.querySelectorAll("br").forEach((br) => {
            br.style.display = "none";
        });
    });

    const setHeight = () => {
        iframe.style.height = doc.documentElement.scrollHeight + "px";
    };

    doc.querySelectorAll(".race_call").forEach((pill) => {
        pill.style.cursor = "pointer";
        pill.addEventListener("click", () => {
            const raceNo = pill.id;

            doc.querySelectorAll(".race_no_data").forEach((block) => {
                const matches = block.classList.contains("race_no_" + raceNo);
                block.style.display = matches ? "" : "none";
            });

            doc.querySelectorAll(".race_call").forEach((p) => {
                p.style.textDecoration = p.id === raceNo ? "underline" : "none";
            });

            setHeight();
            requestAnimationFrame(setHeight);
        });
    });

    
    doc.querySelectorAll(".view_perform").forEach((btn) => {
        btn.style.cursor = "pointer";
        btn.addEventListener("click", () => {
            const performanceRow = doc.getElementById("performance_" + btn.id);

            if (performanceRow) {
                const isHidden = performanceRow.style.display === "none" || performanceRow.style.display === "";
                performanceRow.style.display = isHidden ? "table-row" : "none";
            }

            setHeight();
            requestAnimationFrame(setHeight);
        });
    });

   
    doc.querySelectorAll("a[target='_blank']").forEach((link) => {
        link.addEventListener("click", (ev) => {
            ev.preventDefault();
            window.open(link.href, "_blank", "noopener,noreferrer");
        });
    });

    setHeight();
    requestAnimationFrame(setHeight);
    setTimeout(setHeight, 100);
}}
                    />
                )}

                {!loading && !error && !isHtmlMode && !hasNoData && races.map((race, idx) => (

                    <div className="docRaceBlock" key={race.raceNo ?? idx}>

                        <div className="docRaceBar">
                            <p className="docRaceName">
                                No.:{race.raceNoSeason ?? race.raceNo}&nbsp; {race.raceName}
                                {race.division && <> &nbsp;({race.division})</>}
                            </p>
                            <p className="docRaceMeta">
                                {race.distance && <>(About) {race.distance} Metres.</>}
                                {race.time && <>&nbsp;&nbsp;Time: {race.time}</>}
                                {race.foreignJockeysEligible && (
                                    <span className="docForeignTag">
                                        &nbsp;&nbsp;Foreign Jockeys Eligible
                                    </span>
                                )}
                            </p>
                            {race.narrativeEntry && (
                                <p className="docRaceNarration">{race.narrativeEntry}</p>
                            )}
                        </div>

                        {race.horses && race.horses.map((horse, hIdx) => {

                            const runKey = `${race.raceNo}-${horse.horseseq || hIdx}`;
                            const isOpen = !!openRuns[runKey];

                            return (
                                <div className="docHorseCard" key={horse.horseseq || hIdx}>

                                    <div className="docHorseTop">
                                        <span className="docHorseNo">{horse.cardNo}.</span>
                                        <span className="docHorseName">{horse.name}</span>
                                        <span className="docHorseWeight">{horse.weight} kg</span>
                                        <span className="docHorseJockey">{horse.jockey}</span>
                                    </div>

                                    <div className="docHorseRow">
                                        <span>
                                            {horse.sireDam}
                                            {horse.damNation ? ` (${horse.damNation})` : ""}
                                        </span>
                                        <span className="docHorseDraw">
                                            [{horse.drawNo}] ({horse.equipment}){horse.shoe}
                                            {horse.shoeDetail && horse.shoeDetail !== "-" ? horse.shoeDetail : ""}
                                        </span>
                                    </div>

                                    {horse.stud && <div className="docHorseRow">Stud: {horse.stud}</div>}
                                    {horse.breeder && <div className="docHorseRow">Breeder: {horse.breeder}</div>}
                                    {horse.foaled && <div className="docHorseRow">Foaled: {horse.foaled}</div>}

                                    <div className="docHorseRow">
                                        Rating: {horse.rating}
                                        {horse.hraRating && <> (HRA {horse.hraRating})</>}
                                    </div>

                                    {horse.distanceWon && (
                                        <div className="docHorseRow">DW: {horse.distanceWon}</div>
                                    )}

                                    <div className="docHorseRow docHorseOwnership">
                                        <span>
                                            {horse.ownership}
                                            {horse.sexEtc && <><br />{horse.sexEtc}</>}
                                        </span>
                                        <span className="docHorseTrainer">({horse.trainer})</span>
                                    </div>

                                    {horse.runsData && <div className="docHorseRow">{horse.runsData}</div>}
                                    {horse.colours && <div className="docHorseRow docHorseColours">{horse.colours}</div>}

                                    <button
                                        type="button"
                                        className="docViewRunsBtn"
                                        onClick={() => toggleRuns(runKey)}
                                    >
                                        {isOpen ? "Hide Runs" : "View Runs"}
                                    </button>

                                    {isOpen && (
                                        <div className="docTableWrap">
                                            <table className="docTable">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Race No</th>
                                                        <th>Jockey</th>
                                                        <th>Class</th>
                                                        <th>Distance</th>
                                                        <th>Weight</th>
                                                        <th>Placing</th>
                                                        <th>Time</th>
                                                        <th>Video</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {horse.performanceHistory && horse.performanceHistory.map((perf, pIdx) => (
                                                        <tr key={pIdx}>
                                                            <td>{perf.raceDate}</td>
                                                            <td>{perf.raceNo}</td>
                                                            <td>{perf.jockey}</td>
                                                            <td>{perf.raceClass}</td>
                                                            <td>{perf.distance}</td>
                                                            <td>{perf.weight}</td>
                                                            <td>{perf.placing}</td>
                                                            <td>{perf.time}</td>
                                                            <td>
                                                                {perf.videoUrl && (
                                                                    <a href={perf.videoUrl} target="_blank" rel="noopener noreferrer">
                                                                        Watch
                                                                    </a>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}

                                </div>
                            );
                        })}

                    </div>

                ))}

                {!loading && !error && !isHtmlMode && pools.length > 0 && (
                    <div className="docPoolsBlock">
                        <p className="docPoolsTitle">Pools</p>
                        {pools.map((pool, pIdx) => (
                            <div className="docPoolRow" key={pIdx}>
                                <span className="docPoolName">{pool.pool_name}</span>
                                <span className="docPoolValue">{pool.members}</span>
                            </div>
                        ))}
                    </div>
                )}

            </div>

        </section>
    );
}