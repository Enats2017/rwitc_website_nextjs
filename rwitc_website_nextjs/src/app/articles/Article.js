"use client";
import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { FaArrowLeft, FaHorseHead, FaRegCalendarAlt } from "react-icons/fa";
import "./Articles.css";
import { getArticleById } from "../../services/newsService";
import { UPLOAD_URL } from "../../services/api";

function formatDate(dateStr) {
    const d = new Date(dateStr);
    const day = String(d.getDate()).padStart(2, "0");
    const month = String(d.getMonth() + 1).padStart(2, "0");
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

export default function Article() {
    const router = useRouter();
    const searchParams = useSearchParams();
    const id = searchParams.get("id");

    // Page the user came from (kept in the URL by the list screen)
    const pageParam = parseInt(searchParams.get("page"), 10);
    const page = pageParam > 0 ? pageParam : 1;

    const [article, setArticle] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        window.scrollTo(0, 0);
    }, [id]);

    useEffect(() => {
        let cancelled = false;

        async function loadArticle() {
            setLoading(true);

            const data = await getArticleById(id);

            if (cancelled) return;

            setArticle(data);
            setLoading(false);
        }

        if (id) loadArticle();

        return () => {
            cancelled = true;
        };
    }, [id]);

    const fixImagePaths = (html) => {
        if (!html) return html;
        return html.replace(
            /src=["']\/?page_images\//g,
            `src="${UPLOAD_URL}/page_images/`
        );
    };

    // Go back to the same pagination page the user opened this article from
    const handleBack = () => {
        router.push(`/articles?page=${page}`);
    };

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
                    <button className="backToArticlesBtn" onClick={handleBack}>
                        <FaArrowLeft className="backToArticlesIcon" />
                        <span>Back to Articles</span>
                    </button>

                    {loading && (
                        <div className="articlesState">
                            <div className="articlesLoader" />
                            <p>Loading article…</p>
                        </div>
                    )}

                    {!loading && !article && (
                        <div className="articlesState">
                            <p>Article not found.</p>
                        </div>
                    )}

                    {!loading && article && (
                        <div className="articleCard articleDetailCard">
                            <h4 className="articleTitle articleDetailTitle">
                                {article.article_title}
                            </h4>

                            <p className="articleDate">
                                <FaRegCalendarAlt className="articleDateIcon" />
                                {formatDate(article.date_added)}
                            </p>

                            <div
                                className="articleBodyContent"
                                dangerouslySetInnerHTML={{ __html: fixImagePaths(article.article_body) }}
                            />
                        </div>
                    )}
                </div>

            </div>
        </section>
    );
}