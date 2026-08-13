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
    table { width: 100% !important; max-width: 100% !important; table-layout: fixed; border-collapse: collapse; }
    body { overflow-x: hidden !important; }
    td, th { word-break: break-word; padding: 10px 12px !important; border: 1px solid #cccccc; }
    span, a { display: inline-block; text-decoration: none; color: #333333; font-weight: bold; }
    th { color: #000 !important; font-weight: 700; text-align: center; background: #fff; }
    td { text-align: center; color: #222 !important; font-weight: 400; background: #fff; }
    .alignLeft, td.alignLeft { text-align: left !important; }
    .darkGrey { font-size: 14px; color: #000; text-align: center; font-weight: bold; }
    .download { display: none !important; }
    h3 { font-size: 22px; color: #000; margin: 10px 0; font-weight: 700; }
    .pageHeader, .pageHeading { text-align: center; width: 100%; }
    .subHeading { font-size: 14px; font-weight: 700; color: #000; margin-left: 2% !important; display: block; width: 100%; }
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
    const [dayLabel, setDayLabel] = useState(null);
    const [conditions, setConditions] = useState(null);
    const [races, setRaces] = useState([]);
    const [downloadUrl, setDownloadUrl] = useState(null);

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

    if (data.mode === "html") {
        setRawHtml(data.html || "");
        setFound(data.found);
        setLoading(false);
        return;
    }

    // json mode (dates before cutoff)
    setFound(data.found);
    setMessage(data.message);
    setDayLabel(data.dayLabel);
    setConditions(data.conditions);
    setRaces(data.races || []);
    setDownloadUrl(data.downloadUrl);

            } catch (err) {

                console.error("Race Result Error:", err);
                setError("Unable to load race results for this date.");

            } finally {

                setLoading(false);

            }

        }

        loadRaceResult();

    }, [racedate, raceno]);

    const hasNoResults = !found || races.length === 0;

    function formatOwnership(str) {
        return (str || "").trim();
    }

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
                        if (downloadUrl) {
                            window.open(downloadUrl, "_blank", "noopener,noreferrer");
                        } else {
                            alert("No download file found for this date.");
                        }
                    }}
                >
                    Download Race Results
                </button>

                {mode !== "html" && (
    <div className="docHeader">
        <p className="docClub">
            ROYAL WESTERN INDIA TURF CLUB.
        </p>
        {dayLabel && <p className="docClub">{dayLabel}</p>}
        <h1 className="docWatermark">RACE RESULT</h1>
        <p className="docHint">
            Click on a horse to know its Performance Profile @ RWITC
        </p>
        <p className="docHint">
            Click on the Dam to get her progeny details
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

                {!loading && !error && mode === "html" && !rawHtml.trim() && (
                    <div className="docStateBox">
                        <p>No race results found for this date.</p>
                    </div>
                )}

                {!loading && !error && mode === "html" && rawHtml.trim() && (
                    <iframe
                        className="docArchiveHtml"
                        srcDoc={ARCHIVE_STYLES_RACE_RESULT + rawHtml}
                        title="Race Results"
                        sandbox="allow-same-origin"
                        scrolling="no"
                        style={{ width: "100%", border: "none" }}
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

                {!loading && !error && mode === "json" && hasNoResults && (
                    <div className="docStateBox">
                        <p>{message || "No results found for this date."}</p>
                    </div>
                )}

                {!loading && !error && !hasNoResults && conditions && (
                    <table className="docTable" style={{ marginBottom: "20px" }}>
                        <tbody>
                            <tr>
                                <th>Weather</th>
                                <td className="alignLeft">{conditions.weather}</td>
                            </tr>
                            <tr>
                                <th>Penetrometer Reading</th>
                                <td className="alignLeft">{conditions.penetrometer}</td>
                            </tr>
                            <tr>
                                <th>False Rails</th>
                                <td className="alignLeft">{conditions.false_rails}</td>
                            </tr>
                        </tbody>
                    </table>
                )}

                {!loading && !error && mode === "json" && !hasNoResults && races.map((race, rIdx) => {

                    function formatTote(tote) {
                        if (!tote) return "";
                        let parts = [];

                        if (tote.win) {
                            let win = `WIN : ${tote.win}`;
                            if (tote.win_alternate) win += ` & ${tote.win_alternate}`;
                            parts.push(win);
                        }
                        if (tote.place && tote.place.length) {
                            parts.push(`PLACE : ${tote.place.join(",")}`);
                        }
                        if (tote.shp) parts.push(`SHP : ${tote.shp}`);
                        if (tote.exacta_win) parts.push(`EXW : ${tote.exacta_win}`);
                        if (tote.exacta_win_cf) parts.push(`EXW : C/f ${tote.exacta_win_cf}`);
                        if (tote.exacta_place) parts.push(`EXP : ${tote.exacta_place}`);
                        if (tote.exacta_place_cf) parts.push(`EXP : C/f ${tote.exacta_place_cf}`);
                        if (tote.forecast) parts.push(`FOR : ${tote.forecast}`);
                        if (tote.forecast_cf) parts.push(`FC : ${tote.forecast_cf} (c/f)`);

                        if (tote.quinella && tote.quinella.length) {
                            const q = tote.quinella.map(item =>
                                item.carried_forward ? `${item.value} (c/f)` : item.value
                            ).join(",");
                            parts.push(`QNL : ${q}`);
                        }

                        if (tote.tanala && tote.tanala.length) {
                            const t = tote.tanala.map(item =>
                                item.carried_forward ? `${item.value} (c/f)` : item.value
                            ).join(" & ");
                            parts.push(`TNL : ${t}`);
                        }

                        return parts.join(" ");
                    }

                    return (
                        <table className="docTable" key={race.race_no_season || rIdx} style={{ marginBottom: "24px" }}>
                            <tbody>

                                <tr>
                                    <th rowSpan="2" style={{ width: "8%" }}>No.: {race.race_no_season}</th>
                                    <th colSpan="6" rowSpan="2">
                                        {race.race_name} {race.division}
                                        {race.void && <span className="docVoidTag">&nbsp; VOID</span>}
                                        <br />
                                        {race.narrative_entry}
                                        <br />
                                        Time: {race.time}
                                        <br />
                                        (About) {race.distance} Metres.
                                    </th>
                                    <th rowSpan="2" style={{ width: "8%" }}>
                                        <a href="#" onClick={(e) => e.preventDefault()}>Video</a>
                                    </th>
                                </tr>
                                <tr>
                                    <th>{race.race_no}</th>
                                </tr>

                                {race.cancelled ? (
                                    <tr>
                                        <td colSpan="8" className="alignLeft" style={{ fontWeight: "bold", fontSize: "14px", textAlign: "center" }}>
                                            This race was cancelled.
                                        </td>
                                    </tr>
                                ) : (
                                    <>
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

                                        {race.results.map((res, idx) => (
                                            <tr key={res.horseseq || idx}>
                                                <td>{res.placing}</td>
                                                <td className="alignLeft docHorseName">
                                                    {res.horse_name}
                                                    {res.sire && (
                                                        <span className="docBreeding">
                                                            ({res.sire}-{res.dam})
                                                        </span>
                                                    )}
                                                </td>
                                                <td>{res.weight ?? "-"}</td>
                                                <td>
                                                    {res.jockey}
                                                    {res.jockey_allowance ? ` - ${res.jockey_allowance}` : ""}
                                                </td>
                                                <td>{res.trainer}</td>
                                                <td>{res.odds ?? "--"}</td>
                                                <td>{res.time ?? "-"}</td>
                                                <td>{res.horse_weight}</td>
                                            </tr>
                                        ))}

                                        {race.void ? (
                                            <tr>
                                                <td colSpan="8" className="alignLeft" style={{ fontWeight: "bold", fontSize: "14px", textAlign: "center" }}>
                                                    This race has been declared Null &amp; Void
                                                </td>
                                            </tr>
                                        ) : (
                                            <>
                                                <tr>
                                                    <th colSpan="2">Ownership</th>
                                                    <td colSpan="6" className="alignLeft">{formatOwnership(race.ownership)}</td>
                                                </tr>
                                                <tr>
                                                    <th colSpan="2">Breeder</th>
                                                    <td colSpan="6" className="alignLeft">{race.breeder}</td>
                                                </tr>
                                                <tr>
                                                    <th colSpan="2">Distance</th>
                                                    <td colSpan="6" className="alignLeft">{race.distance_run}</td>
                                                </tr>
                                                <tr>
                                                    <th colSpan="2">Results as per Card Nos</th>
                                                    <td colSpan="6" className="alignLeft">{race.results_by_card_no}</td>
                                                </tr>
                                                <tr>
                                                    <th colSpan="2">Tote Favourite</th>
                                                    <td colSpan="6" className="alignLeft">{race.tote_favourite}</td>
                                                </tr>
                                            </>
                                        )}

                                        <tr>
                                            <th colSpan="2">Tote Dividends</th>
                                            <td colSpan="6" className="alignLeft">{formatTote(race.tote)}</td>
                                        </tr>
                                    </>
                                )}

                            </tbody>
                        </table>
                    );
                })}

            </div>

        </section>
    );
}