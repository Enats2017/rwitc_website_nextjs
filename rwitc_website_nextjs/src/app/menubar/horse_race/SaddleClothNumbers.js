"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { API_URL } from "../../../services/api";
import "./SaddleClothNumbers.css";

export default function SaddleClothNumbers() {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        let cancelled = false;

        async function load() {
            try {
                const res = await fetch(`${API_URL}/saddle_cloth_get_api.php`);
                const json = await res.json();

                if (!res.ok || !json.success) {
                    throw new Error(json.error || "Unable to load saddle cloth data");
                }
                if (!cancelled) setData(json.data);
            } catch (err) {
                if (!cancelled) setError("Unable to load saddle cloth data.");
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
                    <h1 className="aboutHeading">Saddle Cloth</h1>
                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                {loading && (
                    <div className="aboutCard">
                        <p className="statsMsg">Loading...</p>
                    </div>
                )}

                {!loading && error && (
                    <div className="aboutCard">
                        <p className="statsMsg statsMsgError">{error}</p>
                    </div>
                )}

                {!loading && !error && data?.sections?.map((section, index) => (
                    <div
                        className="aboutCard saddleSection statsHtml"
                        key={section.color || index}
                        dangerouslySetInnerHTML={{ __html: section.html }}
                    />
                ))}

            </div>
        </section>
    );
}