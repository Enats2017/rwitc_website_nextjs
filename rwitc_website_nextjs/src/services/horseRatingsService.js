import { API_URL } from "./api";

export async function getHorseRatings() {

    try {

        const response = await fetch(
            `${API_URL}/erp_all_horse_rating_get_api.php`,
            { cache: "no-store" }
        );

        if (!response.ok) {
            throw new Error("Failed to fetch horse ratings");
        }

        const json = await response.json();

        if (!json.success) {
            throw new Error(json.error || "Failed to fetch horse ratings");
        }

        const data = json.data || {};

        return {
            exists: !!data.exists,
            html: data.html || "",
            date: data.date || null,
            downloadFile: data.download_file || null,
            downloadAvailable: !!data.download_available
        };

    } catch (error) {

        console.error("Horse Ratings Error :", error);

        throw error;

    }

}