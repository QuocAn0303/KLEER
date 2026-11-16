/**
 * KLEER Quiz SPA Engine — 6 câu v4
 * Path: wp-content/themes/kleer-theme/assets/js/quiz.js
 *
 * Layer: Frontend Presentation / REST API Integration
 * Contract: POST /wp-json/kleer/v1/skin-quiz?version=v4
 *           questions q1..q6, option codes 1A..6C (khớp QuizQuestions::getV4Questions()).
 */
(function () {
    'use strict';

    const QUESTIONS = [
        {
            id: 'q1',
            title: 'Sau khi rửa mặt, khoảng 30 phút không bôi gì, da bạn cảm thấy thế nào?',
            options: [
                ['1A', 'Căng, khô, có thể bong tróc'],
                ['1B', 'Thoải mái, không căng cũng không bóng'],
                ['1C', 'Bóng dầu khắp mặt'],
                ['1D', 'Bóng ở trán–mũi–cằm, hai má bình thường hoặc hơi khô'],
                ['1E', 'Châm chích, đỏ hoặc ngứa'],
            ],
        },
        {
            id: 'q2',
            title: 'Cuối ngày, vùng chữ T (trán, mũi, cằm) và hai má của bạn trông thế nào?',
            options: [
                ['2A', 'Cả mặt bóng dầu, lỗ chân lông to rõ'],
                ['2B', 'Chữ T bóng, hai má bình thường hoặc khô'],
                ['2C', 'Cả mặt mịn, không bóng, không khô'],
                ['2D', 'Xỉn, khô ráp, có vảy nhỏ'],
                ['2E', 'Hay ửng đỏ, nóng rát dù không làm gì'],
            ],
        },
        {
            id: 'q3',
            title: 'Khi đổi sản phẩm, đổi thời tiết hoặc thử hoạt chất mới (BHA, vitamin C…), da bạn phản ứng thế nào?',
            options: [
                ['3A', 'Rất hay đỏ, ngứa, rát'],
                ['3B', 'Thỉnh thoảng hơi đỏ hoặc châm chích nhẹ'],
                ['3C', 'Hiếm khi có phản ứng'],
                ['3D', 'Gần như không bao giờ có phản ứng'],
            ],
        },
        {
            id: 'q4',
            title: 'Điều bạn muốn cải thiện nhất ở làn da hiện tại là gì?',
            options: [
                ['4A', 'Mụn, mụn ẩn, mụn viêm'],
                ['4B', 'Thâm mụn, nám, da không đều màu'],
                ['4C', 'Nếp nhăn, da kém đàn hồi'],
                ['4D', 'Lỗ chân lông to, da xỉn, sần sùi'],
                ['4E', 'Không có vấn đề nổi bật, chỉ muốn duy trì da khỏe'],
            ],
        },
        {
            id: 'q5',
            title: 'Bạn muốn routine gồm bao nhiêu sản phẩm?',
            options: [
                ['5A', 'Nhanh gọn (2 sản phẩm)'],
                ['5B', 'Cơ bản (3 sản phẩm)'],
                ['5C', 'Tiêu chuẩn (4 sản phẩm)'],
                ['5D', 'Chi tiết (5 sản phẩm)'],
                ['5E', 'Đầy đủ (6 sản phẩm)'],
            ],
        },
        {
            id: 'q6',
            title: 'Hằng ngày bạn có trang điểm hoặc dùng kem chống nắng không?',
            options: [
                ['6A', 'Có, tôi trang điểm hằng ngày hoặc thường xuyên'],
                ['6B', 'Không trang điểm, nhưng có dùng kem chống nắng'],
                ['6C', 'Không trang điểm và không dùng kem chống nắng'],
            ],
        },
    ];

    const STEP_NAMES = {
        makeup_remover: 'Tẩy trang',
        cleanser: 'Làm sạch',
        toner: 'Toner',
        serum: 'Serum',
        treatment: 'Đặc trị',
        moisturizer: 'Dưỡng ẩm',
        sunscreen: 'Chống nắng',
    };

    const LETTERS = ['A', 'B', 'C', 'D', 'E'];
    const SKIN_SLUG_BY_TYPE = {
        oily: 'da-dau',
        dry: 'da-kho',
        combination: 'da-hon-hop',
        normal: '',
        sensitive: 'da-nhay-cam',
    };

    const escapeHtml = (value) => {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(value === null || value === undefined ? '' : value)
            .replace(/[&<>"']/g, (character) => map[character]);
    };

    const formatMoney = (value) => `${Math.round(Number(value) || 0).toLocaleString('vi-VN')}đ`;

    const LEAF_PATH = 'M20.5 3.5C20.5 3.5 13.5 3.5 8.5 8.5C3.5 13.5 2.5 21.5 2.5 21.5C2.5 21.5 10.5 20.5 15.5 15.5C20.5 10.5 20.5 3.5 20.5 3.5Z';
    const LEAF_STEM = 'M2.5 21.5L11.5 12.5';

    /**
     * Cụm lá trắng bất đối xứng: trái nhỏ (24–58px), phải lớn (92–205px).
     */
    function buildAsymmetricWhiteLeavesHTML() {
        const left = [
            [3, 24, 12, -14, 5.4, 0.2], [6, 34, 26, 8, 6.1, 0.8], [9, 30, 44, -6, 5.8, 1.4],
            [12, 48, 62, 12, 6.6, 0.4], [14, 40, 78, -10, 5.2, 1.1], [16, 58, 34, 6, 6.9, 0.6],
            [5, 26, 88, 18, 5.6, 1.6],
        ];
        const right = [
            [-1, 118, 6, 16, 7.4, 0.3], [3, 176, 30, -8, 8.2, 1.2], [7, 132, 52, 10, 6.8, 0.7],
            [11, 205, 20, -12, 9.1, 0.2], [14, 150, 66, 14, 7.7, 1.5], [15, 92, 82, -6, 6.2, 0.9],
        ];

        const leaf = (size, side, offset, top, rotation, duration, delay) => `
            <svg class="kleer-white-leaf kleer-white-leaf--animated"
                style="width:${size}px;height:${size}px;${side}:${offset}%;top:${top}%;--leaf-rot:${rotation}deg;--leaf-dur:${duration}s;--leaf-delay:${delay}s"
                viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="${LEAF_PATH}" stroke="#ffffff" stroke-width="2" stroke-linejoin="round"/>
                <path d="${LEAF_STEM}" stroke="#ffffff" stroke-width="2" stroke-linecap="round"/>
            </svg>`;

        return [
            ...left.map((item) => leaf(item[1], 'left', item[0], item[2], item[3], item[4], item[5])),
            ...right.map((item) => leaf(item[1], 'right', item[0], item[2], item[3], item[4], item[5])),
        ].join('');
    }

    class KleerQuiz {
        constructor(root) {
            this.root = root;
            this.stage = document.getElementById('quiz-stage');
            this.result = document.getElementById('quiz-result');
            this.progressFill = document.getElementById('quiz-progress-fill');
            this.progressTrack = document.getElementById('quiz-progress-track');
            this.stepCount = document.getElementById('quiz-step-count');
            this.noteEl = document.getElementById('quiz-note');
            this.consent = document.getElementById('quiz-policy-consent');
            this.leaves = document.getElementById('quiz-leaves');

            this.index = 0;
            this.answers = {};
            this.isTransitioning = false;
            this.isSubmitting = false;

            this.cfg = window.kleerAjax || {};
            this.endpoint = this.buildEndpoint();
        }

        init() {
            if (!this.stage) {
                return;
            }

            if (this.leaves) {
                this.leaves.innerHTML = buildAsymmetricWhiteLeavesHTML();
            }

            this.renderQuestion(0);

            if (this.consent) {
                this.consent.addEventListener('change', () => {
                    if (this.consent.checked) {
                        this.setNote('');
                    }
                });
            }
        }

        buildEndpoint() {
            const base = this.cfg.rest_url || '/wp-json/kleer/v1/';

            try {
                const url = new URL(`${String(base).replace(/\/+$/, '')}/skin-quiz`, window.location.origin);
                url.searchParams.set('version', 'v4');
                return url.toString();
            } catch (error) {
                return '/wp-json/kleer/v1/skin-quiz?version=v4';
            }
        }

        sessionId() {
            const key = 'kleer_quiz_session_id';

            try {
                const existing = window.localStorage.getItem(key);
                if (existing) {
                    return existing;
                }
            } catch (error) {
                // localStorage unavailable
            }

            const id = (window.crypto && typeof window.crypto.randomUUID === 'function')
                ? window.crypto.randomUUID()
                : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (character) => {
                    const random = Math.floor(Math.random() * 16);
                    const value = character === 'x' ? random : (random & 0x3) | 0x8;
                    return value.toString(16);
                });

            try {
                window.localStorage.setItem(key, id);
            } catch (error) {
                // ignore
            }

            return id;
        }

        setNote(message) {
            if (this.noteEl) {
                this.noteEl.textContent = message || '';
            }
        }

        updateProgress() {
            const percent = Math.round((this.index / QUESTIONS.length) * 100);

            if (this.progressFill) {
                this.progressFill.style.width = `${percent}%`;
            }

            if (this.progressTrack) {
                this.progressTrack.setAttribute('aria-valuenow', String(percent));
            }

            if (this.stepCount) {
                this.stepCount.textContent = `Câu ${Math.min(this.index + 1, QUESTIONS.length)}/${QUESTIONS.length}`;
            }
        }

        renderQuestion(index) {
            const question = QUESTIONS[index];
            if (!question) {
                return;
            }

            this.index = index;
            this.isTransitioning = false;
            this.updateProgress();

            this.stage.innerHTML = `
                <h3 class="quiz-question-title">${escapeHtml(question.title)}</h3>
                <div class="quiz-options-grid">
                    ${question.options.map(([value, label], optionIndex) => `
                        <button type="button" class="quiz-option-btn" data-value="${escapeHtml(value)}">
                            <span class="quiz-option-btn__badge">${LETTERS[optionIndex]}</span>
                            <span>${escapeHtml(label)}</span>
                        </button>
                    `).join('')}
                </div>
            `;

            this.stage.querySelectorAll('.quiz-option-btn').forEach((button) => {
                button.addEventListener('click', () => {
                    if (this.isTransitioning || this.isSubmitting) {
                        return;
                    }
                    this.selectOption(button, question);
                });
            });
        }

        selectOption(button, question) {
            this.isTransitioning = true;

            this.stage.querySelectorAll('.quiz-option-btn').forEach((item) => {
                item.classList.remove('selected');
            });

            button.classList.add('selected');

            const badge = button.querySelector('.quiz-option-btn__badge');
            if (badge) {
                badge.textContent = '✓';
            }

            this.answers[question.id] = button.getAttribute('data-value');

            window.setTimeout(() => {
                if (this.index < QUESTIONS.length - 1) {
                    this.advance(this.index + 1);
                    return;
                }
                this.finish();
            }, 190);
        }

        advance(nextIndex) {
            this.stage.classList.add('is-fading-out');

            window.setTimeout(() => {
                this.renderQuestion(nextIndex);
                this.stage.classList.add('is-fading-in-prep');

                window.requestAnimationFrame(() => {
                    window.requestAnimationFrame(() => {
                        this.stage.classList.remove('is-fading-out');
                        this.stage.classList.remove('is-fading-in-prep');
                        this.isTransitioning = false;
                    });
                });
            }, 310);
        }

        finish() {
            this.isTransitioning = false;
            this.updateProgress();

            const submitButton = document.createElement('button');
            submitButton.type = 'button';
            submitButton.id = 'quiz-submit-btn';
            submitButton.className = 'quiz-submit-btn';
            submitButton.textContent = 'XEM KẾT QUẢ PHÁC ĐỒ';
            submitButton.addEventListener('click', () => this.submit());
            this.stage.appendChild(submitButton);

            if (this.consent && this.consent.checked) {
                this.submit();
                return;
            }

            this.setNote('Vui lòng đồng ý với Chính sách bảo mật để nhận phác đồ da cá nhân hóa.');
        }

        async submit() {
            if (this.isSubmitting) {
                return;
            }

            if (this.consent && !this.consent.checked) {
                this.setNote('Vui lòng đồng ý với Chính sách bảo mật để nhận phác đồ da cá nhân hóa.');
                this.consent.focus();
                return;
            }

            this.isSubmitting = true;
            this.setNote('Đang phân tích câu trả lời và xây dựng Routine phù hợp...');

            const submitButton = document.getElementById('quiz-submit-btn');
            if (submitButton) {
                submitButton.disabled = true;
            }

            const payload = {
                session_id: this.sessionId(),
                consent: { policy_accepted: true, marketing_opt_in: false },
                context: {
                    locale: 'vi-VN',
                    device: window.innerWidth < 768 ? 'mobile' : (window.innerWidth < 1024 ? 'tablet' : 'desktop'),
                },
                answers: QUESTIONS.map((question) => ({
                    question_id: question.id,
                    answer: this.answers[question.id],
                })),
            };

            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': this.cfg.rest_nonce || '',
                        'X-KLEER-Session': payload.session_id,
                    },
                    body: JSON.stringify(payload),
                });

                const json = await response.json().catch(() => null);

                if (!response.ok) {
                    const message = (json && json.message) || `Máy chủ trả về lỗi HTTP ${response.status}.`;
                    throw new Error(message);
                }

                if (!json || json.status !== 'ok' || !json.data) {
                    throw new Error('Dữ liệu phác đồ trả về không đúng định dạng.');
                }

                this.renderResult(json.data);
            } catch (error) {
                console.error('[KLEER] quiz submission failed:', error);
                this.setNote(`${error.message} Vui lòng thử lại sau ít phút.`);

                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'THỬ LẠI';
                }
            } finally {
                this.isSubmitting = false;
            }
        }

        renderResult(data) {
            const profile = data.skin_profile || {};
            const products = Array.isArray(data.recommended_products) ? data.recommended_products : [];
            const warnings = Array.isArray(data.warnings) ? data.warnings : [];

            if (!this.result) {
                return;
            }

            this.stage.hidden = true;
            this.result.hidden = false;
            this.setNote('');

            if (this.progressFill) {
                this.progressFill.style.width = '100%';
            }

            const itemsHtml = products.length
                ? products.map((product, index) => {
                    const step = STEP_NAMES[product.routine_step] || product.routine_step || 'Chăm sóc da';
                    const meta = [product.inciBadge, product.skinLabel].filter(Boolean).join(' · ');
                    const price = Number(product.price) > 0 ? formatMoney(product.price) : '';
                    return `
                        <li class="quiz-result__item">
                            <span class="quiz-result__step">Bước ${index + 1} · ${escapeHtml(step)}</span>
                            <span class="quiz-result__name">${escapeHtml(product.name)}</span>
                            <span class="quiz-result__price">${escapeHtml(price)}</span>
                            ${meta ? `<span class="quiz-result__meta">${escapeHtml(meta)}</span>` : ''}
                        </li>
                    `;
                }).join('')
                : '<li class="quiz-result__item"><span class="quiz-result__name">Kho sản phẩm đang được cập nhật. Vui lòng quay lại sau ít phút.</span></li>';

            const warningsHtml = warnings.length
                ? `<div class="quiz-result__warn">${warnings.map((warning) => escapeHtml(warning)).join('<br>')}</div>`
                : '';

            this.result.innerHTML = `
                <div class="quiz-result__head">
                    <h3 class="quiz-result__title">Phác đồ dành riêng cho bạn</h3>
                    <span class="quiz-result__label">${escapeHtml(profile.label || 'Hồ sơ làn da')}</span>
                </div>
                <ul class="quiz-result__list">${itemsHtml}</ul>
                ${warningsHtml}
                <div class="quiz-result__actions">
                    <button type="button" class="quiz-submit-btn" id="quiz-add-bundle" ${products.length ? '' : 'disabled'}>
                        THÊM TRỌN BỘ VÀO ROUTINE (1 CHẠM)
                    </button>
                    <a class="btn btn--white" href="${escapeHtml(this.cfg.shop_url || '/shop/')}">XEM TẤT CẢ SẢN PHẨM</a>
                </div>
                <p class="quiz-result__disclaimer">${escapeHtml(data.disclaimer || 'Vui lòng thử trước sản phẩm 24 giờ trên vùng da quai hàm.')}</p>
            `;

            const bundleButton = this.result.querySelector('#quiz-add-bundle');
            if (bundleButton) {
                bundleButton.addEventListener('click', () => {
                    const ids = products
                        .map((product) => parseInt(product.id, 10))
                        .filter((id) => Number.isInteger(id) && id > 0);

                    if (ids.length && window.KleerCart && typeof window.KleerCart.addBundle === 'function') {
                        window.KleerCart.addBundle(ids);
                    }
                });
            }

            this.applySkinMatch(profile);

            window.dispatchEvent(new CustomEvent('kleer:quizCompleted', { detail: data }));
        }

        applySkinMatch(profile) {
            const slug = SKIN_SLUG_BY_TYPE[profile.primary_type] !== undefined
                ? SKIN_SLUG_BY_TYPE[profile.primary_type]
                : '';

            document.querySelectorAll('.chapternav-pill[data-skin-filter]').forEach((pill) => {
                const value = pill.getAttribute('data-skin-filter');
                pill.classList.toggle('active', slug ? value === slug : value === 'all');
            });

            document.querySelectorAll('#shelf-sec .rf-recommcard-33, #bento-catalog-grid .product-card').forEach((card) => {
                if (!slug) {
                    card.classList.remove('product-card--dimmed');
                    return;
                }

                const types = String(card.getAttribute('data-skin-types') || '').split(/\s+/).filter(Boolean);
                const matched = types.length === 0 || types.includes(slug);
                card.classList.toggle('product-card--dimmed', !matched);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('quiz-sanctuary');
        if (!root) {
            return;
        }
        new KleerQuiz(root).init();
    });

    window.KleerQuizLeaves = buildAsymmetricWhiteLeavesHTML;
})();
