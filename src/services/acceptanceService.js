import { API_URL } from "./api";

export async function getAcceptance(date) {

    try {

        const response = await fetch(
            `${API_URL}/acceptance_get_api.php?date=${date}`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch acceptance data");
        }

        const json = await response.json();

        if (!json.success) {
            throw new Error(json.error || "Failed to fetch acceptance data");
        }

        const data = json.data;

        // Archive dates (date > 2022-09-25): API returns the raw
        // Acceptance_<date>.html markup as-is. Pass it straight through.
        if (data?.mode === "html") {
            return {
                mode: "html",
                html: data.html || "",
                dayNarrative: "",
                downloadFile: data?.download_file || null,
                downloadAvailable: data?.download_available || false,
                races: [],
                pools: [],
            };
        }

        // DB-sourced dates (date <= 2022-09-25): structured JSON.
        return {
            mode: "json",
            html: null,
            dayNarrative: data?.day_narrative || "",
            downloadFile: data?.download_file || null,
            downloadAvailable: data?.download_available || false,
            races: data?.races || [],
            pools: data?.pools || [],
        };

    } catch (error) {

        console.error("Acceptance Error :", error);

        throw error;

    }

}