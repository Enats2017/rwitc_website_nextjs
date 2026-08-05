import { API_URL } from "./api";

export async function getRatingChange(date) {

    try {

        const response = await fetch(
            `${API_URL}/erp_ratingchange_get_api.php?date=${date}`
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message || json.error || "Failed to fetch rating change");
        }

        const data = json.data;

        
        return {
            found: data?.found ?? false,
            message: data?.message || null,
            html: data?.html || "",
        };

    } catch (error) {

        console.error("Rating Change Error :", error);

        throw error;

    }

}