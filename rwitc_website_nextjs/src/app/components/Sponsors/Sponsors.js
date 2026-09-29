"use client";
import { UPLOAD_URL } from "../../../services/api";
import { useEffect, useState } from "react";
import "./Sponsors.css";
import { getSponsors } from "../../../services/sponsorService";

export default function Sponsors() {
    const [sponsors, setSponsors] = useState([]);

    useEffect(() => {
        async function loadSponsors() {
            const data = await getSponsors();
            setSponsors(data);
        }
        loadSponsors();
    }, []);

    const getSrc = (item) =>
        item.source.startsWith("http")
            ? item.source
            : `${UPLOAD_URL}/sponsors/${item.source}`;

    return (
        <section className="rwSponsorsSection">
            <div className="rwSponsorsContainer">
                <div className="rwSponsorsHeading">
                    <span className="rwSponsorsBadge">SPONSORS</span>
                </div>
                <div className="rwSponsorsSlider">
                    <div className="rwSponsorsTrack">
                        {sponsors.map((item) => (
                            <div className="rwSponsorsItem" key={item.id}>
                                <img
                                    className="rwSponsorsLogo"
                                    src={getSrc(item)}
                                    alt={item.title}
                                    draggable="false"
                                />
                            </div>
                        ))}
                        {/* duplicate set for the seamless loop */}
                        {sponsors.map((item) => (
                            <div
                                className="rwSponsorsItem"
                                key={`duplicate-${item.id}`}
                                aria-hidden="true"
                            >
                                <img
                                    className="rwSponsorsLogo"
                                    src={getSrc(item)}
                                    alt=""
                                    draggable="false"
                                />
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}