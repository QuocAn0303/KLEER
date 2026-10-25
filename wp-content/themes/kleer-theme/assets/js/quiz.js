/**
 * KLEER Quiz SPA Engine
 * Path: wp-content/themes/kleer-theme/assets/js/quiz.js
 *
 * Layer: Frontend Presentation / REST API Integration
 * Responsibility: Điều hướng SPA Quiz 6 câu, lưu state tạm thời,
 *                 gửi payload qua REST API và render Routine cá nhân hóa.
 */
class KleerQuizEngine {
    constructor() {
        this.storageKey = 'kleer_quiz_answers_v4';
        this.sessionKey = 'kleer_quiz_session_id';
        this.totalSteps = 6;
        this.currentStep = 1;
        this.answers = {};
        this.isTransitioning = false;
        this.isSubmitting = false;
        this.lastResult = null;
        this.version = window.kleerQuizConfig?.version || 'v4';
        this.cartEnabled =
            window.kleerQuizConfig?.cartEnabled === true;

        this.questionIds = [
            'q1',
            'q2',
            'q3',
            'q4',
            'q5',
            'q6',
        ];

        this.dom = {
            panels: document.querySelectorAll('.quiz-step-panel'),
            steps: document.querySelectorAll('#quiz-step-indicator .step-item'),
            progressFill: document.getElementById('quiz-progress-fill'),
            progressTrack: document.querySelector('.quiz-progress-track'),
            stepCount: document.getElementById('quiz-step-count'),
            stepPct: document.getElementById('quiz-step-pct'),
            btnPrev: document.getElementById('btn-quiz-prev'),
            btnNext: document.getElementById('btn-quiz-next'),
            form: document.getElementById('kleer-quiz-form'),
            cardHeader: document.getElementById('quiz-card-header'),
            policyConsent: document.getElementById('quiz-policy-consent'),
            marketingConsent: document.getElementById('quiz-marketing-consent'),
        };

        this.init();
    }

    init() {
        if (!this.dom.form) {
            return;
        }

        this.loadStorage();
        this.bindEvents();
        this.renderStep(this.currentStep);
    }

