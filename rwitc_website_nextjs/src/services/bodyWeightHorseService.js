import { API_URL } from "./api";

export async function getBodyWeightHorses() {
    try {
        const response = await fetch(`${API_URL}/bodyWeightHorseApi.php`);

        if (!response.ok) {
            throw new Error("Failed to fetch Body Weight Horses");
        }

        const json = await response.json();

        if (!json.success) {
            throw new Error(json.error || "Failed to fetch Body Weight Horses");
        }

        const horses = json.data?.horses || [];

        return horses.map((item) => ({
            horse: item.horse,
            runs: (item.runs || []).map((run) => ({
                raceno: run.raceno,
                weight: run.weight,
            })),
        }));
    } catch (error) {
        console.error("Body Weight Horses Error :", error);

        return [];
    }
}