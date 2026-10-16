import { API_URL } from "./api";

export async function getComeRacingServices() {
    const response = await fetch(
        `${API_URL}/Come_racing_services_get_api.php`,
        { cache: "no-store" }
    );

    let result;
    try {
        result = await response.json();
    } catch {
        throw new Error("The race course services API returned an invalid response.");
    }

    if (!response.ok || !result?.success || !result.data) {
        throw new Error(result?.error || "Unable to load race course services details.");
    }

    return {
        paragraphs: Array.isArray(result.data.paragraphs) ? result.data.paragraphs : [],
    };
}