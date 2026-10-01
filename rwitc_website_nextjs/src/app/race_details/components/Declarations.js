"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { FaHorseHead } from "react-icons/fa";

import { getDeclarations } from "../../../services/declarationsService";
import { formatArchiveHtml, handleArchiveIframeLoad } from "../../../utils/archiveHtmlHelper";

import "./Declarations.css";

/* Archive iframe styles: identical to Handicaps + mobile table-scroll fix */
const ARCHIVE_STYLES_DECLARATIONS = `
<style>
* { box-sizing: border-box; }
span, a { display: inline-block; text-decoration: none; color: #333333; }
html, body { margin: 0; padding: 0; max-width: 100%; overflow-x: hidden; }
body { font-family: Arial, sans-serif; word-wrap: break-word; }
img { max-width: 100%; height: auto; }
h1 { margin: unset !important; font-size: 26px !important; }
h3 { font-family: 'Roboto Condensed', Arial, sans-serif; font-size: 32px; color: #c1c1c1; margin: 10px 0; }
th { color: #ffffff !important; font-size: 14px; text-align: center; padding: 1px; border: 1px solid #BCBEC0; background: #11a14e; }
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

/* header / race bar wrap inside the card */
#leftArea, .pageHeader, .pageHeading, .subHeading, h1, h3, p, div { max-width: 100%; white-space: normal; overflow-wrap: anywhere; }

/* only data tables scroll */
.tableScroll { width: 100%; max-width: 100%; overflow-x: auto; overflow-y: hidden; -webkit-overflow-scrolling: touch; }
.tableScroll table { width: 100%; min-width: 720px; margin: 0 !important; }
body { display: flow-root; }
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