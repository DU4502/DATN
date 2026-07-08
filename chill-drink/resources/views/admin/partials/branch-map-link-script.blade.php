<script>
(() => {
    if (window.ChillDrinkBranchMapLink) {
        return;
    }

    const COORD_PATTERNS = [
        /@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)(?:,|$)/,
        /[?&](?:q|query|ll|center)=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)(?:&|$)/,
        /\/place\/[^/]+\/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)(?:,|\/|$)/,
        /[?&]destination=(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)(?:&|$)/,
    ];

    function parseNumber(value) {
        const parsed = Number.parseFloat(value);
        return Number.isFinite(parsed) ? parsed : null;
    }

    function extractCoordinatesFromLink(link) {
        const raw = String(link || '').trim();
        if (!raw) {
            return null;
        }

        for (const pattern of COORD_PATTERNS) {
            const match = raw.match(pattern);
            if (!match) {
                continue;
            }

            const latitude = parseNumber(match[1]);
            const longitude = parseNumber(match[2]);

            if (latitude === null || longitude === null) {
                continue;
            }

            return { latitude, longitude };
        }

        return null;
    }

    function formatCoordinates(latitude, longitude) {
        return `Tọa độ: ${Number(latitude).toFixed(6)}, ${Number(longitude).toFixed(6)}`;
    }

    function mountPicker(container) {
        if (!container || container.dataset.branchMapLinkMounted === '1') {
            return container?.__branchMapLink ?? null;
        }

        const linkInput = container.querySelector('[data-branch-map-link-input]');
        const applyButtons = container.querySelectorAll('[data-branch-map-link-apply]');
        const latInput = container.querySelector('[data-branch-map-link-lat]');
        const lngInput = container.querySelector('[data-branch-map-link-lng]');
        const previewEl = container.querySelector('[data-branch-map-link-preview]');

        if (!linkInput || !latInput || !lngInput) {
            return null;
        }

        const updatePreview = () => {
            const latitude = parseNumber(latInput.value);
            const longitude = parseNumber(lngInput.value);

            if (previewEl) {
                previewEl.textContent = latitude !== null && longitude !== null
                    ? formatCoordinates(latitude, longitude)
                    : 'Chưa có tọa độ';
            }
        };

        const applyLink = () => {
            const coordinates = extractCoordinatesFromLink(linkInput.value);

            if (!coordinates) {
                latInput.value = '';
                lngInput.value = '';
                updatePreview();
                if (previewEl && linkInput.value.trim()) {
                    previewEl.textContent = 'Link đã được nhận, sẽ xử lý khi lưu.';
                }
                return false;
            }

            latInput.value = coordinates.latitude.toFixed(6);
            lngInput.value = coordinates.longitude.toFixed(6);
            updatePreview();
            container.dispatchEvent(new CustomEvent('branch-map-link:change', {
                bubbles: true,
                detail: coordinates,
            }));
            return true;
        };

        applyButtons.forEach((button) => {
            button.addEventListener('click', () => {
                applyLink();
            });
        });

        linkInput.addEventListener('change', () => {
            applyLink();
        });

        linkInput.addEventListener('blur', () => {
            applyLink();
        });

        container.addEventListener('paste', () => {
            setTimeout(() => applyLink(), 0);
        });

        updatePreview();

        if (linkInput.value.trim() && (!latInput.value || !lngInput.value)) {
            applyLink();
        }

        container.dataset.branchMapLinkMounted = '1';
        container.__branchMapLink = {
            container,
            linkInput,
            latInput,
            lngInput,
            previewEl,
            extractCoordinatesFromLink,
            applyLink,
            updatePreview,
        };

        return container.__branchMapLink;
    }

    function refreshPickers(root = document) {
        root.querySelectorAll('[data-branch-map-link-picker]').forEach((container) => {
            mountPicker(container);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        refreshPickers();
    });

    document.addEventListener('show.bs.modal', (event) => {
        refreshPickers(event.target);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const picker = form.querySelector('[data-branch-map-link-picker]');
        if (!picker) {
            return;
        }

        const mounted = mountPicker(picker);
        mounted?.applyLink();
    }, true);

    window.ChillDrinkBranchMapLink = {
        mount: mountPicker,
        refresh: refreshPickers,
        extractCoordinatesFromLink,
    };
})();
</script>
