import { API_URL } from "./api";

export async function getWebsiteLogos() {

    try {

        const response = await fetch(
            `${API_URL}/website_logo_get_api.php`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch website logo");
        }

        const data = await response.json();

        return data.data && !Array.isArray(data.data) ? data.data : {};

    }
    catch (error) {

        console.error("Website Logo Error :", error);

        return {};

    }

}