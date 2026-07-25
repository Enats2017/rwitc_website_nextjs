"use client";
import { UPLOAD_URL } from "../../../services/api";
import { useEffect, useState } from "react";
import "./Footer.css";
import { FaFacebookF, FaInstagram, FaYoutube, FaXTwitter, FaGooglePlay, FaApple, } from "react-icons/fa6";
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
                {/* TOP ROW */}
                <div className="footerTopRow">
                    {/* DOWNLOAD */}
                    <div className="downloadCard">
                        <div className="downloadInfo">
                            <img src={`${UPLOAD_URL}/android.png`} alt="Get Our App" className="appImage" />
                            <div className="downloadText">
                                <h3>Get Our App</h3>
                                <p>Available on Android &amp; iOS</p>
                            </div>
                        </div>
                        <div className="downloadButtons">
                            <a
                                href="https://play.google.com/store/apps/details?id=com.nabil_shah.test"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="contactButton"
                            >
                                <FaGooglePlay />
                                <span>
                                    <small>Get it on</small>
                                    Google Play
                                </span>
                            </a>
                            <a
                                href="#"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="contactButton contactButtonApple"
                            >
                                <FaApple />
                                <span>
                                    <small>Download on the</small>
                                    App Store
                                </span>
                            </a>
                        </div>
                    </div>
                    {/* RIGHT */}
                    <div className="footerRightCol">
                        <img src={`${UPLOAD_URL}/rwitc_logo_white.png`} alt="RWITC" className="footerLogo" />
                        <p className="footerText"> Royal Western India Turf Club Ltd.<br />
                            Since 1925 • Pune &amp; Mumbai Race Courses
                        </p>
                        <div className="socialIcons">
                            <a href="#" className="iconFacebook" aria-label="Facebook"> <FaFacebookF /> </a>
                            <a href="#" className="iconX" aria-label="X"> <FaXTwitter /> </a>
                            <a href="#" className="iconInstagram" aria-label="Instagram"> <FaInstagram /> </a>
                            <a href="#" className="iconYoutube" aria-label="YouTube"><FaYoutube /> </a>
                        </div>
                        <button className="contactUsBtn"> Contact Us </button>
                    </div>
                </div>
                {/* WEATHER */}
                <div className="weatherCard">
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
            </div>
            <div className="footerBottom">
                © 2025 ROYAL WESTERN INDIA TURF CLUB PRIVATE LIMITED
            </div>
        </footer>
    );
}