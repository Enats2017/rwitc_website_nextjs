import { API_URL } from "./api";

// Returns: { mumbai: { main: [...] }, pune: { main: [...], monsoon: [...] } }
// Each record: { id, distance, horse_name, weight, race_date, time_min, time_sec, notes }
export async function getRecordTimings() {
    try {
        const response = await fetch(`${API_URL}/record_timings_api.php`);
        if (!response.ok) { throw new Error("Failed to fetch record timings"); }
        const data = await response.json();
        return data.data || {};
    } catch (error) {
        console.error("Record Timings Error :", error);
        throw error;
    }
}