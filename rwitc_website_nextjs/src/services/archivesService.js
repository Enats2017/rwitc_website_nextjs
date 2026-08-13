import { API_URL } from "./api";

export async function getArchives(start, end) {

    try {

        const response = await fetch(
            `${API_URL}/fetchArchives_get_api.php?start=${start}&end=${end}`,
            {
                method: "GET",
                cache: "no-store",
            }
        );

        if (!response.ok) {
            throw new Error("Failed to fetch archives.");
        }

        const result = await response.json();

        if (!result.success || !Array.isArray(result.data)) {
            throw new Error(result.error || "Invalid API response.");
        }

        return result.data;

    } catch (error) {

        console.error("Archives API Error :", error);

        return [];

    }

}