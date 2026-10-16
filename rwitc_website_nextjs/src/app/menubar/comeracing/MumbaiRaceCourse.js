"use client";

import { useState, useEffect } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getComeRacingMumbai } from "../../../services/Comeracingmumbaiservice";
import "./MumbaiRaceCourse.css";

export default function MumbaiRaceCourse() {

    const [points, setPoints] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {

        let cancelled = false;

        (async () => {

            try {

                const data = await getComeRacingMumbai();

                if (!cancelled) {
                    setPoints(data.points);
                }

            } catch (e) {

                if (!cancelled) {
                    setError(e.message || "Unable to load content.");
                }

            } finally {

                if (!cancelled) {
                    setLoading(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };

    }, []);

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">The Mahalaxmi Race Course</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    <div className="aboutContent">

                        {loading && <p>Loading…</p>}

                        {!loading && error && <p>{error}</p>}

                        {!loading && !error && points.length === 0 && (
                            <p>Content will be updated soon.</p>
                        )}

                        {!loading && !error && points.length > 0 && (
                            <ul className="memberList">
                                {points.map((item, index) => (
                                    <li key={index}>{item}</li>
                                ))}
                            </ul>
                        )}

                    </div>

                </div>

            </div>

        </section>

    );

}