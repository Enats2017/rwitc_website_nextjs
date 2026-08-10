"use client";

import { UPLOAD_URL } from "../../../services/api";
import "./FeatureSection.css";
import Link from "next/link";

import {
    FaTrophy,
    FaUserTie,
    FaUsers,
    FaStar,
    FaMoneyBillWave,
    FaVideo,
    FaBriefcase,
    FaCalendarAlt,
    FaFlagCheckered,
    FaBook,
} from "react-icons/fa";

export default function FeatureSection() {

    const features = [
        { title: "Performance Profile of Horses", icon: <FaTrophy />, image: "body_img1.jpeg", link: "/race_details?type=performanceProfile" },
        { title: "Trainerwise Horses In Training", icon: <FaUserTie />, image: "body_img2.jpeg", link: "/race_details?type=trainerHorses" },
        { title: "Webportal For Owners / Trainers", icon: <FaUsers />, image: "body_img4.jpeg" },
        { title: "Rating of all Horses", icon: <FaStar />, image: "body_img3.jpeg" },
        { title: "Tote Dividends", icon: <FaMoneyBillWave />, image: "body_img3.jpeg", wide: true, link: "/race_details?type=dividends",},
        { title: "Video Archives", icon: <FaVideo />, image: "body_img4.jpeg" },
        { title: "Money Leaders", icon: <FaBriefcase />, image: "body_img5.jpeg" },
        { title: "Racing Fixtures", icon: <FaCalendarAlt />, image: "body_img6.jpeg" },
        { title: "Entries For Sweepstake Races", icon: <FaFlagCheckered />, image: "body_img7.jpeg", wide: true, link: "/race_details?type=sweepstakes" },
        { title: "Indian Stud Book", icon: <FaBook />, image: "body_img2.jpeg", wide: true },
    ];

    return (
        <section className="featureSection">
            <div className="featureGrid">
                {features.map((item, index) => {

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

    if (item.link) {
        return (
            <Link href={item.link} key={index} style={{ display: "contents" }}>
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