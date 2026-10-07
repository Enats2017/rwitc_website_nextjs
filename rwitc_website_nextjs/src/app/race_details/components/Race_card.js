"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getRaceCard } from "../../../services/racecardService";
import { formatArchiveHtml, handleArchiveIframeLoad } from "../../../utils/archiveHtmlHelper";
import { FaHorseHead } from "react-icons/fa";
import "./Race_card.css";
const MONTHS = {
    jan: "01", feb: "02", mar: "03", apr: "04", may: "05", jun: "06",
    jul: "07", aug: "08", sep: "09", oct: "10", nov: "11", dec: "12"
};

/* Koi bhi date format -> YYYY-MM-DD */
function toIsoDate(raw) {
    const s = String(raw || "").trim();
    if (!s) return "";
    let m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (m) return `${m[1]}-${m[2]}-${m[3]}`;
    m = s.match(/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})/);
    if (m) return `${m[3]}-${m[2].padStart(2, "0")}-${m[1].padStart(2, "0")}`;
    m = s.match(/^(\d{1,2})[-\/ ]([A-Za-z]{3})[A-Za-z]*[-\/ ,]*(\d{4})/);
    if (m && MONTHS[m[2].toLowerCase()]) {
        return `${m[3]}-${MONTHS[m[2].toLowerCase()]}-${m[1].padStart(2, "0")}`;
    }
    return "";
}

/* Performance profile page ke race result jaisa hi URL banata hai:
   /race_details?type=performanceProfile&horsename=..&race_no=..&race_date=..&view=raceResult */
function openRaceResult(raceNo, isoDate, horseName) {
    if (!raceNo || !isoDate) return;

    const params = new URLSearchParams();
    params.set("type", "performanceProfile");
    if (horseName) params.set("horsename", horseName);
    params.set("race_no", raceNo);
    params.set("race_date", isoDate);
    params.set("view", "raceResult");

    window.open(
        `${window.location.origin}/race_details?${params.toString()}`,
        "_blank"
    );
}

/* Archive iframe me us run row ke horse ka naam nikalta hai */
function getHorseNameFromRow(row) {
    const perfRow = row.closest("tr[id^='performance_']");
    let scope = null;

    if (perfRow) {
        const btn = row.ownerDocument.getElementById(perfRow.id.replace("performance_", ""));
        scope = btn ? btn.closest("table.infoTable") : null;
    }
    if (!scope) scope = row.closest("table.infoTable");

    const el = scope ? scope.querySelector("a, span") : null;
    return el ? el.textContent.trim() : "";
}
/*
 * Styles + script injected INSIDE the archive iframe.
 * Iframe always fits the card width. Only the performance-history
 * table (View Runs) scrolls sideways (wrapped in .tableScroll).
 */
