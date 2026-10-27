"use client";

import { useEffect, useState } from "react";
import { FaHorseHead, FaFilePdf } from "react-icons/fa";
import { getPdfLinks } from "@/services/pdfService";
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
    const [fileUrl, setFileUrl] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");
    const [isTouch, setIsTouch] = useState(false);

    useEffect(() => {
        let active = true;

        // The link is served through pdf_stream.php, so the S3 url is never exposed
        getPdfLinks()
            .then((links) => {
                if (!active) return;
                if (links.affiliated_clubs) {
                    setFileUrl(links.affiliated_clubs);
                } else {
                    setError("Affiliated clubs PDF is not available right now.");
                }
                setLoading(false);
            })
            .catch((loadError) => {
                if (!active) return;
                setError(loadError.message || "Failed to load affiliated clubs.");
                setLoading(false);
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
                    ) : loading ? (
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
                                href={fileUrl}
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
                                    href={fileUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <FaFilePdf /> Open PDF in new tab
                                </a>
                            </div>

                            <iframe
                                className="afcPdfFrame"
                                src={fileUrl}
                                title="Affiliated Clubs with RWITC"
                            />
                        </div>
                    )}
                </div>
            </div>
        </section>
    );
}