
import { API_URL } from "./api";

export async function getRaceResult(racedate, raceno) {

    try {

        const params = new URLSearchParams({ date: racedate });

        if (raceno) {
            params.set("raceno", raceno);
        }

        const response = await fetch(
            `${API_URL}/raceResults_post_race_get_api.php?${params.toString()}`
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message || json.error || "Failed to fetch race results");
        }

        const data = json.data;

        if (data?.mode === "html") {
            return {
                mode: "html",
                html: data.html || "",
                found: data?.found ?? true,
            };
        }

        return {
            mode: "json",
            found: data?.found ?? false,
            message: data?.message || null,
            date: data?.date || racedate,
            dayLabel: data?.day_label || null,
            dayNarrative: data?.day_narrative || null,
            clubName: data?.club_name || null,
            downloadUrl: data?.download_url || null,
            videoUrl: data?.video_url || null,
            conditions: data?.conditions || null,
            races: data?.races || [],
            pools: data?.pools || null,
        };

    } catch (error) {

        console.error("Race Result Error :", error);

        throw error;

    }

}