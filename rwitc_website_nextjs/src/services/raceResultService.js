import { API_URL } from "./api";

export async function getRaceResult(racedate, raceno) {

    try {

        const params = new URLSearchParams({ racedate });

        if (raceno) {
            params.set("raceno", raceno);
        }

        const response = await fetch(
            `${API_URL}/Race_result_get_api.php?${params.toString()}`
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message || json.error || "Failed to fetch race results");
        }

        const data = json.data;

        // Archive dates (racedate > 2022-09-25): API returns raw
        // Race_results_<date>.html markup as-is. Pass it straight through.
        if (data?.mode === "html") {
            return {
                mode: "html",
                html: data.html || "",
                found: data?.found ?? true,
                downloadFile: data?.download_file || null,
                downloadAvailable: data?.download_available || false,
                raceHeader: null,
                voidRace: false,
                results: [],
            };
        }

        // DB-sourced dates (racedate <= 2022-09-25): structured JSON,
        // one race at a time (raceno + racedate).
        return {
            mode: "json",
            html: null,
            found: data?.found ?? false,
            message: data?.message || null,
            raceNo: data?.race_no ?? raceno,
            raceDate: data?.race_date ?? racedate,
            raceHeader: data?.race_header || null,
            voidRace: data?.void_race || false,
            results: data?.results || [],
        };

    } catch (error) {

        console.error("Race Result Error :", error);

        throw error;

    }

}