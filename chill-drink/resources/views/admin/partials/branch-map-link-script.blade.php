<script>
(() => {
    if (window.ChillDrinkBranchMapLink) {
        return;
    }

    const reverseGeocodeUrl = @json(route('api.reverse-geocode'));
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

    function formatAddressFromComponents(payload) {
        const parts = [
            payload?.house_number,
            payload?.road,
            payload?.ward,
            payload?.district,
            payload?.province,
        ]
            .map((part) => String(part || '').trim())
            .filter(Boolean);

        if (parts.length > 0) {
            return parts.join(', ');
        }

        const fallback = String(payload?.display_name || '').trim();
        return fallback;
    }

    async function reverseGeocode(latitude, longitude) {
        const url = new URL(reverseGeocodeUrl, window.location.origin);
        url.searchParams.set('latitude', Number(latitude).toFixed(6));
        url.searchParams.set('longitude', Number(longitude).toFixed(6));

        const response = await fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            return null;
        }

        try {
            return await response.json();
        } catch (error) {
            return null;
        }
    }

    function resolveAddressTarget(container) {
        const selector = container.dataset.addressTarget?.trim();

        if (selector) {
            const target = document.querySelector(selector);
            if (target) {
                return target;
            }
        }

        const form = container.closest('form');
        return form?.querySelector('textarea[name="address"], input[name="address"]') ?? null;
    }

    function mountPicker(container) {
        if (!container || container.dataset.branchMapLinkMounted === '1') {
            return container?.__branchMapLink ?? null;
        }

        const linkInput = container.querySelector('[data-branch-map-link-input]');
        const applyButtons = container.querySelectorAll('[data-branch-map-link-apply]');
        const reverseButton = container.querySelector('[data-branch-map-link-reverse]');
        const latInput = container.querySelector('[data-branch-map-link-lat]');
        const lngInput = container.querySelector('[data-branch-map-link-lng]');
        const previewEl = container.querySelector('[data-branch-map-link-preview]');
        const addressInput = resolveAddressTarget(container);

        if (!linkInput || !latInput || !lngInput) {
            return null;
        }

        const updatePreview = () => {
            const latitude = parseNumber(latInput.value);
            const longitude = parseNumber(lngInput.value);
            const hasCoordinates = latitude !== null && longitude !== null;

            if (previewEl) {
                previewEl.textContent = hasCoordinates
                    ? formatCoordinates(latitude, longitude)
                    : 'Chưa có tọa độ';
            }

            if (reverseButton) {
                reverseButton.disabled = !hasCoordinates;
            }
        };

        const fillAddressFromCoordinates = async (latitude, longitude) => {
            const reverseAddress = await reverseGeocode(latitude, longitude);
            const addressValue = formatAddressFromComponents(reverseAddress);

            if (addressInput && addressValue) {
                addressInput.value = addressValue;
                addressInput.dispatchEvent(new Event('input', { bubbles: true }));
                addressInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            container.dispatchEvent(new CustomEvent('branch-map-link:change', {
                bubbles: true,
                detail: {
                    latitude,
                    longitude,
                    address: addressValue,
                    reverseAddress,
                },
            }));

            return {
                addressValue,
                reverseAddress,
            };
        };

        const applyLink = async () => {
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
            await fillAddressFromCoordinates(coordinates.latitude, coordinates.longitude);
            return true;
        };

        const applyAddress = async () => {
            const latitude = parseNumber(latInput.value);
            const longitude = parseNumber(lngInput.value);

            if (latitude === null || longitude === null) {
                const hasLinkValue = linkInput.value.trim().length > 0;
                if (hasLinkValue) {
                    return applyLink();
                }

                return false;
            }

            updatePreview();
            await fillAddressFromCoordinates(latitude, longitude);
            return true;
        };

        applyButtons.forEach((button) => {
            button.addEventListener('click', () => {
                applyLink();
            });
        });

        if (reverseButton) {
            reverseButton.addEventListener('click', () => {
                applyAddress();
            });
        }

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
            applyAddress,
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
