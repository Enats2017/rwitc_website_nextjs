const API_BASE_URL =
    process.env.NEXT_PUBLIC_API_BASE_URL ||
    "http://localhost/rwitc_website/rwitc_website_api";

export async function getAffiliatedClubs() {
    const response = await fetch(
        `${API_BASE_URL}/Affiliated_clubs_get_api.php`,
        { cache: "no-store" }
    );

    let result;
    try {
        result = await response.json();
    } catch {
        throw new Error("The affiliated clubs service returned an invalid response.");
    }

    if (!response.ok || !result?.success || !result.data) {
        throw new Error(result?.error || "Unable to load affiliated clubs.");
    }

    return {
        fileUrl: typeof result.data.file_url === "string" ? result.data.file_url : "",
        fileName: typeof result.data.file_name === "string" ? result.data.file_name : "",
    };
}
