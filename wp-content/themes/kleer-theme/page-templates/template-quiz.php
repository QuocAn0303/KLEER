<?php
/**
 * Template Name: Quiz Chẩn Đoán Da
 * Description: Template SPA Quiz chẩn đoán da cá nhân hóa chuẩn giao diện Unisex Minimalist Earthy Tone
 * Project: KLEER - Unisex Skincare Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main id="primary" class="site-main kleer-quiz-page">
  <section id="kleer-quiz-app" class="kleer-quiz-container" aria-label="Chẩn đoán da cá nhân hóa">
    
    <!-- Breadcrumb điều hướng -->
    <nav class="quiz-breadcrumb" aria-label="Breadcrumb">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Trang chủ</a> &rsaquo; <span>Chẩn đoán da</span>
    </nav>

    <div class="quiz-layout">
      <!-- Cột trái: Tiến trình khảo sát -->
      <aside class="quiz-sidebar">
        <h1 class="quiz-main-title">Chẩn đoán da</h1>
        <p class="quiz-sidebar-heading">TIẾN TRÌNH</p>
        <ol class="quiz-step-indicator" id="quiz-step-indicator">
          <li class="step-item active" data-step="1">
            <span class="step-badge">1</span>
            <span class="step-label">Độ tiết dầu</span>
          </li>
          <li class="step-item" data-step="2">
            <span class="step-badge">2</span>
            <span class="step-label">Độ nhạy cảm</span>
          </li>
          <li class="step-item" data-step="3">
            <span class="step-badge">3</span>
            <span class="step-label">Vấn đề da ưu tiên</span>
          </li>
          <li class="step-item" data-step="4">
            <span class="step-badge">4</span>
            <span class="step-label">Lối sống</span>
          </li>
          <li class="step-item" data-step="5">
            <span class="step-badge">5</span>
            <span class="step-label">Số bước Routine</span>
          </li>
        </ol>
        <footer class="quiz-sidebar-note">
          <small>Không cần đăng nhập. Câu trả lời của khách vãng lai được lưu ẩn danh.</small>
        </footer>
      </aside>

      <!-- Cột phải: Khung Card tương tác Quiz -->
      <section class="quiz-content-card">
        <header class="card-header" id="quiz-card-header">
          <div class="header-top">
            <span class="quiz-step-count" id="quiz-step-count">Câu 1/5</span>
            <span class="quiz-step-pct" id="quiz-step-pct">0%</span>
          </div>
          <div class="quiz-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div class="quiz-progress-bar" id="quiz-progress-fill" style="width: 0%;"></div>
          </div>
        </header>

        <form id="kleer-quiz-form" class="quiz-form" novalidate>
          <!-- Câu 1: Độ tiết dầu -->
          <fieldset class="quiz-step-panel active" data-step="1">
            <legend class="quiz-question-title">Vùng chữ T và hai má của bạn thường thế nào?</legend>
            <p class="quiz-question-hint">Chọn một đáp án gần đúng nhất.</p>
            <div class="quiz-media-wrapper">
              <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/question1.jpg' ); ?>" alt="Độ tiết dầu" />
            </div>
            <div class="quiz-options-grid cols-2">
              <label class="quiz-option-button">
                <input type="radio" name="skin_type" value="dry" />
                <span>Khô, căng sau khi rửa mặt</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="skin_type" value="normal" />
                <span>Bình thường, ít bóng dầu</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="skin_type" value="combination" />
                <span>Bóng dầu vùng chữ T, má bình thường</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="skin_type" value="oily" />
                <span>Bóng dầu toàn mặt</span>
              </label>
            </div>
          </fieldset>

          <!-- Câu 2: Độ nhạy cảm -->
          <fieldset class="quiz-step-panel" data-step="2">
            <legend class="quiz-question-title">Da bạn nhạy cảm với mỹ phẩm đến đâu?</legend>
            <p class="quiz-question-hint">Chọn một đáp án gần đúng nhất.</p>
            <div class="quiz-media-wrapper">
              <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/question2.jpg' ); ?>" alt="Độ nhạy cảm" />
            </div>
            <div class="quiz-options-grid cols-2">
              <label class="quiz-option-button">
                <input type="radio" name="sensitivity" value="resilient" />
                <span>Hiếm khi kích ứng</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="sensitivity" value="mild" />
                <span>Thỉnh thoảng đỏ hoặc châm chích</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="sensitivity" value="high" />
                <span>Rất hay kích ứng</span>
              </label>
            </div>
          </fieldset>

          <!-- Câu 3: Vấn đề da ưu tiên -->
          <fieldset class="quiz-step-panel" data-step="3">
            <legend class="quiz-question-title">Bạn muốn cải thiện vấn đề da nào trước?</legend>
            <p class="quiz-question-hint">Chọn một đáp án gần đúng nhất.</p>
            <div class="quiz-media-wrapper">
              <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/question3.jpg' ); ?>" alt="Vấn đề da ưu tiên" />
            </div>
            <div class="quiz-options-grid cols-2">
              <label class="quiz-option-button">
                <input type="radio" name="concern" value="acne" />
                <span>Mụn</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="concern" value="dark_spots" />
                <span>Thâm và sạm</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="concern" value="aging" />
                <span>Lão hóa</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="concern" value="dullness" />
                <span>Xỉn màu</span>
              </label>
            </div>
          </fieldset>

          <!-- Câu 4: Lối sống -->
          <fieldset class="quiz-step-panel" data-step="4">
            <legend class="quiz-question-title">Môi trường làm việc và lối sống của bạn?</legend>
            <p class="quiz-question-hint">Chọn một đáp án gần đúng nhất.</p>
            <div class="quiz-media-wrapper">
              <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/question4.jpg' ); ?>" alt="Lối sống" />
            </div>
            <div class="quiz-options-grid cols-2">
              <label class="quiz-option-button">
                <input type="radio" name="lifestyle" value="office" />
                <span>Văn phòng, ít ra nắng</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="lifestyle" value="outdoor" />
                <span>Thường xuyên ngoài trời</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="lifestyle" value="late_night" />
                <span>Thức khuya, nhiều áp lực</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="lifestyle" value="dusty" />
                <span>Di chuyển nhiều, nhiều bụi</span>
              </label>
            </div>
          </fieldset>

          <!-- Câu 5: Số bước Routine -->
          <fieldset class="quiz-step-panel" data-step="5">
            <legend class="quiz-question-title">Bạn muốn Routine gọn đến mức nào?</legend>
            <p class="quiz-question-hint">Chọn một đáp án gần đúng nhất.</p>
            <div class="quiz-media-wrapper">
              <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/question5.jpg' ); ?>" alt="Số bước Routine" />
            </div>
            <div class="quiz-options-grid cols-2">
              <label class="quiz-option-button">
                <input type="radio" name="routine_length" value="minimal_2" />
                <span>Tối giản · 2 bước</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="routine_length" value="balance_3" />
                <span>Cân bằng · 3 bước</span>
              </label>
              <label class="quiz-option-button">
                <input type="radio" name="routine_length" value="intensive_4" />
                <span>Chuyên sâu · 4 bước</span>
              </label>
            </div>
          </fieldset>

          <!-- Điều hướng trong form -->
          <div class="quiz-actions-row">
            <button type="button" id="btn-quiz-prev" class="btn-quiz-outline" disabled>QUAY LẠI</button>
            <button type="button" id="btn-quiz-next" class="btn-quiz-primary" style="display: none;">
              XEM KẾT QUẢ PHÁC ĐỒ ROUTINE
            </button>
          </div>
        </form>

        <!-- Trạng thái Loading (mô phỏng gọi API) -->
        <div id="quiz-loading-state" class="quiz-state-card" hidden>
          <span class="state-subtitle">ĐANG PHÂN TÍCH</span>
          <h2 class="state-title">Đang xây phác đồ dành riêng cho làn da của bạn...</h2>
          <div class="loading-line-indicator"></div>
          <small class="state-mock-note">(Demo) Mô phỏng gọi API phân tích 2.5 giây</small>
        </div>

        <!-- Trạng thái Lỗi Timeout/Mất mạng -->
        <div id="quiz-error-state" class="quiz-state-card" hidden>
          <span class="state-subtitle text-orange">LỖI KẾT NỐI</span>
          <h2 class="state-title">Dịch vụ phân tích đang bận</h2>
          <p class="state-desc">Câu trả lời của bạn đã được tạm lưu. Vui lòng bấm "Thử lại" hoặc để lại email để nhận kết quả.</p>
          <div class="error-form-group">
            <input type="email" id="quiz-fallback-email" placeholder="Email của bạn" class="quiz-input-field" />
            <div class="error-btn-group">
              <button type="button" id="btn-quiz-retry" class="btn-quiz-light">THỬ LẠI</button>
              <button type="button" id="btn-quiz-save-email" class="btn-quiz-dark">ĐỂ LẠI EMAIL</button>
            </div>
          </div>
        </div>
      </section>
    </div>
  </section>
</main>

<?php
get_footer();
