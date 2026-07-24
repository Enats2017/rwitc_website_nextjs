import { API_URL } from "./api";
export async function getBanners() {
    try {
        const response = await fetch(
            `${API_URL}/banner_get_api.php`
        );
        if (!response.ok) { throw new Error("Failed to fetch banners"); }
        const data = await response.json();
        return data.data || [];
    } catch (error) {
        console.error("Banner Error :", error);
        return [];
    }
}