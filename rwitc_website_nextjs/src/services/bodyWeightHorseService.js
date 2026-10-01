import { API_URL } from "./api";

// export async function getBodyWeightHorses() {
//     try {
//         const response = await fetch(
//             `${API_URL}/bodyWeightHorseApi.php`
//         );


export async function getBodyWeightHorses() {
    try {
        console.log(
            "BODY WEIGHT API URL:",
            `${API_URL}/bodyWeightHorseApi.php`
        );

        const response = await fetch(
            `${API_URL}/bodyWeightHorseApi.php`
        );


        if (!response.ok) {
            throw new Error("Failed to fetch Body Weight Horses");
        }

        const data = await response.json();

        return data || [];
    } catch (error) {
        console.error("Body Weight Horses Error :", error);

        return [];
    }
}