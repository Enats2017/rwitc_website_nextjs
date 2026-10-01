"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { FaHorseHead } from "react-icons/fa";

import { getDeclarations } from "../../../services/declarationsService";
import { formatArchiveHtml, handleArchiveIframeLoad } from "../../../utils/archiveHtmlHelper";

import "./Declarations.css";

/*
 * Archive iframe styles + script.
 * Desktop: same look as Handicaps - one joined green race bar, grey rounded
 * header, no borders. Horse cell starts at the LEFT: "1  NAME" + breeding,
 * long names wrap to the next line.
 * Mobile: NO horizontal scroll, Trainer + Jockey on a grey second line.
 * Note rows under the table get a bordered box and a gap below.
 */
const ARCHIVE_STYLES_DECLARATIONS = `
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

.raceBarClean { border-radius: 8px !important; overflow: hidden; }

/* desktop: table wrapper (gap below each table, before the next race bar) */
.tableScroll { width: 100%; max-width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; margin-bottom: 18px; }
.tableScroll table { width: 100%; min-width: 720px; margin: 0 !important; }

/* other tables (pools etc.): smaller text */
table:not(.horseTable) td, table:not(.horseTable) td * { font-size: 12.5px !important; line-height: 1.4; }

/* ---------- Horse table: desktop grey header ---------- */
.horseTable, .horseTable.table-bordered { width: 100%; table-layout: auto; border-collapse: separate; border-spacing: 0; border: none !important; }
.horseTable th, .horseTable td, .horseTable.table-bordered th, .horseTable.table-bordered td { border: none !important; }
.horseTable th { background: #cdcdd2 !important; color: #000000 !important; font-size: 12.5px; font-weight: 700; padding: 10px 6px !important; text-align: center; }
.horseTable th:first-child { text-align: left; padding-left: 10px !important; border-top-left-radius: 8px; }
.horseTable th:last-child { border-top-right-radius: 8px; }
.horseTable th.colHorse { text-align: left !important; padding-left: 10px !important; }
.horseTable td { font-size: 12px; padding: 6px !important; background: #ffffff; vertical-align: top; }
.horseTable td.colTJ { text-align: left !important; }

/* ---------- HORSE CELL: always starts at the left, long names wrap ---------- */
.horseTable td.colHorse { text-align: left !important; padding-left: 10px !important; white-space: normal !important; font-weight: 700; line-height: 1.35; }
.horseTable td.colHorse * { text-align: left !important; float: none !important; }
.horseTable td.colHorse .hRow { display: flex !important; align-items: flex-start; justify-content: flex-start; gap: 8px; width: 100%; }
.horseTable td.colHorse .srNo { display: block !important; flex: 0 0 auto; min-width: 14px; }
.horseTable td.colHorse .hInfo { display: block !important; flex: 1 1 auto; min-width: 0; overflow-wrap: anywhere; white-space: normal !important; }
.horseTable td.colHorse .hName { display: block !important; }
.horseTable td.colHorse .hBreed { display: block !important; font-weight: 600; }

.horseTable tr.subRow { display: none; }

/* note row under the table (e.g. "Weights lowered by 1 kg.") */
.horseTable tr.noteRow td.noteCell { border: 1px solid #dee2e6 !important; background: #ffffff; text-align: center !important; font-size: 12px; font-weight: 700; padding: 8px 10px !important; white-space: normal; }
.horseTable tr.noteRow td.noteCell * { text-align: center !important; }

/* mobile: no horizontal scroll at all */
@media (max-width: 768px) {
    .tableScroll { overflow: visible !important; }
    .tableScroll table, .tableScroll table.horseTable { min-width: 0 !important; width: 100% !important; }

    .horseTable, .horseTable.table-bordered { table-layout: auto; width: 100%; border-collapse: separate; border-spacing: 0; border: none !important; }
    .horseTable th, .horseTable td, .horseTable.table-bordered th, .horseTable.table-bordered td { border: none !important; }

    .horseTable th { background: #cdcdd2 !important; color: #000000 !important; font-size: 12px; font-weight: 700; padding: 10px 3px !important; text-align: center; white-space: normal; line-height: 1.2; }
    .horseTable td { font-size: 12px; padding: 6px 3px !important; background: #ffffff; white-space: normal; overflow-wrap: anywhere; }

    .horseTable th.colHorse { text-align: left !important; padding-left: 10px !important; }

    .horseTable td.colHorse { text-align: left !important; padding-left: 10px !important; font-size: 13px; font-weight: 700; line-height: 1.35; }
    .horseTable td.colHorse .hRow { gap: 10px; }
    .horseTable td.colHorse .hBreed { font-size: 11px; }

    .horseTable th.firstVis { border-top-left-radius: 8px; }
    .horseTable th.lastVis { border-top-right-radius: 8px; }

    /* Trainer + Jockey leave the main row */
    .horseTable .colTJ { display: none !important; }

    /* extra non-horse rows from the archive HTML (e.g. "(A) (N. S. Parmar)") */
    .horseTable tr.extraRow { display: none !important; }

    /* note row stays visible on mobile */
    .horseTable tr.noteRow td.noteCell { font-size: 11px; padding: 6px 8px !important; }

    /* second line */
    .horseTable tr.subRow { display: table-row; }
    .horseTable tr.subRow td.subCell { background: #cdcdd2; font-size: 11px; font-weight: 700; padding: 5px 10px !important; white-space: normal; text-align: left !important; }
    .horseTable tr.subRow:last-child td.subCell { border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
    .horseTable .subFlex { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .horseTable .subFlex .subT { display: block; flex: 0 1 58%; max-width: 58%; text-align: left; line-height: 1.35; }
    .horseTable .subFlex .subJ { display: block; flex: 0 0 auto; text-align: right; line-height: 1.35; }
}
</style>

<script>
(function () {
    function raw(el) {
        return (el.textContent || "").replace(/\\s+/g, " ").trim();
    }

    function txt(el) {
        return raw(el).toLowerCase();
    }

    /* expand a row into logical columns so colspan can't shift the indexes */
    function logical(tr) {
        var arr = [];
        Array.prototype.slice.call(tr.children).forEach(function (c) {
            var n = c.colSpan || 1;
            for (var k = 0; k < n; k++) arr.push(c);
        });
        return arr;
    }

    function uniq(arr) {
        var u = [];
        arr.forEach(function (c) { if (u.indexOf(c) === -1) u.push(c); });
        return u;
    }

    /* a real horse row starts with "1." / "2." ... */
    function isHorseRow(arr) {
        for (var i = 0; i < arr.length && i < 2; i++) {
            if (/^\\d+\\s*\\./.test(raw(arr[i]))) return arr[i];
        }
        return null;
    }

    function forceLeft(el) {
        el.style.setProperty("text-align", "left", "important");
    }

    /* Rebuild the horse cell as: [number] | [name + breeding].
       Number has no dot. Everything is forced to the left. */
    function rebuildHorseCell(cell) {
        if (cell.querySelector(".hRow")) return;

        var parts = [];
        var walker = document.createTreeWalker(cell, NodeFilter.SHOW_TEXT, null);
        var node;

        while ((node = walker.nextNode())) {
            var t = node.nodeValue.replace(/\\s+/g, " ").trim();
            if (t) parts.push(t);
        }

        if (!parts.length) return;

        var m = parts[0].match(/^(\\d+)\\s*\\.?\\s*(.*)$/);
        if (!m) return;

        var num = m[1];
        var name = m[2];
        var rest = parts.slice(1);

        if (!name && rest.length) name = rest.shift();

        var breed = rest.join(" ");

        cell.removeAttribute("align");
        cell.innerHTML = "";

        var row = document.createElement("div");
        row.className = "hRow";

        var sr = document.createElement("span");
        sr.className = "srNo";
        sr.textContent = num;
        row.appendChild(sr);

        var info = document.createElement("div");
        info.className = "hInfo";

        var nm = document.createElement("div");
        nm.className = "hName";
        nm.textContent = name;
        info.appendChild(nm);

        if (breed) {
            var br = document.createElement("div");
            br.className = "hBreed";
            br.textContent = breed;
            info.appendChild(br);
        }

        row.appendChild(info);
        cell.appendChild(row);

        forceLeft(cell);
        Array.prototype.slice.call(cell.querySelectorAll("*")).forEach(forceLeft);
    }

    function transformTables() {
        document.querySelectorAll("table").forEach(function (table) {
            if (table.classList.contains("horseTable")) return;
            if (table.querySelector("table")) return;

            var rows = Array.prototype.slice.call(table.querySelectorAll("tr"));
            var headRow = null, iT = -1, iJ = -1, iH = -1, headLen = 0;

            for (var r = 0; r < rows.length; r++) {
                var arr = logical(rows[r]);
                var t = -1, j = -1, h = -1;

                arr.forEach(function (c, i) {
                    if (i > 0 && arr[i - 1] === c) return;
                    var s = txt(c);
                    if (s.indexOf("trainer") === 0) t = i;
                    else if (s.indexOf("jockey") === 0) j = i;
                    else if (s.indexOf("horse") === 0 && h === -1 && s.indexOf("wt") === -1 && s.indexOf("weight") === -1) h = i;
                });

                if (t > -1 && j > -1) { headRow = rows[r]; iT = t; iJ = j; iH = h; headLen = arr.length; break; }
            }

            if (!headRow) return;

            table.classList.add("horseTable");

            /* columns after Jockey in the header (Horse Wt, Shoe, Draw ...) */
            var tail = headLen - 1 - iJ;
            var headerSeen = false;

            rows.forEach(function (tr) {
                var arr = logical(tr);

                /* ---- header row ---- */
                if (tr === headRow) {
                    headerSeen = true;
                    arr[iT].classList.add("colTJ");
                    arr[iJ].classList.add("colTJ");
                    if (iH > -1) {
                        arr[iH].classList.add("colHorse");
                        forceLeft(arr[iH]);
                    }

                    var vis = uniq(arr).filter(function (c) { return !c.classList.contains("colTJ"); });
                    if (vis.length) {
                        vis[0].classList.add("firstVis");
                        vis[vis.length - 1].classList.add("lastVis");
                    }
                    return;
                }

                if (!headerSeen) return;
                if (tr.classList.contains("subRow")) return;
                if (tr.querySelectorAll(":scope > td").length === 0) return;

                /* ---- not a horse row ---- */
                var horseCell = isHorseRow(arr);
                if (!horseCell) {
                    var cellsHere = Array.prototype.slice.call(tr.children);
                    var noteText = raw(tr);

                    /* note row: one single cell with text, e.g. "Weights lowered by 1 kg." */
                    if (cellsHere.length === 1 && noteText !== "" && noteText.charAt(0) !== "(") {
                        tr.classList.add("noteRow");
                        cellsHere[0].classList.add("noteCell");
                        cellsHere[0].colSpan = Math.max(headLen, 1);
                    } else {
                        /* other extra rows (e.g. "(A) (N. S. Parmar)"): hidden on mobile */
                        tr.classList.add("extraRow");
                    }
                    return;
                }

                /* ALWAYS fix the horse cell first, before any check that can return */
                horseCell.classList.add("colHorse");
                rebuildHorseCell(horseCell);

                if (arr.length < tail + 3) return;

                /* anchor from the RIGHT: Jockey, then Trainer directly before it */
                var jIdx = arr.length - 1 - tail;
                var tIdx = jIdx - 1;

                var trainer = arr[tIdx];
                var jockey = arr[jIdx];
                if (!trainer || !jockey || trainer === jockey) return;
                if (trainer === horseCell || jockey === horseCell) return;

                trainer.classList.add("colTJ");
                jockey.classList.add("colTJ");

                var sub = document.createElement("tr");
                sub.className = "subRow";

                var td = document.createElement("td");
                td.className = "subCell";
                td.colSpan = Math.max(uniq(arr).length - 2, 1);
                td.innerHTML =
                    '<div class="subFlex"><span class="subT">' + trainer.innerHTML +
                    '</span><span class="subJ">' + jockey.innerHTML + '</span></div>';

                sub.appendChild(td);
                tr.parentNode.insertBefore(sub, tr.nextSibling);
            });
        });
    }

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

    /* Race bar split in two blocks (number + title): join them into ONE bar. */
    function joinGreenBars() {
        var bars = Array.prototype.slice.call(document.querySelectorAll(".raceBarClean"));
        var groups = [];

        bars.forEach(function (el) {
            var p = el.parentElement;
            if (!p) return;

            var g = null;
            for (var i = 0; i < groups.length; i++) {
                if (groups[i].parent === p) { g = groups[i]; break; }
            }
            if (!g) {
                g = { parent: p, items: [] };
                groups.push(g);
            }
            g.items.push(el);
        });

        groups.forEach(function (g) {
            if (g.items.length < 2) return;

            var last = g.items.length - 1;

            g.items.forEach(function (el, i) {
                var left = i === 0 ? "8px" : "0";
                var right = i === last ? "8px" : "0";

                el.style.setProperty(
                    "border-radius",
                    left + " " + right + " " + right + " " + left,
                    "important"
                );
            });
        });
    }

    function run() {
        transformTables();
        wrapTables();
        cleanGreenBars();
        joinGreenBars();
    }

    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", run);
    else run();
})();
</script>
`;

