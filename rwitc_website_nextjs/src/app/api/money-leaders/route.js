import { RUN_RACES_URL } from "../../../services/api";

export async function GET(request) {
    const { searchParams } = new URL(request.url);
    const type = searchParams.get("type"); // horse, trainer, jockey, owner

    const fileMap = {
        horse: "horse.html",
        trainer: "trainer.html",
        jockey: "jockey.html",
        owner: "owner.html",
    };

    const fileName = fileMap[type];

    if (!fileName) {
        return new Response("Invalid type", { status: 400 });
    }

    const fileUrl = `${RUN_RACES_URL}/${fileName}`;

    try {
        const res = await fetch(fileUrl, { cache: "no-store" });

        if (!res.ok) {
            return new Response("Failed to fetch file", { status: res.status });
        }

        const html = await res.text();
        const lastModified = res.headers.get("last-modified") || "";

        return new Response(html, {
            status: 200,
            headers: {
                "Content-Type": "text/html",
                "X-Updated-At": lastModified,
            },
        });
    } catch (err) {
        console.error("Money Leaders proxy error:", err);
        return new Response("Error fetching data", { status: 500 });
    }
}