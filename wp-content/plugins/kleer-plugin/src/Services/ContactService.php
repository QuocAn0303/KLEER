<?php
declare(strict_types=1);
namespace Kleer\Services;
defined('ABSPATH') || exit;

final class ContactService
{
    public function register(): void
    {
        add_action('admin_post_kleer_contact', [$this, 'handle']);
        add_action('admin_post_nopriv_kleer_contact', [$this, 'handle']);
    }

    public function handle(): void
    {
        check_admin_referer('kleer_contact');
        $fields = [];
        foreach (['name', 'reply', 'skin', 'message'] as $key) {
            $fields[$key] = isset($_POST[$key]) && is_string($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
        }
        $key = 'kleer_contact_rate_' . hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        $notice = 'contact_error';
        $valid_reply = is_email($fields['reply']) || preg_match('/^\+?[0-9 ()-]{8,20}$/', $fields['reply']);
        if ($fields['name'] !== '' && $valid_reply && $fields['message'] !== '' && strlen($fields['message']) <= 10000 && !get_transient($key)) {
            set_transient($key, 1, MINUTE_IN_SECONDS);
            $sent = wp_mail(get_option('admin_email'), 'KLEER · Yêu cầu tư vấn làn da', implode("\n\n", array_map(static fn ($label, $value) => $label . ': ' . $value, ['Họ tên', 'Liên hệ', 'Loại da', 'Nội dung'], array_values($fields))));
            $notice = $sent ? 'contact_sent' : 'contact_error';
        }
        wp_safe_redirect(add_query_arg('kleer_notice', $notice, home_url('/contact/')));
        exit;
    }
}
