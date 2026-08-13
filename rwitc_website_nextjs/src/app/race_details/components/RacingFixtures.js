"use client";

import { useEffect, useMemo, useRef, useState } from "react";
import { FaChevronLeft, FaChevronRight, FaCalendarAlt } from "react-icons/fa";
import { getRacingFixtures } from "../../../services/racingFixturesService";
import "./RacingFixtures.css";

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

export default function RacingFixtures() {

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
            const data = await getRacingFixtures();
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
        <section className="fixturesSection">

            <h1 className="fixturesTitle">Racing Fixtures</h1>

            <div className="fixturesCard">

                <div className="fixturesToolbar">

                    <div className="fixturesMonthTitle">{monthLabel}</div>

                    <div className="fixturesNavControls">

                        <button className="fixturesTodayBtn" onClick={goToday}>
                            Today
                        </button>

                        <button className="fixturesNavBtn" onClick={goPrevMonth} aria-label="Previous month">
                            <FaChevronLeft />
                        </button>

                        <button className="fixturesNavBtn" onClick={goNextMonth} aria-label="Next month">
                            <FaChevronRight />
                        </button>

                        <div className="fixturesJumpWrapper">

                            <button
                                className="fixturesNavBtn fixturesJumpBtn"
                                onClick={openDatePicker}
                                aria-label="Jump to date"
                                title="Jump to date"
                            >
                                <FaCalendarAlt />
                            </button>

                            <input
                                ref={dateInputRef}
                                type="date"
                                className="fixturesHiddenDateInput"
                                onChange={handleDatePicked}
                                value={`${cursor.getFullYear()}-${String(cursor.getMonth() + 1).padStart(2, "0")}-01`}
                                aria-hidden="true"
                                tabIndex={-1}
                            />

                        </div>

                    </div>

                </div>

                {Object.keys(centreColors).length > 0 && (
                    <div className="fixturesLegendRow">
                        {Object.entries(centreColors).map(([centre, color]) => (
                            <span className="fixturesLegendItem" key={centre}>
                                <span
                                    className="fixturesLegendDot"
                                    style={{ backgroundColor: color }}
                                />
                                {centre}
                            </span>
                        ))}
                    </div>
                )}

                {loading ? (
                    <div className="fixturesLoading">Loading fixtures…</div>
                ) : (
                    <>
                        <div className="fixturesWeekdayRow">
                            {WEEKDAYS.map((day) => (
                                <div className="fixturesWeekdayCell" key={day}>{day}</div>
                            ))}
                        </div>

                        <div className="fixturesGrid">
                            {weeks.map((week, wi) => (
                                <div className="fixturesWeek" key={wi}>
                                    {week.map((day, di) => {

                                        const inMonth = day.getMonth() === cursor.getMonth();
                                        const isToday = isSameDay(day, today);
                                        const dayEvents = eventsByDate[dateKey(day)] || [];

                                        return (
                                            <div
                                                className={
                                                    "fixturesDayCell" +
                                                    (!inMonth ? " fixturesDayCellMuted" : "") +
                                                    (isToday ? " fixturesDayCellToday" : "")
                                                }
                                                key={di}
                                            >
                                                <span className="fixturesDayNumber">{day.getDate()}</span>

                                                <div className="fixturesDayEvents">
                                                    {dayEvents.map((event, ei) => (
                                                        <span
                                                            key={ei}
                                                            className="fixturesEventPill"
                                                            style={{
                                                                backgroundColor:
                                                                    centreColors[event.title] || "#16a34a",
                                                            }}
                                                            title={event.title}
                                                        >
                                                            {event.title}
                                                        </span>
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