<?php
declare(strict_types=1);
// Isolated domain checks: php wp-content/plugins/kleer-plugin/tests/run_credit_tests.php
define('ABSPATH', __DIR__);
define('DAY_IN_SECONDS', 86400);
$locks = []; $coupons = []; $mail_count = 0; $mail_success = true;
function add_option($key, $value, ...$args) { global $locks; if (isset($locks[$key])) return false; $locks[$key] = $value; return true; }
function delete_option($key) { global $locks; unset($locks[$key]); }
function wc_get_order($id) { global $order; return $id === 42 ? $order : false; }
function get_woocommerce_currency() { return 'VND'; }
function wp_salt($scheme) { return 'test-only-secret'; }
function wc_get_coupon_id_by_code($code) { global $coupons; foreach ($coupons as $id => $coupon) if ($coupon->code === $code) return $id; return 0; }
function wp_mail(...$args) { global $mail_count, $mail_success; $mail_count++; return $mail_success; }
function wc_get_page_permalink($page) { return 'https://example.test/shop/'; }
class WC_Order {
    public array $meta = [];
    public string $status = 'completed';
    public int $completed;
    public float $refunded = 0;
    public string $currency = 'VND';
    public function __construct() { $this->completed = time() - DAY_IN_SECONDS; }
    public function get_billing_email() { return 'buyer@example.test'; }
    public function get_date_completed() { return (new DateTimeImmutable())->setTimestamp($this->completed); }
    public function has_status($status) { return $status === $this->status; }
    public function get_total() { return '500000'; }
    public function get_total_refunded() { return $this->refunded; }
    public function get_currency() { return $this->currency; }
    public function get_meta($key) { return $this->meta[$key] ?? ''; }
    public function update_meta_data($key, $value) { $this->meta[$key] = $value; }
    public function save() {}
    public function add_order_note($text) {}
}
class WC_Coupon {
    public string $code = '';
    public array $settings = [];
    public int $id = 0;
    public function __construct($id = '') { global $coupons; if ($id) { $this->id = $id; $this->code = $coupons[$id]->code; $this->settings = $coupons[$id]->settings; } }
    public function __call($method, $args) { $this->settings[$method] = $args[0]; }
    public function set_code($code) { $this->code = $code; }
    public function get_code() { return $this->code; }
    public function save() { global $coupons; $this->id = count($coupons) + 1; $coupons[$this->id] = clone $this; return $this->id; }
}
require __DIR__ . '/../src/Services/InstantRefundService.php';
$service = new \Kleer\Services\InstantRefundService();
$failed = 0; $checks = 0;
function check(bool $ok, string $label): void { global $failed, $checks; $checks++; if (!$ok) $failed++; echo ($ok ? 'PASS ' : 'FAIL ') . $label . PHP_EOL; }
$order = new WC_Order();
check(!$service->issue(42, 'other@example.test', 'TRACK42'), 'Reject incorrect email');
check(!$service->issue(99, 'buyer@example.test', 'TRACK42'), 'Reject missing order');
$order->completed = time() - 8 * DAY_IN_SECONDS;
check(!$service->issue(42, 'buyer@example.test', 'TRACK42'), 'Reject outside seven days');
$order->completed = time() + DAY_IN_SECONDS;
check(!$service->issue(42, 'buyer@example.test', 'TRACK42'), 'Reject future completion timestamp');
$order = new WC_Order(); $order->status = 'processing';
check(!$service->issue(42, 'buyer@example.test', 'TRACK42'), 'Reject incomplete order');
$order = new WC_Order(); $order->refunded = 100;
check(!$service->issue(42, 'buyer@example.test', 'TRACK42'), 'Reject already refunded order');
$order = new WC_Order(); $order->currency = 'USD';
check(!$service->issue(42, 'buyer@example.test', 'TRACK42'), 'Reject incompatible coupon currency');
$order = new WC_Order();
$locks['kleer_credit_lock_42'] = 1;
check(!$service->issue(42, 'buyer@example.test', 'TRACK42'), 'Reject concurrent issuance');
unset($locks['kleer_credit_lock_42']);
$mail_success = false;
check(!$service->issue(42, 'buyer@example.test', 'TRACK42') && count($coupons) === 1, 'Preserve issued coupon if email fails');
$mail_success = true;
check($service->issue(42, 'buyer@example.test', 'TRACK42') && count($coupons) === 1, 'Retry email without duplicate coupon');
check($service->issue(42, 'buyer@example.test', 'TRACK42') && $mail_count === 2 && count($coupons) === 1, 'Duplicate submission is idempotent');
$coupon = $coupons[1];
check($coupon->settings['set_usage_limit'] === 1 && $coupon->settings['set_email_restrictions'] === ['buyer@example.test'] && $coupon->settings['set_amount'] === '500000', 'Full amount, single use, restricted email');
check(!$locks, 'Release issuance lock');
echo "$checks checks; $failed failures" . PHP_EOL;
exit($failed ? 1 : 0);
