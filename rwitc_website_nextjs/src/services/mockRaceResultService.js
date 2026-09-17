import { API_URL } from "./api";

/**
 * Fetch Mock Race Result calendar events.
 *
 * @param {number} year  Full year, e.g. 2026
 * @param {number} month JavaScript month number (0 = January, 11 = December)
 */
export async function getMockRaceResults(year, month) {
    const monthNumber = month + 1;

    const res = await fetch(
        `${API_URL}/mock_race_result_calendar_api.php?year=${year}&month=${monthNumber}`
    );

    if (!res.ok) {
        throw new Error(`Mock Race Result API failed: ${res.status}`);
    }

    const json = await res.json();

    if (!json.success) {
        throw new Error(
            json.error || "Unable to fetch mock race results."
        );
    }

    return Array.isArray(json.data) ? json.data : [];
}
