import { API_URL } from "./api";

/**
 * Download > Forms
 * Active forms uploaded from the admin panel (Application Forms Manager).
 *
 * Returns: [{ id, title, fileType, fileSize, downloadUrl }]
 * Throws on network / invalid-response errors so the component can decide
 * what to show.
 */
export async function getApplicationForms() {
    const response = await fetch(
        `${API_URL}/application_forms_get_api.php`,
        { cache: "no-store" }
    );

    let result;
    try {
        result = await response.json();
    } catch {
        throw new Error("The application forms service returned an invalid response.");
    }

    if (!response.ok || !result?.success || !Array.isArray(result.data)) {
        throw new Error(result?.error || "Unable to load application forms.");
    }

    return result.data
        .filter((form) => form && form.title && form.download_url)
        .map((form) => ({
            id: form.id,
            title: String(form.title),
            fileType: String(form.file_type || ""),
            fileSize: Number(form.file_size) || 0,
            downloadUrl: String(form.download_url),
        }));
}