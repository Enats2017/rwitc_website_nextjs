import { API_URL } from "./api";

export async function getRaceResult(
    racedate,
    raceno,
    type = "race_result",
    raceType = "post_race"
) {
    try {
        const params = new URLSearchParams();

        params.set("date", racedate);
        params.set("type", type);
        params.set("race_type", raceType);

        if (raceno) {
            params.set("raceno", raceno);
        }

        const response = await fetch(
            `${API_URL}/raceResults_post_race_get_api.php?${params.toString()}`
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(
                json.message ||
                json.error ||
                "Failed to fetch race results"
            );
        }

        const data = json.data || {};

        if (data?.mode === "html") {
            return {
                mode: "html",
                html: data.html || "",
                found: data?.found ?? false,
                date: data?.date || racedate,
                type: data?.type || type,
                raceType: data?.race_type || raceType,
                downloadFile: data?.download_file || null,
                downloadAvailable: data?.download_available ?? false,
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