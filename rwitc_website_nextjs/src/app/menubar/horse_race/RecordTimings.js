"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getPdfLinks } from "../../../services/pdfService";
import "./RecordTimings.css";

// Returns the lowercase file extension from a url (example: "htm", "pdf")
function getExtension(url) {
    const clean = decodeURIComponent(String(url).split("?")[0]);
    const name = clean.substring(clean.lastIndexOf("/") + 1);
    return (name.split(".").pop() || "").toLowerCase();
}

// Decodes the file bytes. Tries UTF-8 first, falls back to windows-1252 for old HTM files.
function decodeHtmlBytes(buffer) {
    const utf8 = new TextDecoder("utf-8").decode(buffer);
    if (utf8.indexOf("\uFFFD") === -1) {
        return utf8;
    }
    return new TextDecoder("windows-1252").decode(buffer);
}

// Takes a full HTML document string and returns only what is safe to show inside the page:
// the <style> tags and the <body> content (scripts are removed).
function extractHtmlContent(htmlString) {
    const doc = new DOMParser().parseFromString(htmlString, "text/html");
    doc.querySelectorAll("script").forEach((el) => el.remove());

    let styles = "";
    doc.querySelectorAll("style").forEach((el) => {
        styles += el.outerHTML;
        el.remove();
    });

    return styles + doc.body.innerHTML;
}

export default function RecordTimings() {
    const [fileUrl, setFileUrl] = useState("");
    const [fileType, setFileType] = useState("");   // "html" | "pdf" | "image" | "other"
    const [html, setHtml] = useState("");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState("");

    useEffect(() => {
        let cancelled = false;

        async function load() {
            try {
                // Latest uploaded file link (same service the menu uses)
                const links = await getPdfLinks();
                const url = links["record_timings"];

                if (!url || typeof url !== "string") {
                    if (!cancelled) setError("No record timings file has been uploaded yet.");
                    return;
                }

                const ext = getExtension(url);

                if (ext === "htm" || ext === "html") {
                    const res = await fetch(url);
                    if (!res.ok) { throw new Error("Failed to load file"); }
                    const buffer = await res.arrayBuffer();
                    const content = extractHtmlContent(decodeHtmlBytes(buffer));
                    if (!cancelled) {
                        setHtml(content);
                        setFileType("html");
                    }
                } else if (ext === "pdf") {
                    if (!cancelled) setFileType("pdf");
                } else if (["jpg", "jpeg", "png"].indexOf(ext) !== -1) {
                    if (!cancelled) setFileType("image");
                } else {
                    if (!cancelled) setFileType("other");
                }

                if (!cancelled) setFileUrl(url);
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

                    {/* HTM / HTML file: same container and class as before, so CSS stays the same */}
                    {!loading && !error && fileType === "html" && (
                        <div
                            className="statsTableWrap statsHtml"
                            dangerouslySetInnerHTML={{ __html: html }}
                        />
                    )}

                    {/* PDF file */}
                    {!loading && !error && fileType === "pdf" && (
                        <iframe
                            src={fileUrl}
                            title="Record Timings"
                            style={{ width: "100%", height: "80vh", border: "none" }}
                        />
                    )}

                    {/* Image file */}
                    {!loading && !error && fileType === "image" && (
                        <img
                            src={fileUrl}
                            alt="Record Timings"
                            style={{ maxWidth: "100%", height: "auto", display: "block", margin: "0 auto" }}
                        />
                    )}

                    {/* Any other file (doc, xls, zip, etc.): download link */}
                    {!loading && !error && fileType === "other" && (
                        <p className="statsMsg">
                            <a href={fileUrl} target="_blank" rel="noopener noreferrer">
                                Click here to open / download the file
                            </a>
                        </p>
                    )}

                </div>
            </div>
        </section>
    );
}