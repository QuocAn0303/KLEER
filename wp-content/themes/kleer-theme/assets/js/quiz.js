/**
 * KLEER Quiz SPA Engine
 * Path: wp-content/themes/kleer-theme/assets/js/quiz.js
 */
class KleerQuizEngine {
    constructor() {
        this.storageKey = 'kleer_quiz_answers_v1';
        this.totalSteps = 5;
        this.currentStep = 1;
        this.answers = {};
        this.isTransitioning = false; // Cờ khóa chống click đúp

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
            btnRetry: document.getElementById('btn-quiz-retry')
        };

        this.init();
    }

    init() {
        if (!this.dom.form) return;
        this.loadStorage();
        this.bindEvents();
        this.renderStep(this.currentStep);
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
        // Bắt sự kiện chọn đáp án chuẩn (chống bubble click kép)
        const optionButtons = this.dom.form.querySelectorAll('.quiz-option-button');
        
        optionButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Chặn click nếu đang trong hiệu ứng chuyển bước
                if (this.isTransitioning) {
                    e.preventDefault();
                    return;
                }

                const input = btn.querySelector('input[type="radio"]');
                if (!input) return;

                // Xác định chính xác panel câu hỏi cha
                const parentPanel = btn.closest('.quiz-step-panel');
                const panelStep = parseInt(parentPanel.dataset.step, 10);

                // Check radio và lưu câu trả lời
                input.checked = true;
                this.answers[input.name] = input.value;
                this.saveStorage();

                // Cập nhật viền cam cho lựa chọn trong câu hiện tại
                parentPanel.querySelectorAll('.quiz-option-button').forEach(item => item.classList.remove('is-selected'));
                btn.classList.add('is-selected');

                // Xử lý chuyển câu chính xác
                if (panelStep < this.totalSteps) {
                    this.isTransitioning = true; // Khóa click
                    setTimeout(() => {
                        this.currentStep = panelStep + 1; // Nhảy đúng sang câu kế tiếp (1 -> 2 -> 3)
                        this.renderStep(this.currentStep);
                        this.isTransitioning = false; // Mở khóa click
                    }, 250);
                } else {
                    // Đang ở câu 5: hiển thị nút xem kết quả
                    if (this.dom.btnNext) {
                        this.dom.btnNext.style.display = 'inline-block';
                    }
                }
            });
        });

        // Nút quay lại
        this.dom.btnPrev?.addEventListener('click', () => {
            if (this.isTransitioning) return;
            if (this.currentStep > 1) {
                this.currentStep--;
                this.renderStep(this.currentStep);
            }
        });

        // Nút xem kết quả
        this.dom.btnNext?.addEventListener('click', () => {
            this.triggerAnalysis();
        });

        // Nút thử lại khi gặp lỗi
        this.dom.btnRetry?.addEventListener('click', () => {
            if (this.dom.errorState) this.dom.errorState.hidden = true;
            this.triggerAnalysis();
        });
    }

    renderStep(step) {
        // Ẩn/hiện đúng panel câu hỏi theo step
        this.dom.panels.forEach(p => {
            p.classList.toggle('active', parseInt(p.dataset.step, 10) === step);
        });

        // Cập nhật tiến trình Sidebar (Chỉ tô màu cam, không có dấu tick)
        this.dom.steps.forEach(s => {
            const stepIndex = parseInt(s.dataset.step, 10);
            s.classList.toggle('active', stepIndex === step);
            s.classList.toggle('completed', stepIndex < step);
        });

        // Cập nhật thanh tiến trình %
        const pct = (step - 1) * 20;
        if (this.dom.progressFill) this.dom.progressFill.style.width = `${pct}%`;
        if (this.dom.stepPct) this.dom.stepPct.textContent = `${pct}%`;
        if (this.dom.stepCount) this.dom.stepCount.textContent = `Câu ${step}/5`;

        // Trạng thái các nút
        if (this.dom.btnPrev) this.dom.btnPrev.disabled = step === 1;
        if (this.dom.btnNext) {
            this.dom.btnNext.style.display = (step === this.totalSteps && this.answers['routine_length']) ? 'inline-block' : 'none';
        }
    }

    triggerAnalysis() {
        // 1. Ẩn form và header tiến trình
        if (this.dom.form) this.dom.form.style.display = 'none';
        if (this.dom.cardHeader) this.dom.cardHeader.style.display = 'none';

        const quizLayout = document.querySelector('.quiz-layout');
        const mainCard = document.querySelector('.quiz-content-card');

        // 2. Chuyển sang giao diện Loading chuẩn Frame 07 Figma: Card màu be ở giữa
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
                    <small class="state-mock-note">(Demo) Mô phỏng gọi API phân tích 2.5 giây</small>
                </div>
            `;
        }

        // 3. Sau 2.5 giây: Chuyển sang Màn hình 08 Routine (Layout 2 cột mở rộng)
        setTimeout(() => {
            const isMockError = window.location.search.includes('error=1');

            if (isMockError) {
                if (mainCard) {
                    mainCard.innerHTML = `
                        <div class="quiz-state-card">
                            <span class="state-subtitle text-orange">LỖI KẾT NỐI</span>
                            <h2 class="state-title">Dịch vụ phân tích đang bận</h2>
                            <p class="state-desc">Câu trả lời của bạn đã được tạm lưu. Vui lòng bấm "Thử lại".</p>
                            <button type="button" class="btn-quiz-primary" onclick="location.reload();" style="margin-top: 20px;">THỬ LẠI</button>
                        </div>
                    `;
                }
            } else {
                // Chuyển layout sang chế độ hiển thị kết quả (bỏ is-loading-view, thêm is-result-view)
                if (quizLayout) {
                    quizLayout.classList.remove('is-loading-view');
                    quizLayout.classList.add('is-result-view');
                }

                // Cập nhật Breadcrumb
                const breadcrumbCurrent = document.querySelector('.quiz-breadcrumb span');
                if (breadcrumbCurrent) {
                    breadcrumbCurrent.textContent = 'Kết quả chẩn đoán';
                }

                // Render giao diện Màn hình 08 Routine chuẩn Figma
                if (mainCard) {
                    mainCard.innerHTML = `
                        <h1 class="routine-page-title">Phác đồ dành cho bạn</h1>
                        <div class="routine-grid-container">
                            <!-- Cột trái: Tóm tắt kết quả -->
                            <div class="routine-summary-box">
                                <span class="routine-tag-label">KẾT QUẢ CHẨN ĐOÁN</span>
                                <h2 class="routine-skin-heading">Da dầu mụn, thiếu ẩm</h2>
                                <p class="routine-skin-description">
                                    Vùng chữ T tiết nhiều dầu trong khi hàng rào ẩm còn yếu, dễ bít tắc và nổi mụn.
                                </p>
                                <span class="routine-discount-badge">Ưu đãi Combo Routine được áp dụng tự động</span>
                            </div>

                            <!-- Cột phải: 3 Bước sản phẩm gợi ý -->
                            <div class="routine-steps-column">
                                <div class="routine-item-card">
                                    <div class="routine-color-indicator"></div>
                                    <div class="routine-product-thumbnail"></div>
                                    <div class="routine-item-details">
                                        <span class="routine-step-label">BƯỚC 01</span>
                                        <h3 class="routine-step-title">Làm sạch dịu nhẹ</h3>
                                        <p class="routine-step-desc">Tên sản phẩm gợi ý</p>
                                    </div>
                                </div>

                                <div class="routine-item-card">
                                    <div class="routine-color-indicator"></div>
                                    <div class="routine-product-thumbnail"></div>
                                    <div class="routine-item-details">
                                        <span class="routine-step-label">BƯỚC 02</span>
                                        <h3 class="routine-step-title">Tinh chất điều trị</h3>
                                        <p class="routine-step-desc">Tên sản phẩm gợi ý</p>
                                    </div>
                                </div>

                                <div class="routine-item-card">
                                    <div class="routine-color-indicator"></div>
                                    <div class="routine-product-thumbnail"></div>
                                    <div class="routine-item-details">
                                        <span class="routine-step-label">BƯỚC 03</span>
                                        <h3 class="routine-step-title">Dưỡng khóa ẩm / Chống nắng</h3>
                                        <p class="routine-step-desc">Tên sản phẩm gợi ý</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Nút CTA -->
                            <div class="routine-footer-actions">
                                <button type="button" class="btn-routine-cart">THÊM TRỌN BỘ ROUTINE VÀO GIỎ HÀNG</button>
                                <button type="button" class="btn-routine-save">LƯU ROUTINE VÀO HỒ SƠ CÁ NHÂN</button>
                            </div>
                        </div>
                    `;
                }

                // Phát event cho các module khác
                window.dispatchEvent(new CustomEvent('kleer:quizCompleted', { detail: { answers: this.answers } }));
            }
        }, 2500);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new KleerQuizEngine();
});