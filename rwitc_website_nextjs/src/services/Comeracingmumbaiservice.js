import { API_URL } from "./api";

export async function getComeRacingMumbai() {
    const response = await fetch(
        `${API_URL}/Come_racing_mumbai_get_api.php`,
        { cache: "no-store" }
    );

    let result;
    try {
        result = await response.json();
    } catch {
        throw new Error("The Mumbai race course service returned an invalid response.");
    }

    if (!response.ok || !result?.success || !result.data) {
        throw new Error(result?.error || "Unable to load Mumbai race course details.");
    }

    return {
        points: Array.isArray(result.data.points) ? result.data.points : [],
    };
}