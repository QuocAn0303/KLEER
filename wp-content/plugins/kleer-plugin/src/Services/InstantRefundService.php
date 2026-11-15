<?php
declare(strict_types=1);
namespace Kleer\Services;
defined('ABSPATH') || exit;

/** Store-credit issuance through WooCommerce CRUD (including HPOS). */
final class InstantRefundService
{
    public function register(): void
    {
        add_action('admin_post_kleer_credit', [$this, 'handle']);
        add_action('admin_post_nopriv_kleer_credit', [$this, 'handle']);
    }

    public function handle(): void
    {
        check_admin_referer('kleer_credit');
        $result = 'credit_received';
        $email = isset($_POST['email']) && is_string($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
        $tracking = isset($_POST['tracking']) && is_string($_POST['tracking']) ? sanitize_text_field(wp_unslash($_POST['tracking'])) : '';
        $id = isset($_POST['order_id']) && is_scalar($_POST['order_id']) ? absint($_POST['order_id']) : 0;
        $rate_key = 'kleer_credit_rate_' . hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . $email);
        if (!get_transient($rate_key) && is_email($email) && $id && strlen($tracking) >= 4 && strlen($tracking) <= 100) {
            set_transient($rate_key, 1, 5 * MINUTE_IN_SECONDS);
            try {
                $this->issue($id, $email, $tracking);
            } catch (\Throwable $error) {
                // Do not expose whether an order/email pair exists to anonymous callers.
                if (function_exists('wc_get_logger')) {
                    wc_get_logger()->error('Store credit request failed for order ' . $id, ['source' => 'kleer-credit']);
                }
            }
        }
        wp_safe_redirect(add_query_arg('kleer_notice', $result, home_url('/contact/')));
        exit;
    }

    public function issue(int $id, string $email, string $tracking): bool
    {
        if (!function_exists('wc_get_order') || !class_exists('WC_Coupon')) {
            return false;
        }
        $lock = 'kleer_credit_lock_' . $id;
        if (!add_option($lock, time(), '', false)) {
            return false;
        }
        try {
            $order = wc_get_order($id);
            if (!$order instanceof \WC_Order || !hash_equals(strtolower($order->get_billing_email()), strtolower($email))) {
                return false;
            }
            $completed = $order->get_date_completed();
            if (!$order->has_status('completed') || !$completed || $completed->getTimestamp() > time() || time() - $completed->getTimestamp() > 7 * DAY_IN_SECONDS || (float) $order->get_total() <= 0 || (float) $order->get_total_refunded() > 0 || $order->get_currency() !== get_woocommerce_currency()) {
                return false;
            }
            // A deterministic secret code also recovers safely after a partial save.
            $code = 'KLR-CREDIT-' . $id . '-' . substr(hash_hmac('sha256', 'kleer-credit|' . $id, wp_salt('auth')), 0, 20);
            $coupon_id = (int) $order->get_meta('_kleer_credit_coupon_id');
            if (!$coupon_id) {
                $coupon_id = (int) wc_get_coupon_id_by_code($code);
            }
            if ($coupon_id && $order->get_meta('_kleer_credit_email_sent')) {
                return true;
            }
            $coupon = new \WC_Coupon($coupon_id ?: '');
            if (!$coupon_id) {
                $coupon->set_code($code);
                $coupon->set_discount_type('fixed_cart');
                $coupon->set_amount($order->get_total());
                $coupon->set_usage_limit(1);
                $coupon->set_usage_limit_per_user(1);
                $coupon->set_individual_use(true);
                $coupon->set_email_restrictions([$order->get_billing_email()]);
                $coupon->set_description('KLEER Store Credit for order #' . $id);
                $coupon->update_meta_data('_kleer_credit_order_id', $id);
                $coupon_id = $coupon->save();
                if (!$coupon_id) {
                    throw new \RuntimeException('Unable to persist store credit coupon');
                }
            }
            $order->update_meta_data('_kleer_credit_coupon_id', $coupon_id);
            $order->update_meta_data('_kleer_return_tracking', $tracking);
            $order->save();
            $sent = wp_mail($order->get_billing_email(), 'KLEER · Store Credit đơn #' . $id, sprintf("Mã Store Credit: %s\nGiá trị: %s %s\nMã dùng một lần, áp dụng với email đặt hàng.\nMua sắm: %s", $coupon->get_code(), $order->get_total(), $order->get_currency(), wc_get_page_permalink('shop')));
            if ($sent) {
                $order->update_meta_data('_kleer_credit_email_sent', time());
                $order->save();
                $order->add_order_note('KLEER Store Credit issued; coupon ID ' . $coupon_id . '. Return tracking: ' . $tracking);
            }
            return $sent;
        } finally {
            delete_option($lock);
        }
    }
}
