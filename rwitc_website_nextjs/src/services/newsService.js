import { API_URL } from "./api";
export async function getNews() {

    try {

        const response = await fetch(
            `${API_URL}/News_get_api.php`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch news");
        }

        const data = await response.json();

        return data.data || [];

    } catch (error) {

        console.error("News Error :", error);

        return [];

    }

}

// ---------------- ALL ARTICLES (LIST, PAGE-WISE) ----------------
export async function getAllArticles(page = 1) {

    try {

        const response = await fetch(
            `${API_URL}/viewArticles_new.php?page=${page}`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch articles");
        }

        const data = await response.json();

        return {
            articles: data.data || [],
            currentPage: data.current_page || 1,
            totalPages: data.total_pages || 1,
        };

    } catch (error) {

        console.error("Articles List Error :", error);

        return { articles: [], currentPage: 1, totalPages: 1 };

    }

}

// ---------------- SINGLE ARTICLE (BY ID) ----------------
export async function getArticleById(id) {

    try {

        const response = await fetch(
            `${API_URL}/viewArticles_new.php?id=${id}`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch article");
        }

        const data = await response.json();

        return data.success ? data.data : null;

    } catch (error) {

        console.error("Single Article Error :", error);

        return null;

    }

}