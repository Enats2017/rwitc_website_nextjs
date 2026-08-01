import { API_URL } from "./api";

export async function getHandicaps(date) {

    try {

        const response = await fetch(
            `${API_URL}/erp_handcaps_get_api.php?date=${date}`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch handicaps");
        }

        const json = await response.json();

        if (!json.success) {
            throw new Error(json.error || "Failed to fetch handicaps");
        }

        const data = json.data;

        // Archive dates (date > 2022-09-25): API returns the raw
        // Handicaps_<date>.html markup as-is, same as the old PHP
        // page's `include`. Pass it straight through — no parsing.
        if (data?.mode === "html") {
            return {
                mode: "html",
                html: data.html || "",
                meeting: null,
                downloadFile: data?.download_file || null,
                downloadAvailable: data?.download_available || false,
                races: []
            };
        }

        // DB-sourced dates (date <= 2022-09-25): structured JSON.
        return {
            mode: "json",
            html: null,
            meeting: data?.formatted_date || null,
            dayNarrative: data?.day_narrative || null,
            downloadFile: data?.download_file || null,
            downloadAvailable: data?.download_available || false,
            races: (data?.races || []).map((race) => ({
                srno: race.srno,
                race_name: race.race_name,
                narrent: race.narr_ent,
                distance: race.distance,
                grade: race.grade,
                foreign_jockeys_eligible: race.foreign_jockeys_eligible,
                weight_note: race.hterms,
                ss_ban: race.ss_ban_horses || [],
                vo_ban: race.vo_ban_horses || [],
                mk_ban: race.mk_ban_horses || [],
                horses: (race.weights || []).map((horse) => ({
                    horseseq: horse.HORSESEQ ?? horse.horseseq,
                    order: horse.SORDER ?? horse.order,
                    name: horse.NAME || horse.name,
                    color: horse.COLOR || horse.color || "",
                    sex: horse.SEX || horse.sex || "",
                    age: horse.AGE ?? horse.age ?? null,
                    weight: horse.WEIGHT ?? horse.weight,
                    rating: horse.HRATING ?? horse.rating,
                    breeding: horse.breeding ||
                        [horse.SIRE, horse.DAM, horse.DAMNAT]
                            .filter(Boolean)
                            .join(" / "),
                    trainer: horse.TRAINERNME || horse.trainer
                }))
            }))
        };

    } catch (error) {

        console.error("Handicaps Error :", error);

        throw error;

    }

}