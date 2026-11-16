(() => {
    'use strict';
    document.addEventListener('click', (event) => {
        const thumbnail = event.target.closest('[data-product-image]');
        const image = document.getElementById('mainProductImg');
        if (thumbnail && image) image.src = thumbnail.dataset.productImage;
    });
})();
