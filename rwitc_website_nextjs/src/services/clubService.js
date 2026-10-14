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

// Charity Race Days
export async function getCharityRaceDays() {
    const res = await fetch(`${API_BASE}/Charity_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Contribution to the Communities
export async function getCommunityContribution() {
    const res = await fetch(`${API_BASE}/Community_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Responsible Gambling
export async function getResponsibleGambling() {
    const res = await fetch(`${API_BASE}/Gambling_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Careers
export async function getCareers() {
    const res = await fetch(`${API_BASE}/Careers_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Wagering Overview
export async function getWageringOverview() {
    const res = await fetch(`${API_BASE}/Wagering_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Beginners Guide
export async function getBeginnersGuide() {
    const res = await fetch(`${API_BASE}/Beginners_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}