"use client";
import { UPLOAD_URL } from "../../../services/api";
import { useEffect, useState } from "react";
import Link from "next/link";
import "./MediaSection.css";
import {
    getMedia,
    getRaceMedia
} from "../../../services/mediaService";
export default function MediaSection() {

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
            <img src="/image/Horses_BW_1.jpg" alt="" className="mediaSectionBg" draggable="false" />
            <div className="mediaSectionOverlay"></div>
            <div className="mediaSectionContent">
                <div className="mediaContainer">
                    {/* LEFT VIDEO */}
                    <div className="videoArea" id="live-video">
                        {
                            media.length > 0 && (

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

                            )
                        }
                    </div>
                    {
                        images.map((item) => (

                            <div
                                className="adsArea"
                                key={item.id}
                            >

                                <img
                                    src={`${UPLOAD_URL}/${item.path}`}
                                    alt={item.path}
                                />

                            </div>

                        ))
                    }
                </div>
                {/* RUNNING TICKER */}
                <div className="newsTicker">
                    <img src="/image/rwitc_logo_white.png" alt="RWITC Logo" className="tickerLogo" draggable="false" />
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