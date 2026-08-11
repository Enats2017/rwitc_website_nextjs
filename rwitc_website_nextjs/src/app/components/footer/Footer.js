"use client";
import { UPLOAD_URL } from "../../../services/api";
import { useEffect, useState } from "react";
import "./Footer.css";
import { FaFacebookF, FaInstagram, FaYoutube, FaXTwitter, FaApple } from "react-icons/fa6";

export default function Footer() {
    const [currentTemp, setCurrentTemp] = useState(null);
    const [currentIcon, setCurrentIcon] = useState("🌦️");
    const [weather, setWeather] = useState([]);

    const codeToIcon = (code) => {
        if (code === 0) return "☀️";
        if ([1, 2].includes(code)) return "🌤️";
        if (code === 3) return "⛅";
        if ([45, 48].includes(code)) return "🌫️";
        if ([51, 53, 55, 61, 63, 65, 80, 81, 82].includes(code)) return "🌦️";
        if ([95, 96, 99].includes(code)) return "⛈️";
        return "🌦️";
    };

    useEffect(() => {
        async function loadWeather() {
            const res = await fetch("https://api.open-meteo.com/v1/forecast?latitude=19.0760&longitude=72.8777&daily=weathercode,temperature_2m_max,temperature_2m_min&current_weather=true&timezone=Asia%2FKolkata");
            const data = await res.json();
            setCurrentTemp(Math.round(data.current_weather.temperature));
            setCurrentIcon(codeToIcon(data.current_weather.weathercode));
            const days = data.daily.time.map((dateStr, i) => ({
                day: new Date(dateStr).toLocaleDateString("en-US", {
                    weekday: "short",
                }),
                icon: codeToIcon(data.daily.weathercode[i]),
                max: `${Math.round(data.daily.temperature_2m_max[i])}°`,
                min: `${Math.round(data.daily.temperature_2m_min[i])}°`,
            }));
            setWeather(days);
        }
        loadWeather();
    }, []);

    return (
        <footer className="footer">
            <div className="footerTop">

                <div className="footerMain">

                    {/* LEFT */}
                    <div className="footerLeft">
                        <div className="footerBrandRow">
                            <img src={`${UPLOAD_URL}/rwitc_logo_white.png`} alt="RWITC" className="footerLogo" />
                        </div>

                        <div className="socialIcons">
                            <a href="https://www.facebook.com/rwitcmumbai" className="iconFacebook" aria-label="Facebook"> <FaFacebookF /> </a>
                            <a href="https://x.com/rwitcmumbai" className="iconX" aria-label="X"> <FaXTwitter /> </a>
                            <a href="https://www.instagram.com/rwitcmumbai" className="iconInstagram" aria-label="Instagram"> <FaInstagram /> </a>
                            <a href="https://www.youtube.com/@rwitcltd9390" className="iconYoutube" aria-label="YouTube"> <FaYoutube /> </a>
                        </div>

                        <p className="footerText">
                            A legacy of excellence in horse racing since 1932. Experience the thrill, heritage and prestige.
                        </p>

                        <div className="contactBtnRow">
                            <a href="contact" className="contactUsBtn">Contact Us</a>
                            <a href="menubar?type=about" className="contactUsBtn"> About Us </a>
                            <a href="suggestion" className="contactUsBtn">Suggestion</a>
                        </div>
                    </div>

                    {/* MIDDLE - WEATHER INLINE */}
                    <div className="weatherInline">

                        <div className="weatherMain">
                            <h3>MUMBAI</h3>
                            <div className="weatherMainRow">
                                <span>{currentIcon}</span>
                                <h1>
                                    {currentTemp !== null
                                        ? `+${currentTemp}°C`
                                        : "..."}
                                </h1>
                            </div>
                            <p className="weatherDesc">Partly Cloudy</p>
                        </div>

                        <div className="weatherDays">
                            {weather.map((item, index) => (
                                <div
                                    className="weatherDay"
                                    key={index}
                                >
                                    <h4>{item.day}</h4>
                                    <span>{item.icon}</span>
                                    <div className="weatherTemps">
                                        <strong>{item.max}</strong>
                                        <small>{item.min}</small>
                                    </div>
                                </div>
                            ))}
                        </div>

                    </div>

                    {/* RIGHT - APP (Android + iOS) */}
                    <div className="footerApp">
                        <p className="getAppText">Get our app</p>

                        <div className="appIconsRow">
                            <a href="https://play.google.com/store/apps/details?id=com.nabil_shah.test"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="appCircle"
                                aria-label="Get it on Google Play"
                            >
                                <img src={`${UPLOAD_URL}/android.png`} alt="Get Our App - Android" />
                            </a>

                            <a href="#"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="appCircle"
                                aria-label="Download on the App Store"
                            >
                                <FaApple className="appleIcon" />
                            </a>
                        </div>
                    </div>

                </div>

            </div>

            <div className="footerBottom">
                <span>© 2026 ROYAL WESTERN INDIA TURF CLUB PRIVATE LIMITED. ALL RIGHTS RESERVED.</span>
                <span className="footerLinks">
                    <a href="#">Privacy Policy</a>
                    <span className="footerDivider">|</span>
                    <a href="#">Terms &amp; Conditions</a>
                </span>
            </div>
        </footer>
    );
}