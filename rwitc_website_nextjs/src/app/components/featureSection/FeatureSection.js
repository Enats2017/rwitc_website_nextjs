"use client";

import { useEffect, useState } from "react";
import { UPLOAD_URL } from "../../../services/api";
import { getFeatureLinks } from "../../../services/FeatureLinksService";
import "./FeatureSection.css";
import Link from "next/link";
import { FaTrophy, FaUserTie, FaUsers, FaStar, FaMoneyBillWave, FaVideo, FaBriefcase, FaCalendarAlt, FaFlagCheckered, FaBook, FaImages, } from "react-icons/fa";

export default function FeatureSection() {
    // External links uploaded from the admin panel (empty string until loaded / when not uploaded)
    const [featureLinks, setFeatureLinks] = useState({ indian_stud_book: "", video_archives: "", webportal: "" });

    useEffect(() => {
        let active = true;
        getFeatureLinks().then((links) => {
            if (active) setFeatureLinks(links);
        });
        return () => {
            active = false;
        };
    }, []);

    // Cards with "linkKey" take their url from the admin panel, the others use the fixed "link"
    const features = [
        { title: "Performance Profile of Horses", icon: <FaTrophy />, image: "body_img1.jpeg", link: "/race_details?type=performanceProfile" },
        { title: "Trainerwise Horses In Training", icon: <FaUserTie />, image: "body_img2.jpeg", link: "/race_details?type=trainerHorses" },
        { title: "Webportal For Owners / Trainers", icon: <FaUsers />, image: "body_img4.jpeg", linkKey: "webportal" },
        { title: "Rating of all Horses", icon: <FaStar />, image: "body_img3.jpeg", link: "/race_details?type=horseRatings" },
        { title: "Tote Dividends", icon: <FaMoneyBillWave />, image: "body_img3.jpeg", link: "/race_details?type=dividends", },
        { title: "Video Archives", icon: <FaVideo />, image: "body_img4.jpeg", linkKey: "video_archives" },
        { title: "Money Leaders", icon: <FaBriefcase />, image: "body_img5.jpeg", link: "/race_details?type=moneyLeaders" },
        { title: "Gallery", icon: <FaImages />, image: "body_img6.jpeg", link: "/race_details?type=photos" },
        { title: "Racing Fixtures", icon: <FaCalendarAlt />, image: "body_img6.jpeg", link: "/race_details?type=racingFixtures" },
        { title: "Mock Race Result", icon: <FaFlagCheckered />, image: "body_img1.jpeg", link: "/race_details?type=mockRaceResults" },
        { title: "Entries For Sweepstake Races", icon: <FaFlagCheckered />, image: "body_img7.jpeg", link: "/race_details?type=sweepstakes" },
        { title: "Indian Stud Book", icon: <FaBook />, image: "body_img2.jpeg", linkKey: "indian_stud_book" },
    ];

    return (
        <section className="featureSection">
            <div className="featureGrid">
                {features.map((item, index) => {
                    // Final link: admin-managed url when "linkKey" is set, otherwise the fixed link
                    const href = item.linkKey ? featureLinks[item.linkKey] : item.link;

                    const cardContent = (
                        <div
                            className={`featureCard ${item.wide ? "wide" : ""}`}
                            style={{ backgroundImage: `url(${UPLOAD_URL}/${item.image})` }}
                        >
                            <div className="overlay">
                                <div className="featureIcon">{item.icon}</div>
                                <h3>{item.title}</h3>
                            </div>
                        </div>
                    );

                    if (href) {
                        return (
                            <Link href={href} key={index} style={{ display: "contents" }}>
                                {cardContent}
                            </Link>
                        );
                    }
                    return <div key={index}>{cardContent}</div>;
                })}
            </div>
        </section>
    );
}