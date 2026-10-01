"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getRaceResult } from "../../../services/raceResultService";
import { getRaceDayStatus } from "../../../services/mediaService";
import { formatArchiveHtml, handleArchiveIframeLoad } from "../../../utils/archiveHtmlHelper";
import { FaHorseHead, FaPlayCircle } from "react-icons/fa";
import "./RaceResult.css";

/*
 * Styles + script injected INSIDE the archive iframe.
 * Iframe always fits the card width. Only each race table
 * scrolls sideways (wrapped in .tableScroll).
 */
const ARCHIVE_STYLES_RACE_RESULT = `
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; max-width: 100%; overflow-x: hidden; }
body { font-family: Arial, sans-serif; padding: 12px; display: flow-root; }
img { max-width: 100%; height: auto; }

/* FIX: auto layout so columns size to their content (was: fixed) */
table { width: 100% !important; max-width: 100% !important; table-layout: auto; border-collapse: collapse; }

/* FIX: words no longer break letter-by-letter (was: word-break: break-word) */
td, th { word-break: normal; overflow-wrap: break-word; padding: 10px 12px !important; border: 1px solid #cccccc; }

span, a { display: inline-block; text-decoration: none; color: #333333; font-weight: bold; }
th { color: #000 !important; font-weight: 700; text-align: center; background: #fff; }
td { text-align: center; color: #222 !important; font-weight: 400; background: #fff; }
.alignLeft, td.alignLeft { text-align: left !important; }
.darkGrey { font-size: 14px; color: #000; text-align: center; font-weight: bold; }
.download { display: none !important; }
h3 { font-size: 22px; color: #000; margin: 10px 0; font-weight: 700; }
.pageHeader, .pageHeading { text-align: center; width: 100%; }
.subHeading { font-size: 14px; font-weight: 700; color: #000; margin-left: 2% !important; display: block; width: 100%; }

/* MOBILE: header wraps inside the card */
.pageHeader, .pageHeading, .subHeading, h3, p { max-width: 100%; white-space: normal; overflow-wrap: anywhere; }

/* MOBILE: ONLY the race tables scroll sideways */
.tableScroll { width: 100%; max-width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; margin-bottom: 16px; }
.tableScroll table { width: 100% !important; min-width: 720px; margin: 0 !important; table-layout: auto; }

/* FIX: first column (No.: 91 / Placing) is never narrower than its content */
.tableScroll th:first-child,
.tableScroll td:first-child { min-width: 84px; white-space: nowrap; }

/* FIX: short header labels (Placing, Wt, Jockey...) stay on one line */
.tableScroll th { white-space: nowrap; }

/* Long text cells and label cells (Ownership, Results as per Card Nos...) may wrap */
.tableScroll td.alignLeft,
.tableScroll th[colspan] { white-space: normal; }

/* Race title cell (name, time, distance) reads left-to-right, not cut off */
.tableScroll th[colspan] { text-align: left; }
</style>

<script>
(function () {
    function wrapTables() {
        document.querySelectorAll("table").forEach(function (table) {
            if (table.parentElement && table.parentElement.classList.contains("tableScroll")) return;
            if (table.querySelector("table")) return;

            var maxCells = 0;
            table.querySelectorAll("tr").forEach(function (tr) {
                if (tr.children.length > maxCells) maxCells = tr.children.length;
            });
            if (maxCells < 4) return;

            var wrap = document.createElement("div");
            wrap.className = "tableScroll";
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        });
    }

    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", wrapTables);
    else wrapTables();
})();
</script>
`;

/*
 * Iframe onLoad: run helper, tidy archive markup, then keep the
 * iframe height in sync. Height changes only when the value differs,
 * so it never loops or vibrates.
 */
function handleResultIframeLoad(e) {
    handleArchiveIframeLoad(e);

    const iframe = e.target;
    const doc = iframe.contentWindow?.document;
    if (!doc || !doc.body) return;

    // "No.: 91" cell gets a guaranteed width so text stays inside the column
    doc.querySelectorAll("th").forEach((th) => {
        if (th.textContent.trim().toLowerCase().startsWith("no.:")) {
            th.style.whiteSpace = "nowrap";
            th.style.minWidth = "84px";
            th.style.width = "84px";
        }
    });

    doc.querySelectorAll("a").forEach((link) => {
        if (link.textContent.trim().toLowerCase() === "video") {
            link.textContent = "";
            link.innerHTML =
                '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="#16a34a"><path d="M8 5v14l11-7z"/></svg>';
            link.setAttribute("target", "_blank");
            link.setAttribute("rel", "noopener noreferrer");
            link.style.display = "inline-flex";
            link.style.alignItems = "center";
            link.style.justifyContent = "center";

            link.addEventListener("click", (ev) => {
                ev.preventDefault();
                window.open(link.href, "_blank", "noopener,noreferrer");
            });
        }
    });

    const setHeight = () => {
        if (!iframe.isConnected || !doc.body) return;
        const next = Math.ceil(doc.body.offsetHeight);
        const current = parseInt(iframe.style.height, 10);
        if (next !== current) {
            iframe.style.height = next + "px";
        }
    };

    setHeight();
    requestAnimationFrame(setHeight);

    if (iframe.contentWindow?.ResizeObserver) {
        new iframe.contentWindow.ResizeObserver(setHeight).observe(doc.body);
    }
    if (doc.fonts && doc.fonts.ready) {
        doc.fonts.ready.then(setHeight);
    }
}

