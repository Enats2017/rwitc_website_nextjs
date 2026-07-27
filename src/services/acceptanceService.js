import { API_URL } from "./api";

export async function getAcceptance(date) {

    try {

        const response = await fetch(
            `${API_URL}/acceptance_get_api.php?date=${date}`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch acceptance data");
        }

        const data = await response.json();

        return {
            dayNarrative: data.data?.day_narrative || "",
            races: data.data?.races || [],
            pools: data.data?.pools || [],
        };

    } catch (error) {

        console.error("Acceptance Error :", error);

        throw error;

    }

}