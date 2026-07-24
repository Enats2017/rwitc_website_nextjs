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

    return (

        <section className="sponsorsSection">

            <div className="sponsorsContainer">

                <div className="sponsorsHeading">

                    <span>
                        OUR SPONSORS & TRUSTED PARTNERS
                    </span>

                </div>

                <div className="sponsorsSlider">

                    <div className="sponsorsTrack">

                        {

                            sponsors.map((item) => (

                                <div
                                    className="sponsorItem"
                                    key={item.id}
                                >

                                    <img

                                        src={`${UPLOAD_URL}/${item.source}`}

                                        alt={item.title}

                                        draggable="false"

                                    />

                                </div>

                            ))

                        }

                        {

                            sponsors.map((item) => (

                                <div
                                    className="sponsorItem"
                                    key={`duplicate-${item.id}`}
                                >

                                    <img

                                        src={`${UPLOAD_URL}/${item.source}`}

                                        alt={item.title}

                                        draggable="false"

                                    />

                                </div>

                            ))

                        }

                    </div>

                </div>

            </div>

        </section>

    );

}