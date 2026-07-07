<script>
    document.addEventListener('DOMContentLoaded', function () {
        const shippingAddressInput = document.getElementById('shipping_address_ui');
        const shippingAreaInput = document.getElementById('shipping_area_ui');
        const selectedReceiver = document.getElementById('selectedReceiver');
        const selectedPhone = document.getElementById('selectedPhone');
        const selectedAddressText = document.getElementById('selectedAddressText');
        const selectedDefaultBadge = document.getElementById('selectedDefaultBadge');
        const addressList = document.getElementById('addressList');
        const selectedBranchId = document.getElementById('selectedBranchId');
        const findNearestBranchButton = document.getElementById('findNearestBranch');
        const nearestBranchStatus = document.getElementById('nearestBranchStatus');
        const nearestBranchResult = document.getElementById('nearestBranchResult');
        const nearestBranchEndpoint = @json(route('api.branches.nearest'));

        const addressListModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addressListModal'));
        const addressEditModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addressEditModal'));
        const addressAddModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addressAddModal'));
        const voucherModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('voucherModal'));
        const profileUpdateEndpoint = @json(route('profile.update'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const selectedVoucherCode = document.getElementById('selectedVoucherCode');
        const selectedVoucherText = document.getElementById('selectedVoucherText');
        const summaryVoucherText = document.getElementById('summaryVoucherText');
        const voucherCodeInput = document.getElementById('voucherCodeInput');
        const shippingConfig = {
            subtotal: {{ (int) $total }},
            discount: {{ (int) $discount }},
            fixedShippingFee: {{ (int) $shippingFee }},
        };
        const shippingTiers = @json($shippingDistanceOptions);
        const shippingRules = @json(\App\Support\ShippingFee::estimationRules());
        const shippingDistanceLabel = document.getElementById('shippingDistanceLabel');
        const shippingEstimateDetail = document.getElementById('shippingEstimateDetail');
        const shippingInlineFee = document.getElementById('shippingInlineFee');
        const shippingEta = document.getElementById('shippingEta');
        const summaryShippingFee = document.getElementById('summaryShippingFee');
        const summaryShippingDistance = document.getElementById('summaryShippingDistance');
        const summaryGrandTotal = document.getElementById('summaryGrandTotal');

        const addressStoreEndpoint = @json(route('checkout.addresses.store'));
        const addressUpdateEndpoint = @json(url('/checkout/addresses'));

        let selectedAddressId = String(@json($checkoutAddresses->firstWhere('isDefault', true)['id'] ?? $checkoutAddresses->first()['id'] ?? 'primary'));
        let pendingVoucher = {
            code: document.querySelector('[data-voucher-card].active')?.dataset.voucherCode || '',
            label: document.querySelector('[data-voucher-card].active')?.dataset.voucherLabel || '',
            discount: Number(document.querySelector('[data-voucher-card].active')?.dataset.voucherDiscount || {{ (int) $discount }}),
        };
        const addressBook = @json($checkoutAddresses->values());

        async function persistAddress(address, isNew) {
            const endpoint = isNew ? addressStoreEndpoint : `${addressUpdateEndpoint}/${address.id}`;
            const requestData = {
                name: address.name,
                phone: address.phone,
                area: address.area,
                street: address.street,
                type: address.type,
                is_default: Boolean(address.isDefault),
            };
            const response = await fetch(endpoint, {
                method: isNew ? 'POST' : 'PUT',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(requestData),
            });
            const payload = await response.json();
            if (!response.ok) {
                const message = Object.values(payload.errors || {}).flat()[0] || payload.message || 'Không thể lưu địa chỉ.';
                throw new Error(message);
            }
            return payload.address;
        }

        function compactAddress(parts) {
            return parts.filter(Boolean).join(', ');
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[char]);
        }

        function formatVnd(amount) {
            return `${Math.max(0, Number(amount) || 0).toLocaleString('vi-VN')}đ`;
        }

        function normalizeAddressText(value) {
            return String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd');
        }

        function estimateDistanceFromAddress() {
            const text = normalizeAddressText(`${shippingAddressInput.value} ${shippingAreaInput.value}`);

            if (!text.trim()) {
                return {
                    distance: 3.5,
                    label: 'Chờ địa chỉ',
                    detail: 'chưa có địa chỉ cụ thể',
                };
            }

            for (const rule of shippingRules) {
                const matched = (rule.keywords || []).some((keyword) => text.includes(normalizeAddressText(keyword)));

                if (matched) {
                    return rule;
                }
            }

            return {
                distance: 3.5,
                label: 'Ước tính mặc định',
                detail: 'cần nhân viên xác nhận lại',
            };
        }

        function tierForDistance(distance) {
            return shippingTiers.find((tier) => Number(distance) <= Number(tier.max)) || shippingTiers[shippingTiers.length - 1];
        }

        function updateShippingSummary() {
            const methodInput = document.querySelector('input[name="shipping_method_ui"]:checked')
                || document.querySelector('input[name="shipping_method_ui"]');

            if (!methodInput) {
                return;
            }

            const shippingFee = Number(shippingConfig.fixedShippingFee || 0);
            const grandTotal = shippingConfig.subtotal + shippingFee - Number(shippingConfig.discount || 0);

            if (shippingDistanceLabel) {
                shippingDistanceLabel.textContent = 'Cố định';
            }
            if (shippingEstimateDetail) {
                shippingEstimateDetail.textContent = 'Tạm thời chưa tính theo kilomet';
            }
            if (shippingInlineFee) {
                shippingInlineFee.textContent = formatVnd(shippingFee);
            }
            if (shippingEta) {
                shippingEta.textContent = methodInput.dataset.methodEta || '';
            }
            summaryShippingFee.textContent = formatVnd(shippingFee);
            summaryShippingDistance.textContent = 'Phí giao hàng cố định';
            summaryGrandTotal.textContent = formatVnd(grandTotal);
        }

        function getAddressById(id) {
            return addressBook.find((item) => item.id === id) || addressBook[0];
        }

        function renderNearestBranch(branch) {
            selectedBranchId.value = branch.id;
            nearestBranchResult.classList.remove('d-none');
            nearestBranchResult.innerHTML = `
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <div class="fw-bold mb-1"><i class="bi bi-shop me-2 text-primary"></i>${escapeHtml(branch.name)}</div>
                        <div class="text-secondary small">${escapeHtml(branch.address || 'Chưa cập nhật địa chỉ')}</div>
                        ${branch.phone ? `<div class="text-secondary small mt-1"><i class="bi bi-telephone me-1"></i>${escapeHtml(branch.phone)}</div>` : ''}
                    </div>
                    <span class="branch-distance-badge"><i class="bi bi-geo-alt me-1"></i>${Number(branch.distance_km).toFixed(2)} km</span>
                </div>
            `;
            nearestBranchStatus.textContent = 'Đã chọn chi nhánh gần nhất để tiếp nhận đơn hàng.';
        }

        function findNearestBranch() {
            if (!navigator.geolocation) {
                nearestBranchStatus.textContent = 'Trình duyệt của bạn không hỗ trợ định vị.';
                return;
            }

            findNearestBranchButton.disabled = true;
            nearestBranchStatus.textContent = 'Đang xin quyền vị trí để tìm chi nhánh gần nhất...';

            navigator.geolocation.getCurrentPosition(async function (position) {
                const params = new URLSearchParams({
                    latitude: position.coords.latitude.toFixed(7),
                    longitude: position.coords.longitude.toFixed(7),
                    limit: '1',
                });

                try {
                    const response = await fetch(`${nearestBranchEndpoint}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const payload = await response.json();
                    const branch = payload.data?.[0];

                    if (!response.ok || !branch) {
                        throw new Error(payload.message || 'Không tìm thấy chi nhánh phù hợp.');
                    }

                    renderNearestBranch(branch);
                } catch (error) {
                    selectedBranchId.value = '';
                    nearestBranchResult.classList.add('d-none');
                    nearestBranchStatus.textContent = error.message || 'Không thể tìm chi nhánh lúc này.';
                } finally {
                    findNearestBranchButton.disabled = false;
                }
            }, function () {
                nearestBranchStatus.textContent = 'Bạn chưa cấp quyền vị trí hoặc trình duyệt không lấy được vị trí.';
                findNearestBranchButton.disabled = false;
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000,
            });
        }

        function applyAddress(address) {
            selectedAddressId = address.id;
            selectedReceiver.textContent = address.name || 'Chưa cập nhật';
            selectedPhone.textContent = address.phone || 'Chưa cập nhật';
            selectedAddressText.textContent = compactAddress([address.street, address.area]) || 'Chưa có địa chỉ. Bấm Thay đổi để thêm địa chỉ nhận hàng.';
            selectedDefaultBadge.classList.toggle('d-none', !address.isDefault);
            shippingAddressInput.value = address.street || '';
            shippingAreaInput.value = address.area || '';
            renderAddressList();
            updateShippingSummary();
        }

        function renderAddressList() {
            if (!addressList) {
                return;
            }

            const rows = addressBook.map((address) => {
                const isActive = address.id === selectedAddressId;
                const fullAddress = compactAddress([address.street, address.area]) || 'Chưa có địa chỉ cụ thể';

                return `
                    <div class="address-choice-row" data-address-row="${address.id}">
                        <button type="button" class="address-radio ${isActive ? 'active' : ''}" data-select-address="${address.id}" aria-label="Chọn địa chỉ"></button>
                        <div class="flex-grow-1">
                            <div class="address-person mb-1">
                                <span>${escapeHtml(address.name || 'Chưa cập nhật')}</span>
                                <span class="address-phone-divider"></span>
                                <span class="fw-semibold text-secondary">${escapeHtml(address.phone || 'Chưa cập nhật')}</span>
                            </div>
                            <div class="address-line">${escapeHtml(fullAddress)}</div>
                            ${address.isDefault ? '<span class="address-badge">Mặc định</span>' : ''}
                        </div>
                        <button type="button" class="btn-address-link" data-edit-address="${address.id}">Cập nhật</button>
                    </div>
                `;
            }).join('');

            addressList.innerHTML = rows || '<div class="address-empty">Bạn chưa có địa chỉ nào. Hãy thêm địa chỉ mới để đặt hàng.</div>';
        }

        function setTypeActive(scope, type) {
            document.querySelectorAll(`[data-address-scope="${scope}"]`).forEach((button) => {
                button.classList.toggle('active', button.dataset.addressType === type);
            });
        }

        function getTypeValue(scope) {
            return document.querySelector(`[data-address-scope="${scope}"].active`)?.dataset.addressType || 'Nhà Riêng';
        }

        function fillEditModal(address) {
            document.getElementById('editAddressName').value = address.name || '';
            document.getElementById('editAddressPhone').value = address.phone || '';
            document.getElementById('editAddressArea').value = address.area || '';
            document.getElementById('editAddressStreet').value = address.street || '';
            document.getElementById('editAddressDefault').checked = !!address.isDefault;
            setTypeActive('edit', address.type || 'Nhà Riêng');
        }

        function openEditModal(id = selectedAddressId) {
            fillEditModal(getAddressById(id));
            selectedAddressId = id;
            addressListModal.hide();
            addressEditModal.show();
        }

        function openAddModal() {
            document.getElementById('newAddressName').value = @json($user->name);
            document.getElementById('newAddressPhone').value = @json($user->phone ?? '');
            document.getElementById('newAddressArea').value = '';
            document.getElementById('newAddressStreet').value = '';
            document.getElementById('newAddressDefault').checked = false;
            document.getElementById('newAddressStatus').textContent = 'Có thể bấm thêm vị trí để tự điền địa chỉ từ trình duyệt.';
            setTypeActive('new', 'Nhà Riêng');
            addressListModal.hide();
            addressAddModal.show();
        }

        function setVoucherActive(card) {
            if (!card) {
                return;
            }

            document.querySelectorAll('[data-voucher-card]').forEach((item) => {
                item.classList.remove('active');
                item.querySelector('.voucher-radio')?.classList.remove('active');
                item.querySelector('.voucher-radio')?.setAttribute('aria-pressed', 'false');
            });
            card.classList.add('active');
            card.querySelector('.voucher-radio')?.classList.add('active');
            card.querySelector('.voucher-radio')?.setAttribute('aria-pressed', 'true');
            pendingVoucher = {
                code: card.dataset.voucherCode || '',
                label: card.dataset.voucherLabel || '',
                discount: Number(card.dataset.voucherDiscount || 0),
            };
            voucherCodeInput.value = pendingVoucher.code;
        }

        function commitVoucherSelection() {
            selectedVoucherCode.value = pendingVoucher.code || '';
            selectedVoucherText.textContent = pendingVoucher.label ? `Đã chọn: ${pendingVoucher.label}` : 'Chưa chọn voucher';
            shippingConfig.discount = Number(pendingVoucher.discount || 0);
            summaryVoucherText.textContent = shippingConfig.discount > 0
                ? `-${formatVnd(shippingConfig.discount)}`
                : (pendingVoucher.code ? 'Sẽ kiểm tra khi đặt hàng' : 'Chưa áp dụng');
            updateShippingSummary();
            voucherModal.hide();
        }

        document.addEventListener('click', function (event) {
            const selectButton = event.target.closest('[data-select-address]');
            const editButton = event.target.closest('[data-edit-address]');
            const openEditButton = event.target.closest('[data-open-address-edit]');
            const openAddButton = event.target.closest('[data-open-address-add]');
            const returnButton = event.target.closest('[data-return-address-list]');
            const typeButton = event.target.closest('[data-address-type]');
            const locateButton = event.target.closest('[data-locate-address]');
            const voucherCard = event.target.closest('[data-voucher-card]');

            if (selectButton) {
                applyAddress(getAddressById(selectButton.dataset.selectAddress));
                addressListModal.hide();
            }

            if (editButton) {
                openEditModal(editButton.dataset.editAddress);
            }

            if (openEditButton) {
                openEditModal();
            }

            if (openAddButton) {
                openAddModal();
            }

            if (returnButton) {
                addressEditModal.hide();
                addressAddModal.hide();
                addressListModal.show();
            }

            if (typeButton) {
                setTypeActive(typeButton.dataset.addressScope, typeButton.dataset.addressType);
            }

            if (locateButton) {
                locateAddress(locateButton.dataset.locateAddress);
            }

            if (voucherCard && !event.target.closest('a')) {
                setVoucherActive(voucherCard);
            }
        });

        document.getElementById('saveEditedAddress')?.addEventListener('click', async function () {
            const button = this;
            const address = getAddressById(selectedAddressId);
            address.name = document.getElementById('editAddressName').value.trim();
            address.phone = document.getElementById('editAddressPhone').value.trim();
            address.area = document.getElementById('editAddressArea').value.trim();
            address.street = document.getElementById('editAddressStreet').value.trim();
            address.type = getTypeValue('edit');
            address.isDefault = document.getElementById('editAddressDefault').checked;

            const status = document.getElementById('editAddressStatus');
            try {
                button.disabled = true;
                status.textContent = 'Đang lưu địa chỉ...';
                status.classList.remove('text-danger');
                const saved = await persistAddress(address, address.id === 'primary');
                const index = addressBook.findIndex((item) => item.id === address.id);
                if (saved.isDefault) addressBook.forEach((item) => item.isDefault = false);
                if (index >= 0) addressBook[index] = saved; else addressBook.push(saved);
                applyAddress(saved);
                status.textContent = 'Đã lưu địa chỉ.';
                addressEditModal.hide();
            } catch (error) {
                status.textContent = error.message;
                status.classList.add('text-danger');
            } finally {
                button.disabled = false;
            }
        });

        document.getElementById('saveNewAddress')?.addEventListener('click', async function () {
            const button = this;
            const address = {
                name: document.getElementById('newAddressName').value.trim(),
                phone: document.getElementById('newAddressPhone').value.trim(),
                area: document.getElementById('newAddressArea').value.trim(),
                street: document.getElementById('newAddressStreet').value.trim(),
                type: getTypeValue('new'),
                isDefault: document.getElementById('newAddressDefault').checked,
            };

            const status = document.getElementById('newAddressStatus');
            try {
                button.disabled = true;
                status.textContent = 'Đang lưu địa chỉ...';
                status.classList.remove('text-danger');
                const saved = await persistAddress(address, true);
                if (saved.isDefault) addressBook.forEach((item) => item.isDefault = false);
                addressBook.push(saved);
                applyAddress(saved);
                status.textContent = 'Đã lưu địa chỉ.';
                addressAddModal.hide();
            } catch (error) {
                status.textContent = error.message;
                status.classList.add('text-danger');
            } finally {
                button.disabled = false;
            }
        });

        document.getElementById('voucherManualApply')?.addEventListener('click', function () {
            const code = voucherCodeInput.value.trim().toUpperCase();

            if (!code) {
                voucherCodeInput.focus();
                return;
            }

            const matchedCard = Array.from(document.querySelectorAll('[data-voucher-code]'))
                .find((item) => item.dataset.voucherCode === code);

            if (matchedCard) {
                if (matchedCard.dataset.voucherDisabled === '1') {
                    voucherCodeInput.focus();
                    return;
                }

                setVoucherActive(matchedCard);
                commitVoucherSelection();
                return;
            }

            document.querySelectorAll('[data-voucher-card]').forEach((item) => {
                item.classList.remove('active');
                item.querySelector('.voucher-radio')?.classList.remove('active');
                item.querySelector('.voucher-radio')?.setAttribute('aria-pressed', 'false');
            });
            pendingVoucher = {
                code,
                label: `${code} - Mã nhập thủ công`,
                discount: 0,
            };
            commitVoucherSelection();
        });

        document.getElementById('confirmVoucher')?.addEventListener('click', function () {
            commitVoucherSelection();
        });

        document.querySelectorAll('input[name="shipping_method_ui"]').forEach((input) => {
            input.addEventListener('change', updateShippingSummary);
        });

        findNearestBranchButton?.addEventListener('click', findNearestBranch);

        document.querySelector('[data-toggle-checkout-items]')?.addEventListener('click', function () {
            const extraItems = document.querySelectorAll('[data-checkout-extra-item]');
            const isOpening = Array.from(extraItems).some((item) => item.classList.contains('d-none'));

            extraItems.forEach((item) => item.classList.toggle('d-none', !isOpening));
            this.textContent = isOpening ? 'Thu gọn' : `Xem tất cả ${this.dataset.totalItems} món`;
        });

        async function reverseGeocode(lat, lng, scope) {
            const status = document.getElementById(scope === 'edit' ? 'editAddressStatus' : 'newAddressStatus');
            const streetInput = document.getElementById(scope === 'edit' ? 'editAddressStreet' : 'newAddressStreet');
            const areaInput = document.getElementById(scope === 'edit' ? 'editAddressArea' : 'newAddressArea');
            const mapShell = document.getElementById(scope === 'edit' ? 'editAddressMapShell' : 'newAddressMapShell');

            status.textContent = 'Đã lấy vị trí, đang chuyển thành địa chỉ...';
            mapShell.innerHTML = `
                <div class="text-center">
                    <div class="fs-2 text-primary mb-1"><i class="bi bi-geo-alt-fill"></i></div>
                    <div class="fw-bold">Vị trí đã xác nhận</div>
                    <a class="text-primary fw-semibold" href="https://www.google.com/maps?q=${lat},${lng}" target="_blank" rel="noopener">Mở Google Maps</a>
                </div>
            `;

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&accept-language=vi`);
                const data = await response.json();
                const address = data.address || {};
                const streetLine = compactAddress([
                    address.house_number,
                    address.road || address.pedestrian || address.footway,
                    address.neighbourhood || address.suburb,
                ]);
                const areaLine = compactAddress([
                    address.quarter || address.ward || address.suburb || address.village,
                    address.city_district || address.district || address.town,
                    address.city || address.state,
                ]);

                streetInput.value = streetLine || data.display_name || `${lat}, ${lng}`;
                areaInput.value = areaLine || data.display_name || `${lat}, ${lng}`;
                status.textContent = 'Đã tự điền địa chỉ. Bạn có thể chỉnh lại trước khi hoàn thành.';
            } catch (error) {
                streetInput.value = `Vị trí hiện tại: ${lat}, ${lng}`;
                areaInput.value = `Vị trí hiện tại: ${lat}, ${lng}`;
                status.textContent = 'Đã lấy vị trí nhưng chưa đổi được thành địa chỉ chữ. Bạn có thể chỉnh lại thủ công.';
            }
        }

        function locateAddress(scope) {
            const status = document.getElementById(scope === 'edit' ? 'editAddressStatus' : 'newAddressStatus');

            if (!navigator.geolocation) {
                status.textContent = 'Trình duyệt của bạn không hỗ trợ định vị.';
                return;
            }

            status.textContent = 'Đang xin quyền vị trí...';
            navigator.geolocation.getCurrentPosition(function (position) {
                reverseGeocode(position.coords.latitude.toFixed(6), position.coords.longitude.toFixed(6), scope);
            }, function () {
                status.textContent = 'Bạn chưa cấp quyền vị trí hoặc trình duyệt không lấy được vị trí.';
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            });
        }

        async function loadReceivedVouchers() {
            try {
                const guestIdentifier = sessionStorage.getItem('guest_identifier');
                const response = await fetch('/api/vouchers/received', {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        ...(guestIdentifier && { 'X-Guest-Identifier': guestIdentifier }),
                    },
                });

                const data = await response.json();
                const receivedVouchersSection = document.getElementById('receivedVouchersSection');
                const receivedVouchersList = document.getElementById('receivedVouchersList');

                if (data.vouchers && data.vouchers.length > 0) {
                    receivedVouchersSection.style.display = 'block';
                    receivedVouchersList.innerHTML = '';

                    data.vouchers.forEach(voucher => {
                        const voucherHtml = `
                            <div class="voucher-ticket" data-voucher-card data-voucher-code="${escapeHtml(voucher.code)}" data-voucher-label="${escapeHtml(voucher.description ? `${voucher.code} - ${voucher.description}` : voucher.code)}" data-voucher-discount="0">
                                <div class="voucher-ticket-brand">
                                    <span class="brand-circle"><i class="bi bi-gift"></i></span>
                                    <strong>${escapeHtml(voucher.code)}</strong>
                                </div>
                                <div class="voucher-ticket-body">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <span class="voucher-kind">Đã nhận</span>
                                        <span class="fw-semibold text-secondary">${escapeHtml(voucher.value)}</span>
                                    </div>
                                    <div class="text-secondary small">
                                        ${escapeHtml(voucher.description || 'Voucher')}
                                    </div>
                                    <span class="voucher-only mt-2 mb-2">
                                        Bạn đã nhận voucher này
                                    </span>
                                </div>
                                <button type="button" class="voucher-radio" aria-label="Chọn voucher ${escapeHtml(voucher.code)}"></button>
                            </div>
                        `;
                        receivedVouchersList.innerHTML += voucherHtml;
                    });

                    document.querySelectorAll('[data-voucher-card]').forEach((card) => {
                        card.addEventListener('click', function (event) {
                            if (event.target.closest('.voucher-radio')) {
                                setVoucherActive(this);
                            }
                        });
                    });
                } else {
                    receivedVouchersSection.style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading received vouchers:', error);
            }
        }

        const voucherModalElement = document.getElementById('voucherModal');
        if (voucherModalElement) {
            voucherModalElement.addEventListener('show.bs.modal', loadReceivedVouchers);
        }

        renderAddressList();
        applyAddress(getAddressById(selectedAddressId));
        updateShippingSummary();
    });
</script>