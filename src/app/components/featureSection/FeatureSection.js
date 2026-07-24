"use client";
import "./FeatureSection.css";
import { FaArrowRight, FaHorse, FaChartLine, FaUserTie, FaUsers, FaVideo, FaTrophy, FaMoneyBillWave, } from "react-icons/fa";
export default function FeatureSection() {
    const features = [
        { title: "Performance Profile of Horses", icon: <FaChartLine />, image: "/image/body_img1.jpeg", large: true, },
        { title: "Trainerwise Horses In Training", icon: <FaUserTie />, image: "/image/body_img2.jpeg", },
        { title: "Rating of all Horses", icon: <FaHorse />, image: "/image/body_img3.jpeg", },
        { title: "Webportal For Owners / Trainers", icon: <FaUsers />, image: "/image/body_img4.jpeg", },
        { title: "Tote Dividends", icon: <FaHorse />, image: "/image/body_img3.jpeg", },
        { title: "Video Archives", icon: <FaUsers />, image: "/image/body_img4.jpeg", },
        { title: "Money Leaders", icon: <FaVideo />, image: "/image/body_img5.jpeg", wide: true, },
        { title: "Racing Fixtures", icon: <FaMoneyBillWave />, image: "/image/body_img6.jpeg", },
        { title: "Entries For Sweepstake Races", icon: <FaTrophy />, image: "/image/body_img7.jpeg", },
        { title: "Indian Stud Book", icon: <FaUserTie />, image: "/image/body_img2.jpeg", },
    ];
    return (
        <section className="featureSection">
            <div className="sectionHeading">
                <span> EXPLORE RWITC </span>
                <h2> Racing Services & Information </h2>
                <p> Everything you need about Horses, Trainers, Racing, Results and Performance at one place. </p>
            </div>

            <div className="featureGrid">
                {
                    features.map((item, index) => (
                        <div
                            key={index}
                            className={`featureCard
                            ${item.large ? "large" : ""}
                            ${item.wide ? "wide" : ""}`}
                        >
                            <img src={item.image} alt={item.title} />
                            <div className="overlay">
                                <div className="featureIcon"> {item.icon} </div>
                                <h3> {item.title} </h3>
                                <p> {item.description} </p>
                                <button> View Details <FaArrowRight /> </button>
                            </div>
                        </div>
                    ))
                }
            </div>
        </section>
    );
}