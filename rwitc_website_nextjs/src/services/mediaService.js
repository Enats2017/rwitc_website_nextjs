import { API_URL } from "./api";

export async function getMedia() {

    const res = await fetch(
        `${API_URL}/images_video_upload_get.php`
    );

    const json = await res.json();

    return json.data || [];
}

export async function getRaceMedia() {

    const res = await fetch(
        `${API_URL}/articles_get_api.php`
    );

    const json = await res.json();

    return json.data || { preRace: [], postRace: [], trackWork: [] };
}

export async function getRaceDayStatus() {

    const fallback = { raceDay: false, today: "", mediaTipsUrl: "", updatesUrl: "" };

    try {
        const res = await fetch(
            `${API_URL}/race_day_status_get.php`,
            { cache: "no-store" }
        );

        const json = await res.json();

        return json.data || fallback;
    } catch (err) {
        // API fail ho to normal mode (2 buttons) dikhao
        return fallback;
    }
}