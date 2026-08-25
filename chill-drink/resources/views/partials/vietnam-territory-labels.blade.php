@once
    <style>
        .territory-map-label {
            position: relative;
            z-index: 650;
            pointer-events: none;
        }

        .territory-map-label-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 0.28rem 0.55rem;
            border: 1px solid rgba(13, 147, 115, 0.28);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.96);
            color: #0d6b5b;
            font-size: 0.68rem;
            font-weight: 800;
            white-space: nowrap;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
            transform: translate(-50%, -120%);
        }

        .territory-map-label--maplibre .territory-map-label-chip {
            transform: translate(-50%, -118%);
        }

        .territory-map-label-flag {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 0.95rem;
            height: 0.7rem;
            flex: 0 0 auto;
        }

        .territory-map-label-flag svg {
            display: block;
            width: 100%;
            height: 100%;
        }
    </style>
@endonce

<script>
(() => {
    if (window.ChillDrinkVietnamTerritoryLabels) return;

    const points = [
        { label: 'Hoàng Sa', lat: 16.5, lng: 112.0 },
        { label: 'Trường Sa', lat: 8.6402, lng: 111.9187 },
    ];

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[char]);

    const flagHtml = label => `
        <span class="territory-map-label-chip">
            <span class="territory-map-label-flag" aria-hidden="true">
                <svg viewBox="0 0 30 20" role="img" focusable="false" aria-hidden="true">
                    <rect width="30" height="20" rx="3" fill="#da251d"></rect>
                    <path
                        d="M15 4.2l1.74 3.53 3.9.57-2.82 2.75.67 3.89L15 13.9l-3.49 1.84.67-3.89-2.82-2.75 3.9-.57L15 4.2z"
                        fill="#ffde00"
                    ></path>
                </svg>
            </span>
            <span>${escapeHtml(label)}</span>
        </span>`;

    const makeLeafletIcon = label => L.divIcon({
        className: 'territory-map-label',
        html: flagHtml(label),
        iconSize: [1, 1],
        iconAnchor: [0, 0],
    });

    const makeMapLibreElement = label => {
        const element = document.createElement('div');
        element.className = 'territory-map-label territory-map-label--maplibre';
        element.innerHTML = flagHtml(label);
        return element;
    };

    const addToLeaflet = map => {
        if (!map || !window.L || map.__chillVietnamTerritoryLabelsAdded) return [];
        map.__chillVietnamTerritoryLabelsAdded = true;

        return points.map(point => L.marker([point.lat, point.lng], {
            icon: makeLeafletIcon(point.label),
            interactive: false,
            keyboard: false,
            riseOnHover: false,
        }).addTo(map));
    };

    const addToMapLibre = map => {
        if (!map || !window.maplibregl || map.__chillVietnamTerritoryLabelsAdded) return [];
        map.__chillVietnamTerritoryLabelsAdded = true;

        return points.map(point => new maplibregl.Marker({
            element: makeMapLibreElement(point.label),
            anchor: 'bottom',
        }).setLngLat([point.lng, point.lat]).addTo(map));
    };

    window.ChillDrinkVietnamTerritoryLabels = {
        points,
        addToLeaflet,
        addToMapLibre,
    };
})();
</script>
