<?php
/**
 * 图片操作相关功能
 */

// 通用的响应错误函数
function imghosting_send_error($message, $log = true) {
    if ($log) {
        error_log('ImgHosting错误: ' . $message);
    }
    wp_send_json_error(array('message' => $message));
    die();
}

// 检查用户是否可以修改指定的图片
function imghosting_user_can_modify_image($attachment_id) {
    // 获取当前用户/访客信息
    $visitor_id = imghosting_get_visitor_id();
    $image_owner = get_post_meta($attachment_id, '_imghosting_visitor_id', true);
    $attachment_author = get_post_field('post_author', $attachment_id);
    
    // 1. 管理员可以修改任何图片
    if (current_user_can('manage_options')) {
        return true;
    }
    
    // 2. 登录用户可以修改自己上传的图片
    if (is_user_logged_in()) {
        $current_user_id = get_current_user_id();
        if ($attachment_author == $current_user_id || $image_owner == 'user_' . $current_user_id) {
            return true;
        }
    } 
    // 3. 访客只能修改自己通过cookie识别的图片
    elseif ($image_owner === $visitor_id) {
        return true;
    }
    
    return false;
}

// 删除单个图片
function imghosting_delete_single_image($attachment_id) {
    // 确保是附件类型
    if (get_post_type($attachment_id) !== 'attachment') {
        return array(
            'success' => false,
            'message' => '指定的ID不是有效的图片ID'
        );
    }
    
    // 权限检查
    if (!imghosting_user_can_modify_image($attachment_id)) {
        return array(
            'success' => false,
            'message' => '您没有权限删除此图片'
        );
    }
    
    // 实际删除附件
    $result = wp_delete_attachment($attachment_id, true);
    
    if ($result) {
        return array(
            'success' => true,
            'message' => '图片已成功删除'
        );
    } else {
        return array(
            'success' => false,
            'message' => '删除图片时出错，请稍后重试'
        );
    }
}

// 处理单个图片删除的AJAX请求
function imghosting_handle_delete_image() {
    // 验证nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'imghosting_delete_image')) {
        imghosting_send_error('安全验证失败，请刷新页面后重试');
    }
    
    // 检查ID参数
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        imghosting_send_error('未指定要删除的图片ID');
    }
    
    $attachment_id = intval($_POST['id']);
    $result = imghosting_delete_single_image($attachment_id);
    
    if ($result['success']) {
        wp_send_json_success($result['message']);
    } else {
        imghosting_send_error($result['message']);
    }
}
add_action('wp_ajax_imghosting_delete_image', 'imghosting_handle_delete_image');
add_action('wp_ajax_nopriv_imghosting_delete_image', 'imghosting_handle_delete_image');

// 批量删除图片
function imghosting_batch_delete_images() {
    // 验证安全性
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'imghosting_batch_operation')) {
        imghosting_send_error('安全验证失败，请刷新页面后重试');
    }
    
    // 检查是否有ID数组
    if (!isset($_POST['ids']) || !is_array($_POST['ids'])) {
        imghosting_send_error('参数错误，未提供有效的ID列表');
    }
    
    $ids = array_map('intval', $_POST['ids']);
    $success_count = 0;
    $error_count = 0;
    
    foreach ($ids as $attachment_id) {
        $result = imghosting_delete_single_image($attachment_id);
        if ($result['success']) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    $message = '';
    if ($success_count > 0) {
        $message = sprintf('已成功删除 %d 张图片', $success_count);
        if ($error_count > 0) {
            $message .= sprintf('，但有 %d 张图片删除失败', $error_count);
        }
    } else {
        $message = '删除失败，没有图片被删除';
    }
    
    wp_send_json_success(array(
        'message' => $message,
        'success_count' => $success_count,
        'error_count' => $error_count
    ));
}
add_action('wp_ajax_imghosting_batch_delete_images', 'imghosting_batch_delete_images');
add_action('wp_ajax_nopriv_imghosting_batch_delete_images', 'imghosting_batch_delete_images');

// 批量导出链接
function imghosting_batch_export_links() {
    // 验证安全性
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'imghosting_batch_operation')) {
        imghosting_send_error('安全验证失败，请刷新页面后重试');
    }
    
    // 检查URL数组
    if (!isset($_POST['urls']) || !is_array($_POST['urls'])) {
        imghosting_send_error('参数错误，未提供有效的URL列表');
    }
    
    $urls = array_map('esc_url_raw', $_POST['urls']);
    $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'direct';
    $links = array();
    
    foreach ($urls as $url) {
        switch ($format) {
            case 'html':
                $links[] = '<img src="' . esc_url($url) . '" alt="Uploaded Image">';
                break;
            case 'markdown':
                $links[] = '![Uploaded Image](' . esc_url($url) . ')';
                break;
            case 'bbcode':
                $links[] = '[img]' . esc_url($url) . '[/img]';
                break;
            case 'direct':
            default:
                $links[] = esc_url($url);
                break;
        }
    }
    
    wp_send_json_success(array(
        'links' => $links,
        'count' => count($links),
        'format' => $format
    ));
}
add_action('wp_ajax_imghosting_batch_export_links', 'imghosting_batch_export_links');
add_action('wp_ajax_nopriv_imghosting_batch_export_links', 'imghosting_batch_export_links');