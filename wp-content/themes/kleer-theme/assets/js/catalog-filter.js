/**
 * KLEER Catalog Filter
 * Path: wp-content/themes/kleer-theme/assets/js/catalog-filter.js
 *
 * Lọc không tải lại trang cho kệ Trọn bộ liệu trình. Ưu tiên lọc cục bộ trên
 * kleerAjax.initial_products (<= 50 SKU), vượt ngưỡng thì gọi AJAX kleer_filter_products.
 */
(function () {
    'use strict';

    const cfg = window.kleerAjax || {};
    const grid = document.getElementById('bento-catalog-grid');
    const searchInput = document.getElementById('catalog-search-input');
    const stepSelect = document.getElementById('step-filter-select');
    const concernSelect = document.getElementById('concern-filter-select');
    const pills = Array.from(document.querySelectorAll('.chapternav-pill[data-skin-filter]'));
    const initialProducts = Array.isArray(cfg.initial_products) ? cfg.initial_products : [];
    const LOCAL_LIMIT = 50;

    const escapeHtml = (value) => {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(value === null || value === undefined ? '' : value)
            .replace(/[&<>"']/g, (character) => map[character]);
    };

    const formatMoney = (value) => `Từ ${Math.round(Number(value) || 0).toLocaleString('vi-VN')}đ`;

    // Điều hướng kệ ngang bằng hai nút ‹ ›
    const scroller = document.getElementById('shelf-scroller');
    const shelfPrev = document.getElementById('shelf-prev');
    const shelfNext = document.getElementById('shelf-next');

    if (scroller && (shelfPrev || shelfNext)) {
        const step = () => {
            const card = scroller.querySelector('.rf-recommcard-33');
            return card ? card.getBoundingClientRect().width + 20 : 320;
        };

        shelfPrev?.addEventListener('click', () => {
            scroller.scrollBy({ left: -step(), behavior: 'smooth' });
        });

        shelfNext?.addEventListener('click', () => {
            scroller.scrollBy({ left: step(), behavior: 'smooth' });
        });
    }

    // Trang không có lưới: ô tìm kiếm chuyển sang trang Shop.
    if (!grid) {
        if (searchInput) {
            searchInput.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') {
                    return;
                }
                event.preventDefault();
                const base = cfg.shop_url || '/shop/';
                const separator = base.includes('?') ? '&' : '?';
                window.location.href = `${base}${separator}q=${encodeURIComponent(searchInput.value.trim())}`;
            });
        }
        return;
    }

    const state = { skin: '', step: '', concern: '', q: '' };

    const cardHtml = (product) => {
        const id = parseInt(product.id, 10) || 0;
        const skinTypes = Array.isArray(product.skinTypes) ? product.skinTypes : [];
        const concernSlugs = Array.isArray(product.concernSlugs) ? product.concernSlugs : [];
        const price = Number(product.price) || 0;
        const image = product.image || '';
        const permalink = product.permalink || cfg.shop_url || '/shop/';

        const action = id > 0
            ? `<button type="button" class="apple-card__circle-btn" data-add-cart="${id}" aria-label="Thêm ${escapeHtml(product.name)} vào Routine">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
               </button>`
            : `<a class="apple-card__circle-btn" href="${escapeHtml(permalink)}" aria-label="Xem ${escapeHtml(product.name)}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
               </a>`;

        return `
            <article class="product-card"
                style="--card-top-bg:${escapeHtml(product.cardTopBg || '#d4e6d8')};--card-bottom-bg:${escapeHtml(product.cardBottomBg || '#73957c')};--card-accent:${escapeHtml(product.cardAccent || '#1c4627')}"
                data-product-id="${id}"
                data-name="${escapeHtml(product.name)}"
                data-price="${price}"
                data-skin-types="${escapeHtml(skinTypes.join(' '))}"
                data-step="${escapeHtml(product.stepSlug || '')}"
                data-concern="${escapeHtml(concernSlugs.join(' '))}">
                <div class="apple-card__info">
                    <span class="apple-card__eyebrow">${escapeHtml(product.bestsellerTag || 'CÔNG THỨC NỔI BẬT')}</span>
                    <h3 class="apple-card__title"><a href="${escapeHtml(permalink)}">${escapeHtml(product.name)}</a></h3>
                    <div class="apple-card__meta-row">
                        <span class="apple-card__skin-chip">${escapeHtml(product.skinLabel || 'Mọi loại da')}</span>
                        <span class="apple-card__price-top">${escapeHtml(product.priceFormatted || formatMoney(price))}</span>
                    </div>
                    <p class="apple-card__sensory">${escapeHtml(product.sensory || '')}</p>
                </div>
                <div class="apple-card__stage">
                    ${image ? `<img class="apple-card__img" src="${escapeHtml(image)}" alt="${escapeHtml(product.imageAlt || product.name)}" loading="lazy">` : ''}
                    <span class="apple-card__inci-pill">${escapeHtml(product.inciBadge || 'Chiết xuất thực vật')}</span>
                    ${action}
                </div>
            </article>
        `;
    };

    const renderCards = (products) => {
        if (!products.length) {
            grid.innerHTML = '<div class="catalog-empty">Không tìm thấy sản phẩm phù hợp với bộ lọc.</div>';
            return;
        }
        grid.innerHTML = products.map(cardHtml).join('');
    };

    const matches = (product) => {
        const skinTypes = Array.isArray(product.skinTypes) ? product.skinTypes : [];
        const concernSlugs = Array.isArray(product.concernSlugs) ? product.concernSlugs : [];

        if (state.skin && !skinTypes.includes(state.skin)) {
            return false;
        }
        if (state.step && String(product.stepSlug || '') !== state.step) {
            return false;
        }
        if (state.concern && !concernSlugs.includes(state.concern)) {
            return false;
        }
        if (state.q && !String(product.name || '').toLowerCase().includes(state.q.toLowerCase())) {
            return false;
        }
        return true;
    };

    const syncPills = () => {
        pills.forEach((pill) => {
            const value = pill.getAttribute('data-skin-filter') || 'all';
            pill.classList.toggle('active', value === (state.skin || 'all'));
        });
    };

    const updateUrl = () => {
        const url = new URL(window.location.href);

        ['skin_type', 'routine_step', 'skin_concern', 'q'].forEach((key) => {
            url.searchParams.delete(key);
        });

        if (state.skin) {
            url.searchParams.set('skin_type', state.skin);
        }
        if (state.step) {
            url.searchParams.set('routine_step', state.step);
        }
        if (state.concern) {
            url.searchParams.set('skin_concern', state.concern);
        }
        if (state.q) {
            url.searchParams.set('q', state.q);
        }

        window.history.pushState({}, '', url.toString());
    };

    const fetchRemote = async () => {
        const body = new FormData();
        body.append('action', 'kleer_filter_products');
        if (cfg.nonce) {
            body.append('nonce', cfg.nonce);
        }
        if (state.skin) {
            body.append('skin_type', state.skin);
        }
        if (state.step) {
            body.append('routine_step', state.step);
        }
        if (state.concern) {
            body.append('skin_concern', state.concern);
        }
        if (state.q) {
            body.append('search', state.q);
        }
        body.append('limit', '12');

        try {
            const response = await fetch(cfg.ajax_url || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                credentials: 'same-origin',
                body,
            });
            const json = await response.json().catch(() => null);
            renderCards(json && json.data && Array.isArray(json.data.products) ? json.data.products : []);
        } catch (error) {
            console.error('[KLEER] catalog filter failed:', error);
        }
    };

    const apply = () => {
        syncPills();

        const totalProducts = Number(cfg.product_count) || initialProducts.length;

        if (initialProducts.length && totalProducts <= LOCAL_LIMIT) {
            renderCards(initialProducts.filter(matches).slice(0, 12));
        } else {
            fetchRemote();
        }
    };

    const applyAndUrl = () => {
        updateUrl();
        apply();
    };

    pills.forEach((pill) => {
        pill.addEventListener('click', () => {
            const value = pill.getAttribute('data-skin-filter') || 'all';
            state.skin = value === 'all' ? '' : value;
            applyAndUrl();
        });
    });

    if (stepSelect) {
        stepSelect.addEventListener('change', () => {
            state.step = stepSelect.value;
            applyAndUrl();
        });
    }

    if (concernSelect) {
        concernSelect.addEventListener('change', () => {
            state.concern = concernSelect.value;
            applyAndUrl();
        });
    }

    if (searchInput) {
        let timer = null;
        searchInput.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => {
                state.q = searchInput.value.trim();
                applyAndUrl();
            }, 220);
        });
    }

    const readUrl = () => {
        const params = new URLSearchParams(window.location.search);
        state.skin = params.get('skin_type') || '';
        state.step = params.get('routine_step') || '';
        state.concern = params.get('skin_concern') || '';
        state.q = params.get('q') || '';

        if (stepSelect) {
            stepSelect.value = state.step;
        }
        if (concernSelect) {
            concernSelect.value = state.concern;
        }
        if (searchInput) {
            searchInput.value = state.q;
        }
    };

    window.addEventListener('popstate', () => {
        readUrl();
        apply();
    });

    readUrl();
    syncPills();

    if (state.skin || state.step || state.concern || state.q) {
        apply();
    }
})();
