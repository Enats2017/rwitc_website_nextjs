import { API_URL, UPLOAD_URL } from "./api";

// The PHP API returns image_url as a path relative to the api folder,
// e.g. "../rwitc_upload/gallery/24-Jul-2026/xyz.jpg"
// Locally (and on our server) the actual images folder is "uploads",
// not "rwitc_upload" — so we strip the PHP prefix and prepend our
// real UPLOAD_URL instead.
function buildImageUrl(relativePath) {

    if (!relativePath) return "";

    // strip the leading "../rwitc_upload/" the PHP script prepends
    const cleanPath = relativePath.replace(/^\.\.\/rwitc_upload\//, "");

    return `${UPLOAD_URL}/${cleanPath}`;

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
                url: buildImageUrl(item.image_url),
            })),
        };

    } catch (error) {

        console.error("Photo Gallery Error :", error);

        return { raceDate: null, images: [] };

    }

}