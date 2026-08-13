"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { useRouter } from "next/navigation";
import { FaChevronLeft, FaChevronRight, FaCalendarAlt } from "react-icons/fa";
import { getArchives } from "../../../services/archivesService";
import "./Archives.css";

const WEEKDAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

// Fixed category colors (matches the live rwitc.com calendar, not a
// random per-title palette like Dividends).
const CLASS_COLORS = {
    trackwork: "#0b6d2a",
    handicaps: "#2563eb",
    acceptances: "#2563eb",
    declarations: "#2563eb",
    raceresults: "#8b1e1e",
    "rating-change": "#8b1e1e",
    "raceday-report": "#8b1e1e",
};

// Maps the API's className + PHP-style url (e.g. "erp_handcaps.php?date=..."
// or "trackwork.php?id=...") to the existing /race_details route + type.
const CLASS_TO_TYPE = {
    trackwork: "trackWork",
    handicaps: "handicaps",
    acceptances: "acceptances",
    declarations: "declarations",
    raceresults: "raceResults",
    "rating-change": "ratingChange",
    "raceday-report": "raceDayReport",
};

function dateKey(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
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

        for (let d = 0; d < 7; d++) {
            const current = new Date(gridStart);
            current.setDate(gridStart.getDate() + w * 7 + d);
            week.push(current);
        }

        weeks.push(week);

    }

    return { weeks, gridStart };

}

// Pulls a query param (e.g. "id" or "date") out of a PHP-style url
// string like "trackwork.php?id=4793" without needing a real base URL.
function getUrlParam(phpUrl, key) {
    if (!phpUrl) return null;
    const query = phpUrl.split("?")[1] || "";
    const params = new URLSearchParams(query);
    return params.get(key);
}

function buildHref(event) {

    const type = CLASS_TO_TYPE[event.className];

    if (!type) return null;

    if (event.className === "trackwork") {
        const id = getUrlParam(event.url, "id");
        return `/race_details?type=${type}&id=${id}`;
    }

    return `/race_details?type=${type}&date=${event.start}`;

}

export default function Archives() {

    const router = useRouter();

    const [events, setEvents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [cursor, setCursor] = useState(() => {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), 1);
    });

    const dateInputRef = useRef(null);

    const { weeks, gridStart } = useMemo(
        () => buildMonthMatrix(cursor.getFullYear(), cursor.getMonth()),
        [cursor]
    );

    const gridEnd = useMemo(() => {
        const end = new Date(gridStart);
        end.setDate(gridStart.getDate() + 41);
        return end;
    }, [gridStart]);

    useEffect(() => {

        async function loadData() {

            setLoading(true);

            const data = await getArchives(
                dateKey(gridStart),
                dateKey(gridEnd)
            );

            setEvents(data);
            setLoading(false);

        }

        loadData();

    }, [gridStart, gridEnd]);

    const eventsByDate = useMemo(() => {

        const map = {};

        events.forEach((event) => {
            const key = event.start.split(" ")[0];
            if (!map[key]) map[key] = [];
            map[key].push(event);
        });

        return map;

    }, [events]);

    const monthLabel = cursor.toLocaleDateString("en-GB", {
        month: "long",
        year: "numeric",
    });

    const today = new Date();

    function goPrevMonth() {
        setCursor((prev) => new Date(prev.getFullYear(), prev.getMonth() - 1, 1));
    }

    function goNextMonth() {
        setCursor((prev) => new Date(prev.getFullYear(), prev.getMonth() + 1, 1));
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

        const [year, month, day] = value.split("-").map(Number);

        setCursor(new Date(year, month - 1, 1));

    }

    function handleEventClick(event) {
        const href = buildHref(event);
        if (href) router.push(href);
    }

    return (
        <section className="archivesSection">

            <div className="archivesBadgeWrap">
                <span className="archivesBadge">Archives</span>
            </div>

            <div className="archivesCard">

                <div className="calendarToolbar">

                    <div className="monthTitle">{monthLabel}</div>

                    <div className="navControls">

                        <button className="todayBtn" onClick={goToday}>
                            today
                        </button>

                        <button className="navBtn" onClick={goPrevMonth} aria-label="Previous month">
                            <FaChevronLeft />
                        </button>

                        <button className="navBtn" onClick={goNextMonth} aria-label="Next month">
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
                                value={`${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, "0")}-01`}
                                aria-hidden="true"
                                tabIndex={-1}
                            />

                        </div>

                    </div>

                </div>

                {loading ? (
                    <div className="archivesLoading">Loading archives…</div>
                ) : (
                    <>
                        <div className="weekdayRow">
                            {WEEKDAYS.map((day) => (
                                <div className="weekdayCell" key={day}>{day}</div>
                            ))}
                        </div>

                        <div className="calendarGrid">
                            {weeks.map((week, wi) => (
                                <div className="calendarWeek" key={wi}>
                                    {week.map((day, di) => {

                                        const inMonth = day.getMonth() === cursor.getMonth();
                                        const isToday = isSameDay(day, today);
                                        const dayEvents = eventsByDate[dateKey(day)] || [];

                                        return (
                                            <div
                                                className={
                                                    "dayCell" +
                                                    (!inMonth ? " dayCellMuted" : "") +
                                                    (isToday ? " dayCellToday" : "")
                                                }
                                                key={di}
                                            >
                                                <span className="dayNumber">{day.getDate()}</span>

                                                <div className="dayEvents">
                                                    {dayEvents.map((event, ei) => (

                                                        <button
                                                            key={ei}
                                                            type="button"
                                                            className="eventPill"
                                                            style={{
                                                                backgroundColor:
                                                                    CLASS_COLORS[event.className] || "#16a34a",
                                                            }}
                                                            title={event.title}
                                                            onClick={() => handleEventClick(event)}
                                                        >
                                                            {event.title}
                                                        </button>

                                                    ))}
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