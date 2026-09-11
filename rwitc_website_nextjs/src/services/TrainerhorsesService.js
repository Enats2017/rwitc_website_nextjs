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

export async function getFoalRecords(mareName, damnat) {

    try {

        const response = await fetch(
            `${API_URL}/get_foal_records_api.php?mareName=${encodeURIComponent(mareName)}&damnat=${encodeURIComponent(damnat || "")}`
        );

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message || json.error || "Failed to fetch foal records");
        }

        const data = json.data;

        return {
            mareName: data?.mareName || mareName,
            damNat: data?.damNat || damnat || "",
            foals: data?.foals || [],
        };

    } catch (error) {

        console.error("Foal Records API Error :", error);

        return null;

    }

}