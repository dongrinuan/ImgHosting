<?php
/**
 * 批量上传处理功能
 */

// 添加批量上传会话记录功能
function imghosting_start_batch_upload() {
    // 验证nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'imghosting_upload_nonce')) {
        wp_send_json_error('验证失败，请刷新页面后重试');
        die();
    }
    
    // 创建新的批量上传会话ID
    $batch_id = uniqid('batch_');
    
    // 初始化批量上传会话
    $batch_data = array(
        'id' => $batch_id,
        'uploads' => array(),
        'total_files' => isset($_POST['total_files']) ? intval($_POST['total_files']) : 0,
        'created_at' => time()
    );
    
    // 保存会话到用户元数据或transient
    if (is_user_logged_in()) {
        update_user_meta(get_current_user_id(), 'imghosting_batch_' . $batch_id, $batch_data);
    } else {
        set_transient('imghosting_batch_' . $batch_id, $batch_data, DAY_IN_SECONDS);
    }
    
    wp_send_json_success(array('batch_id' => $batch_id));
    die();
}
add_action('wp_ajax_imghosting_start_batch_upload', 'imghosting_start_batch_upload');
add_action('wp_ajax_nopriv_imghosting_start_batch_upload', 'imghosting_start_batch_upload');

// 记录批量上传结果
function imghosting_record_batch_upload() {
    // 验证nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'imghosting_upload_nonce')) {
        wp_send_json_error('验证失败，请刷新页面后重试');
        die();
    }
    
    $batch_id = isset($_POST['batch_id']) ? sanitize_text_field($_POST['batch_id']) : '';
    $file_id = isset($_POST['file_id']) ? intval($_POST['file_id']) : 0;
    $success = isset($_POST['success']) ? (bool) $_POST['success'] : false;
    
    if (empty($batch_id) || empty($file_id)) {
        wp_send_json_error('缺少必要参数');
        die();
    }
    
    // 获取批量上传会话
    $batch_data = null;
    if (is_user_logged_in()) {
        $batch_data = get_user_meta(get_current_user_id(), 'imghosting_batch_' . $batch_id, true);
    } else {
        $batch_data = get_transient('imghosting_batch_' . $batch_id);
    }
    
    if (!$batch_data) {
        wp_send_json_error('找不到批量上传会话');
        die();
    }
    
    // 记录文件上传结果
    $file_data = array(
        'id' => $file_id,
        'success' => $success,
        'url' => wp_get_attachment_url($file_id),
        'uploaded_at' => time()
    );
    
    $batch_data['uploads'][] = $file_data;
    
    // 更新批量上传会话
    if (is_user_logged_in()) {
        update_user_meta(get_current_user_id(), 'imghosting_batch_' . $batch_id, $batch_data);
    } else {
        set_transient('imghosting_batch_' . $batch_id, $batch_data, DAY_IN_SECONDS);
    }
    
    wp_send_json_success();
    die();
}
add_action('wp_ajax_imghosting_record_batch_upload', 'imghosting_record_batch_upload');
add_action('wp_ajax_nopriv_imghosting_record_batch_upload', 'imghosting_record_batch_upload');

// 限制上传文件大小
function imghosting_upload_size_limit($size) {
    // 设置最大上传大小为10MB
    return 10 * 1024 * 1024;
}
add_filter('upload_size_limit', 'imghosting_upload_size_limit');
