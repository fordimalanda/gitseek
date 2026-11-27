export async function call(action, payload = {}) {
    const res = await fetch(`/api?action=${encodeURIComponent(action)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    });

    const text = await res.text();
    
    // Si on reçoit du HTML au lieu de JSON (erreur fatale PHP)
    if (text.trim().startsWith('<')) {
        throw new Error(`Erreur serveur (HTML reçu) pour ${action}. Vérifie les logs PHP.`);
    }

    let data;
    try { 
        data = JSON.parse(text); 
    }
    catch { 
        throw new Error(`Invalid JSON from ${action}: ${text.slice(0, 200)}`); 
    }

    if (!res.ok || data.success === false) {
        throw new Error(data.error ?? `Request failed (${res.status})`);
    }

    return data;
}