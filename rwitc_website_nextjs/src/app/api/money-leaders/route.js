import { RUN_RACES_URL } from '../../../services/api';

export const dynamic = 'force-static';

const fileMap = {
    horse: 'horse.html',
    trainer: 'trainer.html',
    jockey: 'jockey.html',
    owner: 'owner.html',
};

export async function GET(request) {
    try {
        const url = new URL(request.url);
        const type = url.searchParams.get('type') || 'horse';

        const fileName = fileMap[type];

        if (!fileName) {
            return new Response(
                JSON.stringify({ error: 'Invalid type', received: type }),
                {
                    status: 400,
                    headers: { 'Content-Type': 'application/json' },
                }
            );
        }

        const fileUrl = `${RUN_RACES_URL}/${fileName}`;

        console.log('Fetching:', fileUrl);

        const res = await fetch(fileUrl, {
            cache: 'force-cache',
        });

        if (!res.ok) {
            return new Response(
                JSON.stringify({ error: 'File fetch failed', status: res.status }),
                {
                    status: 500,
                    headers: { 'Content-Type': 'application/json' },
                }
            );
        }

        const html = await res.text();
        const lastModified = res.headers.get('last-modified') || '';

        return new Response(html, {
            status: 200,
            headers: {
                'Content-Type': 'text/html',
                'X-Updated-At': lastModified,
            },
        });
    } catch (err) {
        console.error('Money leaders route error:', err);

        return new Response(
            JSON.stringify({ error: err.message }),
            {
                status: 500,
                headers: { 'Content-Type': 'application/json' },
            }
        );
    }
}