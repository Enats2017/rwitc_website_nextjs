/**
 * Helper to process legacy HTML archives from RWITC backend, ensuring all embedded
 * links navigate correctly within the Next.js app in the top window.
 */

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

        // 1. Convert Horse performanceProfile links (/new/performanceProfile.php?as_values=...)
        doc.querySelectorAll("a[href]").forEach((link) => {
            const href = link.getAttribute("href");
            if (!href) return;

            try {
                const url = new URL(href, "https://rwitc.com");

                if (url.hostname === "rwitc.com" && (url.pathname === "/new/performanceProfile.php" || url.pathname.endsWith("/performanceProfile.php"))) {
                    const horseName = url.searchParams.get("as_values") || url.searchParams.get("horsename");
                    if (horseName) {
                        const newUrl = "race_details?type=performanceProfile&horsename=" + encodeURIComponent(horseName);
                        link.setAttribute("href", newUrl);
                    }
                }
            } catch (error) {
                console.error("Horse link update error:", error);
            }
        });

        // 2. Convert Mother/Mare foalRecords links (/new/foalRecords.php?mareName=...)
        doc.querySelectorAll("a[href]").forEach((link) => {
            const href = link.getAttribute("href");
            if (!href) return;
            try {
                const url = new URL(href, "https://rwitc.com");
                if (url.hostname === "rwitc.com" && (url.pathname === "/new/foalRecords.php" || url.pathname.endsWith("/foalRecords.php"))) {
                    const mareName = url.searchParams.get("mareName");
                    const damNat = url.searchParams.get("damnat");

                    if (!mareName) return;
                    
                    const newUrl = "race_details?type=foalRecords&mareName=" + encodeURIComponent(mareName) + (damNat ? "&damnat=" + encodeURIComponent(damNat) : "");
                    link.setAttribute("href", newUrl);
                }
            } catch (error) {
                console.error("Mother link update error:", error);
            }
        });

        // Intercept all link clicks to navigate top window cleanly
        doc.querySelectorAll("a").forEach((link) => {
            link.setAttribute("target", "_top");
            let href = link.getAttribute("href");
            if (href) {
                href = href.replace(/https?:\/\/(www\.|test\.)?rwitc\.com\/rwitc-website\/race_details/gi, "race_details");
                href = href.replace(/http:\/\/localhost\/rwitc_website\/race_details/gi, "race_details");
                link.setAttribute("href", href);

                link.onclick = (event) => {
                    const targetUrl = link.getAttribute("href");
                    if (targetUrl && !targetUrl.startsWith("#") && !targetUrl.startsWith("javascript:")) {
                        event.preventDefault();
                        if (window.top) {
                            window.top.location.href = targetUrl;
                        } else {
                            window.location.href = targetUrl;
                        }
                    }
                };
            }
        });
    } catch (err) {
        console.warn("Archive iframe load handler warning:", err);
    }
}
