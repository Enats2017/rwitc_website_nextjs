"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useSearchParams } from "next/navigation";
import { FaHorseHead, FaArrowLeft } from "react-icons/fa";
// import { API_URL } from "../../../services/api";
import { SITE_URL } from "../../../services/api";
import "./StewardsReport.css";

export default function StewardsReport() {

    const searchParams = useSearchParams();
    const id = searchParams.get("id");

    const [html, setHtml] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!id) {
            setLoading(false);
            return;
        }

        let cancelled = false;

        async function loadReport() {
            setLoading(true);
            try {
                // const res = await fetch(`${API_URL}/stewardsReport.php?api=1&id=${id}`);
                const res = await fetch(`${SITE_URL}/horseracing/stewardsReport.php?api=1&id=${id}`);
                const text = await res.text();
                if (!cancelled) setHtml(res.ok ? text : null);
            } catch (err) {
                if (!cancelled) setHtml(null);
            }
            if (!cancelled) setLoading(false);
        }

        loadReport();

        return () => { cancelled = true; };
    }, [id]);

    return (
        <section className="aboutPage">
            <div className="aboutContainer">
                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Stewards Notices</h1>
                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">
                    <Link href="/menubar?type=noticefromstewards" className="noticeBackLink">
                        <FaArrowLeft /> Back
                    </Link>

                    {loading ? (
                        <div className="noticeNotFound">Loading...</div>
                    ) : !html ? (
                        <div className="noticeNotFound">Notice not found.</div>
                    ) : (
                        <div
                            className="noticeDetailWrap"
                            dangerouslySetInnerHTML={{ __html: html }}
                        />
                    )}
                </div>
            </div>
        </section>
    );
}