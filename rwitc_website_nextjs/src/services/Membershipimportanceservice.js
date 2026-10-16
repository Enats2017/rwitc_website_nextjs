import { API_URL } from "./api";

export async function getMembershipImportance() {
    try {
        const res = await fetch(
            `${API_URL}/Membership_importance_get_api.php`,
            { cache: "no-store" }
        );

        const json = await res.json();

        if (!res.ok || !json.success) {
            return {
                exists: false,
                text: "",
                error: json?.error || "Unable to load content."
            };
        }

        return {
            exists: json.data.exists,
            text: json.data.text || "",
            error: null
        };
    } catch (e) {
        return {
            exists: false,
            text: "",
            error: "Unable to load content."
        };
    }
}