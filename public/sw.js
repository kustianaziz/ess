const CACHE_NAME = "ess-v1";

self.addEventListener("install", (event) => {
    self.skipWaiting();
});

self.addEventListener("fetch", (event) => {
    // Basic pass-through fetch
});

