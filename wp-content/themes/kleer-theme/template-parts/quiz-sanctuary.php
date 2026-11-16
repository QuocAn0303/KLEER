<?php
/**
 * Khung Chẩn đoán da 6 câu dùng chung (Trang chủ & trang /quiz/).
 * Toàn bộ câu hỏi được render bởi assets/js/quiz.js để chuyển cảnh mờ dần.
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<section id="quiz-sanctuary-sec" class="quiz-sanctuary-sec">
    <div class="container">
        <div class="quiz-sanctuary-frame" id="quiz-sanctuary">
            <div class="quiz-leaves-canvas" id="quiz-leaves" aria-hidden="true"></div>

            <div class="quiz-sanctuary__inner">
                <p class="quiz-sanctuary__eyebrow">CHẨN ĐOÁN DA · 6 CÂU HỎI</p>
                <h2 class="quiz-sanctuary__title">Làn da của bạn đang cần gì?</h2>
                <p class="quiz-sanctuary__desc">Sáu câu hỏi ngắn để nhận phác đồ chăm sóc da unisex tối giản, khoa học, kèm danh sách sản phẩm gợi ý từ chuyên gia KLEER.</p>

                <div class="quiz-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="quiz-progress-track">
                    <div class="quiz-progress-fill" id="quiz-progress-fill"></div>
                </div>

                <span class="quiz-step-count" id="quiz-step-count">Câu 1/6</span>

                <div
                    class="quiz-stage-transition"
                    id="quiz-stage"
                    aria-live="polite"
                ></div>

                <div class="quiz-result" id="quiz-result" hidden></div>

                <label class="quiz-consent" for="quiz-policy-consent">
                    <input type="checkbox" id="quiz-policy-consent" name="policy_accepted">
                    <span>Tôi đồng ý với Chính sách bảo mật của KLEER để nhận phác đồ da cá nhân hóa. KLEER không chia sẻ dữ liệu làn da của bạn cho bên thứ ba.</span>
                </label>

                <p class="quiz-note" id="quiz-note"></p>
            </div>
        </div>
    </div>
</section>
