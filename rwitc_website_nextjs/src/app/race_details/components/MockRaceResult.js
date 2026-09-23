"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { FaChevronLeft, FaChevronRight, FaCalendarAlt } from "react-icons/fa";
import { API_URL } from "../../../services/api";
import "./MockRaceResult.css";

const WEEKDAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const COLOR_PALETTE = [
    "#16a34a", "#2563eb", "#db2777", "#d97706",
    "#7c3aed", "#0891b2", "#dc2626", "#65a30d",
];

/*
 * Styles injected into the single Mock Race Result document view.
 * Rendered inside a sandboxed iframe (srcDoc) so these styles stay
 * isolated and can never clash with the app's own global CSS —
 * exactly like Handicaps' ARCHIVE_STYLES block.
 */
const MOCK_RESULT_DOCUMENT_STYLES = `
<style>
    html,
    body {
        margin: 0;
        padding: 0;
        background: #ffffff;
        color: #222222;
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

    #leftArea {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
    }

    .pageHeader {
        width: 100%;
    }

    .pageHeading {
        text-align: center;
    }

    .subHeading {
        color: #111111;
        font-weight: 700;
        text-align: center;
        font-size: 14px;
        line-height: 1.5;
        margin: 5px 0;
    }

    .pageHeading h3,
    h1,
    h2,
    h3 {
        color: #00843d;
        margin: 10px 0;
    }

    .pageHeading h3 {
        font-size: 22px;
        font-weight: 800;
        text-align: center;
    }

    .download {
        display: none !important;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    .contentTable {
        width: 100% !important;
        max-width: 100% !important;
        margin: 12px auto 0;
        border-collapse: collapse !important;
    }

    .contentTable th {
        background: #11a14e !important;
        color: #ffffff !important;
        border: 1px solid #bcbec0 !important;
        font-size: 14px !important;
        font-weight: 700 !important;
        text-align: center !important;
        padding: 8px !important;
        vertical-align: middle !important;
    }

    .contentTable td {
        background: #ffffff !important;
        color: #333333 !important;
        border: 1px solid #dee2e6 !important;
        padding: 8px !important;
        font-weight: 600;
        vertical-align: middle !important;
    }

    .contentTable tr:nth-child(even) td {
        background: #f8faf9 !important;
    }

    .contentTable .darkGrey {
        background: #eaf5ed !important;
        color: #222222 !important;
        font-weight: 700 !important;
    }

    .contentTable .alignLeft {
        text-align: left !important;
    }

    .contentTable .racehead,
    .racehead {
        background: #11a14e !important;
        color: #ffffff !important;
        padding: 8px !important;
        font-size: 15px !important;
        font-weight: 700 !important;
    }

    .contentTable span {
        line-height: 1.45;
    }

    .clearfix::after {
        content: "";
        display: table;
        clear: both;
    }

    @media (max-width: 768px) {
        body {
            padding: 10px;
        }

        #leftArea {
            min-width: 700px;
        }

        .contentTable {
            min-width: 700px !important;
        }

        .contentTable th {
            font-size: 12px !important;
            padding: 6px !important;
        }

        .contentTable td {
            font-size: 11px !important;
            padding: 6px !important;
        }

        .subHeading {
            font-size: 12px;
        }

        .pageHeading h3 {
            font-size: 18px;
        }
    }
</style>
`;

/*
 * Builds the URL used to actually fetch one Mock Race Result's HTML.
 *
 *  - DB/S3 sourced events: rebuild the fetch URL from our own,
 *    already-correct API_URL constant + id, instead of trusting the
 *    absolute URL the PHP API generated (which can end up as
 *    http:// on some proxy setups and get blocked as Mixed Content).
 *  - Local-file sourced events: use the URL the API gave us, but
 *    force it to https:// as a safety net.
 */
