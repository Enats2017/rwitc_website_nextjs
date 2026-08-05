import { API_URL } from "./api";
function highlightHorseName(html, horseName) {
    if (!horseName || !html) return html;
    const escaped = horseName.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(escaped, "gi");
    return html.replace(regex, (match) => `<span class="highlightHorse">${match}</span>`);
}

export async function getTrackwork(date, horseName = "") {
    try {
        let url = `${API_URL}/trackwork_get_api.php?date=${date}`;
        if (horseName) {
            url += `&horsename=${encodeURIComponent(horseName)}`;
        }
        const response = await fetch(url);
        if (!response.ok) {
            throw new Error("Failed to fetch trackwork data");
        }
        const json = await response.json();
        if (!json.success) {
            throw new Error(json.error || "Failed to fetch trackwork data");
        }
        const data = json.data;
        // data.mode === "single" for date/id based lookups
        if (!data?.found) {
            return {
                found: false,
                html: "",
                published: null,
                date,
            };
        }
        const rawHtml = data.data?.trackwork || "";
        return {
            found: true,
            html: highlightHorseName(rawHtml, horseName),
            published: data.data?.published ?? null,
            date: data.data?.trackwork_date || date,
        };

    } catch (error) {
        console.error("Trackwork Error :", error);
        throw error;
    }
}

// Fetches a single trackwork record by id.
export async function getTrackworkById(id, horseName = "") {
    try {
        const response = await fetch(
            `${API_URL}/trackwork_get_api.php?id=${id}`
        );
        if (!response.ok) {
            throw new Error("Failed to fetch trackwork data");
        }
        const json = await response.json();
        if (!json.success) {
            throw new Error(json.error || "Failed to fetch trackwork data");
        }
        const data = json.data;

        if (!data?.found) {
            return { found: false, html: "", published: null, date: null };
        }
        const rawHtml = data.data?.trackwork || "";
        return {
            found: true,
            html: highlightHorseName(rawHtml, horseName),
            published: data.data?.published ?? null,
            date: data.data?.trackwork_date || null,
        };

    } catch (error) {
        console.error("Trackwork Error :", error);
        throw error;
    }
}

export async function searchTrackworkByHorse(horseName) {
    try {
        const response = await fetch(
            `${API_URL}/trackwork_get_api.php?q=byhorse&horsename=${encodeURIComponent(horseName)}`
        );
        if (!response.ok) {
            throw new Error("Failed to search trackwork by horse");
        }
        const json = await response.json();
        if (!json.success) {
            throw new Error(json.error || "Failed to search trackwork by horse");
        }
        return json.data?.results || [];
    } catch (error) {
        console.error("Trackwork Horse Search Error :", error);
        throw error;
    }
}

export async function getHorseNameSuggestions(letter) {
    try {
        const response = await fetch(
            `${API_URL}/trackwork_get_api.php?q=horsenames&letter=${encodeURIComponent(letter)}`
        );

        if (!response.ok) {
            throw new Error("Failed to fetch horse name suggestions");
        }

        const json = await response.json();

        if (!json.success) {
            throw new Error(json.error || "Failed to fetch horse name suggestions");
        }

        // Expecting an array of horse name strings, e.g. ["LIBAN", "LIBATION"]
        return json.data?.results || [];

    } catch (error) {
        console.error("Horse Suggestion Error :", error);
        return [];
    }
}