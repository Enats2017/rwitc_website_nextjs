"use client";

import { useEffect, useState } from "react";
import { FaHorseHead, FaFilePdf } from "react-icons/fa";
import { getAffiliatedClubs } from "@/services/AffiliatedClubsService";
import "./AffiliatedClubs.css";

export default function AffiliatedClubs() {
    const [data, setData] = useState(null);
    const [error, setError] = useState("");

    useEffect(() => {
        let active = true;

        getAffiliatedClubs()
            .then((result) => {
                if (active) setData(result);
            })
            .catch((loadError) => {
                if (active) setError(loadError.message);
            });

        return () => {
            active = false;
        };
    }, []);

    return (
        <section className="aboutPage">
            <div className="aboutContainer">
                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Affiliated Clubs with RWITC</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">
                    {error ? (
                        <p role="alert">{error}</p>
                    ) : !data ? (
                        <p role="status">Loading affiliated clubs...</p>
                    ) : (
                        <div className="aboutContent">
                            <div className="afcToolbar">
                                <a
                                    className="afcOpenBtn"
                                    href={data.fileUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <FaFilePdf /> Open PDF in new tab
                                </a>
                            </div>

                            <iframe
                                className="afcPdfFrame"
                                src={data.fileUrl}
                                title="Affiliated Clubs with RWITC"
                            />
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}
