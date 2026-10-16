import { API_URL } from "./api";

// Returns a map of ready-to-use links.
// Single PDF  : { mgt7: "https://.../pdf_stream.php/mgt7/5/RWITC_MGT-7.pdf" }
// Many PDFs   : { agm_notice: [ { key, label, href }, ... ] }  (label = menu title built on the server)
// The S3 url is never exposed, files are served through pdf_stream.php
export async function getPdfLinks() {
    try {
        const response = await fetch(`${API_URL}/pdf_links_api.php`);
        if (!response.ok) { throw new Error("Failed to fetch PDF links"); }
        const data = await response.json();
        const list = data.data || {};
        const pdfLinks = {};

        Object.keys(list).forEach((type) => {
            const item = list[type];

            if (Array.isArray(item)) {
                pdfLinks[type] = item.map((entry) => ({
                    key: `${type}_${entry.id}`,
                    label: entry.title,
                    href: `${API_URL}/pdf_stream.php/${type}/${entry.id}/${encodeURIComponent(entry.file_name)}`,
                }));
            } else {
                pdfLinks[type] = `${API_URL}/pdf_stream.php/${type}/${item.id}/${encodeURIComponent(item.file_name)}`;
            }
        });

        return pdfLinks;
    } catch (error) {
        console.error("PDF Links Error :", error);
        return {};
    }
}