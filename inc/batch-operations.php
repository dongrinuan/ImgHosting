<?php
/**
 * 批量操作处理功能
 */

// 处理批量删除图片的AJAX请求
function imghosting_batch_delete_images() {
    // 验证nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'imghosting_batch_operation')) {
        wp_send_json_error(array('message' => '安全验证失败，请刷新页面后重试'));
        die();
    }
    
    // 检查是否提供了ID列表
    if (!isset($_POST['ids']) || empty($_POST['ids'])) {
        wp_send_json_error(array('message' => '未指定要删除的图片'));
        die();
    }
    
    // 确保ids是数组
    $ids = $_POST['ids'];
    if (!is_array($ids)) {
        // 尝试从JSON字符串转换
        if (is_string($ids)) {
            $ids = json_decode($ids, true);
        }
        
        // 如果仍然不是数组，则返回错误
        if (!is_array($ids)) {
            wp_send_json_error(array('message' => '无效的图片ID列表格式'));
            die();
        }
    }
    
    // 获取需要删除的ID数组
    $ids = array_map('intval', $ids);
    if (empty($ids)) {
        wp_send_json_error(array('message' => '无效的图片ID列表'));
        die();
    }
    
    // 获取当前访客ID
    $visitor_id = imghosting_get_visitor_id();
    
    // 跟踪删除结果
    $success_count = 0;
    $failed_count = 0;
    $failed_ids = array();
    
    // 处理删除操作
    foreach ($ids as $id) {
        // 确认是附件类型
        if (get_post_type($id) !== 'attachment') {
            $failed_count++;
            $failed_ids[] = $id;
            continue;
        }
        
        // 检查权限
        $image_owner = get_post_meta($id, '_imghosting_visitor_id', true);
        $attachment_author = get_post_field('post_author', $id);
        $can_delete = false;
        
        if (current_user_can('manage_options')) {
            $can_delete = true;
        } else if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            if ($attachment_author == $current_user_id || $image_owner == 'user_' . $current_user_id) {
                $can_delete = true;
            }
        } else if ($image_owner === $visitor_id) {
            $can_delete = true;
        }
        
        if (!$can_delete) {
            $failed_count++;
            $failed_ids[] = $id;
            continue;
        }
        
        // 执行删除
        $result = wp_delete_attachment($id, true);
        if ($result) {
            $success_count++;
        } else {
            $failed_count++;
            $failed_ids[] = $id;
        }
    }
    
    // 准备响应消息
    if ($failed_count === 0) {
        wp_send_json_success(array(
            'message' => sprintf('成功删除了 %d 张图片', $success_count),
            'success_count' => $success_count
        ));
    } else if ($success_count === 0) {
        wp_send_json_error(array(
            'message' => '所有图片删除失败，请稍后重试',
            'failed_ids' => $failed_ids
        ));
    } else {
        wp_send_json_success(array(
            'message' => sprintf('成功删除了 %d 张图片，%d 张删除失败', $success_count, $failed_count),
            'success_count' => $success_count,
            'failed_count' => $failed_count,
            'failed_ids' => $failed_ids
        ));
    }
    
    die();
}
add_action('wp_ajax_imghosting_batch_delete_images', 'imghosting_batch_delete_images');
add_action('wp_ajax_nopriv_imghosting_batch_delete_images', 'imghosting_batch_delete_images');

// 批量导出图片链接
function imghosting_batch_export_links() {
    // 验证nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'imghosting_batch_operation')) {
        wp_send_json_error(array('message' => '安全验证失败，请刷新页面后重试'));
        die();
    }
    
    // 检查是否提供了URL列表
    if (!isset($_POST['urls']) || !is_array($_POST['urls'])) {
        wp_send_json_error(array('message' => '缺少图片URL列表'));
        die();
    }
    
    // 获取URL列表
    $urls = array_map('esc_url_raw', $_POST['urls']);
    if (empty($urls)) {
        wp_send_json_error(array('message' => '无效的URL列表'));
        die();
    }
    
    // 导出格式（默认为直接链接）
    $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'direct';
    
    // 根据格式生成链接
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
    
    // 返回结果
    wp_send_json_success(array(
        'links' => $links,
        'count' => count($links),
        'format' => $format
    ));
    
    die();
}
add_action('wp_ajax_imghosting_batch_export_links', 'imghosting_batch_export_links');
add_action('wp_ajax_nopriv_imghosting_batch_export_links', 'imghosting_batch_export_links');
