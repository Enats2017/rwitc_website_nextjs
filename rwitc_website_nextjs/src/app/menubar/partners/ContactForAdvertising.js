"use client";

import { useEffect, useState } from "react";
import { FaHorseHead, FaUser, FaPhoneAlt, FaEnvelope } from "react-icons/fa";
import { getPartnersContact } from "../../../services/clubService";
import "./ContactForAdvertising.css";

export default function ContactForAdvertising() {
    const [contacts, setContacts] = useState([]);

    useEffect(() => {
        let active = true;

        getPartnersContact()
            .then((data) => {
                if (!active || !Array.isArray(data?.contacts)) return;
                setContacts(data.contacts);
            })
            .catch(() => {});

        return () => {
            active = false;
        };
    }, []);

    return (
        <section className="aboutPage">
            <div className="aboutContainer">
                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">For Advertising &amp; Sponsorships Contact</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">
                    {contacts.map((c, i) => (
                        <div className="contactBlock" key={i}>
                            <div className="contactIcon">
                                <FaUser />
                            </div>

                            <div className="contactDetails">
                                <h3 className="contactName">{c.name}</h3>

                                {c.position && <p className="contactRole">{c.position}</p>}

                                {c.phone && (
                                    <div className="contactLine">
                                        <FaPhoneAlt className="contactLineIcon" />
                                        <span>{c.phone}</span>
                                    </div>
                                )}

                                {c.email && (
                                    <div className="contactLine">
                                        <FaEnvelope className="contactLineIcon" />
                                        <a href={`mailto:${c.email}`}>{c.email}</a>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}