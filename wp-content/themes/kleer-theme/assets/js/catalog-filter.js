/**
 * KLEER Catalog Filter JS
 */

(function () {
    'use strict';

    const form = document.getElementById('kleer-catalog-filter-form');
    const grid = document.getElementById('kleer-products-grid');
    const emptyState = document.getElementById('kleer-products-empty');
    const clearBtn = document.getElementById('kleer-filter-clear');
    const clearBtnEmpty = document.getElementById('kleer-filter-clear-empty');
    const minPriceInput = document.getElementById('kleer-min-price');
    const maxPriceInput = document.getElementById('kleer-max-price');

    if (!form) {
        return;
    }

    const ajaxUrl = (window.kleerAjax && window.kleerAjax.ajax_url) ? window.kleerAjax.ajax_url : '/wp-admin/admin-ajax.php';
    const nonce = form.getAttribute('data-nonce') || '';

    function buildQueryParams() {
        const params = new URLSearchParams();
        const checkboxes = form.querySelectorAll('input[type="checkbox"]:checked');
        checkboxes.forEach(function (cb) {
            const taxonomy = cb.getAttribute('data-taxonomy') || cb.name.replace('[]', '');
            params.append(taxonomy + '[]', cb.value);
        });
        if (minPriceInput && minPriceInput.value !== '') {
            params.append('min_price', minPriceInput.value);
        }
        if (maxPriceInput && maxPriceInput.value !== '') {
            params.append('max_price', maxPriceInput.value);
        }
        params.append('action', 'kleer_filter_products');
        if (nonce) {
            params.append('nonce', nonce);
        }
        return params;
    }

    function updateUrl(params) {
        const url = new URL(window.location.href);
        const taxonomies = ['skin_type', 'skin_concern', 'routine_step'];
        taxonomies.forEach(function (tax) {
            url.searchParams.delete(tax);
            url.searchParams.delete(tax + '[]');
        });
        url.searchParams.delete('min_price');
        url.searchParams.delete('max_price');
        url.searchParams.delete('paged');

        for (const [key, value] of params.entries()) {
            if (key === 'action' || key === 'nonce') {
                continue;
            }
            if (key.endsWith('[]')) {
                const cleanKey = key.replace('[]', '');
                url.searchParams.append(cleanKey, value);
            } else {
                url.searchParams.append(key, value);
            }
        }
        history.pushState({}, '', url.toString());
    }

    function applyFiltersFromUrl() {
        const url = new URL(window.location.href);
        const taxonomies = ['skin_type', 'skin_concern', 'routine_step'];
        taxonomies.forEach(function (tax) {
            const values = url.searchParams.getAll(tax);
            if (values.length === 0) {
                const values2 = url.searchParams.getAll(tax + '[]');
                values.push.apply(values, values2);
            }
            values.forEach(function (val) {
                const cb = form.querySelector('input[type="checkbox"][data-taxonomy="' + tax + '"][value="' + val + '"]');
                if (cb) {
                    cb.checked = true;
                }
            });
        });
        if (url.searchParams.get('min_price') && minPriceInput) {
            minPriceInput.value = url.searchParams.get('min_price');
        }
        if (url.searchParams.get('max_price') && maxPriceInput) {
            maxPriceInput.value = url.searchParams.get('max_price');
        }
        if (url.searchParams.has('skin_type') || url.searchParams.has('skin_concern') || url.searchParams.has('routine_step') || url.searchParams.has('min_price') || url.searchParams.has('max_price')) {
            fetchProducts();
        }
    }

    function fetchProducts() {
        const params = buildQueryParams();
        updateUrl(params);

        const body = new FormData();
        for (const [key, value] of params.entries()) {
            body.append(key, value);
        }

        const startTime = performance.now();

        fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: body
        })
            .then(function (res) {
                if (!res.ok) {
                    throw new Error('Request failed');
                }
                return res.json();
            })
            .then(function (data) {
                const endTime = performance.now();
                if (endTime - startTime > 200) {
                    // just for info, still process
                }
                if (data && data.success && typeof data.data.html !== 'undefined') {
                    if (grid) {
                        grid.innerHTML = data.data.html;
                    }
                    if (emptyState) {
                        if (data.data.html.indexOf('kleer-no-products') !== -1 || grid && grid.innerHTML.trim() === '') {
                            emptyState.style.display = 'block';
                        } else {
                            emptyState.style.display = 'none';
                        }
                    }
                } else if (data && data.data && data.data.message) {
                    if (grid) {
                        grid.innerHTML = '';
                    }
                    if (emptyState) {
                        emptyState.style.display = 'block';
                    }
                }
            })
            .catch(function (err) {
                console.error('Filter error:', err);
            });
    }

    form.addEventListener('change', function (e) {
        if (e.target.matches('input[type="checkbox"]') || e.target.matches('input[type="number"]')) {
            fetchProducts();
        }
    });

    if (minPriceInput) {
        minPriceInput.addEventListener('change', fetchProducts);
    }
    if (maxPriceInput) {
        maxPriceInput.addEventListener('change', fetchProducts);
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            form.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                cb.checked = false;
            });
            if (minPriceInput) minPriceInput.value = '';
            if (maxPriceInput) maxPriceInput.value = '';
            const url = new URL(window.location.href);
            url.search = '';
            history.pushState({}, '', url.toString());
            fetchProducts();
        });
    }

    if (clearBtnEmpty) {
        clearBtnEmpty.addEventListener('click', function () {
            if (clearBtn) {
                clearBtn.click();
            } else {
                form.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
                    cb.checked = false;
                });
                if (minPriceInput) minPriceInput.value = '';
                if (maxPriceInput) maxPriceInput.value = '';
                const url = new URL(window.location.href);
                url.search = '';
                history.pushState({}, '', url.toString());
                fetchProducts();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', applyFiltersFromUrl);
})();