function buildMockDetailFetchUrl({ id, src }) {
    if (id) {
        return `${API_URL}/mock_race_result_calendar_api.php?open=1&id=${encodeURIComponent(
            id
        )}`;
    }

    if (src) {
        try {
            const decoded = decodeURIComponent(src);
            return decoded.replace(/^http:\/\//i, "https://");
        } catch (error) {
            console.error("Mock Race Result: bad src param", error);
            return "";
        }
    }

    return "";
}

/*
 * Builds the internal route for a calendar event, mirroring how
 * Archives.js sends handicaps/declarations/etc. into /race_details.
 */
function buildMockHref(event) {
    const params = new URLSearchParams();

    params.set("type", "mockRaceResults");
    params.set(
        "title",
        event?.title ||
            (event?.mockSequence
                ? `Mock Race ${event.mockSequence}`
                : "Mock Race Result")
    );

    if (event?.source === "run_race_details" && event?.id) {
        params.set("id", String(event.id));
    } else if (event?.url) {
        params.set("src", encodeURIComponent(event.url));
    }

    return `/race_details?${params.toString()}`;
}

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

/**
 * Fetch Mock Race Result calendar events directly from the API.
 */
async function fetchMockRaceResults(year, month) {
    const monthNumber = month + 1;

    const response = await fetch(
        `${API_URL}/mock_race_result_calendar_api.php?year=${year}&month=${monthNumber}`,
        {
            method: "GET",
            cache: "no-store",
        }
    );

    if (!response.ok) {
        throw new Error(
            `Mock Race Result API failed: ${response.status}`
        );
    }

    const json = await response.json();

    if (!json.success) {
        throw new Error(
            json.error || "Unable to fetch mock race results."
        );
    }

    return Array.isArray(json.data) ? json.data : [];
}

/*
 * Single Mock Race Result document view.
 * Fetches one race's HTML and renders it inside a sandboxed iframe —
 * same technique, same look as Handicaps' archive HTML view.
 */
function MockRaceResultDetail({ id, src, title }) {
    const router = useRouter();

    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [html, setHtml] = useState("");

    useEffect(() => {
        let cancelled = false;

        async function loadDetail() {
            try {
                setLoading(true);
                setError("");

                const fetchUrl = buildMockDetailFetchUrl({ id, src });

                if (!fetchUrl) {
                    throw new Error("Mock Race Result source is missing.");
                }

                const response = await fetch(fetchUrl, {
                    method: "GET",
                    cache: "no-store",
                    headers: {
                        Accept: "text/html",
                    },
                });

                if (!response.ok) {
                    throw new Error(
                        `Unable to load result (${response.status})`
                    );
                }

                const rawHtml = await response.text();

                if (!cancelled) {
                    setHtml(rawHtml);
                }
            } catch (err) {
                console.error("Mock Race Result Detail Error:", err);

                if (!cancelled) {
                    setError(
                        err?.message ||
                            "Unable to load this Mock Race Result."
                    );
                }
            } finally {
                if (!cancelled) {
                    setLoading(false);
                }
            }
        }

        loadDetail();

        return () => {
            cancelled = true;
        };
    }, [id, src]);

    return (
        <section className="raceResultSection docPage">
            <div className="docBadgeWrap">
                <span className="docBadge">
                    {title || "Mock Race Result"}
                </span>
            </div>

            <div className="docContainer">
                <button
                    type="button"
                    className="docBackBtn"
                    onClick={() => router.back()}
                >
                    ← Back to calendar
                </button>

                {loading && (
                    <div className="docStateBox">
                        <div className="docLoader" />
                        <p>Loading result…</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="docStateBox docStateError">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && !html.trim() && (
                    <div className="docStateBox">
                        <p>No data found for this result.</p>
                    </div>
                )}

                {!loading && !error && html.trim() && (
                    <iframe
                        className="docArchiveHtml"
                        srcDoc={MOCK_RESULT_DOCUMENT_STYLES + html}
                        title={title || "Mock Race Result"}
                        sandbox="allow-same-origin"
                        onLoad={(e) => {
                            const iframe = e.target;
                            const doc = iframe.contentWindow?.document;
                            if (!doc) return;

                            const setHeight = () => {
                                iframe.style.height =
                                    doc.documentElement.scrollHeight + "px";
                            };

                            setHeight();
                            requestAnimationFrame(setHeight);
                            setTimeout(setHeight, 100);
                        }}
                    />
                )}
            </div>
        </section>
    );
}

export default function MockRaceResult() {
    const router = useRouter();
    const searchParams = useSearchParams();

    const detailId = searchParams.get("id");
    const detailSrc = searchParams.get("src");
    const detailTitle = searchParams.get("title");

    // Came here via a calendar click → show the single document,
    // same as Handicaps rendering one date's HTML.
    if (detailId || detailSrc) {
        return (
            <MockRaceResultDetail
                id={detailId}
                src={detailSrc}
                title={detailTitle}
            />
        );
    }

    // Otherwise: the calendar itself.
    const [events, setEvents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    const [cursor, setCursor] = useState(() => {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), 1);
    });

    const dateInputRef = useRef(null);

    useEffect(() => {
        let cancelled = false;

        async function loadData() {
            try {
                setLoading(true);
                setError("");

                const data = await fetchMockRaceResults(
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

            if (!key) {
                return;
            }

            if (!map[key]) {
                map[key] = [];
            }

            map[key].push(event);
        });

        Object.keys(map).forEach((key) => {
            map[key].sort((a, b) => {
                const seqA =
                    Number.isFinite(Number(a.mockSequence))
                        ? Number(a.mockSequence)
                        : Number.MAX_SAFE_INTEGER;

                const seqB =
                    Number.isFinite(Number(b.mockSequence))
                        ? Number(b.mockSequence)
                        : Number.MAX_SAFE_INTEGER;

                if (seqA !== seqB) {
                    return seqA - seqB;
                }

                return String(a.id ?? "").localeCompare(
                    String(b.id ?? "")
                );
            });
        });

        return map;
    }, [events]);

    const centreColors = useMemo(() => {
        const uniqueTypes = [
            ...new Set(events.map((event) => event.title).filter(Boolean)),
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
                new Date(prev.getFullYear(), prev.getMonth() - 1, 1)
        );
    }

    function goNextMonth() {
        setCursor(
            (prev) =>
                new Date(prev.getFullYear(), prev.getMonth() + 1, 1)
        );
    }

    function goToday() {
        const now = new Date();
        setCursor(new Date(now.getFullYear(), now.getMonth(), 1));
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

        setCursor(new Date(year, month - 1, 1));
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
                            type="button"
                        >
                            Today
                        </button>

                        <button
                            className="navBtn"
                            onClick={goPrevMonth}
                            type="button"
                            aria-label="Previous month"
                        >
                            <FaChevronLeft />
                        </button>

                        <button
                            className="navBtn"
                            onClick={goNextMonth}
                            type="button"
                            aria-label="Next month"
                        >
                            <FaChevronRight />
                        </button>

                        <div className="jumpWrapper">
                            <button
                                className="navBtn jumpBtn"
                                onClick={openDatePicker}
                                type="button"
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
                                <span className="legendItem" key={type}>
                                    <span
                                        className="legendDot"
                                        style={{ backgroundColor: color }}
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
                            <div className="raceResultError">{error}</div>
                        )}

                        <div className="weekdayRow">
                            {WEEKDAYS.map((day) => (
                                <div className="weekdayCell" key={day}>
                                    {day}
                                </div>
                            ))}
                        </div>

                        <div className="calendarGrid">
                            {weeks.map((week, wi) => (
                                <div className="calendarWeek" key={wi}>
                                    {week.map((day, di) => {
                                        const inMonth =
                                            day.getMonth() ===
                                            cursor.getMonth();

                                        const isToday = isSameDay(
                                            day,
                                            today
                                        );

                                        const dayEvents =
                                            eventsByDate[dateKey(day)] ||
                                            [];

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
                                                        (event, ei) => {
                                                            const eventTitle =
                                                                event?.title ||
                                                                (event?.mockSequence
                                                                    ? `Mock Race ${event.mockSequence}`
                                                                    : "Mock Race Result");

                                                            return (
                                                                <button
                                                                    key={
                                                                        event.id ??
                                                                        `${event.start}-${event.mockSequence ?? ei}`
                                                                    }
                                                                    type="button"
                                                                    className="eventPill"
                                                                    onClick={() =>
                                                                        router.push(
                                                                            buildMockHref(
                                                                                event
                                                                            )
                                                                        )
                                                                    }
                                                                    style={{
                                                                        backgroundColor:
                                                                            centreColors[
                                                                                eventTitle
                                                                            ] ||
                                                                            "#16a34a",
                                                                    }}
                                                                    title={
                                                                        event?.raceNo
                                                                            ? `${eventTitle} — Race ${event.raceNo}`
                                                                            : eventTitle
                                                                    }
                                                                >
                                                                    {eventTitle}
                                                                    {event?.raceNo
                                                                        ? ` · R${event.raceNo}`
                                                                        : ""}
                                                                </button>
                                                            );
                                                        }
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
            </div>
        </section>
    );
}