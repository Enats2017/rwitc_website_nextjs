"use client";

import { useEffect, useState } from "react";
import { FaHorseHead, FaFilePdf } from "react-icons/fa";
import { getAffiliatedClubs } from "@/services/AffiliatedClubsService";
import "./AffiliatedClubs.css";

// Detects phones/tablets where inline PDF iframes cannot be scrolled
function detectTouchDevice() {
    if (typeof window === "undefined") return false;

    const ua = navigator.userAgent || "";
    const isMobileUA = /Android|iPhone|iPad|iPod|Mobile|Tablet|Silk|Kindle/i.test(ua);
    // iPadOS 13+ reports itself as Macintosh, so check touch points too
    const isIPadOS = /Macintosh/i.test(ua) && navigator.maxTouchPoints > 1;
    const isCoarsePointer =
        window.matchMedia && window.matchMedia("(pointer: coarse)").matches;
    const isNarrow = window.innerWidth <= 1024;

    return isMobileUA || isIPadOS || (isCoarsePointer && isNarrow);
}

export default function AffiliatedClubs() {
    const [data, setData] = useState(null);
    const [error, setError] = useState("");
    const [isTouch, setIsTouch] = useState(false);

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

    useEffect(() => {
        const update = () => setIsTouch(detectTouchDevice());
        update();
        window.addEventListener("resize", update);
        return () => window.removeEventListener("resize", update);
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
                    ) : isTouch ? (
                        <div className="afcMobileBox">
                            <FaFilePdf className="afcMobileIcon" />
                            <h2 className="afcMobileTitle">Affiliated Clubs with RWITC</h2>
                            <p className="afcMobileText">
                                Tap the button below to view the full PDF. It opens in
                                your device's PDF viewer where you can scroll and zoom.
                            </p>
                            <a
                                className="afcOpenBtn afcOpenBtnLarge"
                                href={data.fileUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <FaFilePdf /> Open PDF
                            </a>
                        </div>
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