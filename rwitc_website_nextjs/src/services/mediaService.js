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

    return json.data || {};
}