export default function Declarations() {
    const searchParams = useSearchParams();

    const date = searchParams.get("date");
    const type = searchParams.get("type") || "declarations";
    const raceType = searchParams.get("race_type") || "pre_race";

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
        let cancelled = false;

        async function loadDeclarations() {
            if (!date) {
                setError("No date selected.");
                setLoading(false);
                return;
            }

            try {
                setLoading(true);
                setError(null);

                const data = await getDeclarations(date, type, raceType);

                if (cancelled) return;

                setMode(data.mode || "json");
                setRawHtml(data.html || "");
                setDayNarrative(data.dayNarrative || "");
                setRaces(data.races || []);
                setPools(data.pools || []);
                setDownloadFile(data.downloadFile || null);
                setDownloadAvailable(!!data.downloadAvailable);
            } catch (err) {
                console.error("Declarations Error:", err);
                if (!cancelled) setError("Unable to load declarations for this date.");
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        loadDeclarations();

        return () => {
            cancelled = true;
        };
    }, [date, type, raceType]);

    const isHtmlMode = mode === "html";
    const hasNoData = !isHtmlMode && races.length === 0;
    const hasNoHtml = isHtmlMode && !rawHtml.trim();

    /* keep iframe height in sync after tables get wrapped / resized */
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
        <section className="declarationsPage">

            {/* PAGE HEADER */}
            <div className="docTitleWrap">
                <h1 className="docHeading">Declarations</h1>

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
                    Download Declarations
                </button>

                {/* HEADER FOR JSON / DB MODE */}
                {!isHtmlMode && (
                    <div className="docHeader">
                        <p className="docClub">ROYAL WESTERN INDIA TURF CLUB.</p>

                        {dayNarrative && <p className="docMeeting">{dayNarrative}</p>}

                        <h1 className="docWatermark">DECLARATIONS</h1>

                        <p className="docHint">Check the final field for today&apos;s races</p>
                    </div>
                )}

                {/* LOADING */}
                {loading && (
                    <div className="docStateBox">
                        <div className="docLoader" />
                        <p>Loading declarations…</p>
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
                        <p>No declarations found for this date.</p>
                    </div>
                )}

                {/* ARCHIVE HTML MODE */}
                {!loading && !error && isHtmlMode && !hasNoHtml && (
                    <div className="docArchiveScroll">
                        <iframe
                            className="docArchiveHtml"
                            srcDoc={formatArchiveHtml(ARCHIVE_STYLES_DECLARATIONS, rawHtml)}
                            title="Declarations"
                            sandbox="allow-same-origin allow-scripts allow-top-navigation allow-forms"
                            onLoad={onIframeLoad}
                        />
                    </div>
                )}

                {/* DB / JSON MODE */}
                {!loading && !error && !isHtmlMode && !hasNoData && races.map((race, idx) => (
                    <div className="docRaceBlock" key={idx}>

                        {/* RACE HEADER */}
                        <div className="docRaceBar">
                            <p className="docRaceName">
                                {race.race_no ?? idx + 1}.&nbsp;
                                {race.race_name}
                                {race.division && <>&nbsp;({race.division})</>}
                            </p>

                            <p className="docRaceMeta">
                                {race.distance && <>(About) {race.distance} Metres.</>}
                                {race.race_time && <>&nbsp;&nbsp;Time: {race.race_time}</>}
                                {race.foreign_jockeys_eligible && (
                                    <span className="docForeignTag">&nbsp;&nbsp;Foreign Jockeys Eligible</span>
                                )}
                            </p>

                            {race.narrative && <p className="docRaceNarration">{race.narrative}</p>}
                        </div>

                        {/* WEIGHT NOTES */}
                        {race.weight_notes && race.weight_notes.length > 0 && (
                            <div className="docWeightNotes">
                                {race.weight_notes.map((note, nIdx) => (
                                    <p key={nIdx}>{note}</p>
                                ))}
                            </div>
                        )}

                        {/* TABLE */}
                        <div className="docTableWrap">
                            <table className="docTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Horse</th>
                                        <th>Wt</th>
                                        <th>Rating</th>
                                        <th>Trainer</th>
                                        <th>Jockey</th>
                                        <th>Horse Wt</th>
                                        <th>Shoe</th>
                                        <th>Draw</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {race.horses && race.horses.map((horse, hIdx) => (
                                        <tr key={`${idx}-${hIdx}`}>
                                            <td>{horse.card_no}</td>

                                            <td className="docHorseName">
                                                {horse.horse?.name}
                                                {horse.horse?.sire && (
                                                    <span className="docBreeding">
                                                        {horse.horse.sire}
                                                        {horse.horse?.dam ? `-${horse.horse.dam}` : ""}
                                                        {horse.horse?.dam_nationality ? ` (${horse.horse.dam_nationality})` : ""}
                                                    </span>
                                                )}
                                            </td>

                                            <td>{horse.weight ?? "-"}</td>
                                            <td>{horse.rating ?? "NR"}</td>
                                            <td>{horse.trainer?.name}</td>

                                            <td>
                                                {horse.jockey?.name}
                                                {horse.jockey?.allowance && (
                                                    <span className="docAllowance"> (-{horse.jockey.allowance})</span>
                                                )}
                                            </td>

                                            <td>{horse.horse_weight ?? "-"}</td>
                                            <td>{horse.shoe ?? "-"}</td>
                                            <td>{horse.draw ?? "-"}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ))}

                {/* POOLS */}
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