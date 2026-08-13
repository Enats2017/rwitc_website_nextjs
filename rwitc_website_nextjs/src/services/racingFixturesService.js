import { API_URL } from "./api";

export async function getRacingFixtures(start, end) {

    try {

        const params = new URLSearchParams();

        if (start) params.set("start", start);
        if (end) params.set("end", end);

        const qs = params.toString();

        const response = await fetch(
            `${API_URL}/fetchCalendar_get_api.php${qs ? `?${qs}` : ""}`,
            {
                method: "GET",
                cache: "no-store",
            }
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message || json.error || "Failed to fetch racing fixtures");
        }

        return Array.isArray(json.data) ? json.data : [];

    } catch (error) {

        console.error("Racing Fixtures API Error :", error);

        return [];

    }

}