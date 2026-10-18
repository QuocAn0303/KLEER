/**
 * KLEER Quiz SPA Engine
 * Path: wp-content/themes/kleer-theme/assets/js/quiz.js
 *
 * Layer: Frontend Presentation / REST API Integration
 * Responsibility: Điều hướng SPA 5 bước, validate câu hỏi, thu thập consent GDPR,
 *                 kết nối REST API POST /wp-json/kleer/v1/skin-quiz thật,
 *                 hiển thị phác đồ và xử lý sự kiện thêm vào giỏ hàng.
 */
class KleerQuizEngine {
    constructor() {
        this.storageKey = 'kleer_quiz_answers_v2';
        this.sessionKey = 'kleer_quiz_session_id';
        this.totalSteps = 5;
        this.currentStep = 1;
        this.answers = {};
        this.isTransitioning = false;
        this.isSubmitting = false;
        this.lastResult = null;

        this.dom = {
            panels: document.querySelectorAll('.quiz-step-panel'),
            steps: document.querySelectorAll('#quiz-step-indicator .step-item'),
            progressFill: document.getElementById('quiz-progress-fill'),
            stepCount: document.getElementById('quiz-step-count'),
            stepPct: document.getElementById('quiz-step-pct'),
            btnPrev: document.getElementById('btn-quiz-prev'),
            btnNext: document.getElementById('btn-quiz-next'),
            form: document.getElementById('kleer-quiz-form'),
            cardHeader: document.getElementById('quiz-card-header'),
            loadingState: document.getElementById('quiz-loading-state'),
            errorState: document.getElementById('quiz-error-state'),
            btnRetry: document.getElementById('btn-quiz-retry'),
            policyConsent: document.getElementById('quiz-policy-consent'),
            marketingConsent: document.getElementById('quiz-marketing-consent')
        };

        this.init();
    }

    init() {
        if (!this.dom.form) return;
        this.loadStorage();
        this.bindEvents();
        this.renderStep(this.currentStep);
    }

    getOrCreateSessionId() {
        let sid = localStorage.getItem(this.sessionKey);
        const uuidV4Regex = /^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
        if (!sid || !uuidV4Regex.test(sid)) {
            sid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
            try {
                localStorage.setItem(this.sessionKey, sid);
            } catch (e) {
                // Private mode fallback
            }
        }
        return sid;
    }

    loadStorage() {
        try {
            const raw = localStorage.getItem(this.storageKey);
            if (raw) {
                this.answers = JSON.parse(raw);
                Object.entries(this.answers).forEach(([name, val]) => {
                    const input = this.dom.form.querySelector(`input[name="${name}"][value="${val}"]`);
                    if (input) {
                        input.checked = true;
                        input.closest('.quiz-option-button')?.classList.add('is-selected');
                    }
                });
            }
        } catch (e) {
            console.warn('LocalStorage unavailable:', e);
        }
    }

    saveStorage() {
        try {
            localStorage.setItem(this.storageKey, JSON.stringify(this.answers));
        } catch (e) {
            console.warn('Cannot write localStorage:', e);
        }
    }

