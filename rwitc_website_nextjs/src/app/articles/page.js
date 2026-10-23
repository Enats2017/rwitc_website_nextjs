"use client";
import { Suspense, useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import "./Articles.css";
import {
    FaHorseHead,
    FaRegCalendarAlt,
    FaArrowRight,
    FaChevronLeft,
    FaChevronRight,
} from "react-icons/fa";
import { getAllArticles } from "../../services/newsService";
import Article from "./Article";
import TopHeader from "../components/topheader/TopHeader";
import Footer from "../components/footer/Footer";

const SCROLL_KEY = "articlesListScroll";

function getPageNumbers(current, total) {
    const pages = [];
    const delta = 1;

    for (let i = 1; i <= total; i++) {
        if (
            i === 1 ||
            i === total ||
            (i >= current - delta && i <= current + delta)
        ) {
            pages.push(i);
        } else if (pages[pages.length - 1] !== "...") {
            pages.push("...");
        }
    }

    return pages;
}

function formatDate(dateStr) {
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, "0");
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

function ArticlesContent() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const id = searchParams.get("id");

    // The current page lives in the URL (?page=106), so it survives
    // opening an article and pressing Back.
    const pageParam = parseInt(searchParams.get("page"), 10);
    const page = pageParam > 0 ? pageParam : 1;

    const [articles, setArticles] = useState([]);
    const [totalPages, setTotalPages] = useState(1);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        // Detail view is showing, no need to load the list
        if (id) return;

        let cancelled = false;

        async function loadArticles() {
            setLoading(true);

            const result = await getAllArticles(page);

            if (cancelled) return;

            setArticles(result.articles || []);
            setTotalPages(result.totalPages || 1);
            setLoading(false);

            // Restore the scroll position saved when "View More" was clicked
            setTimeout(() => {
                try {
                    const saved = JSON.parse(sessionStorage.getItem(SCROLL_KEY) || "null");

                    if (saved && saved.page === page) {
                        window.scrollTo(0, saved.y);
                    }

                    sessionStorage.removeItem(SCROLL_KEY);
                } catch (e) {
                    // sessionStorage not available, ignore
                }
            }, 50);
        }

        loadArticles();

        return () => {
            cancelled = true;
        };
    }, [page, id]);

    const goToPage = (p) => {
        if (p === page || p < 1 || p > totalPages) return;
        router.push(`/articles?page=${p}`);
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const openArticle = (articleId) => {
        try {
            sessionStorage.setItem(
                SCROLL_KEY,
                JSON.stringify({ page, y: window.scrollY })
            );
        } catch (e) {
            // sessionStorage not available, ignore
        }

        router.push(`/articles?id=${articleId}&page=${page}`);
    };

    if (id) {
        return <Article />;
    }

    return (
        <section className="articlesSection">
            <div className="articlesInner">

                <div className="aboutTitleWrap">
                    <h1 className="aboutHeading">Articles</h1>

                    <div className="sectionDivider">
                        <span className="dividerLine dividerLineLeft"></span>
                        <FaHorseHead className="dividerIcon" />
                        <span className="dividerLine dividerLineRight"></span>
                    </div>
                </div>

                <div className="articlesContainer">
                    {loading ? (
                        <div className="articlesState">
                            <div className="articlesLoader" />
                            <p>Loading articles…</p>
                        </div>
                    ) : articles.length === 0 ? (
                        <div className="articlesState">
                            <p>No articles found.</p>
                        </div>
                    ) : (
                        articles.map((item) => (
                            <div className="articleCard" key={item.articles_id}>
                                <h4 className="articleTitle">{item.article_title}</h4>

                                <div className="articleCardFooter">
                                    <p className="articleDate">
                                        <FaRegCalendarAlt className="articleDateIcon" />
                                        {formatDate(item.date_added)}
                                    </p>

                                    <button
                                        className="articleViewBtn"
                                        onClick={() => openArticle(item.articles_id)}
                                    >
                                        View More
                                        <FaArrowRight className="articleViewBtnIcon" />
                                    </button>
                                </div>
                            </div>
                        ))
                    )}

                    {!loading && totalPages > 1 && (
                        <div className="pagination">
                            <button
                                className="pageBtn navBtn"
                                disabled={page === 1}
                                onClick={() => goToPage(page - 1)}
                            >
                                <FaChevronLeft className="navBtnIcon" />
                                <span className="navBtnText">Prev</span>
                            </button>

                            {getPageNumbers(page, totalPages).map((p, idx) =>
                                p === "..." ? (
                                    <span key={`dots-${idx}`} className="pageDots">...</span>
                                ) : (
                                    <button
                                        key={p}
                                        className={`pageBtn ${p === page ? "activePage" : ""}`}
                                        onClick={() => goToPage(p)}
                                    >
                                        {p}
                                    </button>
                                )
                            )}

                            <button
                                className="pageBtn navBtn"
                                disabled={page === totalPages}
                                onClick={() => goToPage(page + 1)}
                            >
                                <span className="navBtnText">Next</span>
                                <FaChevronRight className="navBtnIcon" />
                            </button>
                        </div>
                    )}
                </div>

            </div>
        </section>
    );
}

export default function ArticlesPage() {
    return (
        <>
            <TopHeader />
            <Suspense fallback={<div className="articlesState"><div className="articlesLoader" /></div>}>
                <ArticlesContent />
            </Suspense>
            <Footer />
        </>
    );
}