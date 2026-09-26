/**
 * Helper to process legacy HTML archives from RWITC backend, ensuring all embedded
 * links navigate correctly within the Next.js app in the top window.
 */

export function transformPhpUrl(href) {
    if (!href) return href;

    try {
        // Parse with dummy base URL if relative
        const url = new URL(href, "https://rwitc.com");
        const pathname = url.pathname.toLowerCase();
        const params = url.searchParams;

        // 1. Performance Profile links (/new/performanceProfile.php or performanceProfile.php)
        if (pathname.includes("performanceprofile.php")) {
            const horseName = params.get("as_values") || params.get("horsename");
            const raceNo = params.get("raceno") || params.get("race_no");
            const raceDate = params.get("racedate") || params.get("race_date");

            if (horseName) {
                return `race_details?type=performanceProfile&horsename=${encodeURIComponent(horseName)}`;
            }
            if (raceNo && raceDate) {
                return `race_details?type=performanceProfile&race_no=${encodeURIComponent(raceNo)}&race_date=${encodeURIComponent(raceDate)}`;
            }
            return `race_details?type=performanceProfile`;
        }

        // 2. Foal Records / Mother links (/new/foalRecords.php or foalRecords.php)
        if (pathname.includes("foalrecords.php")) {
            const mareName = params.get("mareName") || params.get("marename");
            const damNat = params.get("damnat") || params.get("dam_nat");
            if (mareName) {
                let target = `race_details?type=foalRecords&mareName=${encodeURIComponent(mareName)}`;
                if (damNat) target += `&damnat=${encodeURIComponent(damNat)}`;
                return target;
            }
            return `race_details?type=foalRecords`;
        }

        // 3. Horse Ratings links
        if (pathname.includes("horseratings.php")) {
            return `race_details?type=horseRatings`;
        }

        // 4. Dividends links
        if (pathname.includes("dividends.php")) {
            const date = params.get("date") || params.get("racedate");
            return `race_details?type=dividends${date ? `&date=${encodeURIComponent(date)}` : ""}`;
        }

        // 5. Trainer Horses links
        if (pathname.includes("trainerhorses.php")) {
            const trainer = params.get("trainer") || params.get("trainer_name");
            return `race_details?type=trainerHorses${trainer ? `&trainer=${encodeURIComponent(trainer)}` : ""}`;
        }

        // 6. Generic race_details query links
        if (pathname.includes("race_details")) {
            return `race_details${url.search}`;
        }

        // 7. Generic .php link: strip legacy domain prefix if present
        if (pathname.endsWith(".php")) {
            const cleanHref = href
                .replace(/https?:\/\/(www\.|test\.)?rwitc\.com\/rwitc-website\//gi, "")
                .replace(/http:\/\/localhost\/rwitc_website\//gi, "");
            return cleanHref;
        }
    } catch (err) {
        console.error("PHP link transformation error:", err);
    }

    return href;
}

export function formatArchiveHtml(styles = "", rawHtml = "") {
    if (!rawHtml) return "";

    let processed = rawHtml;

    // Replace full domain URLs targeting race_details with relative path
    processed = processed.replace(/https?:\/\/(www\.|test\.)?rwitc\.com\/rwitc-website\/race_details/gi, "race_details");
    processed = processed.replace(/http:\/\/localhost\/rwitc_website\/race_details/gi, "race_details");

    // Replace live, test, and localhost domain paths with relative root
    processed = processed.replace(/https?:\/\/(www\.|test\.)?rwitc\.com\/rwitc-website\//gi, "./");
    processed = processed.replace(/http:\/\/localhost\/rwitc_website\//gi, "./");

    // Replace legacy PHP page links with Next.js race_details routes
    processed = processed.replace(/performanceProfile\.php\?/gi, "race_details?type=performanceProfile&");
    processed = processed.replace(/foalRecords\.php\?/gi, "race_details?type=foalRecords&");
    processed = processed.replace(/horseRatings\.php\?/gi, "race_details?type=horseRatings&");
    processed = processed.replace(/dividends\.php\?/gi, "race_details?type=dividends&");
    processed = processed.replace(/trainerHorses\.php\?/gi, "race_details?type=trainerHorses&");

    const baseTag = '<base target="_top">';
    
    // Combine styles + baseTag + processed HTML
    if (processed.includes("<head>")) {
        return `${styles}${processed.replace("<head>", `<head>${baseTag}`)}`;
    }
    return `${baseTag}${styles}${processed}`;
}

export function handleArchiveIframeLoad(e) {
    const iframe = e.target;
    if (!iframe) return;

    try {
        const doc = iframe.contentWindow?.document;
        if (!doc) return;

        // Auto adjust iframe height to fit document
        const setHeight = () => {
            if (doc.documentElement && doc.documentElement.scrollHeight) {
                iframe.style.height = doc.documentElement.scrollHeight + "px";
            }
        };
        setHeight();
        requestAnimationFrame(setHeight);
        setTimeout(setHeight, 100);

        // Process all anchor tags with href
        doc.querySelectorAll("a[href]").forEach((link) => {
            link.setAttribute("target", "_top");
            const rawHref = link.getAttribute("href");
            if (!rawHref) return;

            // Transform any PHP / legacy link to Next.js route
            const newHref = transformPhpUrl(rawHref);
            link.setAttribute("href", newHref);

            // Add click listener to guarantee top window redirection for all links (including .php)
            link.addEventListener("click", (event) => {
                event.preventDefault();
                event.stopPropagation();

                const hrefToNavigate = link.getAttribute("href") || newHref;
                if (!hrefToNavigate || hrefToNavigate.startsWith("#") || hrefToNavigate.startsWith("javascript:")) {
                    return;
                }

                if (window.top) {
                    window.top.location.href = hrefToNavigate;
                } else {
                    window.location.href = hrefToNavigate;
                }
            });
        });
    } catch (err) {
        console.warn("Archive iframe load handler warning:", err);
    }
}
