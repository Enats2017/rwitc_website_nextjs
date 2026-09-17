"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { FaChevronLeft, FaChevronRight, FaCalendarAlt } from "react-icons/fa";
import { getMockRaceResults } from "../../../services/mockRaceResultService";
import { API_URL } from "../../../services/api";
import "./MockRaceResult.css";

const MOCK_RACE_RESULT_STYLES = `
<style>
html,
body {
    margin: 0;
    padding: 0;
    background: #ffffff;
    color: #333333;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 14px;
}

* {
    box-sizing: border-box;
}

body {
    padding: 20px;
    overflow-x: auto;
}

a {
    color: #00843d;
    text-decoration: none;
}

img {
    max-width: 100%;
    height: auto;
}

table {
    border-collapse: collapse;
    width: 100%;
}

th {
    color: #ffffff !important;
    background: #11a14e !important;
    border: 1px solid #bcbec0 !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    text-align: center !important;
    padding: 8px !important;
}

td {
    color: #333333 !important;
    border: 1px solid #dee2e6 !important;
    padding: 7px !important;
    font-weight: 600;
    vertical-align: middle;
}

tr:nth-child(even) td {
    background: #f8faf9;
}

h1,
h2,
h3 {
    color: #00843d;
    margin-top: 10px;
    margin-bottom: 10px;
}

.pageHeading {
    text-align: center;
}

.download {
    display: none !important;
}

.racehead {
    background: #11a14e !important;
    color: #ffffff !important;
    padding: 8px !important;
    font-size: 15px !important;
    font-weight: 700 !important;
}

@media (max-width: 768px) {
    body {
        padding: 10px;
    }

    table {
        min-width: 700px;
    }

    th {
        font-size: 12px !important;
        padding: 6px !important;
    }

    td {
        font-size: 11px !important;
        padding: 5px !important;
    }

    .racehead {
        font-size: 12px !important;
    }
}
</style>
`;

const WEEKDAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const COLOR_PALETTE = [
    "#16a34a", "#2563eb", "#db2777", "#d97706",
    "#7c3aed", "#0891b2", "#dc2626", "#65a30d",
];

