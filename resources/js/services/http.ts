const BASE_URL = '/api';

async function request<T>(path: string, init?: RequestInit): Promise<T> {
    const response = await fetch(`${BASE_URL}${path}`, {
        ...init,
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...init?.headers,
        },
    });

    if (!response.ok) {
        const body = await response.json().catch(() => ({}));
        throw Object.assign(
            new Error(body?.message ?? `HTTP ${response.status}`),
            { status: response.status, body },
        );
    }

    return response.json() as Promise<T>;
}

export const http = {
    get: <T>(path: string) =>
        request<T>(path),

    post: <T>(path: string, body: unknown) =>
        request<T>(path, {
            method: 'POST',
            body: JSON.stringify(body),
        }),
};
