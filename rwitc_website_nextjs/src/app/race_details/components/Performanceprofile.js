"use client";

import { useState, useRef, useEffect } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { getHorseSuggestions, getHorseProfile, getRaceResult } from "../../../services/Performanceprofileservice";
import "./Performanceprofile.css";

export default function PerformanceProfile() {

    const router = useRouter();
    const searchParams = useSearchParams();

    const [horseName, setHorseName] = useState("");
    const [suggestions, setSuggestions] = useState([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const [submitting, setSubmitting] = useState(false);
    const [profileData, setProfileData] = useState(null);
    const [profileError, setProfileError] = useState(null);

    // "profile" | "raceResult" — controls which card renders below the search box
    const [view, setView] = useState("profile");
    const [raceResultData, setRaceResultData] = useState(null);
    const [raceResultError, setRaceResultError] = useState(null);
    const [loadingRaceResult, setLoadingRaceResult] = useState(false);

    const wrapRef = useRef(null);

    useEffect(() => {

        function handleClickOutside(e) {
            if (wrapRef.current && !wrapRef.current.contains(e.target)) {
                setShowSuggestions(false);
            }
        }

        document.addEventListener("mousedown", handleClickOutside);
        return () => document.removeEventListener("mousedown", handleClickOutside);

    }, []);

    // Auto-load profile if URL already has ?horsename= (e.g. direct link, refresh)
    useEffect(() => {

        const urlHorseName = searchParams.get("horsename") || searchParams.get("as_values");

        if (urlHorseName && urlHorseName.trim()) {

            // setHorseName(urlHorseName);  // search box should stay empty when data comes via horse click

            (async () => {
                setSubmitting(true);
                setProfileError(null);

                const data = await getHorseProfile(urlHorseName.trim());

                if (data.found) {
                    setProfileData(data);
                    setView("profile");
                } else {
                    setProfileData(null);
                    setProfileError(data.message || "No records found.");
                }

                setSubmitting(false);
            })();
        }

        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    async function handleInputChange(e) {
        const value = e.target.value;
        setHorseName(value);
        setActiveIndex(-1);

        if (value.trim().length < 2) {
            setSuggestions([]);
            setShowSuggestions(false);
            return;
        }

        const results = await getHorseSuggestions(value);
        setSuggestions(results);
        setShowSuggestions(true);
    }

    function handleSelectSuggestion(name) {
        setHorseName(name);
        setShowSuggestions(false);
        setActiveIndex(-1);
    }

    function handleKeyDown(e) {

        if (!showSuggestions || suggestions.length === 0) return;

        if (e.key === "ArrowDown") {
            e.preventDefault();
            setActiveIndex((prev) => (prev + 1) % suggestions.length);
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            setActiveIndex((prev) => (prev - 1 + suggestions.length) % suggestions.length);
        } else if (e.key === "Enter") {
            if (activeIndex >= 0) {
                e.preventDefault();
                handleSelectSuggestion(suggestions[activeIndex]);
            }
        } else if (e.key === "Escape") {
            setShowSuggestions(false);
        }
    }

    async function handleSubmit() {
        if (!horseName.trim()) return;

        setSubmitting(true);
        setProfileError(null);
        setProfileData(null);
        setShowSuggestions(false);
        setView("profile");
        setRaceResultData(null);
        setRaceResultError(null);

        const data = await getHorseProfile(horseName.trim());

        if (data.found) {
            setProfileData(data);
        } else {
            setProfileData(null);
            setProfileError(data.message || "No records found.");
        }

        setSubmitting(false);

        // Update URL after data is loaded — avoids remounting mid-fetch
        const params = new URLSearchParams(searchParams.toString());
        params.set("horsename", horseName.trim());
        router.replace(`?${params.toString()}`, { scroll: false });
    }

    // RACENO click — swaps the profile card for the race result card,
    // same as the live site's ?q=result view.
    async function handleRaceNoClick(raceno, racedate) {

        setView("raceResult");
        setLoadingRaceResult(true);
        setRaceResultError(null);
        setRaceResultData(null);

        const data = await getRaceResult(raceno, racedate);

        if (data.found) {
            setRaceResultData(data);
        } else {
            setRaceResultData(null);
            setRaceResultError(data.message || "No results found for this race.");
        }

        setLoadingRaceResult(false);
    }

    // Horse name click inside Race Result — loads that horse's profile, same page
    async function handleHorseNameClick(name) {

        setHorseName(name);
        setView("profile");
        setSubmitting(true);
        setProfileError(null);
        setProfileData(null);

        const data = await getHorseProfile(name);

        if (data.found) {
            setProfileData(data);
        } else {
            setProfileData(null);
            setProfileError(data.message || "No records found.");
        }

        setSubmitting(false);

        const params = new URLSearchParams(searchParams.toString());
        params.set("horsename", name);
        router.replace(`?${params.toString()}`, { scroll: false });
    }

    function handleBackToProfile() {
        setView("profile");
        setRaceResultData(null);
        setRaceResultError(null);
    }

    return (
        <section className="performanceProfilePage">

            <div className="ppPageWrap">

                <div className="ppCard">

                    <div className="ppHeader">
                        <span className="ppHorseIcon" aria-hidden="true">
                            <svg viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                                <path d="M509.8 332.5l-69.9-164.3c-14.9-41.2-50.4-71-93-79.2 18-10.6 46.3-35.9 34.2-82.3-1.3-5-7.1-7.9-12-6.1L166.9 76.3C35.9 123.4 0 238.9 0 398.8V480c0 17.7 14.3 32 32 32h236.2c23.8 0 39.3-25 28.6-46.3L256 384v-.7c-45.6-3.5-84.6-30.7-104.3-69.6-1.6-3.1-.9-6.9 1.6-9.3l12.1-12.1c3.9-3.9 10.6-2.7 12.9 2.4 14.8 33.7 48.2 57.4 87.4 57.4 17.2 0 33-5.1 46.8-13.2l46 63.9c6 8.4 15.7 13.3 26 13.3h50.3c8.5 0 16.6-3.4 22.6-9.4l45.3-39.8c8.9-9.1 11.7-22.6 7.1-34.4zM328 224c-13.3 0-24-10.7-24-24s10.7-24 24-24 24 10.7 24 24-10.7 24-24 24z"/>
                            </svg>
                        </span>
                        <h1 className="ppTitle">Performance Profile @ RWITC</h1>
                    </div>

                    <div className="ppSearchRow">

                        <div className="ppLabelGroup">
                            <span className="ppSearchIconWrap" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="11" cy="11" r="7" strokeWidth="2"/>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65" strokeWidth="2" strokeLinecap="round"/>
                                </svg>
                            </span>
                            <span className="ppLabel">Search Horse</span>
                        </div>

                        <div className="ppInputWrap" ref={wrapRef}>
                            <input
                                type="text"
                                className="ppInput"
                                placeholder="Enter Horsename Here"
                                value={horseName}
                                onChange={handleInputChange}
                                onKeyDown={handleKeyDown}
                                onFocus={() => {
                                    if (suggestions.length > 0) setShowSuggestions(true);
                                }}
                                autoComplete="off"
                            />

                            {showSuggestions && (
                                <ul className="ppSuggestions">
                                    {suggestions.length > 0 ? (
                                        suggestions.map((name, idx) => (
                                            <li
                                                key={name + idx}
                                                className={
                                                    "ppSuggestionItem" +
                                                    (idx === activeIndex ? " active" : "")
                                                }
                                                onMouseDown={() => handleSelectSuggestion(name)}
                                            >
                                                {name}
                                            </li>
                                        ))
                                    ) : (
                                        <li className="ppSuggestionEmpty">No matches found</li>
                                    )}
                                </ul>
                            )}
                        </div>

                        <button
                            type="button"
                            className="ppSubmitBtn"
                            onClick={handleSubmit}
                            disabled={submitting}
                        >
                            Submit
                        </button>

                    </div>

                </div>

                {/* ---------------- PROFILE VIEW ---------------- */}
                {view === "profile" && submitting && (
                    <div className="ppResultsCard">
                        <p className="ppResultsLoading">Loading horse profile…</p>
                    </div>
                )}

                {view === "profile" && !submitting && profileError && (
                    <div className="ppResultsCard">
                        <p className="ppResultsError">{profileError}</p>
                    </div>
                )}

                {view === "profile" && !submitting && profileData && (
                    <div className="ppResultsCard">

                        <div className="ppResultsHeader">
                            <h2 className="ppResultsHorseName">{profileData.horse_name}</h2>
                            {profileData.sire_dame_details && (
                                <p className="ppResultsSireDame">{profileData.sire_dame_details}</p>
                            )}
                        </div>

                        <div className="ppTableWrap">
                            <table className="ppRunsTable">
                                <thead>
                                    <tr>
                                        <th>Venue</th>
                                        <th>Date</th>
                                        <th>RaceNo</th>
                                        <th>Jockey</th>
                                        <th>Class</th>
                                        <th>Distance</th>
                                        <th>Weight</th>
                                        <th>Placing</th>
                                        <th>Time</th>
                                        <th>Stakes</th>
                                        <th>Video</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {profileData.runs_data.map((row, idx) => (
                                        <tr key={idx}>
                                            <td>{row.venue}</td>
                                            <td>{row.date}</td>
                                            <td>
                                                <button
                                                    type="button"
                                                    className="ppRaceNoLink"
                                                    onClick={() => handleRaceNoClick(row.race_no, row.race_date)}
                                                >
                                                    {row.race_no}
                                                </button>
                                            </td>
                                            <td>{row.jockey}</td>
                                            <td>{row.class}</td>
                                            <td>{row.distance}</td>
                                            <td>{row.weight}</td>
                                            <td>{row.placing}</td>
                                            <td>{row.time}</td>
                                            <td>{row.stakes}</td>
                                            <td>
                                                {row.video_url ? (
                                                    <a
                                                        href={`https://www.rwitcraces.com/RaceArchives.aspx?${row.video_url}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="ppVideoIcon"
                                                        aria-label="Watch race video"
                                                    >
                                                        ▶
                                                    </a>
                                                ) : (
                                                    "-"
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {profileData.totals && (
                            <div className="ppTotalsWrap">
                                <p className="ppTotalsTitle">Runs Data for {profileData.horse_name}</p>
                                <table className="ppTotalsTable">
                                    <thead>
                                        <tr>
                                            <th>Runs</th>
                                            <th>Wins</th>
                                            <th>Second</th>
                                            <th>Third</th>
                                            <th>Total Stakes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{profileData.totals.runs}</td>
                                            <td>{profileData.totals.wins}</td>
                                            <td>{profileData.totals.seconds}</td>
                                            <td>{profileData.totals.thirds}</td>
                                            <td>{profileData.totals.stakes}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        )}

                    </div>
                )}

                {/* ---------------- RACE RESULT VIEW ---------------- */}
                {view === "raceResult" && (
                    <div className="ppResultsCard">

                        <button
                            type="button"
                            className="ppBackBtn"
                            onClick={handleBackToProfile}
                        >
                            ← Back to Horse Profile
                        </button>

                        {loadingRaceResult && (
                            <p className="ppResultsLoading">Loading race result…</p>
                        )}

                        {!loadingRaceResult && raceResultError && (
                            <p className="ppResultsError">{raceResultError}</p>
                        )}

                        {!loadingRaceResult && raceResultData && (
                            <>
                                <div className="ppResultsHeader">
                                    <h2 className="ppRaceResultTitle">
                                        Race Results for Race No. {raceResultData.race_no} run on {raceResultData.race_date}
                                    </h2>
                                    {raceResultData.race_header && (
                                        <p className="ppResultsSireDame">
                                            {raceResultData.race_header.race_name}
                                            {raceResultData.race_header.race_term ? ` — ${raceResultData.race_header.race_term}` : ""}
                                            {raceResultData.race_header.distance ? ` — Distance ${raceResultData.race_header.distance}` : ""}
                                        </p>
                                    )}
                                </div>

                                <div className="ppTableWrap">
                                    <table className="ppRunsTable">
                                        <thead>
                                            <tr>
                                                <th>Placing</th>
                                                <th>Horse</th>
                                                <th>Wt</th>
                                                <th>Length</th>
                                                <th>Trainer</th>
                                                <th>Jockey</th>
                                                <th>Odds</th>
                                                <th>Time</th>
                                                <th>Horse Wt</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {raceResultData.results.map((row, idx) => (
                                                <tr key={idx}>
                                                    <td>{row.placing}</td>
                                                    <td>
                                                        <button
                                                            type="button"
                                                            className="ppRaceNoLink"
                                                            onClick={() => handleHorseNameClick(row.horse_name)}
                                                        >
                                                            {row.horse_name}
                                                        </button>
                                                        {(row.sire || row.dam) && (
                                                            <>
                                                                <br />
                                                                <span className="ppSireDameSmall">
                                                                    ({row.sire}-{row.dam})
                                                                </span>
                                                            </>
                                                        )}
                                                    </td>
                                                    <td>{row.weight ?? "-"}</td>
                                                    <td>{row.length}</td>
                                                    <td>{row.trainer}</td>
                                                    <td>{row.jockey}</td>
                                                    <td>{row.odds ?? "--"}</td>
                                                    <td>{row.time ?? "-"}</td>
                                                    <td>{row.horse_weight}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {raceResultData.void_race && (
                                    <p className="ppResultsError">
                                        This race has been declared Null &amp; Void
                                    </p>
                                )}
                            </>
                        )}

                    </div>
                )}

            </div>

        </section>
    );
}