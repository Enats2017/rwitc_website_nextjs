import { API_URL } from "./api";

export async function getMedia() {
    try {
        const res = await fetch(`${API_URL}/images_video_upload_get.php`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        return json.data || [];
    } catch (err) {
        console.warn("getMedia failed:", err.message);
        return [];
    }
}

export async function getRaceMedia() {
    const fallback = { preRace: [], postRace: [], trackWork: [] };
    try {
        const res = await fetch(`${API_URL}/articles_get_api.php`);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        return json.data || fallback;
    } catch (err) {
        console.warn("getRaceMedia failed:", err.message);
        return fallback;
    }
}

export async function getRaceDayStatus() {
    const fallback = { raceDay: false, today: "", mediaTipsUrl: "", updatesUrl: "" };
    try {
        const res = await fetch(
            `${API_URL}/race_day_status_get.php`,
            { cache: "no-store" }
        );
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        return json.data || fallback;
    } catch (err) {
        return fallback;
    }
}