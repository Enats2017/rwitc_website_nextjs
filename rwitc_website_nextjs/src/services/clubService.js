const API_BASE = process.env.NEXT_PUBLIC_API_URL;
// about
export async function getAboutRwitc() {
    const res = await fetch(`${API_BASE}/About_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// vission & Mission
export async function getVisionMission() {
    const res = await fetch(`${API_BASE}/Vision_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Organisation & Management (section: structure, managing_committee, stewards_of_club, board_of_appeal)
export async function getOrganisation(section) {
    const res = await fetch(
        `${API_BASE}/Organisation_get_api.php?section=${encodeURIComponent(section)}`
    );
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// History (section: timeline, bequeathing_legacy)
export async function getHistory(section) {
    const res = await fetch(
        `${API_BASE}/History_get_api.php?section=${encodeURIComponent(section)}`
    );
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}