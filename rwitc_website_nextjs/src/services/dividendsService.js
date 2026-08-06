import { API_URL } from "./api";

export async function getDividends() {

    try {

        const response = await fetch(
            `${API_URL}/fetchdividends_get_api.php`,
            {
                method: "GET",
                cache: "no-store",
            }
        );

        if (!response.ok) {
            throw new Error("Failed to fetch dividends.");
        }

        const data = await response.json();

        if (!Array.isArray(data)) {
            throw new Error("Invalid API response.");
        }

        return data;

    } catch (error) {

        console.error("Dividends API Error :", error);

        return [];

    }

}