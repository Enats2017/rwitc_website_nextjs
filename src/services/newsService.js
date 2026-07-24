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