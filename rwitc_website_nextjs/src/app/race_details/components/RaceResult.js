"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getRaceResult } from "../../../services/raceResultService";
import "./RaceResult.css";

const ARCHIVE_STYLES_RACE_RESULT = `
<style>
    * { box-sizing: border-box; }
    html, body { overflow-x: hidden !important; width: 100% !important; }
    body { font-family: Arial; margin: 0; padding: 12px; }
    table { width: 100% !important; table-layout: fixed; }
    td, th { word-break: break-word; }  
    span, a { display: inline-block; text-decoration: none; color: #333333; }
    img { vertical-align: middle; }
    h1 { margin: unset !important; font-size: 26px !important; }
    h3 { font-family: 'Roboto Condensed', Arial, sans-serif; font-size: 22px; color: #000; margin: 10px 0; font-weight: 700; }
    .pageHeader { text-align: center; width: 100%; }
    .pageHeading { text-align: center; width: 100%; }
    .subHeading { font-size: 14px; font-weight: 700; color: #000; text-align: center; display: block; width: 100%; margin-left: 0 !important; }
    
    table { border-collapse: collapse; width: 100%; }
    .table { width: 100%; max-width: 100%; margin-bottom: 1rem; background-color: transparent; }
    .table-bordered { border: 1px solid #cccccc; }
    .table-bordered th, .table-bordered td { border: 1px solid #cccccc; }

    th {
        color: #000000 !important;
        font-size: 13px;
        font-weight: 700;
        text-align: center;
        padding: 10px 12px;
        border: 1px solid #cccccc;
        background: #ffffff;
        border-radius: 0;
    }
    td {
        text-align: center;
        padding: 10px 12px !important;
        color: #222222 !important;
        font-weight: 400;
        border: 1px solid #cccccc;
        background: #ffffff;
    }
    .alignLeft, td.alignLeft { text-align: left !important; }

    .darkGrey_old { font-size: 14px; color: #000; text-align: center; font-weight: bold; }
    .darkGrey { font-size: 14px; color: #000; text-align: center; font-weight: bold; }
    .white { background-color: #ffffff; color: #000 !important; }

    .download,
    .pageHeader .pageHeading .subHeading .download {
        display: none !important;
    }

    #leftArea .pageHeader .pageHeading .subHeading {
        clear: both;
        float: none;
        display: block;
        width: 100%;
        color: #000;
        font-weight: bold;
        text-align: center;
        font-size: 12px;
        margin: 5px 0;
        padding: 5px 0;
    }

    .block { display: none; }
    .hide { display: block !important; }

    .tbbody { margin-bottom: 4%; margin-top: -2%; }
    .padd { padding: 1%; }

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

export default function RaceResult() {

    const searchParams = useSearchParams();
    const racedate = searchParams.get("racedate") || searchParams.get("date");
    const raceno = searchParams.get("raceno");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [mode, setMode] = useState("json");
    const [rawHtml, setRawHtml] = useState("");
    const [found, setFound] = useState(true);
    const [message, setMessage] = useState(null);
    const [raceHeader, setRaceHeader] = useState(null);
    const [voidRace, setVoidRace] = useState(false);
    const [results, setResults] = useState([]);
    const [downloadFile, setDownloadFile] = useState(null);
    const [downloadAvailable, setDownloadAvailable] = useState(false);

    useEffect(() => {

        async function loadRaceResult() {

            if (!racedate) {
                setError("No race date selected.");
                setLoading(false);
                return;
            }

            try {

                setLoading(true);
                setError(null);

                const data = await getRaceResult(racedate, raceno);

                setMode(data.mode || "json");
                setRawHtml(data.html || "");
                setFound(data.found);
                setMessage(data.message);
                setRaceHeader(data.raceHeader);
                setVoidRace(data.voidRace);
                setResults(data.results);
                setDownloadFile(data.downloadFile);
                setDownloadAvailable(data.downloadAvailable);

            } catch (err) {

                console.error("Race Result Error:", err);
                setError("Unable to load race results for this date.");

            } finally {

                setLoading(false);

            }

        }

        loadRaceResult();

    }, [racedate, raceno]);

    const isHtmlMode = mode === "html";
    const hasNoHtml = isHtmlMode && !rawHtml.trim();
    const hasNoResults = !isHtmlMode && (!found || results.length === 0);

    return (
        <section className="raceResultPage docPage">

            <div className="docBadgeWrap">
                <span className="docBadge">Race Results</span>
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
                    Download Race Results
                </button>

                {!isHtmlMode && (
                    <div className="docHeader">
                        <p className="docClub">
                            ROYAL WESTERN INDIA TURF CLUB.
                        </p>
                        <h1 className="docWatermark">RACE RESULT</h1>
                        <p className="docHint">
                            Click on a horse to know its Performance Profile @ RWITC
                        </p>
                    </div>
                )}

                {loading && (
                    <div className="docStateBox">
                        <div className="docLoader" />
                        <p>Loading race results…</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="docStateBox docStateError">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && isHtmlMode && hasNoHtml && (
                    <div className="docStateBox">
                        <p>No race results found for this date.</p>
                    </div>
                )}

                {!loading && !error && isHtmlMode && !hasNoHtml && (
                    <iframe
                        className="docArchiveHtml"
                        srcDoc={ARCHIVE_STYLES_RACE_RESULT + rawHtml}
                        title="Race Results"
                        sandbox="allow-same-origin"
                        scrolling="no"
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

                {!loading && !error && !isHtmlMode && hasNoResults && (
                    <div className="docStateBox">
                        <p>{message || "No results found for this race."}</p>
                    </div>
                )}

                {!loading && !error && !isHtmlMode && !hasNoResults && (

                    <div className="docRaceBlock">

                        <div className="docRaceBar">
                            <p className="docRaceName">
                                {raceHeader?.race_name}
                                {voidRace && <span className="docVoidTag">&nbsp; VOID</span>}
                            </p>
                            <p className="docRaceMeta">
                                {raceHeader?.race_term && <>{raceHeader.race_term}&nbsp;&nbsp;</>}
                                {raceHeader?.distance && <>(About) {raceHeader.distance} Metres.</>}
                                {raceHeader?.grade && <>&nbsp;&nbsp;{raceHeader.grade}</>}
                            </p>
                        </div>

                        <div className="docTableWrap">
                            <table className="docTable">
                                <thead>
                                    <tr>
                                        <th>Placing</th>
                                        <th>Horse</th>
                                        <th>Wt</th>
                                        <th>Jockey</th>
                                        <th>Trainer</th>
                                        <th>Odds</th>
                                        <th>Time</th>
                                        <th>Horse Wt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {results.map((res, idx) => (
                                        <tr key={res.horseseq || idx}>
                                            <td>{res.placing}</td>
                                            <td className="docHorseName">
                                                {res.horse_name}
                                                {res.sire && (
                                                    <span className="docBreeding">
                                                        {res.sire}
                                                        {res.dam ? `-${res.dam}` : ""}
                                                    </span>
                                                )}
                                            </td>
                                            <td>{res.weight ?? "-"}</td>
                                            <td>{res.jockey}</td>
                                            <td>{res.trainer}</td>
                                            <td>{res.odds ?? "-"}</td>
                                            <td>{res.time ?? "-"}</td>
                                            <td>{res.horse_weight ?? "-"}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                    </div>

                )}

            </div>

        </section>
    );
}