"use client";
import { useEffect, useRef, useState } from "react";
import "./NewsRoom.css";
import { FaTrophy, FaRegNewspaper, FaChevronLeft, FaChevronRight, } from "react-icons/fa";
import { UPLOAD_URL } from "../../../services/api";
import { getNews } from "../../../services/newsService";
import { getTopStories } from "../../../services/topStoriesService";
import Watermark from "../../../security/Watermark";

export default function NewsRoom() {
    const [news, setNews] = useState([]);
    const [topStories, setTopStories] = useState([]);
    const [showAll, setShowAll] = useState(false);
    const [raceHeight, setRaceHeight] = useState(null);
    const newsCardRef = useRef(null);

    useEffect(() => {
        async function loadNews() {
            const data = await getNews();
            setNews(data);
        }
        loadNews();
    }, []);

    useEffect(() => {
        async function loadTopStories() {
            const data = await getTopStories();
            setTopStories(data);
        }
        loadTopStories();
    }, []);

    // Measure NewsRoom card height ONLY while collapsed,
    // and lock Top Stories card to that height permanently.
    useEffect(() => {
        if (!showAll && newsCardRef.current) {
            setRaceHeight(newsCardRef.current.offsetHeight);
        }
    }, [news, showAll]);

    const handleNewClick = (item) => {
        console.log(item);
    };

    // Block right-click (context menu) inside this section only
    const handleContextMenu = (e) => {
        e.preventDefault();
    };

    const visibleNews = showAll ? news : news.slice(0, 5);

    return (

        <section
            className="newsRoom"
            style={{ position: "relative" }}
            onContextMenu={handleContextMenu}
        >

            {/* Watermark shown only inside this NewsRoom section (data coming from DB) */}
            <Watermark />

            <div className="newsContainer">
                <div
                    className="raceCard"
                    id="top-stories"
                    style={raceHeight ? { height: `${raceHeight}px` } : undefined}
                >
                    <div className="raceContent">
                        <div className="cardTag">
                            <FaTrophy />
                            <span>Top Stories</span>
                        </div>
                        {
                            topStories.length > 0 ? (
                                <h2 style={{ whiteSpace: "pre-line", lineHeight: "1.5" }}>
                                    {topStories[0].body}
                                </h2>
                            ) : (
                                <h2>Loading...</h2>
                            )
                        }

                        <div className="raceNav">
                            <button className="navCircle" aria-label="Previous">
                                <FaChevronLeft />
                            </button>
                            <button className="navCircle" aria-label="Next">
                                <FaChevronRight />
                            </button>
                        </div>
                    </div>
                    <div className="raceImageWrap">
                        <img
                            src={`${UPLOAD_URL}/body_img5.jpeg`}
                            alt="Race"
                        />
                    </div>
                </div>
                <div className="newsCard" ref={newsCardRef}>
                    <div className="cardTag">
                        <FaRegNewspaper />
                        <span>News Room</span>
                    </div>
                    <div className="newsList">
                        {
                            visibleNews.map((item) => (
                                <div className="newsItem" key={item.id}>
                                    <p className="newsText">
                                        {item.title}
                                        {
                                            item.new === "Y" && (
                                                <button
                                                    type="button"
                                                    className="newLabel"
                                                    onClick={() => handleNewClick(item)}
                                                >
                                                    New
                                                </button>
                                            )
                                        }
                                    </p>
                                </div>
                            ))
                        }
                    </div>
                    <div className="viewAllWrap">
                        {
                            news.length > 5 && (
                                <button
                                    type="button"
                                    className="viewAllBtn"
                                    onClick={() => setShowAll(!showAll)}
                                >
                                    {showAll ? "View Less News" : "View More News"}
                                </button>
                            )
                        }
                    </div>
                </div>
            </div>
        </section>
    );
}