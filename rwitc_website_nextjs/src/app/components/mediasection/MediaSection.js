"use client";
import { UPLOAD_URL } from "../../../services/api";
import { useEffect, useRef, useState } from "react";
import Link from "next/link";
import "./MediaSection.css";
import { FaPlay, FaVolumeMute, FaVolumeUp, FaExpand, FaChevronLeft, FaChevronRight } from "react-icons/fa";
import { Swiper, SwiperSlide } from "swiper/react";
import { Navigation, Autoplay } from "swiper/modules";
import "swiper/css";
import "swiper/css/navigation";
import { getMedia, getRaceMedia } from "../../../services/mediaService";
export default function MediaSection() {

    const videoRef = useRef(null);
    const [isPlaying, setIsPlaying] = useState(true);
    const [isMuted, setIsMuted] = useState(true);
    const adsPrevRef = useRef(null);
    const adsNextRef = useRef(null);

    const togglePlay = () => {
        if (!videoRef.current) return;
        if (videoRef.current.paused) {
            videoRef.current.play();
            setIsPlaying(true);
        } else {
            videoRef.current.pause();
            setIsPlaying(false);
        }
    };

    const toggleMute = () => {
        if (!videoRef.current) return;
        videoRef.current.muted = !videoRef.current.muted;
        setIsMuted(videoRef.current.muted);
    };

    const toggleFullscreen = () => {
        if (videoRef.current?.requestFullscreen) {
            videoRef.current.requestFullscreen();
        }
    };

    const [media, setMedia] = useState([]);
    const [raceMedia, setRaceMedia] = useState({
        preRace: [],
        postRace: [],
        trackWork: []
    });
    useEffect(() => {

        async function loadData() {

            const mediaData = await getMedia();
            setMedia(mediaData);

            const raceData = await getRaceMedia();
            setRaceMedia(raceData);

            console.log(raceData);

        }

        loadData();

    }, []);

    const video = media.find(item => item.type == 2);
    const images = media.filter(item => item.type == 1);

    // video is there or not
    const hasVideo = Boolean(video);

    // ✅ when video is there so max 2 slider or video is not there so max 3 sliders of imgs
    const maxSlides = hasVideo ? 2 : 3;
    const slidesPerView = Math.min(images.length, maxSlides) || 1;
    const canLoop = images.length > slidesPerView;

    const preRaceDates = raceMedia.preRace.map(item =>
        new Date(item.racedate).toLocaleDateString(
            "en-GB",
            {
                day: "2-digit",
                month: "2-digit"
            }
        )
    );
    const preRaceRows = [

        {
            label: "Handicaps",
            key: "handicaps"
        },

        {
            label: "Acceptances",
            key: "acceptances"
        },

        {
            label: "Declarations",
            key: "declarations"
        },

        {
            label: "Race Card",
            key: "raceCard"
        }

    ];
    const postRaceDates = raceMedia.postRace.map(item =>
        new Date(item.racedate).toLocaleDateString(
            "en-GB",
            {
                day: "2-digit",
                month: "2-digit"
            }
        )
    );
    const postRaceRows = [

        {
            label: "Race Results",
            key: "raceResults"
        },

        {
            label: "Rating Change",
            key: "ratingChange"
        },

        {
            label: "Raceday Report",
            key: "raceDayReport"
        },

        {
            label: "Photos",
            key: "photos"
        },

        {
            label: "Videos",
            key: "videos"
        }

    ];
    const groupedTrackWork = [];

    for (let i = 0; i < raceMedia.trackWork.length; i += 3) {

        groupedTrackWork.push(
            raceMedia.trackWork.slice(i, i + 3)
        );

    }
    return (
        <section className="mediaSection">
            <div className="mediaSectionContent">
                <div className="mediaContainer">
                    {
                        hasVideo && (
                            <div className="videoArea" id="live-video">
                                <video
                                    controls
                                    autoPlay
                                    muted
                                    loop
                                    width="100%"
                                    height="100%"
                                >

                                    <source
                                        src={`${UPLOAD_URL}/${video?.path}`}
                                        type="video/mp4"
                                    />

                                </video>
                            </div>
                        )
                    }
                    {
                        images.length > 0 && (
                            <div className={`adsArea ${!hasVideo ? "adsAreaFull" : ""}`}>
                                <Swiper
                                    key={`${images.length}-${hasVideo}`}
                                    modules={[Navigation, Autoplay]}
                                    slidesPerView={slidesPerView}
                                    spaceBetween={12}
                                    loop={canLoop}
                                    speed={800}
                                    autoplay={
                                        canLoop
                                            ? { delay: 3500, disableOnInteraction: false }
                                            : false
                                    }
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
                                    {
                                        images.map((item) => (
                                            <SwiperSlide key={item.id}>
                                                <div className="adsImgWrap">
                                                    <img
                                                        src={`${UPLOAD_URL}/${item.path}`}
                                                        alt={item.path}
                                                        className="adsImgMain"
                                                    />
                                                </div>
                                            </SwiperSlide>
                                        ))
                                    }
                                </Swiper>

                                {
                                    canLoop && (
                                        <>
                                            <button ref={adsPrevRef} className="adsNavBtn adsNavPrev" aria-label="Previous">
                                                <FaChevronLeft />
                                            </button>
                                            <button ref={adsNextRef} className="adsNavBtn adsNavNext" aria-label="Next">
                                                <FaChevronRight />
                                            </button>
                                        </>
                                    )
                                }
                            </div>
                        )
                    }
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
                                    {preRaceDates.map((date, i) => (
                                        <th key={i}>{date}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {preRaceRows.map((row, i) => (
                                    <tr key={i}>
                                        <td className="rowLabel">{row.label}</td>
                                        {raceMedia.preRace.map((item, j) => (

                                            <td key={j}>

                                                {
                                                    item[row.key].available ? (
                                                        <Link
                                                            href={`/race_details?type=${row.key}&date=${item.racedate}`}
                                                            className="statusDot active"
                                                            prefetch={false}
                                                        />
                                                    ) : (
                                                        <span className="statusDot" />
                                                    )
                                                }

                                            </td>

                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                        <div className="viewArchives">
                            <a href="#">View Archives</a>
                        </div>
                    </div>
                    {/* POST-RACE */}
                    <div className="infoBox">
                        <h3 className="infoBoxTitle">Post-Race</h3>
                        <table className="infoTable">
                            <thead>
                                <tr>
                                    <th></th>
                                    {postRaceDates.map((date, i) => (
                                        <th key={i}>{date}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {postRaceRows.map((row, i) => (
                                    <tr key={i}>
                                        <td className="rowLabel">{row.label}</td>
                                        {raceMedia.postRace.map((item, j) => (

                                            <td key={j}>

                                                {
                                                    item[row.key].available ? (
                                                        <Link
                                                            href={`/race_details?type=${row.key}&date=${item.racedate}`}
                                                            className="statusDot active"
                                                            prefetch={false}
                                                        />
                                                    ) : (
                                                        <span className="statusDot" />
                                                    )
                                                }

                                            </td>

                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="viewArchives">
                            <a href="#">View Archives</a>
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
                                                    href={`/race_details?type=trackWork&date=${item.trackwork_date}`}
                                                    className="trackWorkLink"
                                                    prefetch={false}
                                                >
                                                    {
                                                        new Date(item.trackwork_date).toLocaleDateString(
                                                            "en-GB",
                                                            {
                                                                day: "2-digit",
                                                                month: "short"
                                                            }
                                                        )
                                                    }
                                                </Link>

                                            </td>

                                        ))}

                                    </tr>

                                ))}
                            </tbody>
                        </table>
                        <div className="viewArchives">
                            <a href="#">View Archives</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}