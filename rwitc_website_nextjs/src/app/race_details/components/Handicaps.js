"use client";

import { useEffect, useState } from "react";
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
 *
 * Goal: the iframe always fits the card width.
 * Only the horse data table scrolls sideways (wrapped in .tableScroll).
 */
const ARCHIVE_STYLES = `
<style>

* {
    box-sizing: border-box;
}

span,
a {
    display: inline-block;
    text-decoration: none;
    color: #333333;
}

html,
body {
    margin: 0;
    padding: 0;
    max-width: 100%;
    overflow-x: hidden;
}

body {
    font-family: Arial, sans-serif;
    word-wrap: break-word;
}

img {
    max-width: 100%;
    height: auto;
}

h1 {
    margin: unset !important;
    font-size: 26px !important;
}

h3 {
    font-family: 'Roboto Condensed', Arial, sans-serif;
    font-size: 32px;
    color: #c1c1c1;
    margin: 10px 0;
}

th {
    color: #ffffff !important;
    font-size: 14px;
    text-align: center;
    padding: 1px;
    border: 1px solid #BCBEC0;
    background: #11a14e;
}

td {
    text-align: left !important;
    padding: 4px !important;
    color: #333333 !important;
    font-weight: 600;
}

tbody > tr > th {
    text-align: left !important;
}

tbody tr td:nth-child(2) {
    text-align: center !important;
}

tbody tr td:nth-child(3) {
    text-align: center !important;
}

table {
    border-collapse: collapse;
}

.table {
    width: 100%;
    max-width: 100%;
    margin-bottom: 1rem;
    background-color: transparent;
}

.table th,
.table td {
    padding: 8px;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}

.table-bordered {
    border: 1px solid #dee2e6;
}

.table-bordered th,
.table-bordered td {
    border: 1px solid #dee2e6;
}

.table-bordered thead th,
.table-bordered thead td {
    border-bottom-width: 2px;
}

.table-bordered {
    font-weight: bold;
    width: 100%;
    border-collapse: collapse;
}

.download {
    display: none !important;
}

.pageHeading {
    text-align: center;
}

.hclass {
    text-align: center;
    display: inline-block;
    background-color: #11a14e;
    border-radius: 33px;
    padding: 11px;
    vertical-align: middle !important;
    color: white;
    margin-top: 25px;
}

#leftArea .pageHeader .pageHeading .subHeading {
    clear: both;
    float: left;
    width: 100%;
    color: #000;
    font-weight: bold;
    text-align: center;
    font-size: 12px;
    margin: 5px 0;
    padding: 5px 0;
}

.show1 {
    display: none;
}

/* ---------- MOBILE FIX: header / race bar wrap inside card ---------- */

#leftArea,
.pageHeader,
.pageHeading,
.subHeading,
h1, h3, p, div {
    max-width: 100%;
    white-space: normal;
    overflow-wrap: anywhere;
}

/* ---------- MOBILE FIX: ONLY the data table scrolls ---------- */

.tableScroll {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}

.tableScroll table {
    width: 100%;
    min-width: 640px;
    margin-bottom: 0;
}

</style>

<script>
(function () {
    function wrapTables() {
        var tables = document.querySelectorAll("table");

        tables.forEach(function (table) {
            // already wrapped
            if (
                table.parentElement &&
                table.parentElement.classList.contains("tableScroll")
            ) {
                return;
            }

            // wrap ONLY the horse data tables, not header / race-bar tables
            var text = (table.innerText || table.textContent || "");
            if (text.indexOf("Horse Name") === -1) return;

            var wrap = document.createElement("div");
            wrap.className = "tableScroll";

            table.parentNode.insertBefore(wrap, table);
            wrap.appendChild(table);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", wrapTables);
    } else {
        wrapTables();
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
     * in sync when the content re-flows (script wrapping tables, resize,
     * orientation change).
     */
    function onIframeLoad(e) {
        handleArchiveIframeLoad(e);

        const iframe = e.currentTarget;

        try {
            const doc = iframe.contentDocument;
            if (!doc || typeof ResizeObserver === "undefined") return;

            const resize = () => {
                iframe.style.height =
                    doc.documentElement.scrollHeight + "px";
            };

            resize();

            const ro = new ResizeObserver(resize);
            ro.observe(doc.body);
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
                                            <th>Breeding</th>
                                            <th>Trainer</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        {race.horses &&
                                            race.horses.map((horse, hIdx) => (
                                                <tr
                                                    key={
                                                        horse.horseseq || hIdx
                                                    }
                                                >
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

                                                    <td>{horse.breeding}</td>

                                                    <td>{horse.trainer}</td>
                                                </tr>
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