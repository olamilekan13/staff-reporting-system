import kingsChatWebSdk from 'kingschat-web-sdk';

const STORAGE_KEY = 'kc_auth';
const KC_API_BASE = 'https://connect.kingsch.at';

function getClientId() {
    const meta = document.querySelector('meta[name="kingschat-app-id"]');
    return meta ? meta.content : '';
}

function getStoredAuth() {
    try {
        const data = localStorage.getItem(STORAGE_KEY);
        return data ? JSON.parse(data) : null;
    } catch {
        return null;
    }
}

function storeAuth(authResponse) {
    const data = {
        accessToken: authResponse.accessToken,
        refreshToken: authResponse.refreshToken,
        expiresAt: Date.now() + authResponse.expiresInMillis,
    };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    return data;
}

function clearAuth() {
    localStorage.removeItem(STORAGE_KEY);
}

function isTokenExpired(auth) {
    if (!auth || !auth.expiresAt) return true;
    return Date.now() >= (auth.expiresAt - 60000);
}

/**
 * Ensures we have a valid access token.
 * Tries: cached token -> refresh -> full login popup.
 */
async function ensureAuthenticated() {
    const stored = getStoredAuth();

    if (stored && !isTokenExpired(stored)) {
        return stored.accessToken;
    }

    if (stored && stored.refreshToken) {
        try {
            const refreshed = await kingsChatWebSdk.refreshAuthenticationToken({
                clientId: getClientId(),
                refreshToken: stored.refreshToken,
            });
            const auth = storeAuth(refreshed);
            return auth.accessToken;
        } catch {
            clearAuth();
        }
    }

    const loginResponse = await kingsChatWebSdk.login({
        clientId: getClientId(),
        scopes: ['send_chat_message'],
    });
    const auth = storeAuth(loginResponse);
    return auth.accessToken;
}

/**
 * Send message directly via KingsChat API (bypasses SDK's poor error handling).
 */
async function sendMessageDirect(userIdentifier, message, accessToken) {
    const url = `${KC_API_BASE}/api/users/${encodeURIComponent(userIdentifier)}/new_message`;

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${accessToken}`,
        },
        body: JSON.stringify({
            message: {
                body: {
                    text: {
                        body: message,
                    },
                },
            },
        }),
    });

    if (response.ok) {
        return await response.json();
    }

    // Extract meaningful error details
    let errorMessage = `KingsChat API error (${response.status})`;
    try {
        const errorBody = await response.json();
        console.error('[KingsChat] API error response:', errorBody);
        if (errorBody.message) {
            errorMessage = errorBody.message;
        } else if (errorBody.error) {
            errorMessage = errorBody.error;
        }
    } catch {
        const errorText = await response.text().catch(() => '');
        console.error('[KingsChat] API error text:', errorText);
    }

    const error = new Error(errorMessage);
    error.status = response.status;
    throw error;
}

/**
 * Send a KingsChat message to a user.
 * Handles authentication transparently.
 */
async function sendKingsChatMessage(userIdentifier, message) {
    const accessToken = await ensureAuthenticated();

    try {
        await sendMessageDirect(userIdentifier, message, accessToken);
    } catch (firstError) {
        // Auth issue - clear tokens and retry with fresh login
        if (firstError.status === 401 || firstError.status === 403) {
            clearAuth();
            const freshToken = await ensureAuthenticated();
            await sendMessageDirect(userIdentifier, message, freshToken);
            return;
        }

        // Server error - clear tokens so next attempt triggers fresh login
        if (firstError.status >= 500) {
            clearAuth();
        }

        throw firstError;
    }
}

export { ensureAuthenticated, sendKingsChatMessage, clearAuth };
