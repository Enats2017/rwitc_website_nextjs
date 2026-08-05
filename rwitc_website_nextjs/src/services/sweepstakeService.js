import { API_URL } from "./api";

export async function getSweepstakes(id) {

    try {

        const params = id ? `?id=${id}` : "";

        const response = await fetch(
            `${API_URL}/SWEEPSTAKE_get_api.php${params}`
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message || json.error || "Failed to fetch sweepstakes");
        }

        const data = json.data;

        // Detail mode (?id=): a single sweepstake record
        if (data?.mode === "detail") {
            return {
                mode: "detail",
                sweepstake: data.sweepstake || null,
                sweepstakes: [],
            };
        }

        // List mode (default): all sweepstakes
        return {
            mode: "list",
            sweepstake: null,
            sweepstakes: data?.sweepstakes || [],
        };

    } catch (error) {

        console.error("Sweepstake Error :", error);

        throw error;

    }

}