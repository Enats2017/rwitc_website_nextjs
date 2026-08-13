"use client";

import { useEffect, useState } from "react";
import "./MoneyLeaders.css";

const TABS = [
    { key: "horse", label: "Horses" },
    { key: "trainer", label: "Trainers" },
    { key: "jockey", label: "Jockeys" },
    { key: "owner", label: "Owners" },
];

const TABLE_STYLES = `
<style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; margin: 0; padding: 12px; }
    table { width: 100%; border-collapse: collapse; }
    th {
        background: #16a34a;
        color: #ffffff !important;
        font-size: 14px;
        text-align: center;
        padding: 10px;
        border: 1px solid #BCBEC0;
    }
    td {
        text-align: center !important;
        padding: 10px !important;
        color: #333333 !important;
        font-weight: 600;
        border-bottom: 1px dotted #cccccc;
    }
    td:first-child {
        text-align: left !important;
    }
    tr:nth-child(even) td {
        background: #f4faf6;
    }
    html, body {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }
    html::-webkit-scrollbar, body::-webkit-scrollbar {
        display: none;
    }
</style>
`;

export default function MoneyLeaders() {
    const [activeTab, setActiveTab] = useState("horse");
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [rawHtml, setRawHtml] = useState("");
    const [updatedAt, setUpdatedAt] = useState("");

    useEffect(() => {
        async function loadData() {
            try {
                setLoading(true);
                setError(null);

                const res = await fetch(`/api/money-leaders?type=${activeTab}`, {
                    cache: "no-store",
                });

                if (!res.ok) throw new Error("Failed to load");

                const html = await res.text();
                const lastModifiedHeader = res.headers.get("X-Updated-At");

                setRawHtml(html);

                if (lastModifiedHeader) {
                    const formatted = new Date(lastModifiedHeader).toLocaleDateString(
                        "en-GB",
                        { day: "2-digit", month: "2-digit", year: "numeric" }
                    );
                    setUpdatedAt(formatted);
                } else {
                    setUpdatedAt("");
                }
            } catch (err) {
                console.error(err);
                setError("Unable to load data.");
            } finally {
                setLoading(false);
            }
        }

        loadData();
    }, [activeTab]);

    return (
        <section className="moneyLeadersPage">
            <div className="moneyLeadersOuter">

                <div className="moneyLeadersCard">

                    <div className="moneyLeadersTabs">
                        {TABS.map((tab) => (
                            <button
                                key={tab.key}
                                className={`moneyLeadersTabBtn ${activeTab === tab.key ? "active" : ""}`}
                                onClick={() => setActiveTab(tab.key)}
                            >
                                {tab.label}
                            </button>
                        ))}
                    </div>

                    <div className="moneyLeadersContentWrapper">
                        {loading && (
                            <div className="moneyLeadersLoaderOverlay">
                                <div className="moneyLeadersLoader" />
                                <p>Loading…</p>
                            </div>
                        )}

                        {!loading && error && (
                            <div className="moneyLeadersStateBox moneyLeadersStateError">
                                <p>{error}</p>
                            </div>
                        )}

                        {!loading && !error && (
                            <iframe
                                key={activeTab}
                                className="moneyLeadersFrame"
                                srcDoc={TABLE_STYLES + rawHtml}
                                title={`Money Leaders - ${activeTab}`}
                                sandbox="allow-same-origin"
                                onLoad={(e) => {
                                    const iframe = e.target;
                                    const doc = iframe.contentWindow?.document;
                                    if (!doc) return;
                                    const setHeight = () => {
                                        iframe.style.height = doc.documentElement.scrollHeight + "px";
                                    };
                                    setHeight();
                                    requestAnimationFrame(setHeight);
                                    setTimeout(setHeight, 100);
                                }}
                            />
                        )}
                    </div>

                    {!loading && !error && updatedAt && (
                        <div className="moneyLeadersUpdatedAt">
                            Updated upto {updatedAt}
                        </div>
                    )}

                </div>
            </div>
        </section>
    );
}