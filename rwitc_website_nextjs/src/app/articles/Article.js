"use client";
import { useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { FaArrowLeft } from "react-icons/fa";
import "./Articles.css";
import { getArticleById } from "../../services/newsService";
import { UPLOAD_URL } from "../../services/api";

export default function Article() {
    const searchParams = useSearchParams();
    const id = searchParams.get("id");
    const [article, setArticle] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        async function loadArticle() {
            setLoading(true);
            const data = await getArticleById(id);
            setArticle(data);
            setLoading(false);
        }
        if (id) loadArticle();
    }, [id]);

    const formatDate = (dateStr) => {
        const d = new Date(dateStr);
        const day = String(d.getDate()).padStart(2, "0");
        const month = String(d.getMonth() + 1).padStart(2, "0");
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    };

    const fixImagePaths = (html) => {
        if (!html) return html;
        return html.replace(
            /src=["']\/?page_images\//g,
            `src="${UPLOAD_URL}/page_images/`
        );
    };

    if (loading) return <p style={{ padding: "40px", color: "#1c1c1c" }}>Loading...</p>;
    if (!article) return <p style={{ padding: "40px", color: "#1c1c1c" }}>Article not found.</p>;

    return (
        <section className="articlesSection">
            <div className="articlesContainer">
                <button
                    className="backToArticlesBtn"
                    onClick={() => { window.location.href = "/rwitc-website/articles"; }}
                >
                    <FaArrowLeft className="backToArticlesIcon" />
                    <span>Back to Articles</span>
                </button>

                <div className="articleCard" style={{ marginTop: "20px" }}>
                    <h4 className="articleTitle">{article.article_title}</h4>
                    <p className="articleDate">{formatDate(article.date_added)}</p>
                    <div
                        className="articleBodyContent"
                        dangerouslySetInnerHTML={{ __html: fixImagePaths(article.article_body) }}
                    />
                </div>
            </div>
        </section>
    );
}