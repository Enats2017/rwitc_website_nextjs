import { API_URL } from "./api";

export async function getMembershipCategories() {
    const response = await fetch(
        `${API_URL}/Membership_categories_get_api.php`,
        { cache: "no-store" }
    );

    let result;
    try {
        result = await response.json();
    } catch {
        throw new Error("The membership categories service returned an invalid response.");
    }

    if (!response.ok || !result?.success || !result.data) {
        throw new Error(result?.error || "Unable to load membership categories.");
    }

    return {
        content: typeof result.data.content === "string" ? result.data.content : "",
    };
}