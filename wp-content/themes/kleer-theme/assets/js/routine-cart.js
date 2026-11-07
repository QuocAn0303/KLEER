/**
 * KLEER Routine Cart JS
 * Path: wp-content/themes/kleer-theme/assets/js/routine-cart.js
 */

(function ($) {
    'use strict';

    function addRoutineToCart(productIds) {
        if (!Array.isArray(productIds)) {
            return;
        }

        const ids = productIds
            .map(function (id) {
                return parseInt(id, 10);
            })
            .filter(function (id) {
                return !isNaN(id) && id > 0;
            });

        if (ids.length === 0) {
            return;
        }

        const ajaxUrl = (window.kleerAjax && window.kleerAjax.ajax_url)
            ? window.kleerAjax.ajax_url
            : ((window.ajaxurl) ? window.ajaxurl : '/wp-admin/admin-ajax.php');

        const nonce = (window.kleerAjax && window.kleerAjax.nonce)
            ? window.kleerAjax.nonce
            : '';

        const formData = new FormData();
        formData.append('action', 'kleer_add_routine_to_cart');
        formData.append('product_ids', JSON.stringify(ids));
        if (nonce) {
            formData.append('nonce', nonce);
        }

        return fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': nonce
            },
            body: formData
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Request failed');
                }
                return response.json();
            })
            .then(function (data) {
                if (window.jQuery && typeof jQuery(document.body).trigger === 'function') {
                    if (data && data.data && data.data.fragments) {
                        jQuery(document.body).trigger('wc_fragment_refresh');
                        try {
                            jQuery.each(data.data.fragments, function (key, value) {
                                jQuery(key).replaceWith(value);
                            });
                        } catch (e) {
                            // ignore
                        }
                    }
                    jQuery(document.body).trigger('added_to_cart', [data && data.data && data.data.fragments ? data.data.fragments : {}, data && data.data && data.data.cart_hash ? data.data.cart_hash : '']);
                }

                // Open mini-cart if available
                if (window.jQuery) {
                    jQuery(document.body).trigger('wc_handle_cart_fragments_refreshed');
                }

                if (typeof data === 'object' && data.success && data.data && data.data.fragments) {
                    try {
                        if (window.jQuery) {
                            jQuery.each(data.data.fragments, function (key, value) {
                                jQuery(key).replaceWith(value);
                            });
                            jQuery(document.body).trigger('wc_fragments_loaded');
                        }
                    } catch (e) {
                        // ignore
                    }
                }

                return data;
            })
            .catch(function (err) {
                console.error('Error adding routine to cart:', err);
                throw err;
            });
    }

    // Listen for custom event from quiz
    if (window && window.addEventListener) {
        window.addEventListener('kleer:addRoutineToCart', function (event) {
            var detail = event.detail || {};
            var productIds = detail.product_ids || [];
            addRoutineToCart(productIds).then(function () {
                // Optionally redirect to cart page
                // window.location.href = '/gio-hang/' or use cart URL
            });
        });
    }

    // Expose globally
    window.kleerAddRoutineToCart = addRoutineToCart;

})(jQuery);
