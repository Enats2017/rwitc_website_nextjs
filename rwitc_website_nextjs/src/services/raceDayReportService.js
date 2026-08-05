import { API_URL } from "./api";

export async function getRaceDayReport(date) {

    try {

        const response = await fetch(
            `${API_URL}/raceDayReport_get_api.php?date=${date}`
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message || json.error || "Failed to fetch race day report");
        }

        const data = json.data;

        // Raceday Report has no DB-vs-archive date cutoff like
        // Acceptance/Declarations/Race Result. It always resolves to a
        // static .HTM file on disk (DB only stores the filename pointer),
        // same single-mode pattern as Rating Change.
        return {
            found: data?.found ?? false,
            message: data?.message || null,
            date: data?.date || date,
            dayLabel: data?.day_label || null,
            html: data?.report_html || "",
            downloadFile: data?.download_url || null,
            downloadAvailable: data?.file_exists ?? false,
        };

    } catch (error) {

        console.error("Race Day Report Error :", error);

        throw error;

    }

}