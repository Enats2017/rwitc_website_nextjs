"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";

import { getComeRacingServices } from "../../../services/Comeracingservicesservice";
import "./RaceCourseServicesOthers.css";

const DEFAULT_HEADING = "Race Course Services";

export default function RaceCourseServicesOthers() {
 
    const [paragraphs, setParagraphs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
 
    useEffect(() => {
        let active = true;
 
        async function load() {
            try {
                const data = await getComeRacingServices();
                if (active) setParagraphs(data.paragraphs);
            } catch (err) {
                if (active) setError(err.message || "Unable to load race course services details.");
            } finally {
                if (active) setLoading(false);
            }
        }
 
        load();
        return () => { active = false; };
    }, []);
 
    return (
 
        <section className="aboutPage">
 
            <div className="aboutContainer">
 
                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Race Course Services</h1>
 
                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>
 
                <div className="aboutCard">
 
                    {/* <h2 className="rcsSubHeading">
                        Making it a pleasurable outing for the enthusiasts
                    </h2> */}
 
                    <div className="aboutContent">
 
                        {loading && <p>Loading...</p>}
 
                        {!loading && error && <p>{error}</p>}
 
                        {!loading && !error && paragraphs.length === 0 && (
                            <p>Details will be updated soon.</p>
                        )}
 
                        {!loading && !error && paragraphs.map((text, index) => (
                            <p key={index}>{text}</p>
                        ))}
 
                    </div>
 
                </div>
 
            </div>
 
        </section>
 
    );
 
}