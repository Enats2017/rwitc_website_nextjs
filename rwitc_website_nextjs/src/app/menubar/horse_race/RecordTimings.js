"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { API_URL } from "../../../services/api";
import "./RecordTimings.css";

export default function RecordTimings() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        let cancelled = false;

        async function load() {
            try {
                const res = await fetch(`${API_URL}/record_timings_get_api.php`);
                const json = await res.json();

                if (!res.ok || !json.success) {
                    throw new Error(json.error || "Unable to load record timings");
                }
                if (!cancelled) setData(json.data);
            } catch (err) {
                if (!cancelled) setError("Unable to load record timings.");
            } finally {
                if (!cancelled) setLoading(false);
            }
        }

        load();
        return () => { cancelled = true; };
    }, []);

    return (
        <section className="aboutPage">
            <div className="aboutContainer">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Record Timings</h1>
                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    {loading && <p className="statsMsg">Loading...</p>}
                    {!loading && error && <p className="statsMsg statsMsgError">{error}</p>}

                    {!loading && !error && data && data.mode === "html" && (
                        <div
                            className="statsTableWrap statsHtml"
                            dangerouslySetInnerHTML={{ __html: data.html }}
                        />
                    )}

                </div>

            </div>

        </section>
    );
}