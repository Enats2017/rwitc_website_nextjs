import { API_URL } from "./api";

export async function getHandicaps(date) {

    try {

        const response = await fetch(
            `${API_URL}/erp_handcaps_get_api.php?date=${date}`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch handicaps");
        }

        const data = await response.json();

        return {
            meeting: data.data?.meeting || null,
            races: data.data?.races || [],
        };

    } catch (error) {

        console.error("Handicaps Error :", error);

        throw error;

    }

}