    getOrCreateSessionId() {
        let sessionId = null;

        try {
            sessionId = localStorage.getItem(this.sessionKey);
        } catch (error) {
            console.warn('LocalStorage unavailable:', error);
        }

        const uuidV4Regex = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

        if (sessionId && uuidV4Regex.test(sessionId)) {
            return sessionId;
        }

        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            sessionId = window.crypto.randomUUID();
        } else {
            sessionId = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
                /[xy]/g,
                (character) => {
                    const random = Math.floor(Math.random() * 16);
                    const value = character === 'x'
                        ? random
                        : (random & 0x3) | 0x8;

                    return value.toString(16);
                }
            );
        }

        try {
            localStorage.setItem(this.sessionKey, sessionId);
        } catch (error) {
            console.warn('Cannot persist quiz session ID:', error);
        }

        return sessionId;
    }

    loadStorage() {
        try {
            const raw = localStorage.getItem(this.storageKey);

            if (!raw) {
                return;
            }

            const storedAnswers = JSON.parse(raw);

            if (!storedAnswers || typeof storedAnswers !== 'object') {
                return;
            }

            this.answers = storedAnswers;

            Object.entries(this.answers).forEach(([questionId, value]) => {
                const input = this.dom.form.querySelector(
                    `input[name="${questionId}"][value="${value}"]`
                );

                if (!input) {
                    return;
                }

                input.checked = true;
                input.closest('.quiz-option-button')?.classList.add('is-selected');
            });
        } catch (error) {
            console.warn('Cannot restore quiz answers:', error);
            this.answers = {};
        }
    }

    saveStorage() {
        try {
            localStorage.setItem(
                this.storageKey,
                JSON.stringify(this.answers)
            );
        } catch (error) {
            console.warn('Cannot write quiz answers to LocalStorage:', error);
        }
    }

    bindEvents() {
        const optionButtons = this.dom.form.querySelectorAll(
            '.quiz-option-button'
        );

        optionButtons.forEach((button) => {
            button.addEventListener('click', (event) => {
                if (this.isTransitioning || this.isSubmitting) {
                    event.preventDefault();
                    return;
                }

                const input = button.querySelector('input[type="radio"]');
                const parentPanel = button.closest('.quiz-step-panel');

                if (!input || !parentPanel) {
                    return;
                }

                const panelStep = Number.parseInt(
                    parentPanel.dataset.step,
                    10
                );

                input.checked = true;
                this.answers[input.name] = input.value;
                this.saveStorage();

                parentPanel
                    .querySelectorAll('.quiz-option-button')
                    .forEach((item) => {
                        item.classList.remove('is-selected');
                    });

                button.classList.add('is-selected');

                if (panelStep < this.totalSteps) {
                    this.isTransitioning = true;

                    window.setTimeout(() => {
                        this.currentStep = panelStep + 1;
                        this.renderStep(this.currentStep);
                        this.isTransitioning = false;
                    }, 250);

                    return;
                }

                this.updateSubmitButton();
            });
        });

        this.dom.btnPrev?.addEventListener('click', () => {
            if (
                this.isTransitioning
                || this.isSubmitting
                || this.currentStep <= 1
            ) {
                return;
            }

            this.currentStep -= 1;
            this.renderStep(this.currentStep);
        });

        this.dom.btnNext?.addEventListener('click', () => {
            this.handleSubmission();
        });
    }

    renderStep(step) {
        this.currentStep = step;

        this.dom.panels.forEach((panel) => {
            const panelStep = Number.parseInt(panel.dataset.step, 10);

            panel.classList.toggle(
                'active',
                panelStep === step
            );
        });

        this.dom.steps.forEach((stepElement) => {
            const stepIndex = Number.parseInt(
                stepElement.dataset.step,
                10
            );

            stepElement.classList.toggle(
                'active',
                stepIndex === step
            );

            stepElement.classList.toggle(
                'completed',
                stepIndex < step
            );
        });

        const progress = Math.round(
            ((step - 1) / (this.totalSteps - 1)) * 100
        );

        if (this.dom.progressFill) {
            this.dom.progressFill.style.width = `${progress}%`;
        }

        if (this.dom.progressTrack) {
            this.dom.progressTrack.setAttribute(
                'aria-valuenow',
                String(progress)
            );
        }

        if (this.dom.stepPct) {
            this.dom.stepPct.textContent = `${progress}%`;
        }

        if (this.dom.stepCount) {
            this.dom.stepCount.textContent =
                `Câu ${step}/${this.totalSteps}`;
        }

        if (this.dom.btnPrev) {
            this.dom.btnPrev.disabled = step === 1;
        }

        this.updateSubmitButton();
    }

    updateSubmitButton() {
        if (!this.dom.btnNext) {
            return;
        }

        const currentQuestionId = `q${this.currentStep}`;
        const canSubmit =
            this.currentStep === this.totalSteps
            && Boolean(this.answers[currentQuestionId]);

        this.dom.btnNext.style.display =
            canSubmit ? 'inline-block' : 'none';
    }

    hasAllRequiredAnswers() {
        return this.questionIds.every((questionId) => {
            return typeof this.answers[questionId] === 'string'
                && this.answers[questionId] !== '';
        });
    }

    findFirstMissingStep() {
        const missingIndex = this.questionIds.findIndex(
            (questionId) => !this.answers[questionId]
        );

        return missingIndex === -1
            ? null
            : missingIndex + 1;
    }

    buildApiPayload() {
        const sessionId = this.getOrCreateSessionId();

        return {
            session_id: sessionId,

            consent: {
                policy_accepted: Boolean(
                    this.dom.policyConsent?.checked
                ),
                marketing_opt_in: Boolean(
                    this.dom.marketingConsent?.checked
                ),
            },

            context: {
                locale: 'vi-VN',
                device: this.getDeviceType(),
            },

            answers: this.questionIds.map((questionId) => ({
                question_id: questionId,
                answer: this.answers[questionId],
            })),
        };
    }

    getDeviceType() {
        if (window.innerWidth < 768) {
            return 'mobile';
        }

        if (window.innerWidth < 1024) {
            return 'tablet';
        }

        return 'desktop';
    }

    async handleSubmission() {
        if (this.isSubmitting) {
            return;
        }

        if (!this.hasAllRequiredAnswers()) {
            const missingStep = this.findFirstMissingStep();

            if (missingStep !== null) {
                this.renderStep(missingStep);
            }

            window.alert(
                'Vui lòng trả lời đầy đủ 6 câu hỏi trước khi xem kết quả.'
            );

            return;
        }

        if (
            this.dom.policyConsent
            && !this.dom.policyConsent.checked
        ) {
            window.alert(
                'Vui lòng đồng ý với Chính sách bảo mật của KLEER để nhận phác đồ da cá nhân hóa.'
            );

            this.dom.policyConsent.focus();
            return;
        }

        this.isSubmitting = true;
        this.renderLoadingState();

        const payload = this.buildApiPayload();

        const submitUrl =
            window.kleerQuizConfig?.submitUrl
            || '/wp-json/kleer/v1/skin-quiz';

        // Dùng URL API để xử lý đúng cả pretty permalinks và plain permalinks
        // (plain permalinks có sẵn ?rest_route=... trong submitUrl).
        let endpoint;

        try {
            const url = new URL(submitUrl, window.location.origin);
            url.searchParams.set('version', this.version);
            endpoint = url.toString();
        } catch (error) {
            const separator = submitUrl.includes('?') ? '&' : '?';
            endpoint = `${submitUrl}${separator}version=${encodeURIComponent(this.version)}`;
        }

        try {
            const response = await fetch(endpoint, {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce':
                        window.kleerQuizConfig?.nonce || '',
                    'X-KLEER-Session':
                        payload.session_id,
                },

                body: JSON.stringify(payload),
            });

            const responseData = await response
                .json()
                .catch(() => null);

            if (!response.ok) {
                const message =
                    responseData?.message
                    || `Máy chủ trả về lỗi HTTP ${response.status}.`;

                const httpError = new Error(message);
                httpError.httpStatus = response.status;
                throw httpError;
            }

            if (
                !responseData
                || responseData.status !== 'ok'
                || !responseData.data
            ) {
                throw new Error(
                    'Dữ liệu phác đồ trả về không đúng định dạng.'
                );
            }

            this.lastResult = responseData.data;
            this.renderSuccessResult(responseData.data);
        } catch (error) {
            console.error('Quiz submission error:', error);

            const message = navigator.onLine
                ? error.message
                : 'Kết nối mạng đã bị gián đoạn. Vui lòng kiểm tra Internet và thử lại.';

            const status = Number(error?.httpStatus) || 0;
            const canRetry =
                status === 0
                || status === 408
                || status === 429
                || status >= 500;

            this.renderErrorState(message, canRetry);
        } finally {
            this.isSubmitting = false;
        }
    }

    renderLoadingState() {
        const quizLayout = document.querySelector('.quiz-layout');
        const mainCard = document.querySelector(
            '.quiz-content-card'
        );

        quizLayout?.classList.remove('is-result-view');
        quizLayout?.classList.add('is-loading-view');

        if (!mainCard) {
            return;
        }

        mainCard.innerHTML = `
            <div class="quiz-state-card" aria-live="polite">
                <span class="state-subtitle">
                    ĐANG PHÂN TÍCH
                </span>

                <h2 class="state-title">
                    Đang xây phác đồ dành riêng cho làn da của bạn...
                </h2>

                <div class="loading-line-indicator"></div>

                <small class="state-mock-note">
                    Hệ thống đang phân tích câu trả lời và xây dựng Routine phù hợp.
                </small>
            </div>
        `;
    }

    renderSuccessResult(data) {
        const quizLayout = document.querySelector('.quiz-layout');
        const mainCard = document.querySelector(
            '.quiz-content-card'
        );

        if (!mainCard) {
            return;
        }

        quizLayout?.classList.remove('is-loading-view');
        quizLayout?.classList.add('is-result-view');

        const breadcrumbCurrent = document.querySelector(
            '.quiz-breadcrumb span'
        );

        if (breadcrumbCurrent) {
            breadcrumbCurrent.textContent =
                'Kết quả phác đồ Routine';
        }

        const skinProfile = data.skin_profile || {};
        const profileLabel =
            skinProfile.label || 'Hồ sơ làn da';

        const concerns = Array.isArray(skinProfile.concerns)
            ? skinProfile.concerns
            : [];

        const products = Array.isArray(
            data.recommended_products
        )
            ? data.recommended_products
            : [];

        const warnings = Array.isArray(data.warnings)
            ? data.warnings
            : [];

        const missingSteps = Array.isArray(data.missing_steps)
            ? data.missing_steps
            : [];

        const requestedSteps =
            Number(data.routine_steps_requested) || 0;

        const selectedSteps = Array.isArray(
            data.routine_steps_selected
        )
            ? data.routine_steps_selected
            : [];

        const disclaimer =
            data.disclaimer
            || 'Vui lòng thử trước sản phẩm 24h trên vùng da quai hàm.';

        const canAddToCart =
            this.cartEnabled && products.length > 0;

        const productsHtml = products.length > 0
            ? products
                .map((product, index) => {
                    return this.renderProductCard(
                        product,
                        index
                    );
                })
                .join('')
            : `
                <div class="routine-empty-state">
                    Hiện chưa có sản phẩm phù hợp trong kho dữ liệu
                    cho Routine này.
                </div>
            `;

        const concernsHtml = concerns.length > 0
            ? `
                <ul class="routine-concerns-list">
                    ${concerns
                        .map(
                            (concern) =>
                                `<li>${this.escapeHtml(concern)}</li>`
                        )
                        .join('')}
                </ul>
            `
            : '';

        const warningsHtml = warnings.length > 0
            ? `
                <div
                    class="routine-warning-box"
                    role="status"
                >
                    <strong>Lưu ý về Routine</strong>

                    <ul>
                        ${warnings
                            .map(
                                (warning) =>
                                    `<li>${this.escapeHtml(warning)}</li>`
                            )
                            .join('')}
                    </ul>
                </div>
            `
            : '';

        const missingStepsHtml =
            missingSteps.length > 0
                ? `
                    <p class="routine-missing-note">
                        Một số bước chưa có sản phẩm phù hợp:
                        ${missingSteps
                            .map((step) => {
                                return this.escapeHtml(
                                    this.getStepName(step)
                                );
                            })
                            .join(', ')}.
                    </p>
                `
                : '';

        const routineMetaHtml =
            requestedSteps > 0
                ? `
                    <p class="routine-meta">
                        Bạn chọn Routine
                        <strong>${requestedSteps} sản phẩm</strong>.
                        Hệ thống hiện đề xuất
                        <strong>${products.length} sản phẩm</strong>
                        ${selectedSteps.length > 0
                            ? `trên ${selectedSteps.length} bước được chọn.`
                            : '.'}
                    </p>
                `
                : '';

        mainCard.innerHTML = `
            <h1 class="routine-page-title">
                Phác đồ cá nhân hóa dành cho bạn
            </h1>

            <div class="routine-grid-container">
                <div class="routine-summary-box">
                    <span class="routine-tag-label">
                        HỒ SƠ LÀN DA
                    </span>

                    <h2 class="routine-skin-heading">
                        ${this.escapeHtml(profileLabel)}
                    </h2>

                    ${concernsHtml}

                    ${routineMetaHtml}

                    ${warningsHtml}

                    ${missingStepsHtml}

                    <div class="routine-disclaimer-box">
                        ${this.escapeHtml(disclaimer)}
                    </div>
                </div>

                <div class="routine-steps-column">
                    <div class="routine-products-list">
                        ${productsHtml}
                    </div>
                </div>

                <div class="routine-footer-actions">
                    <button
                        type="button"
                        class="btn-quiz-primary btn-routine-cart"
                        ${canAddToCart ? '' : 'disabled aria-disabled="true"'}
                    >
                        ${canAddToCart
                            ? 'THÊM TRỌN BỘ ROUTINE VÀO GIỎ HÀNG'
                            : 'THÊM ROUTINE VÀO GIỎ HÀNG (SẮP RA MẮT)'}
                    </button>

                    ${canAddToCart
                        ? ''
                        : `
                            <p class="routine-missing-note">
                                Tính năng thêm vào giỏ hàng sẽ có trong bản cập nhật tiếp theo.
                            </p>
                        `}
                </div>
            </div>
        `;

        this.bindResultEvents(
            mainCard,
            data,
            products
        );

        window.dispatchEvent(
            new CustomEvent('kleer:quizCompleted', {
                detail: {
                    submission_id: data.submission_id,
                    skin_profile: skinProfile,
                    recommended_products: products,
                    routine_steps_requested:
                        data.routine_steps_requested,
                    routine_steps_selected:
                        data.routine_steps_selected,
                    missing_steps: missingSteps,
                    warnings,
                },
            })
        );
    }

    renderProductCard(product, index) {
        const productName =
            product?.name || 'Sản phẩm KLEER';

        const stepName = this.getStepName(
            product?.routine_step
        );

        const price = Number(product?.price || 0);

        const priceHtml = price > 0
            ? `
                <span class="routine-product-price">
                    ${price.toLocaleString('vi-VN')} đ
                </span>
            `
            : '';

        const reasonHtml = product?.reason
            ? `
                <p class="routine-step-desc">
                    ${this.escapeHtml(product.reason)}
                </p>
            `
            : '';

        return `
            <article
                class="routine-item-card"
                data-product-id="${this.escapeHtml(product?.id)}"
            >
                <div class="routine-color-indicator"></div>

                <div class="routine-item-details">
                    <span class="routine-step-label">
                        Bước ${index + 1}: ${this.escapeHtml(stepName)}
                    </span>

                    <h3 class="routine-step-title">
                        ${this.escapeHtml(productName)}
                    </h3>

                    ${reasonHtml}
                    ${priceHtml}
                </div>
            </article>
        `;
    }

    bindResultEvents(mainCard, data, products) {
        if (!this.cartEnabled) {
            return;
        }

        const btnCart = mainCard.querySelector(
            '.btn-routine-cart'
        );

        btnCart?.addEventListener('click', () => {
            const productIds = products
                .map((product) => Number(product.id))
                .filter((id) => Number.isInteger(id) && id > 0);

            window.dispatchEvent(
                new CustomEvent('kleer:addRoutineToCart', {
                    detail: {
                        submission_id: data.submission_id,
                        product_ids: productIds,
                        products,
                    },
                })
            );
        });
    }

    renderErrorState(errorMessage, canRetry = true) {
        const mainCard = document.querySelector(
            '.quiz-content-card'
        );

        const quizLayout = document.querySelector(
            '.quiz-layout'
        );

        quizLayout?.classList.remove('is-loading-view');

        if (!mainCard) {
            return;
        }

        const actionHtml = canRetry
            ? `
                <button
                    type="button"
                    id="btn-quiz-retry"
                    class="btn-quiz-primary"
                >
                    THỬ LẠI
                </button>
            `
            : `
                <button
                    type="button"
                    id="btn-quiz-restart"
                    class="btn-quiz-primary"
                >
                    LÀM LẠI QUIZ
                </button>
            `;

        const savedNote = canRetry
            ? 'Câu trả lời của bạn vẫn được lưu trên thiết bị. Vui lòng thử lại.'
            : 'Câu trả lời không hợp lệ hoặc đã cũ. Vui lòng làm lại Quiz để nhận phác đồ mới.';

        mainCard.innerHTML = `
            <div
                class="quiz-state-card"
                role="alert"
            >
                <span class="state-subtitle text-orange">
                    ${canRetry ? 'LỖI KẾT NỐI' : 'DỮ LIỆU KHÔNG HỢP LỆ'}
                </span>

                <h2 class="state-title">
                    Chưa thể tải phác đồ da
                </h2>

                <p class="state-desc">
                    ${this.escapeHtml(
                        errorMessage
                        || 'Dịch vụ phân tích đang tạm thời gián đoạn.'
                    )}
                </p>

                <p class="state-desc">
                    ${savedNote}
                </p>

                ${actionHtml}
            </div>
        `;

        if (canRetry) {
            mainCard
                .querySelector('#btn-quiz-retry')
                ?.addEventListener('click', () => {
                    this.handleSubmission();
                });

            return;
        }

        mainCard
            .querySelector('#btn-quiz-restart')
            ?.addEventListener('click', () => {
                this.resetQuiz();
            });
    }

    resetQuiz() {
        try {
            localStorage.removeItem(this.storageKey);
            localStorage.removeItem(this.sessionKey);
        } catch (error) {
            console.warn('Cannot clear quiz storage:', error);
        }

        window.location.reload();
    }

    getStepName(step) {
        const stepNames = {
            makeup_remover: 'Tẩy trang',
            cleanser: 'Làm sạch',
            toner: 'Toner',
            serum: 'Serum',
            treatment: 'Điều trị',
            moisturizer: 'Dưỡng ẩm',
            sunscreen: 'Chống nắng',
        };

        return stepNames[step] || step || 'Chăm sóc da';
    }

    escapeHtml(value) {
        if (
            value === null
            || value === undefined
        ) {
            return '';
        }

        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        };

        return String(value).replace(
            /[&<>"']/g,
            (character) => map[character]
        );
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new KleerQuizEngine();
});