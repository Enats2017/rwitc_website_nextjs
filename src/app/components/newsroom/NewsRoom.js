"use client";

import { useEffect, useState } from "react";
import "./NewsRoom.css";

import {
    FaArrowRight,
    FaCalendarAlt,
    FaRegNewspaper,
    FaChevronLeft,
    FaChevronRight,
} from "react-icons/fa";

import { getNews } from "../../../services/newsService";
import { getTopStories } from "../../../services/topStoriesService";

export default function NewsRoom() {

    const [news, setNews] = useState([]);
    const [topStories, setTopStories] = useState([]);
    const [showAll, setShowAll] = useState(false);

    // News API
    useEffect(() => {

        async function loadNews() {

            const data = await getNews();

            setNews(data);

        }

        loadNews();

    }, []);

    // Top Stories API
    useEffect(() => {

        async function loadTopStories() {

            const data = await getTopStories();

            setTopStories(data);

        }

        loadTopStories();

    }, []);

    const handleNewClick = (item) => {

        console.log(item);

        // router.push(`/news/${item.id}`);

    };

    const visibleNews = showAll ? news : news.slice(0, 5);

    return (

        <section className="newsRoom">

            <div className="newsContainer">

                {/* LEFT CARD */}

               <div className="raceCard" id="top-stories">

                    <div className="cardTag">

                        <FaCalendarAlt />

                        <span>Top Stories</span>

                    </div>

                    {

                        topStories.length > 0 ? (

                            <h2
                                style={{
                                    whiteSpace: "pre-line",
                                    lineHeight: "1.5"
                                }}
                            >
                                {topStories[0].body}
                            </h2>

                        ) : (

                            <h2>Loading...</h2>

                        )

                    }

                    <div className="raceNav">

                        <button
                            className="navCircle"
                            aria-label="Previous"
                        >

                            <FaChevronLeft />

                        </button>

                        <button
                            className="navCircle"
                            aria-label="Next"
                        >

                            <FaChevronRight />

                        </button>

                    </div>

                    <button className="raceButton">

                        View Schedule

                        <FaArrowRight />

                    </button>

                </div>

                {/* RIGHT CARD */}

                <div className="newsCard">

                    <div className="cardTag">

                        <FaRegNewspaper />

                        <span>News Room</span>

                    </div>

                    <div className="newsList">

                        {

                            visibleNews.map((item) => ( // 👈 CHANGED: news -> visibleNews

                                <div
                                    className="newsItem"
                                    key={item.id}
                                >

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

                                
                                <a href="#"
                                    className="viewAll"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        setShowAll(!showAll);
                                    }}
                                >

                                    {showAll ? "View Less News" : "View All News"}

                                </a>

                            )

                        }

                    </div>

                </div>

            </div>

        </section>

    );

}