const ARCHIVE_STYLES_RACECARD = `
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; max-width: 100%; overflow-x: hidden; }
body { font-family: Arial, sans-serif; word-wrap: break-word; padding: 12px; background: #ffffff; display: flow-root; }
span, a { display: inline-block; text-decoration: none; color: #c9c9c9; }
img { max-width: 100%; height: auto; vertical-align: middle; }
h3 { font-family: 'Roboto Condensed', Arial, sans-serif; font-size: 32px; letter-spacing: 3px; color: #c9c9c9; text-align: center; margin: 10px 0; }
.pageHeading { text-align: center; margin-bottom: 24px; }
.subHeading { text-align: center; font-size: 13px; font-weight: bold; color: #111; margin: 4px 0; }

/* Legacy "download" link - hidden, page already renders its own button. */
.download { display: none !important; }

/* Race-number quick-nav pills */
.slider { text-align: center; margin: 20px 0; }
.slider a.race_call { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; margin: 0 4px; border-radius: 50%; background: #16a34a; color: #ffffff !important; font-weight: 700; font-size: 14px; }

table { border-collapse: collapse; width: 100%; }
.table { max-width: 100%; margin-bottom: 1rem; background-color: transparent; }
.table-bordered { border: 1px solid #e2e2e2; }
.table-bordered th, .table-bordered td { border: none; }

/* Race header bar (green) */
.race_no_data { background: #16a34a !important; border-radius: 4px; margin: 36px 0 18px; overflow: hidden; }
.race_no_data th { background: transparent; color: #ffffff !important; text-align: left; padding: 12px 26px; font-size: 14px; line-height: 1.1; vertical-align: top; border: none !important; }
.race_no_data th, .race_no_data th span, .race_no_data th * { color: #ffffff !important; }
.darkGrey { color: #ffffff !important; font-weight: bold; }
.foreign_eligible2 span { display: block; margin: 2px 0; }

/* Spacing between horse cards */
td > table.infoTable[style*='box-shadow'] { margin: 18px 0 !important; padding: 24px 28px !important; }

/* Horse card (grey box, background comes from inline style) */
.infoTable { width: 100%; }
.infoTable td { border: 0; padding: 4px 6px; font-size: 13.5px; color: #222; font-weight: 700; }
.horse_number_class { color: #111 !important; }
.infoTable span, .infoTable a,
.infoTable font, .infoTable b, .infoTable strong,
.infoTable td span, .infoTable td a, .infoTable td font,
.infoTable td *:not(img):not(.view_runs) { color: #111 !important; }
.infoTable a:hover { text-decoration: underline; }
.alignLeft { text-align: left !important; }
.alignRight { text-align: right !important; }
.infoTable img { border: 2px solid #16a34a !important; border-radius: 2px; }
.infoTable td:has(> img) { text-align: right !important; }

/* "View Runs" button */
.view_perform { cursor: pointer; }
.view_runs { display: inline-block; background: #16a34a; color: #ffffff !important; font-weight: 700; font-size: 12px; padding: 6px 16px; border-radius: 4px; cursor: pointer; }
.view_runs:hover { background: #12833b; }

/* Performance history table */
.perform_head td { background: #f7f7f7; font-weight: bold; font-size: 12px; padding: 8px; border: 1px solid #e2e2e2; text-align: center; }
.perform_data td { font-size: 12px; padding: 8px; border: 1px solid #e2e2e2; text-align: center; font-weight: 700; }
.perform_data td:nth-child(2), .perform_data td:nth-child(2) *, .perform_data td:nth-child(2) span, .perform_data td:nth-child(2) font, .perform_data td:nth-child(2) a { color: #111 !important; }

/* Pools table */
.poolsTable th { background: #16a34a; color: #ffffff !important; padding: 10px; text-align: left; }
.poolsTable td { padding: 10px; border: 1px solid #e2e2e2; }

/* Duplicate "show1" row hidden on ALL devices. .perform_data stays visible. */
.show1 { display: none; }

/* MOBILE FIX: header / cards wrap inside the card */
.pageHeading, .subHeading, h3, p, .race_no_data th { max-width: 100%; white-space: normal; overflow-wrap: anywhere; }
@media (max-width: 600px) {
    h3 { font-size: 24px; letter-spacing: 2px; }
    .race_no_data th { padding: 10px 14px; }
    td > table.infoTable[style*='box-shadow'] { padding: 14px !important; }
}

/* MOBILE FIX: ONLY the performance table scrolls */
.tableScroll { width: 0; min-width: 100%; max-width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; overscroll-behavior-x: contain; padding-bottom: 6px; }
.tableScroll table { width: 100% !important; min-width: 640px !important; margin: 0 !important; }
.tableScroll::-webkit-scrollbar { height: 6px; }
.tableScroll::-webkit-scrollbar-track { background: #f1f5f9; }
.tableScroll::-webkit-scrollbar-thumb { background: #16a34a; border-radius: 10px; }
</style>

<script>
(function () {
    function wrapTables() {
        document.querySelectorAll(".perform_head").forEach(function (head) {
            var table = head.closest("table");
            if (!table) return;
            if (table.parentElement && table.parentElement.classList.contains("tableScroll")) return;

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

export default function RaceCard() {

    const searchParams = useSearchParams();
    const date = searchParams.get("date");
    const type = searchParams.get("type") || "racecard";
    const raceType = searchParams.get("race_type") || "pre_race";

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

                const data = await getRaceCard(date, type, raceType);

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

    }, [date, type, raceType]);

    const toggleRuns = (key) => {
        setOpenRuns((prev) => ({ ...prev, [key]: !prev[key] }));
    };

    /*
     * Iframe onLoad handler for archive HTML.
     * Height = body.scrollHeight (real content height), re-measured on
     * images / fonts / resize / View Runs / race filter. Only updates
     * when the value changes, so it never loops or vibrates.
     */
    const handleRaceCardIframeLoad = (e) => {
        handleArchiveIframeLoad(e);

        const iframe = e.target;
        const doc = iframe.contentWindow?.document;
        if (!doc || !doc.body) return;

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

        /* horse name + dam link ko force black karo */
        doc.querySelectorAll(".infoTable span, .infoTable a, .infoTable font").forEach((el) => {
            if (el.classList.contains("view_runs")) return;
            el.style.setProperty("color", "#111", "important");
        });

        /* wrap the runs table so ONLY it scrolls sideways (runs here too,
           so it works even if the inline script did not run) */
        doc.querySelectorAll(".perform_head").forEach((head) => {
            const table = head.closest("table");
            if (!table || table.parentElement?.classList.contains("tableScroll")) return;

            const wrap = doc.createElement("div");
            wrap.className = "tableScroll";
            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        });

        const setHeight = () => {
            if (!iframe.isConnected || !doc.body) return;
            const next = Math.ceil(doc.body.scrollHeight);
            const current = parseInt(iframe.style.height, 10);
            if (next !== current) {
                iframe.style.height = next + "px";
            }
        };

        setHeight();
        requestAnimationFrame(setHeight);

        doc.querySelectorAll("img").forEach((img) => {
            if (!img.complete) {
                img.addEventListener("load", setHeight);
                img.addEventListener("error", setHeight);
            }
        });

        if (iframe.contentWindow?.ResizeObserver) {
            const ro = new iframe.contentWindow.ResizeObserver(setHeight);
            ro.observe(doc.body);
        }
        if (doc.fonts && doc.fonts.ready) {
            doc.fonts.ready.then(setHeight);
        }

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
        /* Run table me Race No click -> sahi Race Result URL kholo */
        doc.addEventListener(
            "click",
            (ev) => {
                const row = ev.target.closest("tr.perform_data");
                if (!row) return;

                const cells = row.querySelectorAll("td");
                const raceCell = cells[1];                   // 2nd column = Race No
                if (!raceCell) return;

                const clickedCell = ev.target.closest("td");
                if (clickedCell !== raceCell) return;        // sirf Race No pe click

                const raceNo = raceCell.textContent.trim();
                let raceDate = toIsoDate(cells[0].textContent);

                if (!raceDate) {
                    const a = row.querySelector("a[href]");
                    if (a) {
                        try {
                            const u = new URL(a.getAttribute("href"), "https://rwitc.com");
                            raceDate = toIsoDate(
                                u.searchParams.get("racedate") || u.searchParams.get("race_date") || ""
                            );
                        } catch (e) { }
                    }
                }

                const horseName = getHorseNameFromRow(row);

                ev.preventDefault();      // legacy link ka purana navigation band
                ev.stopPropagation();
                openRaceResult(raceNo, raceDate, horseName);
            },
            true   // capture: legacy link se pehle chalega
        );
    };

    const isHtmlMode = mode === "html";
    const hasNoHtml = isHtmlMode && !rawHtml.trim();
    const hasNoData = !isHtmlMode && races.length === 0;

    return (
        <section className="racecardPage docPage">

            <div className="aboutTitleWrap">
                <h1 className="aboutHeading">Race Card</h1>
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
                        if (downloadAvailable && downloadFile) {
                            window.open(downloadFile, "_blank", "noopener,noreferrer");
                        } else {
                            alert("No download file found for this date. Showing data below.");
                        }
                    }}
                >
                    Download Race Card
                </button>

                {/* Header block only applies to the structured (DB) view */}
                {!isHtmlMode && (
                    <div className="docHeader">
                        <p className="docClub">ROYAL WESTERN INDIA TURF CLUB.</p>
                        {dayLabel && <p className="docMeeting">{dayLabel}</p>}
                        {dayNarrative && <p className="docMeeting">{dayNarrative}</p>}
                        <h1 className="docWatermark">RACE CARD</h1>
                        <p className="docHint">Click on a horse to know its Performance Profile @ RWITC</p>
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

                {/* Archive dates: iframe fits the card, only the runs table scrolls */}
                {!loading && !error && isHtmlMode && !hasNoHtml && (
                    <div className="docArchiveScroll">
                        <iframe
                            className="docArchiveHtml"
                            srcDoc={formatArchiveHtml(ARCHIVE_STYLES_RACECARD, rawHtml)}
                            title="Race Card"
                            sandbox="allow-same-origin allow-scripts allow-top-navigation allow-forms"
                            scrolling="no"
                            onLoad={handleRaceCardIframeLoad}
                        />
                    </div>
                )}

                {/* DB-sourced dates: structured cards */}
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
                                    <span className="docForeignTag">&nbsp;&nbsp;Foreign Jockeys Eligible</span>
                                )}
                            </p>
                            {race.narrativeEntry && <p className="docRaceNarration">{race.narrativeEntry}</p>}
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

                                    {horse.distanceWon && <div className="docHorseRow">DW: {horse.distanceWon}</div>}

                                    <div className="docHorseRow docHorseOwnership">
                                        <span>
                                            {horse.ownership}
                                            {horse.sexEtc && <><br />{horse.sexEtc}</>}
                                        </span>
                                        <span className="docHorseTrainer">({horse.trainer})</span>
                                    </div>

                                    {horse.runsData && <div className="docHorseRow">{horse.runsData}</div>}
                                    {horse.colours && <div className="docHorseRow docHorseColours">{horse.colours}</div>}

                                    <button type="button" className="docViewRunsBtn" onClick={() => toggleRuns(runKey)}>
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
                                                            <td>
                                                                <button
                                                                    type="button"
                                                                    style={{ background: "none", border: "none", color: "#16a34a", fontWeight: 700, cursor: "pointer", textDecoration: "underline" }}
                                                                    onClick={() => openRaceResult(perf.raceNo, toIsoDate(perf.raceDate), horse.name)}
                                                                >
                                                                    {perf.raceNo}
                                                                </button>
                                                            </td>
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