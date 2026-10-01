"use client";

import { Fragment, useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { FaHorseHead } from "react-icons/fa";

import { getHandicaps } from "../../../services/handicapsService";
import {
    formatArchiveHtml,
    handleArchiveIframeLoad,
} from "../../../utils/archiveHtmlHelper";

import "./Handicaps.css";

/*
 * Styles + script injected INSIDE the archive iframe.
 * Same as Acceptance: grey header on desktop + mobile, rounded table,
 * no vertical lines. On mobile, Breeding + Trainer move to a highlighted
 * second line per horse. Note rows under the table (e.g. "Weights lowered
 * by 1 kg.") get a bordered, centered box.
 */
const ARCHIVE_STYLES = `
<style>
* { box-sizing: border-box; }
span, a { display: inline-block; text-decoration: none; color: #333333; }
html, body { margin: 0; padding: 0; max-width: 100%; overflow-x: hidden; }
body { font-family: Arial, sans-serif; word-wrap: break-word; display: flow-root; }
img { max-width: 100%; height: auto; }
h1 { margin: unset !important; font-size: 26px !important; }
h3 { font-family: 'Roboto Condensed', Arial, sans-serif; font-size: 32px; color: #c1c1c1; margin: 10px 0; }
th { color: #ffffff !important; font-size: 14px; text-align: center; padding: 4px 2px; border: 1px solid #BCBEC0; background: #11a14e; }
td { text-align: left !important; padding: 4px !important; color: #333333 !important; font-weight: 600; }
tbody > tr > th { text-align: left !important; }
tbody tr td:nth-child(2) { text-align: center !important; }
tbody tr td:nth-child(3) { text-align: center !important; }
table { border-collapse: collapse; }
.table { width: 100%; max-width: 100%; margin-bottom: 1rem; background-color: transparent; }
.table th, .table td { padding: 8px; vertical-align: top; border-top: 1px solid #dee2e6; }
.table-bordered { border: 1px solid #dee2e6; font-weight: bold; width: 100%; border-collapse: collapse; }
.table-bordered th, .table-bordered td { border: 1px solid #dee2e6; }
.table-bordered thead th, .table-bordered thead td { border-bottom-width: 2px; }
.download, .pageHeader .pageHeading .subHeading .download { display: none !important; }
.pageHeading { text-align: center; }
.hclass { text-align: center; display: inline-block; background-color: #11a14e; border-radius: 33px; padding: 11px; vertical-align: middle !important; color: white; margin-top: 25px; }
#leftArea .pageHeader .pageHeading .subHeading { clear: both; float: left; width: 100%; color: #000; font-weight: bold; text-align: center; font-size: 12px; margin: 5px 0; padding: 5px 0; }
.darkGrey_old { font-size: 14px; color: black; text-align: center; font-weight: bold; }
.darkGrey { font-size: 14px; color: white; text-align: center; font-weight: bold; }
.white { background-color: #ffffff; color: black !important; }
.block { display: none; }
.hide { display: block !important; }
.tbbody { margin: 0 0 16px !important; }
.padd { padding: 1%; }
.left, .left1 { text-align: left !important; }
.show1 { display: none; }

#leftArea, .pageHeader, .pageHeading, .subHeading, h1, h3, p, div { max-width: 100%; white-space: normal; overflow-wrap: anywhere; }

/* tables that are not horse tables: scroll inside the card */
.tableScroll { width: 100%; max-width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; }
.tableScroll table { width: 100%; min-width: 640px; margin: 0 !important; }

/* other tables: smaller text */
table:not(.horseTable) td, table:not(.horseTable) td * { font-size: 12.5px !important; line-height: 1.4; }

.raceBarClean { border-radius: 8px !important; overflow: hidden; }

/* ---------- Horse table: desktop + mobile grey header ---------- */
.horseTable, .horseTable.table-bordered { width: 100%; table-layout: auto; border-collapse: separate; border-spacing: 0; border: none !important; }
.horseTable th, .horseTable td, .horseTable.table-bordered th, .horseTable.table-bordered td { border: none !important; }
.horseTable th, .horseTable thead th, .horseTable tr:first-child th { background: #cdcdd2 !important; color: #000000 !important; font-size: 12.5px; font-weight: 700; padding: 10px 6px !important; text-align: center; }
.horseTable th:first-child { text-align: left; padding-left: 10px !important; border-top-left-radius: 8px; }
.horseTable th:last-child { border-top-right-radius: 8px; }
.horseTable td { font-size: 12px; padding: 6px !important; background: #ffffff; }
.horseTable tr.subRow { display: none; }

/* note row under the table (e.g. "Weights lowered by 1 kg.") */
.horseTable tr.noteRow td.noteCell { border: 1px solid #dee2e6 !important; background: #ffffff; text-align: center !important; font-size: 12px; font-weight: 700; padding: 8px 10px !important; white-space: normal; }
.horseTable tr.noteRow td.noteCell * { text-align: center !important; }

@media (max-width: 768px) {
    .horseTable th, .horseTable thead th, .horseTable tr:first-child th { font-size: 11.5px; padding: 10px 5px !important; }
    .horseTable th:last-child { border-top-right-radius: 0; }
    .horseTable th.lastVis { border-top-right-radius: 8px; }
    .horseTable td { font-size: 11px; padding: 5px !important; }
    .horseTable td:first-child { padding-left: 4px !important; }
    .horseTable th.colNarrow, .horseTable td.colNarrow { width: 1%; white-space: nowrap; text-align: center !important; padding-left: 6px !important; padding-right: 6px !important; }
    .horseTable th.colHorse, .horseTable td.colHorse { width: auto; white-space: normal; text-align: left !important; }
    .horseTable td.colHorse br { display: none !important; }
    .horseTable td.colHorse * { display: inline !important; margin: 0 !important; padding: 0 !important; float: none !important; width: auto !important; font-size: 11px !important; }
    .horseTable .colBT { display: none !important; }
    .horseTable tr.subRow { display: table-row; }
    .horseTable tr.subRow td.subCell { background: #cdcdd2; font-size: 10px; font-weight: 700; padding: 4px 10px !important; white-space: normal; width: auto; text-align: left !important; }
    .horseTable tr.subRow:last-child td.subCell { border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
    .horseTable .subFlex { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .horseTable .subFlex .subB { display: block; flex: 0 1 58%; max-width: 58%; text-align: left; line-height: 1.35; }
    .horseTable .subFlex .subT { display: block; flex: 0 0 auto; text-align: right; line-height: 1.35; }
    .horseTable tr.noteRow td.noteCell { font-size: 11px; padding: 6px 8px !important; width: auto; }
}
</style>

<script>
(function () {
    function txt(el) {
        return ((el && (el.innerText || el.textContent)) || "").trim();
    }

    /* Sr.no + horse name on the same line */
    function inlineHorseCell(cell) {
        if (!cell) return;

        Array.prototype.slice.call(cell.querySelectorAll("br")).forEach(function (br) {
            br.parentNode.replaceChild(document.createTextNode(" "), br);
        });

        Array.prototype.slice.call(cell.children).forEach(function (ch) {
            if (ch.previousSibling) {
                ch.parentNode.insertBefore(document.createTextNode(" "), ch);
            }
        });
    }

    function transformTables() {
        document.querySelectorAll("table").forEach(function (table) {
            if (table.classList.contains("horseTable")) return;
            if (table.querySelector("table")) return;

            var text = table.innerText || table.textContent || "";
            if (!/horse/i.test(text) || !/trainer/i.test(text)) return;

            var rows = Array.prototype.slice.call(table.querySelectorAll("tr"));

            var headerRow = null;
            for (var i = 0; i < rows.length; i++) {
                var ths = rows[i].querySelectorAll("th");
                if (ths.length >= 4 && /trainer/i.test(rows[i].innerText || rows[i].textContent || "")) {
                    headerRow = rows[i];
                    break;
                }
            }
            if (!headerRow) return;

            var headCells = Array.prototype.slice.call(headerRow.children);
            var n = headCells.length;

            var horseIdx = -1, breedingIdx = -1, trainerIdx = -1;
            headCells.forEach(function (c, idx) {
                var t = txt(c);
                if (horseIdx === -1 && /horse/i.test(t)) horseIdx = idx;
                if (breedingIdx === -1 && /breeding/i.test(t)) breedingIdx = idx;
                if (trainerIdx === -1 && /trainer/i.test(t)) trainerIdx = idx;
            });

            if (trainerIdx === -1) return;
            if (horseIdx === -1) horseIdx = 0;

            var hidden = [trainerIdx];
            if (breedingIdx !== -1) hidden.push(breedingIdx);

            var lastVisible = -1;
            for (var k = n - 1; k >= 0; k--) {
                if (hidden.indexOf(k) === -1) { lastVisible = k; break; }
            }

            table.classList.add("horseTable");

            var visibleCount = n - hidden.length;

            rows.forEach(function (tr) {
                var cells = Array.prototype.slice.call(tr.children);

                /* note row: fewer cells than the header (usually one colspan cell) */
                if (cells.length !== n) {
                    if (
                        cells.length > 0 &&
                        cells.length < n &&
                        !tr.querySelector("th") &&
                        txt(tr) !== ""
                    ) {
                        tr.classList.add("noteRow");
                        cells.forEach(function (c) {
                            c.classList.add("noteCell");
                            if (cells.length === 1) c.colSpan = n;
                        });
                    }
                    return;
                }

                var isHeader = tr.querySelector("th") !== null;

                cells.forEach(function (c, idx) {
                    if (hidden.indexOf(idx) !== -1) {
                        c.classList.add("colBT");
                    } else if (idx === horseIdx) {
                        c.classList.add("colHorse");
                    } else {
                        c.classList.add("colNarrow");
                    }
                });

                if (isHeader) {
                    if (cells[lastVisible]) cells[lastVisible].classList.add("lastVis");
                    return;
                }

                inlineHorseCell(cells[horseIdx]);

                var breeding = breedingIdx !== -1 ? cells[breedingIdx] : null;
                var trainer = cells[trainerIdx];

                var sub = document.createElement("tr");
                sub.className = "subRow";

                var td = document.createElement("td");
                td.className = "subCell";
                td.colSpan = visibleCount;
                td.innerHTML =
                    '<div class="subFlex"><span class="subB">' + (breeding ? breeding.innerHTML : "") +
                    '</span><span class="subT">' + trainer.innerHTML + '</span></div>';

                sub.appendChild(td);
                tr.parentNode.insertBefore(sub, tr.nextSibling);
            });
        });
    }

    function wrapTables() {
        document.querySelectorAll("table").forEach(function (table) {
            if (table.classList.contains("horseTable")) return;
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

    /* Green race bar: remove any border / outline / shadow on it and its parents
       so nothing shows behind the rounded corners. */
    function cleanGreenBars() {
        document.body.querySelectorAll("*").forEach(function (el) {
            if (el.closest(".horseTable")) return;

            var bg = window.getComputedStyle(el).backgroundColor || "";
            var m = bg.match(/\\d+/g);
            if (!m || m.length < 3) return;
            if (m.length > 3 && parseFloat(bg.split(",")[3]) === 0) return;

            var r = +m[0], g = +m[1], b = +m[2];
            if (!(g > r + 40 && g > b + 40)) return;

            el.classList.add("raceBarClean");

            var n = el;
            while (n && n !== document.body) {
                n.style.setProperty("border", "none", "important");
                n.style.setProperty("outline", "none", "important");
                n.style.setProperty("box-shadow", "none", "important");

                if (n.tagName === "TABLE") {
                    n.style.setProperty("border-collapse", "separate", "important");
                    n.style.setProperty("border-spacing", "0", "important");
                }

                n = n.parentElement;
            }
        });
    }

    function run() {
        transformTables();
        wrapTables();
        cleanGreenBars();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", run);
    } else {
        run();
    }
})();
</script>
`;

export default function Handicaps() {
    const searchParams = useSearchParams();

    const date = searchParams.get("date");
    const type = searchParams.get("type") || "";
    const raceType = searchParams.get("race_type") || "";

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const [mode, setMode] = useState("json");
    const [rawHtml, setRawHtml] = useState("");

    const [meeting, setMeeting] = useState(null);
    const [races, setRaces] = useState([]);

    const [downloadFile, setDownloadFile] = useState(null);
    const [downloadAvailable, setDownloadAvailable] = useState(false);

    useEffect(() => {
        let cancelled = false;

        async function loadHandicaps() {
            if (!date) {
                setError("No date selected.");
                setLoading(false);
                return;
            }

            try {
                setLoading(true);
                setError(null);

                const data = await getHandicaps(date, type, raceType);

                if (cancelled) return;

                setMode(data.mode || "json");
                setRawHtml(data.html || "");
                setMeeting(data.meeting || null);
                setRaces(data.races || []);
                setDownloadFile(data.downloadFile || null);
                setDownloadAvailable(!!data.downloadAvailable);
            } catch (err) {
                console.error("Handicaps Error:", err);
                if (!cancelled) setError("Unable to load handicaps for this date.");
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        loadHandicaps();

        return () => {
            cancelled = true;
        };
    }, [date, type, raceType]);

    const isHtmlMode = mode === "html";
    const hasNoData = !isHtmlMode && races.length === 0;
    const hasNoHtml = isHtmlMode && !rawHtml.trim();

    /*
     * Iframe load: run the existing helper, then keep the iframe height
     * in sync when the content re-flows.
     */
    function onIframeLoad(e) {
        handleArchiveIframeLoad(e);

        const iframe = e.currentTarget;

        try {
            const doc = iframe.contentDocument;
            if (!doc || typeof ResizeObserver === "undefined") return;

            const resize = () => {
                const h = doc.body.offsetHeight;
                if (Math.abs(iframe.offsetHeight - h) > 1) iframe.style.height = h + "px";
            };

            resize();

            new ResizeObserver(resize).observe(doc.body);
        } catch (err) {
            /* helper height is enough */
        }
    }

    return (
        <section className="handicapsPage">
            {/* PAGE HEADER */}
            <div className="docTitleWrap">
                <h1 className="docHeading">Handicaps</h1>

                <div className="docSectionDivider">
                    <span className="docDividerLine docDividerLineLeft" />
                    <FaHorseHead className="docDividerIcon" />
                    <span className="docDividerLine docDividerLineRight" />
                </div>
            </div>

            {/* MAIN DOCUMENT CONTAINER */}
            <div className="docContainer">
                {/* DOWNLOAD BUTTON */}
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
                    Download Handicaps
                </button>

                {/* HEADER FOR JSON / DB MODE */}
                {!isHtmlMode && (
                    <div className="docHeader">
                        <p className="docClub">ROYAL WESTERN INDIA TURF CLUB.</p>

                        {meeting && <p className="docMeeting">{meeting}</p>}

                        <h1 className="docWatermark">HANDICAPS</h1>

                        <p className="docHint">
                            Click on a horse to know its Performance Profile @ RWITC
                        </p>
                    </div>
                )}

                {/* LOADING */}
                {loading && (
                    <div className="docStateBox">
                        <div className="docLoader" />
                        <p>Loading handicaps…</p>
                    </div>
                )}

                {/* ERROR */}
                {!loading && error && (
                    <div className="docStateBox docStateError">
                        <p>{error}</p>
                    </div>
                )}

                {/* EMPTY DATA */}
                {!loading && !error && (hasNoData || hasNoHtml) && (
                    <div className="docStateBox">
                        <p>No handicaps data found for this date.</p>
                    </div>
                )}

                {/* ARCHIVE HTML MODE */}
                {!loading && !error && isHtmlMode && !hasNoHtml && (
                    <div className="docArchiveScroll">
                        <iframe
                            className="docArchiveHtml"
                            srcDoc={formatArchiveHtml(ARCHIVE_STYLES, rawHtml)}
                            title="Handicaps"
                            sandbox="allow-same-origin allow-scripts allow-top-navigation allow-forms"
                            onLoad={onIframeLoad}
                        />
                    </div>
                )}

                {/* DB / JSON MODE */}
                {!loading &&
                    !error &&
                    !isHtmlMode &&
                    !hasNoData &&
                    races.map((race, idx) => (
                        <div className="docRaceBlock" key={race.srno || idx}>
                            {/* RACE HEADER */}
                            <div className="docRaceBar">
                                <p className="docRaceName">
                                    {idx + 1}.&nbsp;
                                    {race.race_name}
                                    {race.grade && <>&nbsp;({race.grade})</>}
                                </p>

                                <p className="docRaceMeta">
                                    {race.distance && <>(About) {race.distance} Metres.</>}

                                    {race.foreign_jockeys_eligible && (
                                        <span className="docForeignTag">
                                            &nbsp;&nbsp;Foreign Jockeys Eligible
                                        </span>
                                    )}
                                </p>
                            </div>

                            {/* WEIGHT NOTE */}
                            {race.weight_note && (
                                <p className="docWeightNote">{race.weight_note}</p>
                            )}

                            {/* TABLE */}
                            <div className="docTableWrap">
                                <table className="docTable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Horse Name</th>
                                            <th>Color/Sex</th>
                                            <th>Age</th>
                                            <th>Weight</th>
                                            <th>Rating</th>
                                            <th className="docColBT">Breeding</th>
                                            <th className="docColBT">Trainer</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        {race.horses &&
                                            race.horses.map((horse, hIdx) => (
                                                <Fragment key={horse.horseseq || hIdx}>
                                                    <tr>
                                                        <td>{horse.order}</td>

                                                        <td className="docHorseName">{horse.name}</td>

                                                        <td>
                                                            {horse.color}/{horse.sex}
                                                        </td>

                                                        <td>{horse.age ?? "-"}</td>

                                                        <td>{horse.weight ?? "-"}</td>

                                                        <td>{horse.rating ?? "NR"}</td>

                                                        <td className="docColBT">{horse.breeding}</td>

                                                        <td className="docColBT">{horse.trainer}</td>
                                                    </tr>

                                                    <tr className="docSubRow">
                                                        <td className="docSubCell" colSpan={6}>
                                                            <div className="docSubFlex">
                                                                <span className="docSubBreeding">
                                                                    {horse.breeding}
                                                                </span>

                                                                <span className="docSubTrainer">
                                                                    {horse.trainer}
                                                                </span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </Fragment>
                                            ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* BAN NOTES */}
                            {(race.ss_ban?.length > 0 ||
                                race.vo_ban?.length > 0 ||
                                race.mk_ban?.length > 0) && (
                                <div className="docBanNotes">
                                    {race.ss_ban?.length > 0 && (
                                        <p>SS Ban : {race.ss_ban.join(", ")}</p>
                                    )}

                                    {race.vo_ban?.length > 0 && (
                                        <p>Vet Ban : {race.vo_ban.join(", ")}</p>
                                    )}

                                    {race.mk_ban?.length > 0 && (
                                        <p>MK Ban : {race.mk_ban.join(", ")}</p>
                                    )}
                                </div>
                            )}
                        </div>
                    ))}
            </div>
        </section>
    );
}