function dateKey(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const dd = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${dd}`;
}

function isSameDay(a, b) {
    return (
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
    );
}

function buildMonthMatrix(year, month) {
    const firstOfMonth = new Date(year, month, 1);
    const startOffset = firstOfMonth.getDay();
    const gridStart = new Date(year, month, 1 - startOffset);

    const weeks = [];

    for (let w = 0; w < 6; w++) {
        const week = [];

        for (let dIdx = 0; dIdx < 7; dIdx++) {
            const current = new Date(gridStart);
            current.setDate(gridStart.getDate() + w * 7 + dIdx);
            week.push(current);
        }

        weeks.push(week);
    }

    return weeks;
}

export default function MockRaceResult() {
    const [events, setEvents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    // Mock Race Result viewer
    const [selectedResult, setSelectedResult] = useState(null);
    const [resultHtml, setResultHtml] = useState("");
    const [resultLoading, setResultLoading] = useState(false);
    const [resultError, setResultError] = useState("");

    const [cursor, setCursor] = useState(() => {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), 1);
    });

    const dateInputRef = useRef(null);
    const viewerRef = useRef(null);

    useEffect(() => {
        let cancelled = false;

        async function loadData() {
            try {
                setLoading(true);
                setError("");

                const data = await getMockRaceResults(
                    cursor.getFullYear(),
                    cursor.getMonth()
                );

                if (!cancelled) {
                    setEvents(data);
                }
            } catch (err) {
                console.error("Mock Race Result API Error:", err);

                if (!cancelled) {
                    setEvents([]);
                    setError(
                        err?.message || "Unable to load race results."
                    );
                }
            } finally {
                if (!cancelled) {
                    setLoading(false);
                }
            }
        }

        loadData();

        return () => {
            cancelled = true;
        };
    }, [cursor]);

    const eventsByDate = useMemo(() => {
        const map = {};

        events.forEach((event) => {
            const key = event.start;

            if (!map[key]) {
                map[key] = [];
            }

            map[key].push(event);
        });

        return map;
    }, [events]);

    const centreColors = useMemo(() => {
        const uniqueTypes = [
            ...new Set(events.map((event) => event.title)),
        ];

        const colorMap = {};

        uniqueTypes.forEach((type, index) => {
            colorMap[type] =
                COLOR_PALETTE[index % COLOR_PALETTE.length];
        });

        return colorMap;
    }, [events]);

    const weeks = useMemo(
        () =>
            buildMonthMatrix(
                cursor.getFullYear(),
                cursor.getMonth()
            ),
        [cursor]
    );

    const monthLabel = cursor.toLocaleDateString("en-GB", {
        month: "long",
        year: "numeric",
    });

    const today = new Date();

    function goPrevMonth() {
        setCursor(
            (prev) =>
                new Date(
                    prev.getFullYear(),
                    prev.getMonth() - 1,
                    1
                )
        );
    }

    function goNextMonth() {
        setCursor(
            (prev) =>
                new Date(
                    prev.getFullYear(),
                    prev.getMonth() + 1,
                    1
                )
        );
    }

    function goToday() {
        const now = new Date();

        setCursor(
            new Date(
                now.getFullYear(),
                now.getMonth(),
                1
            )
        );
    }

    function openDatePicker() {
        const input = dateInputRef.current;

        if (!input) return;

        if (typeof input.showPicker === "function") {
            input.showPicker();
        } else {
            input.focus();
            input.click();
        }
    }

    function handleDatePicked(e) {
        const value = e.target.value;

        if (!value) return;

        const [year, month] = value.split("-").map(Number);

        setCursor(
            new Date(
                year,
                month - 1,
                1
            )
        );
    }

    // Open the PHP API response inside the NextJS page.
    // This keeps the S3 URL out of the browser address bar.
    async function openMockRaceResult(event) {
        try {
            setSelectedResult(event);
            setResultHtml("");
            setResultError("");
            setResultLoading(true);

            const response = await fetch(
                `${API_URL}/mock_race_result_calendar_api.php?open=1&id=${encodeURIComponent(event.id)}`
            );

            if (!response.ok) {
                throw new Error(
                    `Unable to load Mock Race Result (${response.status})`
                );
            }

            const html = await response.text();

            if (!html.trim()) {
                throw new Error("Mock Race Result file is empty.");
            }

            setResultHtml(html);

            // Scroll the viewer into view once content is set —
            // matches the "opens like a document, in-flow" feel.
            requestAnimationFrame(() => {
                viewerRef.current?.scrollIntoView({
                    behavior: "smooth",
                    block: "start",
                });
            });
        } catch (err) {
            console.error("Mock Race Result Load Error:", err);

            setResultError(
                err?.message || "Unable to load Mock Race Result."
            );
        } finally {
            setResultLoading(false);
        }
    }

    function closeMockRaceResult() {
        setSelectedResult(null);
        setResultHtml("");
        setResultError("");
    }

    return (
        <section className="raceResultSection">
            <h1 className="raceResultTitle">Race Results</h1>

            <div className="raceResultCard">
                <div className="calendarToolbar">
                    <div className="monthTitle">{monthLabel}</div>

                    <div className="navControls">
                        <button
                            className="todayBtn"
                            onClick={goToday}
                        >
                            Today
                        </button>

                        <button
                            className="navBtn"
                            onClick={goPrevMonth}
                            aria-label="Previous month"
                        >
                            <FaChevronLeft />
                        </button>

                        <button
                            className="navBtn"
                            onClick={goNextMonth}
                            aria-label="Next month"
                        >
                            <FaChevronRight />
                        </button>

                        <div className="jumpWrapper">
                            <button
                                className="navBtn jumpBtn"
                                onClick={openDatePicker}
                                aria-label="Jump to date"
                                title="Jump to date"
                            >
                                <FaCalendarAlt />
                            </button>

                            <input
                                ref={dateInputRef}
                                type="date"
                                className="hiddenDateInput"
                                onChange={handleDatePicked}
                                value={`${cursor.getFullYear()}-${String(
                                    cursor.getMonth() + 1
                                ).padStart(2, "0")}-01`}
                                aria-hidden="true"
                                tabIndex={-1}
                            />
                        </div>
                    </div>
                </div>

                {Object.keys(centreColors).length > 0 && (
                    <div className="legendRow">
                        {Object.entries(centreColors).map(
                            ([type, color]) => (
                                <span
                                    className="legendItem"
                                    key={type}
                                >
                                    <span
                                        className="legendDot"
                                        style={{
                                            backgroundColor: color,
                                        }}
                                    />
                                    {type}
                                </span>
                            )
                        )}
                    </div>
                )}

                {loading ? (
                    <div className="raceResultLoading">
                        Loading race results…
                    </div>
                ) : (
                    <>
                        {error && (
                            <div
                                style={{
                                    padding: "12px 14px",
                                    marginBottom: "16px",
                                    borderRadius: "10px",
                                    background: "#fff1f2",
                                    color: "#be123c",
                                    fontSize: "14px",
                                    fontWeight: 600,
                                }}
                            >
                                {error}
                            </div>
                        )}

                        <div className="weekdayRow">
                            {WEEKDAYS.map((day) => (
                                <div
                                    className="weekdayCell"
                                    key={day}
                                >
                                    {day}
                                </div>
                            ))}
                        </div>

                        <div className="calendarGrid">
                            {weeks.map((week, wi) => (
                                <div
                                    className="calendarWeek"
                                    key={wi}
                                >
                                    {week.map((day, di) => {
                                        const inMonth =
                                            day.getMonth() ===
                                            cursor.getMonth();

                                        const isToday =
                                            isSameDay(day, today);

                                        const dayEvents =
                                            eventsByDate[
                                                dateKey(day)
                                            ] || [];

                                        return (
                                            <div
                                                className={
                                                    "dayCell" +
                                                    (!inMonth
                                                        ? " dayCellMuted"
                                                        : "") +
                                                    (isToday
                                                        ? " dayCellToday"
                                                        : "")
                                                }
                                                key={di}
                                            >
                                                <span className="dayNumber">
                                                    {day.getDate()}
                                                </span>

                                                <div className="dayEvents">
                                                    {dayEvents.map(
                                                        (
                                                            event,
                                                            ei
                                                        ) => (
                                                            <button
                                                                key={
                                                                    event.id ??
                                                                    ei
                                                                }
                                                                type="button"
                                                                className="eventPill"
                                                                onClick={() =>
                                                                    openMockRaceResult(
                                                                        event
                                                                    )
                                                                }
                                                                style={{
                                                                    backgroundColor:
                                                                        centreColors[
                                                                            event.title
                                                                        ] ||
                                                                        "#16a34a",
                                                                }}
                                                                title={
                                                                    event.raceNo
                                                                        ? `${event.title} — Race ${event.raceNo}`
                                                                        : event.title
                                                                }
                                                            >
                                                                {event.title}
                                                                {event.raceNo
                                                                    ? ` · R${event.raceNo}`
                                                                    : ""}
                                                            </button>
                                                        )
                                                    )}
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            ))}
                        </div>
                    </>
                )}

                {selectedResult && (
                    <div className="mockRaceResultViewer" ref={viewerRef}>
                        <div className="mockRaceResultViewerHeader">
                            <div>
                                <h2>Mock Race Result</h2>
                                {selectedResult.start && (
                                    <span>{selectedResult.start}</span>
                                )}
                            </div>

                            <button
                                type="button"
                                className="closeResultBtn"
                                onClick={closeMockRaceResult}
                            >
                                Close
                            </button>
                        </div>

                        {resultLoading ? (
                            <div className="raceResultLoading">
                                Loading Mock Race Result...
                            </div>
                        ) : resultError ? (
                            <div className="mockRaceResultError">
                                {resultError}
                            </div>
                        ) : (
                            <iframe
                                className="mockRaceResultIframe"
                                title="Mock Race Result"
                                srcDoc={
                                    MOCK_RACE_RESULT_STYLES +
                                    resultHtml
                                }
                                sandbox="allow-same-origin"
                                onLoad={(e) => {
                                    const iframe = e.target;
                                    const doc = iframe.contentWindow?.document;
                                    if (!doc) return;

                                    const setHeight = () => {
                                        iframe.style.height =
                                            doc.documentElement.scrollHeight + "px";
                                    };

                                    // Measure again after layout/fonts settle,
                                    // same pattern as Handicaps' archive iframe.
                                    setHeight();
                                    requestAnimationFrame(setHeight);
                                    setTimeout(setHeight, 100);
                                    setTimeout(setHeight, 400);
                                }}
                            />
                        )}
                    </div>
                )}
            </div>
        </section>
    );
}