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
 * Mobile: grey header, rounded table, no vertical lines,
 * Breeding + Trainer on a highlighted second line per horse.
 * Green race bar: no visible border behind rounded corners.
 * Desktop: normal table.
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
.download { display: none !important; }
.pageHeading { text-align: center; }
.hclass { text-align: center; display: inline-block; background-color: #11a14e; border-radius: 33px; padding: 11px; vertical-align: middle !important; color: white; margin-top: 25px; }
#leftArea .pageHeader .pageHeading .subHeading { clear: both; float: left; width: 100%; color: #000; font-weight: bold; text-align: center; font-size: 12px; margin: 5px 0; padding: 5px 0; }
.show1 { display: none; }

#leftArea, .pageHeader, .pageHeading, .subHeading, h1, h3, p, div { max-width: 100%; white-space: normal; overflow-wrap: anywhere; }

.raceBarClean { border-radius: 8px !important; overflow: hidden; }

.horseTable { width: 100%; table-layout: auto; }
.horseTable tr.subRow { display: none; }

@media (max-width: 768px) {
    .horseTable, .horseTable.table-bordered { table-layout: auto; width: 100%; border-collapse: separate; border-spacing: 0; border: none !important; }
    .horseTable th, .horseTable td, .horseTable.table-bordered th, .horseTable.table-bordered td { border: none !important; }
    .horseTable th { background: #cdcdd2 !important; color: #000000 !important; font-size: 13px; font-weight: 700; padding: 12px 6px !important; text-align: center; }
    .horseTable th:first-child { text-align: left; padding-left: 10px !important; border-top-left-radius: 8px; }
    .horseTable th:nth-child(5) { border-top-right-radius: 8px; }
    .horseTable td { font-size: 12.5px; padding: 6px !important; background: #ffffff; }
    .horseTable td:first-child { padding-left: 4px !important; }
    .horseTable th:nth-child(n+2), .horseTable td:nth-child(n+2) { width: 1%; white-space: nowrap; text-align: center !important; padding-left: 8px !important; padding-right: 8px !important; }
    .horseTable th:nth-child(1), .horseTable td:nth-child(1) { width: auto; white-space: normal; }
    .horseTable .colBT { display: none !important; }
    .horseTable tr.subRow { display: table-row; }
    .horseTable tr.subRow td.subCell { background: #cdcdd2; font-size: 11px; font-weight: 700; padding: 5px 10px !important; white-space: normal; width: auto; text-align: left !important; }
    .horseTable tr.subRow:last-child td.subCell { border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }
    .horseTable .subFlex { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; }
    .horseTable .subFlex .subB { display: block; flex: 0 1 58%; max-width: 58%; text-align: left; line-height: 1.35; }
    .horseTable .subFlex .subT { display: block; flex: 0 0 auto; text-align: right; line-height: 1.35; }
}
</style>

<script>
(function () {
    function transformTables() {
        document.querySelectorAll("table").forEach(function (table) {
            if (table.classList.contains("horseTable")) return;

            var text = table.innerText || table.textContent || "";
            if (text.indexOf("Horse Name") === -1) return;

            table.classList.add("horseTable");

            Array.prototype.slice.call(table.querySelectorAll("tr")).forEach(function (tr) {
                var cells = Array.prototype.slice.call(tr.children);
                if (cells.length < 7) return;

                var breeding = cells[5];
                var trainer = cells[6];
                var isHeader = tr.querySelector("th") !== null;

                breeding.classList.add("colBT");
                trainer.classList.add("colBT");

                if (isHeader) return;

                var sub = document.createElement("tr");
                sub.className = "subRow";

                var td = document.createElement("td");
                td.className = "subCell";
                td.colSpan = 5;
                td.innerHTML =
                    '<div class="subFlex"><span class="subB">' + breeding.innerHTML +
                    '</span><span class="subT">' + trainer.innerHTML + '</span></div>';

                sub.appendChild(td);
                tr.parentNode.insertBefore(sub, tr.nextSibling);
            });
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
                setMeeting(data.meeting);
                setRaces(data.races || []);
                setDownloadFile(data.downloadFile);
                setDownloadAvailable(data.downloadAvailable);
            } catch (err) {
                console.error("Handicaps Error:", err);

                if (!cancelled) {
                    setError("Unable to load handicaps for this date.");
                }
            } finally {
                if (!cancelled) {
                    setLoading(false);
                }
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
            // cross-origin or unavailable: the helper's height is enough
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
                            window.open(
                                downloadFile,
                                "_blank",
                                "noopener,noreferrer"
                            );
                        } else {
                            alert(
                                "No download file found for this date. Showing data below."
                            );
                        }
                    }}
                >
                    Download Handicaps
                </button>

                {/* HEADER FOR JSON / DB MODE */}
                {!isHtmlMode && (
                    <div className="docHeader">
                        <p className="docClub">
                            ROYAL WESTERN INDIA TURF CLUB.
                        </p>

                        {meeting && (
                            <p className="docMeeting">{meeting}</p>
                        )}

                        <h1 className="docWatermark">HANDICAPS</h1>

                        <p className="docHint">
                            Click on a horse to know its Performance Profile
                            @ RWITC
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
                            srcDoc={formatArchiveHtml(
                                ARCHIVE_STYLES,
                                rawHtml
                            )}
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
                        <div
                            className="docRaceBlock"
                            key={race.srno || idx}
                        >
                            {/* RACE HEADER */}
                            <div className="docRaceBar">
                                <p className="docRaceName">
                                    {idx + 1}.&nbsp;
                                    {race.race_name}
                                    {race.grade && (
                                        <>&nbsp;({race.grade})</>
                                    )}
                                </p>

                                <p className="docRaceMeta">
                                    {race.distance && (
                                        <>
                                            (About) {race.distance} Metres.
                                        </>
                                    )}

                                    {race.foreign_jockeys_eligible && (
                                        <span className="docForeignTag">
                                            &nbsp;&nbsp;Foreign Jockeys
                                            Eligible
                                        </span>
                                    )}
                                </p>
                            </div>

                            {/* WEIGHT NOTE */}
                            {race.weight_note && (
                                <p className="docWeightNote">
                                    {race.weight_note}
                                </p>
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
                                                <Fragment
                                                    key={
                                                        horse.horseseq || hIdx
                                                    }
                                                >
                                                    <tr>
                                                        <td>{horse.order}</td>

                                                        <td className="docHorseName">
                                                            {horse.name}
                                                        </td>

                                                        <td>
                                                            {horse.color}/
                                                            {horse.sex}
                                                        </td>

                                                        <td>{horse.age ?? "-"}</td>

                                                        <td>
                                                            {horse.weight ?? "-"}
                                                        </td>

                                                        <td>
                                                            {horse.rating ?? "NR"}
                                                        </td>

                                                        <td className="docColBT">
                                                            {horse.breeding}
                                                        </td>

                                                        <td className="docColBT">
                                                            {horse.trainer}
                                                        </td>
                                                    </tr>

                                                    <tr className="docSubRow">
                                                        <td
                                                            className="docSubCell"
                                                            colSpan={6}
                                                        >
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
                                        <p>
                                            SS Ban : {race.ss_ban.join(", ")}
                                        </p>
                                    )}

                                    {race.vo_ban?.length > 0 && (
                                        <p>
                                            Vet Ban : {race.vo_ban.join(", ")}
                                        </p>
                                    )}

                                    {race.mk_ban?.length > 0 && (
                                        <p>
                                            MK Ban : {race.mk_ban.join(", ")}
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    ))}
            </div>
        </section>
    );
}