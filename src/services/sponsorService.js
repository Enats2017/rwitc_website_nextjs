import { API_URL } from "./api";
export async function getSponsors() {

    try {

        const response = await fetch(
            `${API_URL}/sponser_get_api.php`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch sponsors");
        }

        const data = await response.json();

        return data.data || [];

    }
    catch (error) {

        console.error("Sponsor Error :", error);

        return [];

    }

}