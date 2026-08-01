"use client";

import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { getHandicaps } from "../../../services/handicapsService";
import "./Handicaps.css";

const ARCHIVE_STYLES = `
<style>
span, a { display: inline-block; text-decoration: none; color: #333333; }
body { font-family: Arial; margin: 0; }
h1 { margin: unset !important; font-size: 26px !important; }
h3 { font-family: 'Roboto Condensed', Arial, sans-serif; font-size: 32px; color: #c1c1c1; margin: 10px 0; }
th {
    color: #ffffff !important;
    font-size: 14px;
    text-align: center;
    padding: 1px;
    border: 1px solid #BCBEC0;
    background: #11a14e;
}
    td { text-align: left !important; padding: 4px !important; color: #333333 !important; font-weight: 600; }
tbody > tr > th { text-align: left !important; }
tbody tr td:nth-child(2) { text-align: center !important; }
tbody tr td:nth-child(3) { text-align: center !important; }
table { border-collapse: collapse; }
.table { width: 100%; max-width: 100%; margin-bottom: 1rem; background-color: transparent; }
.table th, .table td {
    padding: 8px;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}
.table-bordered {
    border: 1px solid #dee2e6;
}
.table-bordered th, .table-bordered td {
    border: 1px solid #dee2e6;
}
.table-bordered thead th, .table-bordered thead td {
    border-bottom-width: 2px;
}
.table-bordered { font-weight: bold; width: 100%; border-collapse: collapse; }
.download {
    display: none !important;
}
.pageHeading { text-align: center; }
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
@media (max-width: 500px) {
    .text_size { font-size: 8px; }
    .font12 { font-size: 8px; }
    .download { float: unset !important; margin-bottom: 10px; }
    .nm { text-align: left !important; }
    .perform_data {
        display: contents;
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
    .perform_data td { font-size: 11px !important; position: relative; border: unset !important; }
    td, th { font-size: 10px !important; border: unset; }
    .racehead { padding: 5px !important; font-size: 12px !important; }
}
</style>
`;

export default function Handicaps() {

    const searchParams = useSearchParams();
    const date = searchParams.get("date");

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [mode, setMode] = useState("json");
    const [rawHtml, setRawHtml] = useState("");
    const [meeting, setMeeting] = useState(null);
    const [races, setRaces] = useState([]);
    const [downloadFile, setDownloadFile] = useState(null);
    const [downloadAvailable, setDownloadAvailable] = useState(false);

    useEffect(() => {

        async function loadHandicaps() {

            if (!date) {
                setError("No date selected.");
                setLoading(false);
                return;
            }

            try {

                setLoading(true);
                setError(null);

                const data = await getHandicaps(date);

                setMode(data.mode || "json");
                setRawHtml(data.html || "");
                setMeeting(data.meeting);
                setRaces(data.races || []);
                setDownloadFile(data.downloadFile);
                setDownloadAvailable(data.downloadAvailable);

            } catch (err) {

                console.error("Handicaps Error:", err);
                setError("Unable to load handicaps for this date.");

            } finally {

                setLoading(false);

            }

        }

        loadHandicaps();

    }, [date]);

    const isHtmlMode = mode === "html";
    const hasNoData = !isHtmlMode && races.length === 0;
    const hasNoHtml = isHtmlMode && !rawHtml.trim();

    return (
        <section className="handicapsPage docPage">

            <div className="docBadgeWrap">
                <span className="docBadge">Handicaps</span>
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
                    Download Handicaps
                </button>

                {/* Header block only applies to the structured (DB) view —
                    the archived HTML already carries its own header markup. */}
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

                {loading && (
                    <div className="docStateBox">
                        <div className="docLoader" />
                        <p>Loading handicaps…</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="docStateBox docStateError">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && (hasNoData || hasNoHtml) && (
                    <div className="docStateBox">
                        <p>No handicaps data found for this date.</p>
                    </div>
                )}

                {/* Archive dates (date > 2022-09-25): render the
                    Handicaps_<date>.html markup returned by the API
                    as-is, same as the live page's server-side include.
                    Rendered inside an iframe so the archive file's own
                    <style> block stays isolated and can't clash with the
                    app's global CSS. */}
                {!loading && !error && isHtmlMode && !hasNoHtml && (
                    <iframe
                        className="docArchiveHtml"
                        srcDoc={ARCHIVE_STYLES + rawHtml}
                        title="Handicaps"
                        sandbox="allow-same-origin"
                        onLoad={(e) => {
                            const iframe = e.target;
                            const doc = iframe.contentWindow?.document;
                            if (!doc) return;

                            const setHeight = () => {
                                iframe.style.height = doc.documentElement.scrollHeight + "px";
                            };

                            // Measure again after layout/styles fully settle, so the
                            // height reflects content AFTER .show1 rows are hidden.
                            setHeight();
                            requestAnimationFrame(setHeight);
                            setTimeout(setHeight, 100);
                        }}
                    />
                )}

                {/* DB-sourced dates (date <= 2022-09-25): structured table. */}
                {!loading && !error && !isHtmlMode && !hasNoData && races.map((race, idx) => (

                    <div className="docRaceBlock" key={race.srno || idx}>

                        <div className="docRaceBar">
                            <p className="docRaceName">
                                {idx + 1}.&nbsp; {race.race_name}
                                {race.grade && <> &nbsp;({race.grade})</>}
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

                        {race.weight_note && (
                            <p className="docWeightNote">{race.weight_note}</p>
                        )}

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
                                    {race.horses && race.horses.map((horse, hIdx) => (
                                        <tr key={horse.horseseq || hIdx}>
                                            <td>{horse.order}</td>
                                            <td className="docHorseName">{horse.name}</td>
                                            <td>{horse.color}/{horse.sex}</td>
                                            <td>{horse.age ?? "-"}</td>
                                            <td>{horse.weight ?? "-"}</td>
                                            <td>{horse.rating ?? "NR"}</td>
                                            <td>{horse.breeding}</td>
                                            <td>{horse.trainer}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

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