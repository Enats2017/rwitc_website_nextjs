"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getAcceptance } from "../../../services/acceptanceService";
import "./Acceptance.css";

const ARCHIVE_STYLES_ACCEPTANCE = `
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial; margin: 0; padding: 12px; }
    span, a { display: inline-block; text-decoration: none; color: #333333; }
    img { vertical-align: middle; }
    h1 { margin: unset !important; font-size: 26px !important; }
    h3 { font-family: 'Roboto Condensed', Arial, sans-serif; font-size: 32px; color: #c1c1c1; margin: 10px 0; }
    .pageHeading { text-align: center; }

    table { border-collapse: collapse; }
    .table { width: 100%; max-width: 100%; margin-bottom: 1rem; background-color: transparent; }
    .table-bordered { font-weight: bold; border: 1px solid #BCBEC0; }
    .table-bordered th, .table-bordered td { border: 1px solid #BCBEC0; }

    th {
        color: #ffffff !important;
        font-size: 14px;
        text-align: center;
        padding: 6px;
        border: solid #BCBEC0;
        border-radius: 10px;
        background: #11a14e;
    }
    td { text-align: left !important; padding: 6px !important; color: #333333 !important; font-weight: 600; }
    tbody > tr > th { text-align: left !important; }
    tbody tr td:nth-child(2) { text-align: center !important; }
    tbody tr td:nth-child(3) { text-align: center !important; }

    .darkGrey_old { font-size: 14px; color: black; text-align: center; font-weight: bold; }
    .darkGrey { font-size: 14px; color: white; text-align: center; font-weight: bold; }
    .white { background-color: #ffffff; color: black !important; }

    /* Legacy "download" link inside the archive markup — hidden because
       the React page already renders its own working download button. */
    .download,
    .pageHeader .pageHeading .subHeading .download {
        display: none !important;
    }

    #leftArea .pageHeader .pageHeading .subHeading {
        clear: both;
        float: left;
        width: 100%;
        color: #000;
        text-align: center;
        font-size: 12px;
        font-weight: bold;
        margin: 5px 0;
        padding: 5px 0;
    }

    .block { display: none; }
    .hide { display: block !important; }

    .tbbody { margin-bottom: 4%; margin-top: -2%; }
    .padd { padding: 1%; }

    /* Desktop: hide the duplicate/mobile-card "show1" row only.
       .perform_data is the MAIN data row (Horse Name, Color/Sex, Age,
       Weight, Rating, Breeding, Trainer) and must stay visible here —
       hiding it globally was the bug that made all tables look empty. */
    @media (min-width: 500px) and (max-width: 2560px) {
        .show1 {
            display: none;
        }
        .left, .left1 {
            text-align: left !important;
        }
    }

    @media (max-width: 500px) {
        .text_size { font-size: 8px; }
        #leftArea { padding: 0px !important; }
        .download { float: unset !important; margin-bottom: 10px; }
        td { padding: 4px !important; }
        .table-bordered { border: unset; }
        .text_size { border: unset !important; }
        .hide { display: none !important; }
        .myhead { display: none; }
        .block { display: block; }
        .nm { text-align: center !important; }

        .perform_data {
            display: contents !important;
            border: 1px solid #cdced3 !important;
            border-radius: 12px;
            background: #cdced3 !important;
            margin-bottom: 5%;
            padding: 10px !important;
        }
        .perform_data td:first-child { padding-left: 10px; }
        .perform_data td:before {
            content: attr(data-label);
            float: left;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
            width: 45%;
        }
        .perform_data td { font-size: 10px !important; position: relative; border: unset !important; }

        .poolsTable tr:nth-child(1) th {
            border: unset !important;
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
        }
        .poolsTable tr th span { margin-left: 6%; }
        .poolsTable tr td { border: unset !important; padding-left: 30px !important; }
        .poolsTable { background-color: #cdced3 !important; border-radius: 10px !important; }

        .tbbody { margin-top: -8% !important; }
        .bot10 { margin-bottom: 20px !important; }

        .tabhead { border: 1px solid #cdced3 !important; background: #cdced3 !important; text-align: center !important; }
        .tabpads { text-align: center !important; font-size: 12px !important; }
        .darkGrey { font-size: 12px !important; }
    }

    @media (min-width: 320px) and (max-width: 375px) {
        td { padding: 2px !important; }
    }
</style>
`;

