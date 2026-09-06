<script>
(() => {
    if (window.ChillDrinkBranchMapLink) {
        return;
    }

    const reverseGeocodeUrl = @json(route('api.reverse-geocode'));
    const resolveMapLinkUrl = @json(route('api.map-link.resolve'));
    const MANUAL_LOADING_MIN_MS = 600;
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

    function normalizeLink(value) {
        return String(value || '').trim();
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

    async function resolveCoordinatesFromLink(link) {
        const directCoordinates = extractCoordinatesFromLink(link);
        if (directCoordinates) {
            return directCoordinates;
        }

        const raw = String(link || '').trim();
        if (!raw) {
            return null;
        }

        try {
            const url = new URL(resolveMapLinkUrl, window.location.origin);
            url.searchParams.set('map_link', raw);

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return null;
            }

            const payload = await response.json().catch(() => null);
            const latitude = parseNumber(payload?.latitude);
            const longitude = parseNumber(payload?.longitude);

            if (latitude === null || longitude === null) {
                return null;
            }

            return { latitude, longitude };
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

        let isBusy = false;
        let lastAnalyzedLink = '';
        let processingPopup = null;
        const linkInput = container.querySelector('[data-branch-map-link-input]');
        const applyButtons = container.querySelectorAll('[data-branch-map-link-apply]');
        const latInput = container.querySelector('[data-branch-map-link-lat]');
        const lngInput = container.querySelector('[data-branch-map-link-lng]');
        const previewEl = container.querySelector('[data-branch-map-link-preview]');
        const statusEl = container.querySelector('[data-branch-map-link-status]');
        const addressInput = resolveAddressTarget(container);
        const buttonHtmlCache = new WeakMap();

        if (!linkInput || !latInput || !lngInput) {
            return null;
        }

        const ensureProcessingPopup = () => {
            if (processingPopup) {
                return processingPopup;
            }

            const overlay = document.createElement('div');
            overlay.className = 'branch-map-link-processing-popup';
            overlay.setAttribute('aria-hidden', 'true');
            overlay.style.cssText = [
                'position:fixed',
                'inset:0',
                'z-index:20000',
                'display:none',
                'align-items:center',
                'justify-content:center',
                'padding:1rem',
                'background:rgba(15,23,42,0.42)',
                'backdrop-filter:blur(2px)',
            ].join(';');

            overlay.innerHTML = `
                <div style="min-width:min(92vw, 380px); max-width: 420px; padding: 1.1rem 1.25rem; border-radius: 14px; background: #fff; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.2); border: 1px solid rgba(13, 147, 115, 0.15); text-align: center;">
                    <div class="spinner-border text-success mb-3" role="status" aria-hidden="true"></div>
                    <div style="color: #111827; font-size: 0.98rem; font-weight: 800; line-height: 1.45;">Đang lấy tọa độ và địa chỉ...</div>
                    <div style="margin-top: 0.35rem; color: #6b7280; font-size: 0.8rem; line-height: 1.45;">Vui lòng chờ trong giây lát để hệ thống phân tích link Google Maps.</div>
                </div>
            `;

            document.body.appendChild(overlay);
            processingPopup = overlay;
            return overlay;
        };

        const showProcessingPopup = () => {
            const overlay = ensureProcessingPopup();
            overlay.style.display = 'flex';
            overlay.setAttribute('aria-hidden', 'false');
        };

        const hideProcessingPopup = () => {
            if (!processingPopup) {
                return;
            }

            processingPopup.style.display = 'none';
            processingPopup.setAttribute('aria-hidden', 'true');
        };

        const setButtonLoading = (button, loading) => {
            if (!button) {
                return;
            }

            if (loading) {
                if (!buttonHtmlCache.has(button)) {
                    buttonHtmlCache.set(button, button.innerHTML);
                }

                if (button.hasAttribute('data-branch-map-link-analyze')) {
                    button.innerHTML = '<span class="spinner-border spinner-border-sm text-success me-1" role="status" aria-hidden="true"></span>Đang phân tích...';
                }

                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                return;
            }

            const originalHtml = buttonHtmlCache.get(button);
            if (originalHtml !== undefined) {
                button.innerHTML = originalHtml;
            }

            button.disabled = false;
            button.removeAttribute('aria-busy');
        };

        const setStatus = (type, message) => {
            if (!statusEl) {
                return;
            }

            statusEl.textContent = message || '';
            statusEl.className = 'form-text mt-2';

            if (!message) {
                statusEl.classList.add('d-none');
                linkInput.classList.remove('is-invalid');
                return;
            }

            statusEl.classList.remove('d-none');

            if (type === 'error') {
                statusEl.classList.add('text-danger', 'fw-semibold');
                linkInput.classList.add('is-invalid');
                return;
            }

            linkInput.classList.remove('is-invalid');

            if (type === 'success') {
                statusEl.classList.add('text-success', 'fw-semibold');
                return;
            }

            statusEl.classList.add('text-secondary');
        };

        const setPickerBusy = (busy, activeButton = null) => {
            isBusy = busy;

            applyButtons.forEach((button) => {
                setButtonLoading(button, busy);
            });

            if (!busy) {
                updatePreview();
                return;
            }

            if (activeButton && activeButton.hasAttribute('data-branch-map-link-analyze')) {
                setButtonLoading(activeButton, true);
            }
        };

        const updatePreview = () => {
            const latitude = parseNumber(latInput.value);
            const longitude = parseNumber(lngInput.value);
            const hasCoordinates = latitude !== null && longitude !== null;

            if (previewEl) {
                previewEl.textContent = hasCoordinates
                    ? formatCoordinates(latitude, longitude)
                    : 'Chưa có tọa độ';
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

        const performLinkLookup = async (rawLink) => {
            if (!rawLink) {
                latInput.value = '';
                lngInput.value = '';
                updatePreview();
                setStatus('error', 'Vui lòng dán link Google Maps có chứa tọa độ.');
                return false;
            }

            setStatus('muted', 'Đang phân tích link Google Maps...');
            const coordinates = await resolveCoordinatesFromLink(rawLink);

            if (!coordinates) {
                latInput.value = '';
                lngInput.value = '';
                updatePreview();
                setStatus('error', 'Không đọc được tọa độ từ link này. Hãy dán link Google Maps có dạng chứa @lat,lng hoặc ?q=lat,lng.');
                return false;
            }

            latInput.value = coordinates.latitude.toFixed(6);
            lngInput.value = coordinates.longitude.toFixed(6);
            updatePreview();
            lastAnalyzedLink = rawLink;
            await fillAddressFromCoordinates(coordinates.latitude, coordinates.longitude);
            setStatus('success', 'Đã lấy tọa độ từ link Google Maps.');
            return true;
        };

        const runWithLoading = async (task, activeButton = null, showPopup = false) => {
            if (isBusy) {
                return false;
            }

            setPickerBusy(true, activeButton);

            const shouldShowPopup = showPopup || (activeButton && activeButton.hasAttribute('data-branch-map-link-analyze'));
            const popupStartedAt = performance.now();

            if (shouldShowPopup) {
                showProcessingPopup();
            }

            try {
                return await task();
            } finally {
                if (shouldShowPopup) {
                    const elapsed = performance.now() - popupStartedAt;
                    const remaining = MANUAL_LOADING_MIN_MS - elapsed;
                    if (remaining > 0) {
                        await new Promise((resolve) => setTimeout(resolve, remaining));
                    }
                }

                setPickerBusy(false);
                hideProcessingPopup();
            }
        };

        const applyLink = async (activeButton = null, options = {}) => {
            const rawLink = normalizeLink(linkInput.value);
            const hasCoordinates = parseNumber(latInput.value) !== null && parseNumber(lngInput.value) !== null;
            const showPopup = options.showPopup !== false;

            if (rawLink && rawLink === lastAnalyzedLink && hasCoordinates) {
                if (showPopup) {
                    return runWithLoading(async () => {
                        updatePreview();
                        setStatus('success', 'Link này đã được phân tích và có tọa độ.');
                        return true;
                    }, activeButton, true);
                }

                updatePreview();
                return true;
            }

            return runWithLoading(async () => {
                return performLinkLookup(rawLink);
            }, activeButton, showPopup);
        };

        applyButtons.forEach((button) => {
            button.addEventListener('click', () => {
                applyLink(button);
            });
        });

        linkInput.addEventListener('change', () => {
            applyLink(null, { showPopup: true });
        });

        linkInput.addEventListener('blur', () => {
            applyLink(null, { showPopup: true });
        });

        container.addEventListener('paste', () => {
            setTimeout(() => applyLink(null, { showPopup: true }), 0);
        });

        updatePreview();

        if (linkInput.value.trim() && (!latInput.value || !lngInput.value)) {
            applyLink(null, { showPopup: true });
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

    window.ChillDrinkBranchMapLink = {
        mount: mountPicker,
        refresh: refreshPickers,
        extractCoordinatesFromLink,
        resolveCoordinatesFromLink,
    };
})();
</script>
