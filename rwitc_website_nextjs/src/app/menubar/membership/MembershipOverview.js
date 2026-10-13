"use client";

import { useState, useEffect } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getMembershipImportance } from "../../../services/Membershipimportanceservice";
import "./MembershipOverview.css";

export default function MembershipOverview() {

    const [paragraphs, setParagraphs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {

        let cancelled = false;

        (async () => {

            const data = await getMembershipImportance();

            if (cancelled) return;

            if (data.error) {
                setError(data.error);
            } else if (data.exists) {
                // a blank line in the admin text = a new paragraph
                setParagraphs(
                    data.text
                        .split(/\n\s*\n/)
                        .map((p) => p.trim())
                        .filter(Boolean)
                );
            }

            setLoading(false);
        })();

        return () => {
            cancelled = true;
        };

    }, []);

    return (

        <section className="aboutPage">

            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Importance of being a Member</h1>

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