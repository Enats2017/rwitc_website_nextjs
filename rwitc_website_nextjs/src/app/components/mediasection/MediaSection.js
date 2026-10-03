"use client";
import { UPLOAD_URL } from "../../../services/api";
import { useEffect, useState } from "react";
import { useRef } from "react";
import Link from "next/link";
import "./MediaSection.css";
import { FaPlay, FaChevronLeft, FaChevronRight } from "react-icons/fa";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, Autoplay } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import { getMedia, getRaceMedia } from "../../../services/mediaService";

const LEFT_POSTER = "/image.jpg";

function toDateOnly(dateStr) {
    if (!dateStr) return "";
    return String(dateStr).split(" ")[0].split("T")[0];
}

export default function MediaSection() {
    const adsPrevRef = useRef(null);
    const adsNextRef = useRef(null);

    const [media, setMedia] = useState([]);
    const [raceMedia, setRaceMedia] = useState({ preRace: [], postRace: [], trackWork: [] });

    useEffect(() => {
        async function loadData() {
            const mediaData = await getMedia();
            setMedia(mediaData);
            const raceData = await getRaceMedia();
            setRaceMedia(raceData);
        }
        loadData();
    }, []);

    const images = media.filter(item => item.type == 1);

    // Left side hamesha poster dikhata hai, isliye right side max 2 slides
    const slidesPerView = Math.min(images.length, 2) || 1;
    const canLoop = images.length > slidesPerView;

    const formatDM = (d) =>
        new Date(d).toLocaleDateString("en-GB", { day: "2-digit", month: "2-digit" });

    const preRaceDates = raceMedia.preRace.map(item => formatDM(item.racedate));
    const preRaceRows = [
        { label: "Handicaps", key: "handicaps" },
        { label: "Acceptances", key: "acceptances" },
        { label: "Declarations", key: "declarations" },
        { label: "Race Card", key: "raceCard" }
    ];

    const postRaceDates = raceMedia.postRace.map(item => formatDM(item.racedate));
    const postRaceRows = [
        { label: "Race Results", key: "raceResults" },
        { label: "Rating Change", key: "ratingChange" },
        { label: "Raceday Report", key: "raceDayReport" },
        { label: "Photos", key: "photos" },
        { label: "Videos", key: "videos" }
    ];

    const groupedTrackWork = [];
    for (let i = 0; i < raceMedia.trackWork.length; i += 3) {
        groupedTrackWork.push(raceMedia.trackWork.slice(i, i + 3));
    }

    return (
        <section className="mediaSection">
            <div className="mediaSectionContent">
                {/* RUNNING TICKER */}
                <div className="newsTicker">
                    <img src={`${UPLOAD_URL}/rwitc_logo_white.png`} alt="RWITC Logo" className="tickerLogo" draggable="false" />
                    <div className="tickerTrack">
                        <p> website is currently being upgraded. We apologize for any inconvenience caused and appreciate your patience. </p>
                    </div>
                </div>

                <div className="mediaContainer">
                    {/* LEFT: video-looking poster with play icon (UI only, not clickable) */}
                    <div className="videoArea" id="live-video">
                        <div className="videoPoster">
                            <img src={LEFT_POSTER} alt="RWITC" className="videoPosterImg" draggable="false" />
                            <div className="videoPosterOverlay" />
                            <div className="fakePlayBtn" aria-hidden="true">
                                <FaPlay />
                            </div>
                        </div>
                    </div>

                    {/* RIGHT: images slider */}
                    {images.length > 0 && (
                        <div className="adsArea">
                            <Swiper
                                key={`${images.length}`}
                                modules={[Navigation, Autoplay]}
                                slidesPerView={slidesPerView}
                                spaceBetween={12}
                                loop={canLoop}
                                speed={800}
                                autoplay={canLoop ? { delay: 3500, disableOnInteraction: false } : false}
                                navigation={{
                                    prevEl: adsPrevRef.current,
                                    nextEl: adsNextRef.current,
                                }}
                                onBeforeInit={(swiper) => {
                                    swiper.params.navigation.prevEl = adsPrevRef.current;
                                    swiper.params.navigation.nextEl = adsNextRef.current;
                                }}
                                className="adsSwiper"
                            >
                                {images.map((item) => (
                                    <SwiperSlide key={item.id}>
                                        <div className="adsImgWrap">
                                            <img
                                                src={item.path.startsWith('http') ? item.path : `${UPLOAD_URL}/${item.path}`}
                                                alt={item.path}
                                                className="adsImgMain"
                                            />
                                        </div>
                                    </SwiperSlide>
                                ))}
                            </Swiper>

                            {canLoop && (
                                <>
                                    <button ref={adsPrevRef} className="adsNavBtn adsNavPrev" aria-label="Previous">
                                        <FaChevronLeft />
                                    </button>
                                    <button ref={adsNextRef} className="adsNavBtn adsNavNext" aria-label="Next">
                                        <FaChevronRight />
                                    </button>
                                </>
                            )}
                        </div>
                    )}
                </div>

                {/* RUNNING TICKER */}
                <div className="newsTicker">
                    <img src={`${UPLOAD_URL}/rwitc_logo_white.png`} alt="RWITC Logo" className="tickerLogo" draggable="false" />
                    <div className="tickerTrack">
                        <p> A view of Royal Western India&apos;s Turf Club&apos;s new clubhouse, which aims to embody the perfect fusion of heritage, classic charm and contemporary luxury. </p>
                    </div>
                </div>

                {/* INFO BOXES */}
                <div className="infoBoxes">
                    {/* PRE-RACE */}
                    <div className="infoBox">
                        <h3 className="infoBoxTitle">Pre-Race</h3>
                        <table className="infoTable">
                            <thead>
                                <tr>
                                    <th></th>
                                    {preRaceDates.map((date, i) => (<th key={i}>{date}</th>))}
                                </tr>
                            </thead>
                            <tbody>
                                {preRaceRows.map((row, i) => (
                                    <tr key={i}>
                                        <td className="rowLabel">{row.label}</td>
                                        {raceMedia.preRace.map((item, j) => (
                                            <td key={j}>
                                                {item[row.key].available ? (
                                                    <Link
                                                        href={`/race_details?type=${row.key}&date=${toDateOnly(item.racedate)}`}
                                                        className="statusDot active"
                                                        prefetch={false}
                                                    />
                                                ) : (
                                                    <span className="statusDot" />
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <div className="viewArchives">
                            <Link href="/race_details?type=archives" prefetch={false}>View Archives</Link>
                        </div>
                    </div>

                    {/* POST-RACE */}
                    <div className="infoBox">
                        <h3 className="infoBoxTitle">Post-Race</h3>
                        <table className="infoTable">
                            <thead>
                                <tr>
                                    <th></th>
                                    {postRaceDates.map((date, i) => (<th key={i}>{date}</th>))}
                                </tr>
                            </thead>
                            <tbody>
                                {postRaceRows.map((row, i) => (
                                    <tr key={i}>
                                        <td className="rowLabel">{row.label}</td>
                                        {raceMedia.postRace.map((item, j) => (
                                            <td key={j}>
                                                {item[row.key].available ? (
                                                    row.key === "videos" ? (
                                                        <a
                                                            href={`https://rwitcraces.com/RaceArchives.aspx?d=${new Date(item.racedate).toLocaleDateString('en-GB').replace(/\//g, '')}`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="statusDot active"
                                                        />
                                                    ) : (
                                                        <Link
                                                            href={`/race_details?type=${row.key}&date=${toDateOnly(item.racedate)}`}
                                                            className="statusDot active"
                                                            prefetch={false}
                                                        />
                                                    )
                                                ) : (
                                                    <span className="statusDot" />
                                                )}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <div className="viewArchives">
                            <Link href="/race_details?type=archives" prefetch={false}>View Archives</Link>
                        </div>
                    </div>

                    {/* TRACK WORK */}
                    <div className="infoBox">
                        <h3 className="infoBoxTitle">Track Work</h3>
                        <table className="infoTable trackWorkTable">
                            <tbody>
                                {groupedTrackWork.map((row, rowIndex) => (
                                    <tr key={rowIndex}>
                                        {row.map((item, colIndex) => (
                                            <td key={colIndex}>
                                                <Link
                                                    href={`/race_details?type=trackWork&id=${item.id}`}
                                                    className="trackWorkLink"
                                                    prefetch={false}
                                                >
                                                    {new Date(item.trackwork_date).toLocaleDateString("en-GB", {
                                                        day: "2-digit",
                                                        month: "short"
                                                    })}
                                                </Link>
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <div className="viewArchives">
                            <Link href="/race_details?type=archives" prefetch={false}>View Archives</Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}