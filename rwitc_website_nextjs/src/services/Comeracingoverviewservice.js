import { API_URL } from "./api";

export async function getComeRacingOverview() {
    const response = await fetch(
        `${API_URL}/Come_racing_overview_get_api.php`,
        { cache: "no-store" }
    );

    let result;
    try {
        result = await response.json();
    } catch {
        throw new Error("The come racing overview service returned an invalid response.");
    }

    if (!response.ok || !result?.success || !result.data) {
        throw new Error(result?.error || "Unable to load come racing overview.");
    }

    return {
        content: typeof result.data.content === "string" ? result.data.content : "",
    };
}