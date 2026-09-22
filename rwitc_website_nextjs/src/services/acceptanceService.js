import { API_URL } from "./api";

export async function getAcceptance(
    date,
    type = "",
    raceType = ""
) {
    try {
        const params = new URLSearchParams();

        params.set("date", date);

        if (type) {
            params.set("type", type);
        }

        if (raceType) {
            params.set("race_type", raceType);
        }

        const response = await fetch(
            `${API_URL}/acceptance_get_api.php?${params.toString()}`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch acceptance data");
        }

        const json = await response.json();

        if (!json.success) {
            throw new Error(
                json.error || "Failed to fetch acceptance data"
            );
        }

        const data = json.data || {};

        // Archive dates:
        // API returns the raw Acceptance_<date>.html markup.
        if (data?.mode === "html") {
            return {
                mode: "html",
                html: data.html || "",
                dayNarrative: "",
                downloadFile: data?.download_file || null,
                downloadAvailable:
                    data?.download_available || false,
                races: [],
                pools: [],
            };
        }

        // Historical / DB-sourced dates.
        return {
            mode: "json",
            html: null,
            dayNarrative: data?.day_narrative || "",
            downloadFile: data?.download_file || null,
            downloadAvailable:
                data?.download_available || false,
            races: data?.races || [],
            pools: data?.pools || [],
        };
    } catch (error) {
        console.error("Acceptance Error :", error);
        throw error;
    }
}
