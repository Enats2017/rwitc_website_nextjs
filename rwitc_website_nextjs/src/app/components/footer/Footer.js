"use client";
import { UPLOAD_URL } from "../../../services/api";
import { getFooterDocuments, getAppLinks } from "../../../services/Footerservice";
import { useEffect, useState } from "react";
import "./Footer.css";
import { FaFacebookF, FaInstagram, FaYoutube, FaXTwitter, FaApple, FaLocationDot } from "react-icons/fa6";

const WEATHER_CITIES = [
    { name: "PUNE", lat: 18.5204, lon: 73.8567 },
    { name: "MUMBAI", lat: 19.0760, lon: 72.8777 },
];

const codeToIcon = (code) => {
    if (code === 0) return "☀️";
    if ([1, 2].includes(code)) return "🌤️";
    if (code === 3) return "⛅";
    if ([45, 48].includes(code)) return "🌫️";
    if ([51, 53, 55, 61, 63, 65, 80, 81, 82].includes(code)) return "🌦️";
    if ([95, 96, 99].includes(code)) return "⛈️";
    return "🌦️";
};

const codeToText = (code) => {
    if (code === 0) return "Clear Sky";
    if (code === 1) return "Mostly Clear";
    if (code === 2) return "Partly Cloudy";
    if (code === 3) return "Overcast";
    if ([45, 48].includes(code)) return "Foggy";
    if ([51, 53, 55].includes(code)) return "Drizzle";
    if ([61, 63, 65].includes(code)) return "Rain";
    if ([80, 81, 82].includes(code)) return "Rain Showers";
    if ([95, 96, 99].includes(code)) return "Thunderstorm";
    return "Partly Cloudy";
};

async function fetchCityWeather(city) {
    const res = await fetch(
        `https://api.open-meteo.com/v1/forecast?latitude=${city.lat}&longitude=${city.lon}&daily=weathercode,temperature_2m_max,temperature_2m_min&current_weather=true&timezone=Asia%2FKolkata`
    );
    if (!res.ok) throw new Error("Weather request failed");
    const data = await res.json();

    return {
        name: city.name,
        temp: Math.round(data.current_weather.temperature),
        icon: codeToIcon(data.current_weather.weathercode),
        desc: codeToText(data.current_weather.weathercode),
        days: data.daily.time.map((dateStr, i) => ({
            day: new Date(`${dateStr}T00:00:00`).toLocaleDateString("en-US", {
                weekday: "short",
            }),
            icon: codeToIcon(data.daily.weathercode[i]),
            max: `${Math.round(data.daily.temperature_2m_max[i])}°`,
            min: `${Math.round(data.daily.temperature_2m_min[i])}°`,
        })),
    };
}

export default function Footer() {
    // Weather data stored per city name, e.g. { PUNE: {...}, MUMBAI: {...} }
    const [weatherData, setWeatherData] = useState({});
    // Document urls uploaded from the admin panel (empty string until loaded / when not uploaded)
    const [footerDocs, setFooterDocs] = useState({ term_condition: "", privacy_policy: "" });
    const [appLinks, setAppLinks] = useState({ android: "", ios: "" });

    useEffect(() => {
        let cancelled = false;

        async function loadWeather() {
            const results = await Promise.all(
                WEATHER_CITIES.map((city) =>
                    fetchCityWeather(city).catch(() => null)
                )
            );
            if (cancelled) return;

            const map = {};
            results.forEach((r) => {
                if (r) map[r.name] = r;
            });
            setWeatherData(map);
        }

        loadWeather();
        return () => {
            cancelled = true;
        };
    }, []);

    // Load the Privacy Policy and Terms & Conditions document links
    useEffect(() => {
        let active = true;
        getFooterDocuments().then((docs) => {
            if (active) setFooterDocs(docs);
        });
        return () => {
            active = false;
        };
    }, []);

    // Load the Android / iOS app download links
    useEffect(() => {
        let active = true;
        getAppLinks().then((links) => {
            if (active) setAppLinks(links);
        });
        return () => {
            active = false;
        };
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
                            <a href="officialcontact" className="contactUsBtn">Contact Us</a>
                            <a href="menubar?type=about" className="contactUsBtn"> About Us </a>
                            <a href="suggestion" className="contactUsBtn">Suggestion</a>
                        </div>
                    </div>

                    {/* MIDDLE - WEATHER (Pune + Mumbai stacked together) */}
                    <div className="weatherInline">
                        {WEATHER_CITIES.map((city) => {
                            const w = weatherData[city.name];
                            return (
                                <div className="weatherRow" key={city.name}>
                                    <div className="weatherMain">
                                        <h3>
                                            <FaLocationDot className="weatherPin" />
                                            {city.name}
                                        </h3>
                                        <div className="weatherMainRow">
                                            <span>{w ? w.icon : "🌦️"}</span>
                                            <h1>{w ? `+${w.temp}°C` : "..."}</h1>
                                        </div>
                                        <p className="weatherDesc">{w ? w.desc : "Loading..."}</p>
                                    </div>

                                    <div className="weatherDays">
                                        {(w ? w.days : []).map((item, index) => (
                                            <div className="weatherDay" key={index}>
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
                            );
                        })}
                    </div>

                    {/* RIGHT - APP (a button is shown only when its link is saved in the admin panel) */}
                    <div className="footerApp">
                        {(appLinks.android || appLinks.ios) && (
                            <>
                                <p className="getAppText">Get our app</p>
                                <div className="appIconsRow">
                                    {appLinks.android && (
                                        <a href={appLinks.android}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="appCircle"
                                            aria-label="Get it on Google Play"
                                        >
                                            <img src={`${UPLOAD_URL}/android.png`} alt="Get Our App - Android" />
                                        </a>
                                    )}
                                    {appLinks.ios && (
                                        <a href={appLinks.ios}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="appCircle"
                                            aria-label="Download on the App Store"
                                        >
                                            <FaApple className="appleIcon" />
                                        </a>
                                    )}
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>

            <div className="footerBottom">
                <span>© 2026 ROYAL WESTERN INDIA TURF CLUB LIMITED. ALL RIGHTS RESERVED.</span>
                <span className="footerLinks">
                    <a
                        href={footerDocs.privacy_policy || "#"}
                        target={footerDocs.privacy_policy ? "_blank" : undefined}
                        rel={footerDocs.privacy_policy ? "noopener noreferrer" : undefined}
                    >
                        Privacy Policy
                    </a>
                    <span className="footerDivider">|</span>
                    <a
                        href={footerDocs.term_condition || "#"}
                        target={footerDocs.term_condition ? "_blank" : undefined}
                        rel={footerDocs.term_condition ? "noopener noreferrer" : undefined}
                    >
                        Terms &amp; Conditions
                    </a>
                </span>
            </div>
        </footer>
    );
}