export default function Acceptance() {

    const searchParams = useSearchParams();
    const date = searchParams.get("date");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [mode, setMode] = useState("json");
    const [rawHtml, setRawHtml] = useState("");
    const [dayNarrative, setDayNarrative] = useState("");
    const [races, setRaces] = useState([]);
    const [pools, setPools] = useState([]);
    const [downloadFile, setDownloadFile] = useState(null);
    const [downloadAvailable, setDownloadAvailable] = useState(false);

    useEffect(() => {

        async function loadAcceptance() {

            if (!date) {
                setError("No date selected.");
                setLoading(false);
                return;
            }

            try {

                setLoading(true);
                setError(null);

                const data = await getAcceptance(date);

                setMode(data.mode || "json");
                setRawHtml(data.html || "");
                setDayNarrative(data.dayNarrative);
                setRaces(data.races);
                setPools(data.pools);
                setDownloadFile(data.downloadFile);
                setDownloadAvailable(data.downloadAvailable);

            } catch (err) {

                console.error("Acceptance Error:", err);
                setError("Unable to load acceptances for this date.");

            } finally {

                setLoading(false);

            }

        }

        loadAcceptance();

    }, [date]);

    const isHtmlMode = mode === "html";
    const hasNoHtml = isHtmlMode && !rawHtml.trim();

    return (
        <section className="acceptancePage docPage">

            <div className="docBadgeWrap">
                <span className="docBadge">Acceptances</span>
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
                    Download Acceptance
                </button>

                {!isHtmlMode && (
                    <div className="docHeader">
                        <p className="docClub">ROYAL WESTERN INDIA TURF CLUB.</p>
                        {dayNarrative && (
                            <p className="docMeeting">{dayNarrative}</p>
                        )}
                        <h1 className="docWatermark">ACCEPTANCES</h1>
                        <p className="docHint">
                            Click on a horse to view its entry details
                        </p>
                    </div>
                )}

                {loading && (
                    <div className="docStateBox">
                        <div className="docLoader" />
                        <p>Loading acceptances…</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="docStateBox docStateError">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && !isHtmlMode && races.length === 0 && (
                    <div className="docStateBox">
                        <p>No acceptance data found for this date.</p>
                    </div>
                )}

                {!loading && !error && isHtmlMode && hasNoHtml && (
                    <div className="docStateBox">
                        <p>No acceptance data found for this date.</p>
                    </div>
                )}

                {!loading && !error && isHtmlMode && !hasNoHtml && (
                    <iframe
                        className="docArchiveHtml"
                        srcDoc={ARCHIVE_STYLES_ACCEPTANCE + rawHtml}
                        title="Acceptances"
                        sandbox="allow-same-origin"
                        onLoad={(e) => {
                            const iframe = e.target;
                            const doc = iframe.contentWindow?.document;
                            if (!doc) return;

                            const setHeight = () => {
                                iframe.style.height = doc.documentElement.scrollHeight + "px";
                            };

                            setHeight();
                            requestAnimationFrame(setHeight);
                            setTimeout(setHeight, 100);
                        }}
                    />
                )}

                {!loading && !error && !isHtmlMode && races.map((race, idx) => (

                    <div className="docRaceBlock" key={idx}>

                        <div className="docRaceBar">
                            <p className="docRaceName">
                                {race.race_no ?? idx + 1}.&nbsp; {race.race_name}
                                {race.division && <> &nbsp;({race.division})</>}
                                {race.void && <span className="docVoidTag">&nbsp; VOID</span>}
                            </p>
                            <p className="docRaceMeta">
                                {race.distance && <>(About) {race.distance} Metres.</>}
                                {race.time && <>&nbsp;&nbsp;Time: {race.time}</>}
                                {race.foreign_jockeys_eligible && (
                                    <span className="docForeignTag">
                                        &nbsp;&nbsp;Foreign Jockeys Eligible
                                    </span>
                                )}
                            </p>
                            {race.narrative_entry && (
                                <p className="docRaceNarration">{race.narrative_entry}</p>
                            )}
                        </div>

                        {race.weight_adjustments && race.weight_adjustments.length > 0 && (
                            <div className="docWeightNotes">
                                {race.weight_adjustments.map((adj, nIdx) => (
                                    <p key={nIdx}>
                                        Weights {adj.direction} by {adj.kg} kg at {adj.stage} stage.
                                    </p>
                                ))}
                            </div>
                        )}

                        <div className="docTableWrap">
                            <table className="docTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Horse</th>
                                        <th>Weight</th>
                                        <th>Rating</th>
                                        <th>Trainer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {race.horses && race.horses.map((horse, hIdx) => (
                                        <tr key={horse.horseseq || hIdx}>
                                            <td>{hIdx + 1}</td>
                                            <td className="docHorseName">
                                                {horse.name}
                                                {horse.sire && (
                                                    <span className="docBreeding">
                                                        {horse.sire}
                                                        {horse.dam ? `-${horse.dam}` : ""}
                                                        {horse.dam_nation
                                                            ? ` (${horse.dam_nation})`
                                                            : ""}
                                                    </span>
                                                )}
                                            </td>
                                            <td>{horse.weight ?? "-"}</td>
                                            <td>{horse.rating ?? "NR"}</td>
                                            <td>{horse.trainer}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

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