export default function RaceResult() {

    const searchParams = useSearchParams();
    const racedate = searchParams.get("racedate") || searchParams.get("date");
    const raceno = searchParams.get("raceno");
    const type = searchParams.get("type") || "race_result";
    const raceType = searchParams.get("race_type") || "post_race";

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
    const [downloadAvailable, setDownloadAvailable] = useState(false);
    const [mediaTipsUrl, setMediaTipsUrl] = useState(null);
    const [updatesUrl, setUpdatesUrl] = useState(null);

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

                const data = await getRaceResult(racedate, raceno, type, raceType);

                setMode(data.mode || "json");

                if (data.mode === "html") {
                    setRawHtml(data.html || "");
                    setFound(data.found);
                    setDownloadUrl(data.downloadFile || null);
                    setDownloadAvailable(data.downloadAvailable || false);
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

    }, [racedate, raceno, type, raceType]);

    // Media Tips / Updates links (always shown on this page)
    useEffect(() => {
        let active = true;

        getRaceDayStatus()
            .then((data) => {
                if (!active || !data) return;
                setMediaTipsUrl(data.mediaTipsUrl || null);
                setUpdatesUrl(data.updatesUrl || null);
            })
            .catch(() => {});

        return () => {
            active = false;
        };
    }, []);

    const hasNoResults = !found || races.length === 0;

    function formatOwnership(str) {
        return (str || "").trim();
    }

    return (
        <section className="raceResultPage docPage">

            <div className="aboutTitleWrap">
                <h1 className="aboutHeading">Race Results</h1>
                <div className="sectionDivider">
                    <span className="dividerLine dividerLineLeft"></span>
                    <FaHorseHead className="dividerIcon" />
                    <span className="dividerLine dividerLineRight"></span>
                </div>
            </div>

            <div className="docContainer">

                <button
                    type="button"
                    className="docDownloadBtn"
                    onClick={() => {
                        if (downloadUrl) {
                            window.open(downloadUrl, "_blank", "noopener,noreferrer");
                        } else {
                            alert("No race result HTML found for this date.");
                        }
                    }}
                >
                    Download Race Results
                </button>

                {/* Header block only applies to the structured (DB) view */}
                {mode !== "html" && (
                    <div className="docHeader">
                        <p className="docClub">ROYAL WESTERN INDIA TURF CLUB.</p>
                        {dayLabel && <p className="docClub">{dayLabel}</p>}
                        <h1 className="docWatermark">RACE RESULT</h1>
                        <p className="docHint">Click on a horse to know its Performance Profile @ RWITC</p>
                        <p className="docHint">Click on the Dam to get her progeny details</p>
                    </div>
                )}

                {/* MEDIA TIPS + UPDATES buttons */}
                {(mediaTipsUrl || updatesUrl) && (
                    <div className="raceResultActions">
                        {mediaTipsUrl && (
                            <a
                                href={mediaTipsUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="raceResultActionBtn"
                            >
                                Media Tips
                            </a>
                        )}
                        {updatesUrl && (
                            <a
                                href={updatesUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="raceResultActionBtn"
                            >
                                Updates
                            </a>
                        )}
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

                {/* Archive dates: iframe fits the card, only race tables scroll */}
                {!loading && !error && mode === "html" && rawHtml.trim() && (
                    <div className="docArchiveScroll">
                        <iframe
                            className="docArchiveHtml"
                            srcDoc={formatArchiveHtml(ARCHIVE_STYLES_RACE_RESULT, rawHtml)}
                            title="Race Results"
                            sandbox="allow-same-origin allow-scripts allow-top-navigation allow-forms allow-popups"
                            scrolling="no"
                            style={{ width: "100%", border: "none" }}
                            onLoad={handleResultIframeLoad}
                        />
                    </div>
                )}

                {!loading && !error && mode === "json" && hasNoResults && (
                    <div className="docStateBox">
                        <p>{message || "No results found for this date."}</p>
                    </div>
                )}

                {/* DB-sourced dates: structured tables */}
                {!loading && !error && !hasNoResults && conditions && (
                    <div className="docTableWrap" style={{ marginBottom: "20px" }}>
                        <table className="docTable">
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
                    </div>
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
                        <div className="docTableWrap" key={race.race_no_season || rIdx} style={{ marginBottom: "24px" }}>
                            <table className="docTable">
                                <tbody>

                                    <tr>
                                        <th rowSpan="2" style={{ width: "8%", whiteSpace: "nowrap" }}>No.: {race.race_no_season}</th>
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
                                            <a href="#" onClick={(e) => e.preventDefault()} title="Video">
                                                <FaPlayCircle size={22} />
                                            </a>
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

                                            {(race.results || []).map((res, idx) => (
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
                        </div>
                    );
                })}

            </div>

        </section>
    );
}