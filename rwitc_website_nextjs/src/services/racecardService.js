import { API_URL } from "./api";

export async function getRaceCard(date) {

    try {

        const response = await fetch(
            `${API_URL}/Racecard_get_api.php?date=${date}`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch race card");
        }

        const json = await response.json();

        if (!json.success) {
            throw new Error(json.error || "Failed to fetch race card");
        }

        const data = json.data;

        // Archive dates (date > 2022-11-08): API returns the raw
        // Race_Card_<date>.html markup as-is, same as the old PHP
        // page's `include`. Pass it straight through — no parsing.
        if (data?.mode === "html") {
            return {
                mode: "html",
                html: data.html || "",
                dayLabel: null,
                dayNarrative: "",
                clubName: null,
                downloadFile: data?.download_file || null,
                downloadAvailable: data?.download_available || false,
                races: [],
                pools: [],
            };
        }

        // DB-sourced dates (date <= 2022-11-08): structured JSON.
        return {
            mode: "json",
            html: null,
            dayLabel: data?.day_label || null,
            dayNarrative: data?.day_narrative || "",
            clubName: data?.club_name || null,
            downloadFile: data?.download_url || null,
            downloadAvailable: !!data?.download_url,
            races: (data?.races || []).map((race) => ({
                raceNo: race.race_no,
                raceNoSeason: race.race_no_season,
                raceName: race.race_name,
                division: race.division,
                narrativeEntry: race.narrative_entry,
                distance: race.distance,
                time: race.time,
                foreignJockeysEligible: race.foreign_jockeys_eligible,
                horses: (race.horses || []).map((horse) => ({
                    cardNo: horse.card_no,
                    name: horse.name,
                    horseseq: horse.horseseq,
                    weight: horse.weight,
                    jockey: horse.jockey,
                    trainer: horse.trainer,
                    sireDam: horse.sire_dam,
                    damNation: horse.dam_nation,
                    drawNo: horse.draw_no,
                    equipment: horse.equipment,
                    shoe: horse.shoe,
                    shoeDetail: horse.shoe_detail,
                    bitsDetail: horse.bits_detail,
                    stud: horse.stud,
                    colourNo: horse.colour_no,
                    breeder: horse.breeder,
                    foaled: horse.foaled,
                    rating: horse.rating,
                    hraRating: horse.hra_rating,
                    distanceWon: horse.distance_won,
                    ownership: horse.ownership,
                    sexEtc: horse.sex_etc,
                    runsData: horse.runs_data,
                    colours: horse.colours,
                    performanceHistory: (horse.performance_history || []).map((perf) => ({
                        raceDate: perf.race_date,
                        raceNo: perf.race_no,
                        jockey: perf.jockey,
                        raceClass: perf.race_class,
                        distance: perf.distance,
                        weight: perf.weight,
                        placing: perf.placing,
                        time: perf.time,
                        videoUrl: perf.video_url,
                    })),
                })),
            })),
            pools: data?.pools || [],
        };

    } catch (error) {

        console.error("Race Card Error :", error);

        throw error;

    }

}