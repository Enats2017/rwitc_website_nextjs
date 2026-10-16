
"use client";

import { useState, useEffect } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getComeRacingOverview } from "../../../services/Comeracingoverviewservice";
import "./ComeRacingOverview.css";

export default function ComeRacingOverview() {

    const [paragraphs, setParagraphs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {

        let cancelled = false;

        (async () => {

            try {

                const data = await getComeRacingOverview();

                if (cancelled) return;

                // a blank line in the admin text = a new paragraph
                setParagraphs(
                    data.content
                        .split(/\n\s*\n/)
                        .map((p) => p.trim())
                        .filter(Boolean)
                );

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
                    <h1 className="aboutHeading">Enjoy the thrills and spill</h1>

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

                        {!loading && !error && paragraphs.length === 0 && (
                            <p>Content will be updated soon.</p>
                        )}

                        {!loading && !error && paragraphs.map((p, i) => (
                            <p key={i}>{p}</p>
                        ))}

                    </div>

                </div>

            </div>

        </section>

    );

}