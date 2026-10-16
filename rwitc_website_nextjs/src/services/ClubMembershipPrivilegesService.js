import { API_URL } from "./api";

export async function getClubMembershipPrivileges() {
    const response = await fetch(
        `${API_URL}/Club_membership_privileges_get_api.php`,
        { cache: "no-store" }
    );

    let result;
    try {
        result = await response.json();
    } catch {
        throw new Error("The membership privileges service returned an invalid response.");
    }

    if (!response.ok || !result?.success || !result.data) {
        throw new Error(result?.error || "Unable to load membership privileges.");
    }

    return {
        content: typeof result.data.content === "string" ? result.data.content : "",
    };
}