import { API_URL } from "./api";
import { getPdfLinks } from "./pdfService";

export async function getFooterDocuments() {
    const links = await getPdfLinks();

    return {
        term_condition: typeof links.term_condition === "string" ? links.term_condition : "",
        privacy_policy: typeof links.privacy_policy === "string" ? links.privacy_policy : "",
    };
}

export async function getAppLinks() {
    const empty = { android: "", ios: "" };

    try {
        const res = await fetch(`${API_URL}/app_links_api.php`, {
            method: "GET",
            cache: "no-store",
        });
        if (!res.ok) return empty;

        const json = await res.json();
        const data = (json && json.data) || {};

        return {
            android: typeof data.android === "string" ? data.android : "",
            ios: typeof data.ios === "string" ? data.ios : "",
        };
    } catch (error) {
        console.error("App links request failed:", error);
        return empty;
    }
}