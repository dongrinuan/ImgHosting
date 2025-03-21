<?php
if (!defined('ABSPATH')) {
    exit;
}

function handle_update_user_profile() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => '请先登录'));
    }

    if (!wp_verify_nonce($_POST['nonce'], 'update_user_profile')) {
        wp_send_json_error(array('message' => '安全验证失败'));
    }

    $user_id = get_current_user_id();
    $nickname = sanitize_text_field($_POST['nickname']);
    $website = esc_url_raw($_POST['website']);
    $description = sanitize_textarea_field($_POST['description']);

    if (empty($nickname)) {
        wp_send_json_error(array('message' => '昵称不能为空'));
    }

    $user_data = array(
        'ID' => $user_id,
        'display_name' => $nickname,
        'user_url' => $website
    );

    $update_user = wp_update_user($user_data);

    if (is_wp_error($update_user)) {
        wp_send_json_error(array('message' => $update_user->get_error_message()));
    }

    update_user_meta($user_id, 'description', $description);

    wp_send_json_success(array('message' => '资料更新成功'));
}

add_action('wp_ajax_update_user_profile', 'handle_update_user_profile');