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

// Partners Overview
export async function getPartnersOverview() {
    const res = await fetch(`${API_BASE}/Partners_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Sponsor Privileges
export async function getSponsorPrivileges() {
    const res = await fetch(`${API_BASE}/SponsorPrivileges_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Partners - Advertising & Sponsorship Opportunities
export async function getPartnersOpportunities() {
    const res = await fetch(`${API_BASE}/PartnersOpportunities_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Partners - Contact for Advertising & Sponsorships
export async function getPartnersContact() {
    const res = await fetch(`${API_BASE}/PartnersContact_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Beginners Luck in Racing
export async function getBeginnersLuck() {
    const res = await fetch(`${API_BASE}/BeginnersLuck_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Wagering Terms
export async function getWageringTerms() {
    const res = await fetch(`${API_BASE}/WageringTerms_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Betting Pools (How to bet on Tote)
export async function getBettingPools() {
    const res = await fetch(`${API_BASE}/BettingPools_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// National Tote Pools
export async function getNationalTotePools() {
    const res = await fetch(`${API_BASE}/NationalTotePools_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Betting Channels
export async function getBettingChannels() {
    const res = await fetch(`${API_BASE}/BettingChannels_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}

// Deduction Norms
export async function getDeductionNorms() {
    const res = await fetch(`${API_BASE}/DeductionNorms_get_api.php`);
    if (!res.ok) return null;

    const json = await res.json();
    return json?.data ?? json;
}