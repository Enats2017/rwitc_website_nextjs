import { API_URL } from "./api";

export async function getTrainers() {

    try {

        const response = await fetch(
            `${API_URL}/get_trainers_api.php`
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message || json.error || "Failed to fetch trainers");
        }

        return json.data || [];

    } catch (error) {

        console.error("Trainers API Error :", error);

        return null;

    }

}

export async function getTrainerHorses(trainerName) {

    try {

        const response = await fetch(
            `${API_URL}/get_trainer_horses_api.php?trainer=${encodeURIComponent(trainerName)}`
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message || json.error || "Failed to fetch trainer horses");
        }

        const data = json.data;

        return {
            trainer: data?.trainer || trainerName,
            horses: data?.horses || [],
        };

    } catch (error) {

        console.error("Trainer Horses API Error :", error);

        return null;

    }

}