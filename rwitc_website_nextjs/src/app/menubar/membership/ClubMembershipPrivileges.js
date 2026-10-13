"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getClubMembershipPrivileges } from "@/services/ClubMembershipPrivilegesService";
import "./ClubMembershipPrivileges.css";

export default function ClubMembershipPrivileges() {
    const [content, setContent] = useState(null);
    const [error, setError] = useState("");

    useEffect(() => {
        let active = true;

        getClubMembershipPrivileges()
            .then((data) => {
                if (active) setContent(data);
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
                    <h1 className="aboutHeading">Club Member Privileges</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="aboutCard">

                    {error ? (
                        <p role="alert">{error}</p>
                    ) : !content ? (
                        <p role="status">Loading membership privileges...</p>
                    ) : (
                        <div
                            className="aboutContent cmpRichContent"
                            dangerouslySetInnerHTML={{ __html: content.content }}
                        />
                    )}

                </div>

            </div>

        </section>

    );

}