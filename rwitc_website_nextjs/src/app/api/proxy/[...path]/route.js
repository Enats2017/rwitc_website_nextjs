import { NextResponse } from 'next/server';

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL || 'https://test.rwitc.com/rwitc-website';
const TARGET_API_BASE = `${SITE_URL.replace(/\/+$/, '')}/rwitc_website_api`;

export async function GET(request, { params }) {
    const resolvedParams = await Promise.resolve(params);
    const pathSegments = resolvedParams?.path || [];
    const pathString = Array.isArray(pathSegments) ? pathSegments.join('/') : pathSegments;
    const { searchParams } = new URL(request.url);
    const queryString = searchParams.toString();
    
    const targetUrl = `${TARGET_API_BASE}/${pathString}${queryString ? `?${queryString}` : ''}`;

    try {
        const response = await fetch(targetUrl, {
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) NextJS-API-Proxy',
                'Accept': 'application/json, text/plain, */*',
            },
            cache: 'no-store'
        });

        const data = await response.text();

        return new NextResponse(data, {
            status: response.status,
            headers: {
                'Content-Type': response.headers.get('content-type') || 'application/json',
                'Cache-Control': 'no-store, max-age=0',
            },
        });
    } catch (error) {
        console.error('API Proxy GET Error:', error);
        return NextResponse.json({ error: error.message }, { status: 500 });
    }
}

export async function POST(request, { params }) {
    const resolvedParams = await Promise.resolve(params);
    const pathSegments = resolvedParams?.path || [];
    const pathString = Array.isArray(pathSegments) ? pathSegments.join('/') : pathSegments;
    const { searchParams } = new URL(request.url);
    const queryString = searchParams.toString();
    
    const targetUrl = `${TARGET_API_BASE}/${pathString}${queryString ? `?${queryString}` : ''}`;

    try {
        const contentType = request.headers.get('content-type') || '';
        let body;
        
        if (contentType.includes('multipart/form-data')) {
            body = await request.formData();
        } else {
            body = await request.text();
        }

        const fetchOptions = {
            method: 'POST',
            headers: {},
            cache: 'no-store'
        };

        if (contentType && !contentType.includes('multipart/form-data')) {
            fetchOptions.headers['Content-Type'] = contentType;
        }

        if (body) {
            fetchOptions.body = body;
        }

        const response = await fetch(targetUrl, fetchOptions);
        const data = await response.text();

        return new NextResponse(data, {
            status: response.status,
            headers: {
                'Content-Type': response.headers.get('content-type') || 'application/json',
                'Cache-Control': 'no-store, max-age=0',
            },
        });
    } catch (error) {
        console.error('API Proxy POST Error:', error);
        return NextResponse.json({ error: error.message }, { status: 500 });
    }
}

export async function OPTIONS() {
    return new NextResponse(null, {
        status: 204,
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers': 'Content-Type, Authorization',
        },
    });
}
