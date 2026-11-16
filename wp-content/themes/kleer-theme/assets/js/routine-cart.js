/**
 * KLEER Routine Cart
 * Path: wp-content/themes/kleer-theme/assets/js/routine-cart.js
 *
 * Vanilla ES6. Điều khiển ngăn kéo giỏ hàng Liquid Glass và gọi AJAX
 * kleer_cart_mutate để cập nhật Combo 15% + cảnh báo xung đột hoạt chất.
 */
(function () {
    'use strict';

    const cfg = window.kleerAjax || {};
    const ajaxUrl = cfg.ajax_url || '/wp-admin/admin-ajax.php';
    const nonce = cfg.nonce || '';

    let lastState = null;
    let isBusy = false;

    const drawer = () => document.getElementById('kleer-cart-drawer');

    const formatMoney = (value) => `${Math.round(Number(value) || 0).toLocaleString('vi-VN')}đ`;

    const escapeHtml = (value) => {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(value === null || value === undefined ? '' : value)
            .replace(/[&<>"']/g, (character) => map[character]);
    };

    const post = async (payload) => {
        const body = new FormData();
        body.append('action', 'kleer_cart_mutate');
        if (nonce) {
            body.append('nonce', nonce);
        }

        Object.entries(payload).forEach(([key, value]) => {
            if (value === undefined || value === null) {
                return;
            }
            body.append(key, typeof value === 'object' ? JSON.stringify(value) : value);
        });

        const response = await fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: nonce ? { 'X-WP-Nonce': nonce } : {},
            body,
        });

        const json = await response.json().catch(() => null);

        if (!response.ok || !json || !json.success) {
            const message = json && json.data && json.data.message
                ? json.data.message
                : 'Không thể cập nhật giỏ hàng. Vui lòng thử lại.';
            throw new Error(message);
        }

        return json.data;
    };

    const render = (state) => {
        lastState = state;

        const itemsEl = document.getElementById('kleer-cart-items');
        const badge = document.getElementById('kleer-cart-count');
        const conflictEl = document.getElementById('kleer-cart-conflict');
        const discountRow = document.getElementById('kleer-cart-discount-row');
        const discountEl = document.getElementById('kleer-cart-discount');
        const discountLabel = document.getElementById('kleer-cart-discount-label');
        const subtotalEl = document.getElementById('kleer-cart-subtotal');
        const totalEl = document.getElementById('kleer-cart-total');
        const checkoutEl = document.getElementById('kleer-cart-checkout');

        const items = Array.isArray(state.items) ? state.items : [];

        if (itemsEl) {
            itemsEl.innerHTML = items.length
                ? items.map((item) => `
                    <article class="cart-item" data-key="${escapeHtml(item.key)}">
                        <img class="cart-item__img" src="${escapeHtml(item.image)}" alt="">
                        <div>
                            <a class="cart-item__name" href="${escapeHtml(item.permalink)}">${escapeHtml(item.name)}</a>
                            <p class="cart-item__price">${formatMoney(item.line_total)}</p>
                            <div class="cart-item__qty">
                                <button type="button" data-qty-minus="${escapeHtml(item.key)}" aria-label="Giảm số lượng">−</button>
                                <span>${escapeHtml(item.qty)}</span>
                                <button type="button" data-qty-plus="${escapeHtml(item.key)}" aria-label="Tăng số lượng">+</button>
                            </div>
                        </div>
                    </article>
                `).join('')
                : '<p class="cart-drawer__empty">Giỏ hàng đang trống.</p>';
        }

        if (badge) {
            badge.textContent = String(state.item_count || 0);
            badge.hidden = !state.item_count;
        }

        if (subtotalEl) {
            subtotalEl.textContent = formatMoney(state.subtotal);
        }

        if (totalEl) {
            totalEl.textContent = formatMoney(state.total);
        }

        if (discountEl && discountRow) {
            const active = Boolean(state.discount_active) && Number(state.discount_amount) > 0;
            discountRow.hidden = !active;
            discountEl.textContent = `-${formatMoney(state.discount_amount)}`;
            if (discountLabel && state.discount_label) {
                discountLabel.textContent = `${state.discount_label} (-15%)`;
            }
        }

        if (conflictEl) {
            const conflict = state.conflict || {};
            conflictEl.hidden = !conflict.has_conflict;
            conflictEl.textContent = conflict.has_conflict ? (conflict.message || '') : '';
        }

        if (checkoutEl) {
            checkoutEl.href = state.checkout_url || checkoutEl.href;
            checkoutEl.classList.toggle('is-disabled', !items.length);
        }
    };

    const open = () => {
        const el = drawer();
        if (!el) {
            return;
        }
        el.classList.add('is-open');
        el.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (!lastState) {
            refresh();
        }
    };

    const close = () => {
        const el = drawer();
        if (!el) {
            return;
        }
        el.classList.remove('is-open');
        el.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    const refresh = async () => {
        try {
            render(await post({ op: 'state' }));
        } catch (error) {
            console.warn('[KLEER] cart state unavailable:', error.message);
        }
    };

    const add = async (productId, trigger) => {
        const id = parseInt(productId, 10);
        if (!Number.isInteger(id) || id <= 0 || isBusy) {
            return;
        }

        isBusy = true;
        if (trigger) {
            trigger.disabled = true;
        }

        try {
            const quantityInput = trigger?.getAttribute('data-quantity-input');
            const input = quantityInput ? document.getElementById(quantityInput) : null;
            const qty = input ? Math.max(1, Math.min(Number(input.max) || 99, parseInt(input.value, 10) || 1)) : 1;
            render(await post({ op: 'add', product_id: id, qty }));
            open();
        } catch (error) {
            console.error('[KLEER] add to cart failed:', error);
            window.alert(error.message);
        } finally {
            isBusy = false;
            if (trigger) {
                trigger.disabled = false;
            }
        }
    };

    const addBundle = async (productIds) => {
        const ids = (Array.isArray(productIds) ? productIds : [])
            .map((id) => parseInt(id, 10))
            .filter((id) => Number.isInteger(id) && id > 0);

        if (!ids.length || isBusy) {
            return;
        }

        isBusy = true;

        try {
            render(await post({ op: 'add_bundle', product_ids: ids }));
            open();
        } catch (error) {
            console.error('[KLEER] add bundle failed:', error);
            window.alert(error.message);
        } finally {
            isBusy = false;
        }
    };

    const updateQty = async (cartItemKey, qty) => {
        if (!cartItemKey || isBusy) {
            return;
        }

        isBusy = true;

        try {
            render(await post({ op: 'update_qty', cart_item_key: cartItemKey, qty }));
        } catch (error) {
            console.error('[KLEER] update quantity failed:', error);
        } finally {
            isBusy = false;
        }
    };

    document.addEventListener('click', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        const addButton = target.closest('[data-add-cart]');
        if (addButton) {
            event.preventDefault();
            add(addButton.getAttribute('data-add-cart'), addButton);
            return;
        }

        if (target.closest('[data-cart-open]')) {
            event.preventDefault();
            open();
            return;
        }

        if (target.closest('[data-cart-close]')) {
            event.preventDefault();
            close();
            return;
        }

        const minus = target.closest('[data-qty-minus]');
        if (minus && lastState) {
            const key = minus.getAttribute('data-qty-minus');
            const item = (lastState.items || []).find((entry) => entry.key === key);
            if (item) {
                updateQty(key, Math.max(0, Number(item.qty) - 1));
            }
            return;
        }

        const plus = target.closest('[data-qty-plus]');
        if (plus && lastState) {
            const key = plus.getAttribute('data-qty-plus');
            const item = (lastState.items || []).find((entry) => entry.key === key);
            if (item) {
                updateQty(key, Number(item.qty) + 1);
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            close();
        }
    });

    const header = document.getElementById('kleer-header');
    if (header) {
        const onScroll = () => {
            header.classList.toggle('header--scrolled', window.scrollY > 8);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    window.KleerCart = {
        add: (id) => add(id),
        addBundle,
        open,
        close,
        refresh,
        updateQty,
    };
})();
