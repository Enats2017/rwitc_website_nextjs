import { API_URL } from "./api";

export async function getHorseSuggestions(letter) {

    try {

        const response = await fetch(
            `${API_URL}/Horse_autocomplete_get_api.php?letter=${encodeURIComponent(letter)}`
        );

        const json = await response.json();

        if (!json.success) {
            return [];
        }

        return json.data?.results || [];

    } catch (error) {

        console.error("Performance Profile Autocomplete Error:", error);

        return [];

    }

}

export async function getHorseProfile(horseName) {

    try {

        const response = await fetch(
            `${API_URL}/Horse_profile_get_api.php?horsename=${encodeURIComponent(horseName)}`
        );

        const json = await response.json();

        if (!json.success || !json.data) {
            return { found: false, runs_data: [], totals: null, horse_name: "", sire_dame_details: "", message: "Something went wrong." };
        }

        if (!json.data.found) {
            return { found: false, runs_data: [], totals: null, horse_name: "", sire_dame_details: "", message: json.data.message || "No records found." };
        }

        return json.data;

    } catch (error) {

        console.error("Horse Profile Error:", error);

        return { found: false, runs_data: [], totals: null, horse_name: "", sire_dame_details: "", message: "Something went wrong. Please try again." };

    }

}

export async function getRaceResult(raceno, racedate) {

    try {

        const response = await fetch(
            `${API_URL}/Race_result_get_api_new.php?raceno=${encodeURIComponent(raceno)}&racedate=${encodeURIComponent(racedate)}`
        );

        const json = await response.json();

        if (!json.success || !json.data) {
            return { found: false, results: [], message: "Something went wrong." };
        }

        if (!json.data.found) {
            return { found: false, results: [], message: json.data.message || "No results found." };
        }

        return json.data;

    } catch (error) {

        console.error("Race Result Error:", error);

        return { found: false, results: [], message: "Something went wrong. Please try again." };

    }

}