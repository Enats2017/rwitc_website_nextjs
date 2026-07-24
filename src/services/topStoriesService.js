import { API_URL } from "./api";
export async function getTopStories() {

    try {

        const response = await fetch(
            `${API_URL}/top-stories_get_api.php`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch Top Stories");
        }

        const data = await response.json();

        return data.data || [];

    }
    catch (error) {

        console.error("Top Stories Error :", error);

        return [];

    }

}