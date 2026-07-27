import { API_URL } from "./api";

export async function getDeclarations(date) {

    try {

        const response = await fetch(
            `${API_URL}/declarations_get_api.php?date=${date}`
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message || "Failed to fetch declarations");
        }

        return {
            dayNarrative: json.data?.day_narrative || "",
            races: json.data?.races || [],
            pools: json.data?.pools || [],
        };

    } catch (error) {

        console.error("Declarations Error :", error);

        throw error;

    }

}