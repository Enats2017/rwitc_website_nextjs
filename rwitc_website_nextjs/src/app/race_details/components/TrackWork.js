"use client";
import { useEffect, useState } from "react";
import { useSearchParams, useRouter } from "next/navigation";
import { FaCalendarAlt, FaSearch, FaChevronUp, FaChevronDown, } from "react-icons/fa";
import { GiHorseHead } from "react-icons/gi";
import { getTrackwork, getTrackworkById, searchTrackworkByHorse, getHorseNameSuggestions, } from "../../../services/trackworkService";
import "./TrackWork.css";

function toInputDate(dateStr) {
    if (!dateStr) return new Date().toISOString().slice(0, 10);
    // Handle values that may still carry a time part ("YYYY-MM-DD HH:MM:SS")
    const cleaned = String(dateStr).split(" ")[0].split("T")[0];
    const d = new Date(cleaned);
    if (isNaN(d)) return new Date().toISOString().slice(0, 10);
    return d.toISOString().slice(0, 10);
}

function toBadgeDate(dateStr) {
    const d = dateStr ? new Date(dateStr) : new Date();
    if (isNaN(d)) return "";
    return d
        .toLocaleDateString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
        })
        .toUpperCase();
}

export default function TrackWork() {
    const searchParams = useSearchParams();
    const router = useRouter();
    const urlDate = searchParams.get("date");
    const urlId = searchParams.get("id");
    const urlHorse = searchParams.get("horsename") || "";
    const urlQ = searchParams.get("q");
    const urlSubmit = searchParams.get("submit");
    const [inputDate, setInputDate] = useState(() => toInputDate(urlDate));
    const [appliedDate, setAppliedDate] = useState(() => toInputDate(urlDate));
    const [appliedId, setAppliedId] = useState(urlId || null);
    const [activeHorseName, setActiveHorseName] = useState(urlHorse);
    const [horseSearch, setHorseSearch] = useState(urlHorse);
    const [horseMatches, setHorseMatches] = useState(null); // null = not searched yet
    const [horseSearchLoading, setHorseSearchLoading] = useState(false);
    const [horseSearchError, setHorseSearchError] = useState("");
    const [matchedHorseTerm, setMatchedHorseTerm] = useState("");
    const [suggestions, setSuggestions] = useState([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [cardOpen, setCardOpen] = useState(true);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [found, setFound] = useState(false);
    const [trackworkHtml, setTrackworkHtml] = useState("");

    useEffect(() => {
        let cancelled = false;
        async function fetchData() {
            setLoading(true);
            setError("");

            try {
                const result = appliedId
                    ? await getTrackworkById(appliedId, activeHorseName)
                    : await getTrackwork(appliedDate, activeHorseName);

                if (cancelled) return;

                setFound(result.found);
                setTrackworkHtml(result.html);
                if (result.date) {
                    const normalized = toInputDate(result.date);
                    setInputDate(normalized);
                    setAppliedDate(normalized);
                }
            } catch (err) {
                if (cancelled) return;
                setError("Unable to load trackwork data. Please try again.");
                setFound(false);
                setTrackworkHtml("");
            } finally {
                if (!cancelled) setLoading(false);
            }
        }
        fetchData();
        return () => {
            cancelled = true;
        };
    }, [appliedDate, appliedId, activeHorseName]);
    const handleDateSearch = () => {
        setHorseMatches(null);
        setHorseSearchError("");
        setActiveHorseName("");
        setAppliedId(null);
        setAppliedDate(inputDate);
        router.push(
            `/race_details?type=trackWork&date=${inputDate}`
        );
    };
    const handleHorseInputChange = async (e) => {
        const value = e.target.value;
        setHorseSearch(value);

        if (value.trim().length < 2) {
            setSuggestions([]);
            setShowSuggestions(false);
            return;
        }
        const results = await getHorseNameSuggestions(value.trim());
        setSuggestions(results);
        setShowSuggestions(results.length > 0);
    };

    const handleSuggestionClick = (name) => {
        setHorseSearch(name);
        setSuggestions([]);
        setShowSuggestions(false);
    };

    const handleHorseSearch = async () => {
        const term = horseSearch.trim();
        router.push(
            `/race_details?type=trackWork&horsename=${encodeURIComponent(
                term
            )}&q=byhorse&submit=Search`
        );
        if (!term) {
            setHorseMatches(null);
            setHorseSearchError("");
            setMatchedHorseTerm("");
            return;
        }

        setShowSuggestions(false);
        setHorseSearchLoading(true);
        setHorseSearchError("");

        try {
            const results = await searchTrackworkByHorse(term);
            setHorseMatches(results);
            setMatchedHorseTerm(term);

            if (results.length === 0) {
                setHorseSearchError(`No trackwork records found for "${term}".`);
            }
        } catch (err) {
            setHorseMatches(null);
            setMatchedHorseTerm("");
            setHorseSearchError("Unable to search for the horse. Please try again.");
        } finally {
            setHorseSearchLoading(false);
        }
    };

    const handlePickMatch = (dateStr) => {
        const normalized = toInputDate(dateStr);

        setInputDate(normalized);
        setActiveHorseName(matchedHorseTerm);
        setAppliedId(null);
        setAppliedDate(normalized);

        setHorseMatches(null);
        setHorseSearchError("");

        router.push(
            `/race_details?type=trackWork&date=${normalized}&horsename=${encodeURIComponent(
                matchedHorseTerm
            )}`
        );
    };

    return (
        <section className="trackWorkSection">
            <div className="trackWorkHeader">
                <h1 className="trackWorkTitle">Track Work</h1>
                <div className="trackWorkDivider">
                    <span className="dividerLine" />
                    <GiHorseHead className="horseIcon" />
                    <span className="dividerLine" />
                </div>
            </div>

            <div className="searchBarsRow">
                <div className="searchBarGroup">
                    <div className="dateInputWrap">
                        <FaCalendarAlt className="inputIcon" />
                        <input
                            type="date"
                            className="dateInput"
                            value={inputDate}
                            onChange={(e) => setInputDate(e.target.value)}
                        />
                    </div>
                    <button type="button" className="searchBtnDark" onClick={handleDateSearch}>
                        Search
                    </button>
                </div>

                <div className="searchBarGroup">
                    <div className="textInputWrap horseInputWrap">
                        <FaSearch className="inputIcon" />
                        <input
                            type="text"
                            className="textInput"
                            placeholder="Search Trackwork By Horsename"
                            value={horseSearch}
                            onChange={handleHorseInputChange}
                            onKeyDown={(e) => e.key === "Enter" && handleHorseSearch()}
                            onFocus={() => setShowSuggestions(suggestions.length > 0)}
                            onBlur={() => setTimeout(() => setShowSuggestions(false), 150)}
                            autoComplete="off"
                        />

                        {showSuggestions && (
                            <ul className="autocompleteList">
                                {suggestions.map((name, i) => (
                                    <li
                                        key={i}
                                        className="autocompleteItem"
                                        onMouseDown={() => handleSuggestionClick(name)}
                                    >
                                        {name}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                    <button type="button" className="searchBtnGold" onClick={handleHorseSearch}>
                        {horseSearchLoading ? "Searching..." : "Search"}
                    </button>
                </div>
            </div>

            {horseMatches !== null && (
                <div className="horseMatchesWrap">
                    {/* Same heading as old PHP page: "Matched Trackworks for Horse X" */}
                    {horseMatches.length > 0 && (
                        <h3 className="horseMatchesHeading">
                            Matched Trackworks for Horse {matchedHorseTerm}
                        </h3>
                    )}

                    {horseSearchError && (
                        <p className="horseMatchesMsg">{horseSearchError}</p>
                    )}

                    {horseMatches.length > 0 && (
                        <ul className="horseMatchesList">
                            {horseMatches.map((m) => (
                                <li key={m.id}>
                                    <button
                                        type="button"
                                        className="horseMatchBtn"
                                        onClick={() => handlePickMatch(m.trackwork_date)}
                                    >
                                        {toBadgeDate(m.trackwork_date)}
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            )}

            <div className="trackworkBadgeWrap">
                <span className="trackworkBadge">
                    Trackwork for {toBadgeDate(appliedDate)}
                </span>
            </div>

            <div className="trackWorkCard">
                <div
                    className="trackCardHeader"
                    onClick={() => setCardOpen((prev) => !prev)}
                >
                    <span className="trackCardHeaderLeft">Trackwork Report</span>
                    {cardOpen ? <FaChevronUp /> : <FaChevronDown />}
                </div>

                {cardOpen && (
                    <>
                        {loading && (
                            <p className="trackworkStateMsg">Loading trackwork...</p>
                        )}

                        {!loading && error && (
                            <p className="trackworkStateMsg trackworkStateMsgError">{error}</p>
                        )}

                        {!loading && !error && !found && (
                            <p className="trackworkStateMsg">
                                No trackwork record is available for the selected date.
                            </p>
                        )}

                        {!loading && !error && found && (
                            <div
                                className="trackworkContent"
                                dangerouslySetInnerHTML={{ __html: trackworkHtml }}
                            />
                        )}

                        <div className="bottomDivider">
                            <span className="dividerLine" />
                            <GiHorseHead className="horseIconGold" />
                            <span className="dividerLine" />
                        </div>
                    </>
                )}
            </div>
        </section>
    );
}