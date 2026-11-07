<?php
/**
 * Template Name: Quiz Chẩn Đoán Da
 * Description: Template SPA Quiz chẩn đoán da cá nhân hóa chuẩn giao diện Unisex Minimalist Earthy Tone
 * Project: KLEER - Unisex Skincare Store
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main kleer-quiz-page">
    <section
        id="kleer-quiz-app"
        class="kleer-quiz-container"
        aria-label="Chẩn đoán da cá nhân hóa"
    >
        <nav class="quiz-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a>
            &rsaquo;
            <span>Chẩn đoán da</span>
        </nav>

        <div class="quiz-layout">
            <aside class="quiz-sidebar">
                <h1 class="quiz-main-title">Chẩn đoán da</h1>
                <p class="quiz-sidebar-heading">TIẾN TRÌNH</p>

                <ol class="quiz-step-indicator" id="quiz-step-indicator">
                    <li class="step-item active" data-step="1">
                        <span class="step-badge">1</span>
                        <span class="step-label">Cảm giác sau khi rửa mặt</span>
                    </li>

                    <li class="step-item" data-step="2">
                        <span class="step-badge">2</span>
                        <span class="step-label">Tình trạng cuối ngày</span>
                    </li>

                    <li class="step-item" data-step="3">
                        <span class="step-badge">3</span>
                        <span class="step-label">Độ nhạy cảm</span>
                    </li>

                    <li class="step-item" data-step="4">
                        <span class="step-badge">4</span>
                        <span class="step-label">Vấn đề da ưu tiên</span>
                    </li>

                    <li class="step-item" data-step="5">
                        <span class="step-badge">5</span>
                        <span class="step-label">Số sản phẩm Routine</span>
                    </li>

                    <li class="step-item" data-step="6">
                        <span class="step-badge">6</span>
                        <span class="step-label">Trang điểm & chống nắng</span>
                    </li>
                </ol>

                <footer class="quiz-sidebar-note">
                    <small>
                        Không cần đăng nhập. Câu trả lời của khách vãng lai được lưu ẩn danh.
                    </small>
                </footer>
            </aside>

            <section class="quiz-content-card">
                <header class="card-header" id="quiz-card-header">
                    <div class="header-top">
                        <span class="quiz-step-count" id="quiz-step-count">Câu 1/6</span>
                        <span class="quiz-step-pct" id="quiz-step-pct">0%</span>
                    </div>

                    <div
                        class="quiz-progress-track"
                        role="progressbar"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuenow="0"
                    >
                        <div
                            class="quiz-progress-bar"
                            id="quiz-progress-fill"
                            style="width: 0%;"
                        ></div>
                    </div>
                </header>

                <form id="kleer-quiz-form" class="quiz-form" novalidate>
                    <!-- Q1 -->
                    <fieldset class="quiz-step-panel active" data-step="1">
                        <legend class="quiz-question-title">
                            Sau khi rửa mặt, khoảng 30 phút không bôi gì, da bạn cảm thấy thế nào?
                        </legend>

                        <p class="quiz-question-hint">
                            Chọn một đáp án gần đúng nhất.
                        </p>

                        <div class="quiz-media-wrapper">
                            <img
                                src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/question1.jpg'); ?>"
                                alt="Cảm giác của da sau khi rửa mặt"
                            />
                        </div>

                        <div class="quiz-options-grid cols-2">
                            <label class="quiz-option-button">
                                <input type="radio" name="q1" value="1A" />
                                <span>Căng, khô, có thể bong tróc</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q1" value="1B" />
                                <span>Thoải mái, không căng cũng không bóng</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q1" value="1C" />
                                <span>Bóng dầu khắp mặt</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q1" value="1D" />
                                <span>Bóng ở trán–mũi–cằm, hai má bình thường hoặc hơi khô</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q1" value="1E" />
                                <span>Châm chích, đỏ hoặc ngứa</span>
                            </label>
                        </div>
                    </fieldset>

                    <!-- Q2 -->
                    <fieldset class="quiz-step-panel" data-step="2">
                        <legend class="quiz-question-title">
                            Cuối ngày, vùng chữ T (trán, mũi, cằm) và hai má của bạn trông thế nào?
                        </legend>

                        <p class="quiz-question-hint">
                            Chọn một đáp án gần đúng nhất.
                        </p>

                        <div class="quiz-media-wrapper">
                            <img
                                src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/question2.jpg'); ?>"
                                alt="Tình trạng da vào cuối ngày"
                            />
                        </div>

                        <div class="quiz-options-grid cols-2">
                            <label class="quiz-option-button">
                                <input type="radio" name="q2" value="2A" />
                                <span>Cả mặt bóng dầu, lỗ chân lông to rõ</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q2" value="2B" />
                                <span>Chữ T bóng, hai má bình thường hoặc khô</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q2" value="2C" />
                                <span>Cả mặt mịn, không bóng, không khô</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q2" value="2D" />
                                <span>Xỉn, khô ráp, có vảy nhỏ</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q2" value="2E" />
                                <span>Hay ửng đỏ, nóng rát dù không làm gì</span>
                            </label>
                        </div>
                    </fieldset>

                    <!-- Q3 -->
                    <fieldset class="quiz-step-panel" data-step="3">
                        <legend class="quiz-question-title">
                            Khi đổi sản phẩm, đổi thời tiết hoặc thử hoạt chất mới (BHA, vitamin C…), da bạn phản ứng thế nào?
                        </legend>

                        <p class="quiz-question-hint">
                            Chọn một đáp án gần đúng nhất.
                        </p>

                        <div class="quiz-media-wrapper">
                            <img
                                src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/question3.jpg'); ?>"
                                alt="Mức độ nhạy cảm của da"
                            />
                        </div>

                        <div class="quiz-options-grid cols-2">
                            <label class="quiz-option-button">
                                <input type="radio" name="q3" value="3A" />
                                <span>Rất hay đỏ, ngứa, rát</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q3" value="3B" />
                                <span>Thỉnh thoảng hơi đỏ hoặc châm chích nhẹ</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q3" value="3C" />
                                <span>Hiếm khi có phản ứng</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q3" value="3D" />
                                <span>Gần như không bao giờ có phản ứng</span>
                            </label>
                        </div>
                    </fieldset>

                    <!-- Q4 -->
                    <fieldset class="quiz-step-panel" data-step="4">
                        <legend class="quiz-question-title">
                            Điều bạn muốn cải thiện nhất ở làn da hiện tại là gì? (chỉ chọn 1)
                        </legend>

                        <p class="quiz-question-hint">
                            Chọn vấn đề bạn ưu tiên nhất.
                        </p>

                        <div class="quiz-media-wrapper">
                            <img
                                src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/question4.jpg'); ?>"
                                alt="Vấn đề da ưu tiên"
                            />
                        </div>

                        <div class="quiz-options-grid cols-2">
                            <label class="quiz-option-button">
                                <input type="radio" name="q4" value="4A" />
                                <span>Mụn, mụn ẩn, mụn viêm</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q4" value="4B" />
                                <span>Thâm mụn, nám, da không đều màu</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q4" value="4C" />
                                <span>Nếp nhăn, da kém đàn hồi</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q4" value="4D" />
                                <span>Lỗ chân lông to, da xỉn, sần sùi</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q4" value="4E" />
                                <span>Không có vấn đề nổi bật, chỉ muốn duy trì da khỏe</span>
                            </label>
                        </div>
                    </fieldset>

                    <!-- Q5 -->
                    <fieldset class="quiz-step-panel" data-step="5">
                        <legend class="quiz-question-title">
                            Bạn muốn routine gồm bao nhiêu sản phẩm (mỗi sản phẩm một bước)?
                        </legend>

                        <p class="quiz-question-hint">
                            Chọn số lượng phù hợp với thói quen chăm sóc da của bạn.
                        </p>

                        <div class="quiz-media-wrapper">
                            <img
                                src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/question5.jpg'); ?>"
                                alt="Số sản phẩm trong Routine"
                            />
                        </div>

                        <div class="quiz-options-grid cols-2">
                            <label class="quiz-option-button">
                                <input type="radio" name="q5" value="5A" />
                                <span>Nhanh gọn (2 sản phẩm)</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q5" value="5B" />
                                <span>Cơ bản (3 sản phẩm)</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q5" value="5C" />
                                <span>Tiêu chuẩn (4 sản phẩm)</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q5" value="5D" />
                                <span>Chi tiết (5 sản phẩm)</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q5" value="5E" />
                                <span>Đầy đủ (6 sản phẩm)</span>
                            </label>
                        </div>
                    </fieldset>

                    <!-- Q6 -->
                    <fieldset class="quiz-step-panel" data-step="6">
                        <legend class="quiz-question-title">
                            Hằng ngày bạn có trang điểm hoặc dùng kem chống nắng không?
                        </legend>

                        <p class="quiz-question-hint">
                            Chọn một đáp án gần đúng nhất.
                        </p>

                        <div class="quiz-options-grid cols-2">
                            <label class="quiz-option-button">
                                <input type="radio" name="q6" value="6A" />
                                <span>Có, tôi trang điểm hằng ngày hoặc thường xuyên</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q6" value="6B" />
                                <span>Không trang điểm, nhưng có dùng kem chống nắng</span>
                            </label>

                            <label class="quiz-option-button">
                                <input type="radio" name="q6" value="6C" />
                                <span>Không trang điểm và không dùng kem chống nắng</span>
                            </label>
                        </div>
                    </fieldset>

                    <div
                        class="quiz-consent-wrapper"
                        style="margin: 20px 0 10px; padding-top: 15px; border-top: 1px solid #f0ede8;"
                    >
                        <label
                            class="quiz-consent-label"
                            style="display: flex; align-items: flex-start; gap: 10px; font-size: 13px; color: #555; cursor: pointer; line-height: 1.4;"
                        >
                            <input
                                type="checkbox"
                                id="quiz-policy-consent"
                                name="policy_accepted"
                                style="margin-top: 2px;"
                            />

                            <span>
                                Tôi đồng ý với
                                <a
                                    href="<?php echo esc_url(home_url('/chinh-sach-bao-mat/')); ?>"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    style="color: #c25e2e; text-decoration: underline;"
                                >
                                    Chính sách bảo mật
                                </a>
                                của KLEER để nhận phác đồ da cá nhân hóa.
                                <strong style="color: #c25e2e;">*</strong>
                            </span>
                        </label>

                        <label
                            class="quiz-consent-label"
                            style="display: flex; align-items: flex-start; gap: 10px; font-size: 13px; color: #777; cursor: pointer; line-height: 1.4; margin-top: 8px;"
                        >
                            <input
                                type="checkbox"
                                id="quiz-marketing-consent"
                                name="marketing_opt_in"
                                style="margin-top: 2px;"
                            />

                            <span>
                                Đăng ký nhận hướng dẫn chăm sóc da khoa học và ưu đãi độc quyền qua email (tùy chọn).
                            </span>
                        </label>
                    </div>

                    <div class="quiz-actions-row">
                        <button
                            type="button"
                            id="btn-quiz-prev"
                            class="btn-quiz-outline"
                            disabled
                        >
                            QUAY LẠI
                        </button>

                        <button
                            type="button"
                            id="btn-quiz-next"
                            class="btn-quiz-primary"
                            style="display: none;"
                        >
                            XEM KẾT QUẢ PHÁC ĐỒ ROUTINE
                        </button>
                    </div>
                </form>

                <div id="quiz-loading-state" class="quiz-state-card" hidden>
                    <span class="state-subtitle">ĐANG PHÂN TÍCH</span>
                    <h2 class="state-title">
                        Đang xây phác đồ dành riêng cho làn da của bạn...
                    </h2>
                    <div class="loading-line-indicator"></div>
                </div>

                <div id="quiz-error-state" class="quiz-state-card" hidden>
                    <span class="state-subtitle text-orange">LỖI KẾT NỐI</span>
                    <h2 class="state-title">Chưa thể tải phác đồ da</h2>
                    <p class="state-desc">
                        Câu trả lời của bạn đã được tạm lưu. Vui lòng thử lại.
                    </p>

                    <button
                        type="button"
                        id="btn-quiz-retry"
                        class="btn-quiz-primary"
                    >
                        THỬ LẠI
                    </button>
                </div>
            </section>
        </div>
    </section>
</main>

<?php
get_footer();