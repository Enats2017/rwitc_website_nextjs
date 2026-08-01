import { API_URL } from "./api";

export async function getDeclarations(date) {

    try {

        const response = await fetch(
            `${API_URL}/declarations_get_api.php?date=${date}`
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message || "Failed to fetch declarations");
        }

        const data = json.data;

        // Archive dates (date > 2022-09-25): API returns raw
        // Declarations_<date>.html markup as-is. Pass it straight through.
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
            races: (data?.races || []).map((race) => ({
                ...race,
                horses: (race.horses || []).map((horse) => ({
                    card_no: horse.card_no,
                    weight: horse.weight,
                    rating: horse.rating,
                    horse_weight: horse.horse_weight,
                    shoe: horse.shoe,
                    draw: horse.draw_no,
                    horse: {
                        name: horse.name,
                        sire: horse.sire,
                        dam: horse.dam,
                        dam_nationality: horse.dam_nation,
                    },
                    trainer: {
                        name: horse.trainer,
                    },
                    jockey: {
                        name: horse.jockey,
                        allowance: horse.jockey_allowance,
                    },
                })),
            })),
            pools: data?.pools || [],
        };

    } catch (error) {

        console.error("Declarations Error :", error);

        throw error;

    }

}