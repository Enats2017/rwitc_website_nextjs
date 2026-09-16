"use client";
import { Suspense, useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import "./Articles.css";
import { FaRegNewspaper } from "react-icons/fa";
import { getAllArticles } from "../../services/newsService";
import Article from "./Article";
import TopHeader from "../components/topheader/TopHeader";
import Footer from "../components/footer/Footer";

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

function ArticlesContent() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const id = searchParams.get("id");

    const [articles, setArticles] = useState([]);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        async function loadArticles() {
            setLoading(true);
            const result = await getAllArticles(page);
            setArticles(result.articles);
            setTotalPages(result.totalPages);
            setLoading(false);
        }
        loadArticles();
    }, [page]);

    const formatDate = (dateStr) => {
        const d = new Date(dateStr);
        const day = String(d.getDate()).padStart(2, "0");
        const month = String(d.getMonth() + 1).padStart(2, "0");
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    };

    if (id) {
        return <Article />;
    }

    return (
        <section className="articlesSection">
            <div className="articlesHeading">
                <h1><FaRegNewspaper /> Articles</h1>
            </div>

            <div className="articlesContainer">
                {loading ? (
                    <p>Loading...</p>
                ) : (
                    articles.map((item) => (
                        <div className="articleCard" key={item.articles_id}>
                            <h4 className="articleTitle">{item.article_title}</h4>
                            <p className="articleDate">{formatDate(item.date_added)}</p>
                            <div className="articleBtnWrap">
                                <button
                                    className="articleViewBtn"
                                    onClick={() => router.push(`/articles?id=${item.articles_id}`)}
                                >
                                    View More ...
                                </button>
                            </div>
                        </div>
                    ))
                )}

                {totalPages > 1 && (
                    <div className="pagination">
                        <button
                            className="pageBtn navBtn"
                            disabled={page === 1}
                            onClick={() => setPage(page - 1)}
                        >
                            Prev
                        </button>

                        {getPageNumbers(page, totalPages).map((p, idx) =>
                            p === "..." ? (
                                <span key={`dots-${idx}`} className="pageDots">...</span>
                            ) : (
                                <button
                                    key={p}
                                    className={`pageBtn ${p === page ? "activePage" : ""}`}
                                    onClick={() => setPage(p)}
                                >
                                    {p}
                                </button>
                            )
                        )}

                        <button
                            className="pageBtn navBtn"
                            disabled={page === totalPages}
                            onClick={() => setPage(page + 1)}
                        >
                            Next
                        </button>
                    </div>
                )}
            </div>
        </section>
    );
}

export default function ArticlesPage() {
    return (
        <>
            <TopHeader />
            <Suspense fallback={<div>Loading...</div>}>
                <ArticlesContent />
            </Suspense>
            <Footer />
        </>
    );
}