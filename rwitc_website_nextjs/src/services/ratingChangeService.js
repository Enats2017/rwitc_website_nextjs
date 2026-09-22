import { API_URL } from "./api";

export async function getRatingChange(
    date,
    type = "rating_change",
    raceType = "post_race"
) {
    try {
        const params = new URLSearchParams();

        params.set("date", date);
        params.set("type", type);
        params.set("race_type", raceType);

        const response = await fetch(
            `${API_URL}/erp_ratingchange_get_api.php?${params.toString()}`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch rating change data");
        }

        const json = await response.json();

        if (!json.success) {
            throw new Error(
                json.error ||
                json.message ||
                "Failed to fetch rating change data"
            );
        }

        const data = json.data || {};

        return {
            found: data?.found ?? false,
            message: data?.message || null,
            html: data?.html || "",
            downloadFile: data?.download_file || null,
            downloadAvailable: data?.download_available ?? false,
        };
    } catch (error) {
        console.error("Rating Change Error :", error);
        throw error;
    }
}