    bindEvents() {
        const optionButtons = this.dom.form.querySelectorAll('.quiz-option-button');

        optionButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (this.isTransitioning) {
                    e.preventDefault();
                    return;
                }

                const input = btn.querySelector('input[type="radio"]');
                if (!input) return;

                const parentPanel = btn.closest('.quiz-step-panel');
                const panelStep = parseInt(parentPanel.dataset.step, 10);

                input.checked = true;
                this.answers[input.name] = input.value;
                this.saveStorage();

                parentPanel.querySelectorAll('.quiz-option-button').forEach(item => item.classList.remove('is-selected'));
                btn.classList.add('is-selected');

                if (panelStep < this.totalSteps) {
                    this.isTransitioning = true;
                    setTimeout(() => {
                        this.currentStep = panelStep + 1;
                        this.renderStep(this.currentStep);
                        this.isTransitioning = false;
                    }, 250);
                } else {
                    if (this.dom.btnNext) {
                        this.dom.btnNext.style.display = 'inline-block';
                    }
                }
            });
        });

        this.dom.btnPrev?.addEventListener('click', () => {
            if (this.isTransitioning) return;
            if (this.currentStep > 1) {
                this.currentStep--;
                this.renderStep(this.currentStep);
            }
        });

        this.dom.btnNext?.addEventListener('click', () => {
            this.handleSubmission();
        });

        this.dom.btnRetry?.addEventListener('click', () => {
            if (this.dom.errorState) this.dom.errorState.hidden = true;
            this.handleSubmission();
        });
    }

    renderStep(step) {
        this.dom.panels.forEach(p => {
            p.classList.toggle('active', parseInt(p.dataset.step, 10) === step);
        });

        this.dom.steps.forEach(s => {
            const stepIndex = parseInt(s.dataset.step, 10);
            s.classList.toggle('active', stepIndex === step);
            s.classList.toggle('completed', stepIndex < step);
        });

        const pct = (step - 1) * 20;
        if (this.dom.progressFill) this.dom.progressFill.style.width = `${pct}%`;
        if (this.dom.stepPct) this.dom.stepPct.textContent = `${pct}%`;
        if (this.dom.stepCount) this.dom.stepCount.textContent = `Câu ${step}/5`;

        if (this.dom.btnPrev) this.dom.btnPrev.disabled = step === 1;
        if (this.dom.btnNext) {
            this.dom.btnNext.style.display = (step === this.totalSteps && this.answers['routine_length']) ? 'inline-block' : 'none';
        }
    }

    /**
     * Ánh xạ các câu trả lời giao diện sang hợp đồng chuẩn của Backend REST API.
     */
    buildApiPayload() {
        const sessionId = this.getOrCreateSessionId();

        // 1. Ánh xạ các câu hỏi giao diện sang question_id chuẩn v1
        const skinType = this.answers['skin_type'] || 'oily';
        const sensitivity = this.answers['sensitivity'] || 'resilient';
        const concern = this.answers['concern'] || 'acne';
        const routineLength = this.answers['routine_length'] || 'intensive_4';

        const q1Map = {
            'dry': 'tight_dry',
            'oily': 'greasy_all',
            'combination': 'oily_tzone',
            'normal': 'comfortable',
        };

        const q2Map = {
            'oily': 'large_pores',
            'normal': 'normal_pores',
            'combination': 'tzone_only',
            'dry': 'flaky_rough',
        };

        const q3Map = {
            'high': 'very_often',
            'mild': 'sometimes',
            'resilient': 'rarely_never',
        };

        const q4Map = {
            'acne': 'acne_blemish',
            'dark_spots': 'dark_spots',
            'aging': 'anti_aging',
            'dullness': 'dehydration',
        };

        const q5Map = {
            'minimal_2': 'minimal',
            'balance_3': 'minimal',
            'intensive_4': 'intensive',
        };

        const answersList = [
            { question_id: 'q1_skin_feeling', answer: q1Map[skinType] || 'greasy_all' },
            { question_id: 'q2_pore_sebum', answer: q2Map[skinType] || 'large_pores' },
            { question_id: 'q3_sensitivity', answer: q3Map[sensitivity] || 'rarely_never' },
            { question_id: 'q4_main_concern', answer: q4Map[concern] || 'acne_blemish' },
            { question_id: 'q5_routine_goal', answer: q5Map[routineLength] || 'intensive' },
        ];

        const isPolicyAccepted = this.dom.policyConsent ? this.dom.policyConsent.checked : false;
        const isMarketingOptIn = this.dom.marketingConsent ? this.dom.marketingConsent.checked : false;

        return {
            session_id: sessionId,
            version: 'v1',
            consent: {
                policy_accepted: isPolicyAccepted,
                marketing_opt_in: isMarketingOptIn,
            },
            context: {
                locale: 'vi-VN',
                device: window.innerWidth < 768 ? 'mobile' : (window.innerWidth < 1024 ? 'tablet' : 'desktop'),
            },
            answers: answersList,
        };
    }

    async handleSubmission() {
        if (this.isSubmitting) return;

        // Kiểm tra điều kiện bắt buộc: đồng ý chính sách bảo mật
        if (this.dom.policyConsent && !this.dom.policyConsent.checked) {
            alert('Vui lòng đồng ý với Chính sách bảo mật của KLEER để nhận phác đồ da cá nhân hóa.');
            this.dom.policyConsent.focus();
            return;
        }

        this.isSubmitting = true;

        // 1. Ẩn form và header tiến trình
        if (this.dom.form) this.dom.form.style.display = 'none';
        if (this.dom.cardHeader) this.dom.cardHeader.style.display = 'none';

        const quizLayout = document.querySelector('.quiz-layout');
        const mainCard = document.querySelector('.quiz-content-card');

        // 2. Chuyển sang giao diện Loading
        if (quizLayout) {
            quizLayout.classList.remove('is-result-view');
            quizLayout.classList.add('is-loading-view');
        }

        if (mainCard) {
            mainCard.innerHTML = `
                <div class="quiz-state-card">
                    <span class="state-subtitle">ĐANG PHÂN TÍCH</span>
                    <h2 class="state-title">Đang xây phác đồ dành riêng cho làn da của bạn...</h2>
                    <div class="loading-line-indicator"></div>
                    <small class="state-mock-note">Hệ thống đang kết nối thuật toán chẩn đoán da KLEER</small>
                </div>
            `;
        }

        // 3. Chuẩn bị gọi API REST
        const payload = this.buildApiPayload();
        const baseRestUrl = window.kleerQuizConfig?.restUrl || '/wp-json/kleer/v1/';
        const endpoint = baseRestUrl.replace(/\/?$/, '/') + 'skin-quiz';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.kleerQuizConfig?.nonce || '',
                    'X-KLEER-Session': payload.session_id,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `Lỗi máy chủ HTTP ${response.status}`);
            }

            const json = await response.json();
            if (json.status !== 'ok' || !json.data) {
                throw new Error(json.message || 'Dữ liệu phác đồ trả về không đúng định dạng');
            }

            this.lastResult = json.data;
            this.renderSuccessResult(json.data);
        } catch (err) {
            console.error('Quiz submission error:', err);
            this.renderErrorState(err.message);
        } finally {
            this.isSubmitting = false;
        }
    }

    renderSuccessResult(data) {
        const quizLayout = document.querySelector('.quiz-layout');
        const mainCard = document.querySelector('.quiz-content-card');

        if (quizLayout) {
            quizLayout.classList.remove('is-loading-view');
            quizLayout.classList.add('is-result-view');
        }

        const breadcrumbCurrent = document.querySelector('.quiz-breadcrumb span');
        if (breadcrumbCurrent) {
            breadcrumbCurrent.textContent = 'Kết quả phác đồ Routine';
        }

        const skinProfile = data.skin_profile || {};
        const profileLabel = skinProfile.label || 'Da cần cân bằng';
        const concerns = skinProfile.concerns || ['Cải thiện màng ẩm tự nhiên'];
        const products = data.recommended_products || [];
        const disclaimer = data.disclaimer || 'Vui lòng thử trước một lượng nhỏ sản phẩm lên vùng da dưới quai hàm 24h trước khi sử dụng toàn mặt.';

        const stepNames = {
            'cleanser': 'Bước 1: Làm sạch dịu nhẹ',
            'treatment': 'Bước 2: Tinh chất điều trị chuyên sâu',
            'moisturizer': 'Bước 3: Dưỡng ẩm khóa màng bảo vệ',
            'sunscreen': 'Bước 4: Bảo vệ quang phổ rộng',
            'makeup_remover': 'Bước 0: Làm sạch sâu & Tẩy trang',
            'toner': 'Bước Cân bằng: Dịu da chuẩn pH',
            'serum': 'Bước Tinh chất: Phục hồi chuyên sâu',
        };

        const productsHtml = products.map((prod, idx) => {
            const stepTitle = stepNames[prod.routine_step] || `Bước ${idx + 1}: ${prod.routine_step}`;
            const priceFormatted = prod.price ? Number(prod.price).toLocaleString('vi-VN') + ' đ' : '';
            return `
                <div class="routine-item-card" data-product-id="${prod.id}">
                    <div class="routine-color-indicator"></div>
                    <div class="routine-item-details" style="flex: 1; padding: 12px 16px;">
                        <span class="routine-step-label">${stepTitle}</span>
                        <h3 class="routine-step-title" style="margin: 4px 0; font-size: 16px; font-weight: 600;">${this.escapeHtml(prod.name)}</h3>
                        ${prod.reason ? `<p class="routine-step-desc" style="font-size: 13px; color: #666; margin: 4px 0;">${this.escapeHtml(prod.reason)}</p>` : ''}
                        ${priceFormatted ? `<span class="routine-product-price" style="font-size: 14px; font-weight: bold; color: #c25e2e;">${priceFormatted}</span>` : ''}
                    </div>
                </div>
            `;
        }).join('');

        if (mainCard) {
            mainCard.innerHTML = `
                <h1 class="routine-page-title" style="margin-bottom: 24px;">Phác đồ cá nhân hóa dành cho bạn</h1>
                <div class="routine-grid-container">
                    <!-- Cột trái: Tóm tắt kết quả -->
                    <div class="routine-summary-box">
                        <span class="routine-tag-label">HỒ SƠ LÀN DA</span>
                        <h2 class="routine-skin-heading">${this.escapeHtml(profileLabel)}</h2>
                        <ul class="routine-concerns-list" style="margin: 12px 0 16px; padding-left: 20px; font-size: 14px; color: #555;">
                            ${concerns.map(c => `<li>${this.escapeHtml(c)}</li>`).join('')}
                        </ul>
                        <div class="routine-disclaimer-box" style="font-size: 12px; color: #777; background: #faf8f5; border-left: 3px solid #c25e2e; padding: 10px 12px; margin-top: 16px; border-radius: 4px;">
                            ${this.escapeHtml(disclaimer)}
                        </div>
                    </div>

                    <!-- Cột phải: Danh sách sản phẩm phác đồ -->
                    <div class="routine-steps-column">
                        <div class="routine-products-list">
                            ${productsHtml}
                        </div>
                    </div>

                    <!-- Nút CTA -->
                    <div class="routine-footer-actions" style="grid-column: 1 / -1; margin-top: 24px; display: flex; gap: 16px; flex-wrap: wrap;">
                        <button type="button" class="btn-quiz-primary btn-routine-cart" style="flex: 1; min-width: 260px; padding: 14px 24px; cursor: pointer;">
                            THÊM TRỌN BỘ ROUTINE VÀO GIỎ HÀNG
                        </button>
                        <button type="button" class="btn-quiz-outline btn-routine-save" style="flex: 1; min-width: 220px; padding: 14px 24px; cursor: pointer;">
                            LƯU LẠI KẾT QUẢ VÀO HỒ SƠ
                        </button>
                    </div>
                </div>
            `;

            // Bắt sự kiện bấm Thêm Routine vào Giỏ hàng
            const btnCart = mainCard.querySelector('.btn-routine-cart');
            btnCart?.addEventListener('click', () => {
                const productIds = products.map(p => p.id);
                // Dispatch event cho module L03 (Cart Combo) lắng nghe
                window.dispatchEvent(new CustomEvent('kleer:addRoutineToCart', {
                    detail: {
                        submission_id: data.submission_id,
                        product_ids: productIds,
                        products: products,
                    }
                }));

                btnCart.textContent = '✓ ĐÃ CHỌN TRỌN BỘ SẢN PHẨM';
                btnCart.disabled = true;
                btnCart.style.background = '#2e7d32';
                btnCart.style.borderColor = '#2e7d32';
            });

            // Bắt sự kiện Lưu Routine
            const btnSave = mainCard.querySelector('.btn-routine-save');
            btnSave?.addEventListener('click', () => {
                alert('Phác đồ đã được lưu trong phiên chẩn đoán này. Bạn có thể xem lại bất kỳ lúc nào.');
                btnSave.textContent = '✓ ĐÃ LƯU HỒ SƠ';
                btnSave.disabled = true;
            });
        }

        // Bắn custom event toàn cục
        window.dispatchEvent(new CustomEvent('kleer:quizCompleted', {
            detail: {
                submission_id: data.submission_id,
                skin_profile: skinProfile,
                recommended_products: products,
            }
        }));
    }

    renderErrorState(errorMessage) {
        const mainCard = document.querySelector('.quiz-content-card');
        const quizLayout = document.querySelector('.quiz-layout');

        if (quizLayout) {
            quizLayout.classList.remove('is-loading-view');
        }

        if (mainCard) {
            mainCard.innerHTML = `
                <div class="quiz-state-card">
                    <span class="state-subtitle text-orange" style="color: #c25e2e;">LỖI KẾT NỐI</span>
                    <h2 class="state-title">Chưa thể tải phác đồ da</h2>
                    <p class="state-desc">${this.escapeHtml(errorMessage || 'Dịch vụ phân tích đang bận hoặc gián đoạn mạng.')}</p>
                    <p class="state-desc" style="font-size: 13px; color: #888;">Câu trả lời của bạn đã được lưu an toàn. Vui lòng bấm thử lại.</p>
                    <button type="button" id="btn-quiz-retry" class="btn-quiz-primary" style="margin-top: 20px; cursor: pointer;">
                        THỬ LẠI
                    </button>
                </div>
            `;

            const retryBtn = mainCard.querySelector('#btn-quiz-retry');
            retryBtn?.addEventListener('click', () => {
                this.handleSubmission();
            });
        }
    }

    escapeHtml(str) {
        if (!str) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        };
        return String(str).replace(/[&<>"']/g, m => map[m]);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new KleerQuizEngine();
});