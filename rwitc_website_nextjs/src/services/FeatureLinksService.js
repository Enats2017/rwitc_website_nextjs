import { API_URL } from "./api";

export async function getFeatureLinks() {
    const empty = { indian_stud_book: "", video_archives: "", webportal: "", live_stream: "" };

    try {
        const res = await fetch(`${API_URL}/feature_links_api.php`, {
            method: "GET",
            cache: "no-store",
        });
        if (!res.ok) return empty;

        const json = await res.json();
        const data = (json && json.data) || {};

        return {
            indian_stud_book: typeof data.indian_stud_book === "string" ? data.indian_stud_book : "",
            video_archives: typeof data.video_archives === "string" ? data.video_archives : "",
            webportal: typeof data.webportal === "string" ? data.webportal : "",
            live_stream: typeof data.live_stream === "string" ? data.live_stream : "",
        };
    } catch (error) {
        console.error("Feature links request failed:", error);
        return empty;
    }
}