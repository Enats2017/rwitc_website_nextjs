"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { FaChevronLeft, FaChevronRight, FaCalendarAlt } from "react-icons/fa";
import { getDividends } from "../../../services/dividendsService";
import "./Dividends.css";

const WEEKDAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const COLOR_PALETTE = [
    "#16a34a", "#2563eb", "#db2777", "#d97706",
    "#7c3aed", "#0891b2", "#dc2626", "#65a30d",
];

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

    return weeks;

}

export default function Dividends() {

    const [events, setEvents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [cursor, setCursor] = useState(() => {
        const now = new Date();
        return new Date(now.getFullYear(), now.getMonth(), 1);
    });

    const dateInputRef = useRef(null);

    useEffect(() => {

        async function loadData() {
            setLoading(true);
            const data = await getDividends();
            setEvents(data);
            setLoading(false);
        }

        loadData();

    }, []);

    const eventsByDate = useMemo(() => {

        const map = {};

        events.forEach((event) => {
            const key = event.start;
            if (!map[key]) map[key] = [];
            map[key].push(event);
        });

        return map;

    }, [events]);

    const centreColors = useMemo(() => {

        const uniqueCentres = [...new Set(events.map((e) => e.title))];
        const colorMap = {};

        uniqueCentres.forEach((centre, index) => {
            colorMap[centre] = COLOR_PALETTE[index % COLOR_PALETTE.length];
        });

        return colorMap;

    }, [events]);

    const weeks = useMemo(
        () => buildMonthMatrix(cursor.getFullYear(), cursor.getMonth()),
        [cursor]
    );

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

    return (
        <section className="dividendsSection">

            <h1 className="dividendsTitle">Tote Dividends</h1>

            <div className="dividendsCard">

                <div className="calendarToolbar">

                    <div className="monthTitle">{monthLabel}</div>

                    <div className="navControls">

                        <button className="todayBtn" onClick={goToday}>
                            Today
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

                {Object.keys(centreColors).length > 0 && (
                    <div className="legendRow">
                        {Object.entries(centreColors).map(([centre, color]) => (
                            <span className="legendItem" key={centre}>
                                <span
                                    className="legendDot"
                                    style={{ backgroundColor: color }}
                                />
                                {centre}
                            </span>
                        ))}
                    </div>
                )}

                {loading ? (
                    <div className="dividendsLoading">Loading dividends…</div>
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
                                                        
                                                        <a    key={ei}
                                                            href={event.url}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="eventPill"
                                                            style={{
                                                                backgroundColor:
                                                                    centreColors[event.title] || "#16a34a",
                                                            }}
                                                            title={event.title}
                                                        >
                                                            {event.title}
                                                        </a>
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