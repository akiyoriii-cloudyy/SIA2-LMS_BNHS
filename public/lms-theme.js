(() => {
    const root = document.documentElement;

    const seedFromServer =
        typeof window !== "undefined" && window.__LMS_THEME_SEED
            ? String(window.__LMS_THEME_SEED)
            : "";

    let seed = seedFromServer;
    if (!seed || seed === "guest") {
        const key = "lms_theme_seed_v1";
        seed = localStorage.getItem(key) || "";
        if (!seed) {
            seed = `${Math.random().toString(16).slice(2)}-${Date.now().toString(16)}`;
            localStorage.setItem(key, seed);
        }
    }

    // FNV-1a 32-bit hash (fast, deterministic)
    let hash = 0x811c9dc5;
    for (let i = 0; i < seed.length; i++) {
        hash ^= seed.charCodeAt(i);
        hash = (hash * 0x01000193) >>> 0;
    }

    const hue = hash % 360;
    const hue2 = (hue + 40 + ((hash >>> 8) % 30)) % 360;

    root.style.setProperty("--lms-h", String(hue));
    root.style.setProperty("--lms-h2", String(hue2));
})();

