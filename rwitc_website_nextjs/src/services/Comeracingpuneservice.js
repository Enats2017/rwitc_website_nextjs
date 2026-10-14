import { API_URL } from "./api";

export async function getComeRacingPune() {
    const response = await fetch(
        `${API_URL}/Come_racing_pune_get_api.php`,
        { cache: "no-store" }
    );

    let result;
    try {
        result = await response.json();
    } catch {
        throw new Error("The Pune race course service returned an invalid response.");
    }

    if (!response.ok || !result?.success || !result.data) {
        throw new Error(result?.error || "Unable to load Pune race course details.");
    }

    return {
        points: Array.isArray(result.data.points) ? result.data.points : [],
    };
}