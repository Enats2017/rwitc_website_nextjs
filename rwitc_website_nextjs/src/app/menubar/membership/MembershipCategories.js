"use client";

import { useEffect, useState } from "react";
import { FaHorseHead } from "react-icons/fa";
import { getMembershipCategories } from "@/services/MembershipCategoriesService";
import "./MembershipCategories.css";

export default function MembershipCategories() {
    const [content, setContent] = useState(null);
    const [error, setError] = useState("");

    useEffect(() => {
        let active = true;

        getMembershipCategories()
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
                    <h1 className="aboutHeading">Categories of Members</h1>

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
                        <p role="status">Loading membership categories...</p>
                    ) : (
                        <div
                            className="aboutContent mcRichContent"
                            dangerouslySetInnerHTML={{ __html: content.content }}
                        />
                    )}
                </div>

            </div>

        </section>

    );

}