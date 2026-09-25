import { API_URL, RWITC_UPLOAD_URL } from "./api";

function buildImageUrl(relativePath) {

    if (!relativePath) return "";

    // "../rwitc_upload/gallery/12-Sep-2026/2.jpg" -> "gallery/12-Sep-2026/2.jpg"
    const cleanPath = relativePath.replace(/^\.\.\/rwitc_upload\//, "");

    return `${RWITC_UPLOAD_URL}/${cleanPath}`;

}

export async function getPhotoGallery(date) {

    try {

        const url = date
            ? `${API_URL}/photoGallery_get.php?date=${date}`
            : `${API_URL}/photoGallery_get.php`;

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error("Failed to fetch photo gallery");
        }

        const result = await response.json();

        if (!result.success || !result.data) {
            return { raceDate: null, images: [] };
        }

        const { race_date, images } = result.data;

        return {
            raceDate: race_date || null,
            images: (images || []).map((item) => ({
                id: item.id,
                caption: item.caption,
                // url: buildImageUrl(item.image_url),
                url: item.image_url && item.image_url.startsWith('http') ? item.image_url : buildImageUrl(item.image_url),
            })),
        };

    } catch (error) {

        console.error("Photo Gallery Error :", error);

        return { raceDate: null, images: [] };

    }

}