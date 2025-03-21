<?php
/**
 * 图片上传和删除处理
 */

// 处理图片上传的AJAX请求
function imghosting_handle_upload() {
    // 设置无限执行时间，适用于大文件上传
    set_time_limit(0);
    
    // 验证nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'imghosting_upload_nonce')) {
        wp_send_json_error('验证失败，请刷新页面后重试');
        die();
    }
    
    // 检查是否有文件上传
    if (empty($_FILES['file'])) {
        wp_send_json_error('没有选择文件');
        die();
    }
    
    // 添加更详细的错误处理和日志
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error_message = '上传失败，错误代码: ' . $_FILES['file']['error'];
        switch($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = '文件太大，超出系统限制';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = '文件只上传了部分';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = '没有文件被上传';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = '临时文件夹不存在';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = '无法写入磁盘';
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = '上传被扩展程序中断';
                break;
        }
        wp_send_json_error($error_message);
        die();
    }
    
    // 获取文件类型
    $filetype = wp_check_filetype(basename($_FILES['file']['name']));
    if (!$filetype['type']) {
        wp_send_json_error('无法确定文件类型');
        die();
    }
    
    // 允许的文件类型 - 从设置中获取
    $custom_allowed_types = get_option('imghosting_allowed_types', array('jpg', 'jpeg', 'png', 'gif', 'webp'));
    
    // 构建允许的mime类型数组
    $allowed_types = array();
    foreach ($custom_allowed_types as $ext) {
        if ($ext == 'jpg' || $ext == 'jpeg') {
            $allowed_types[] = 'image/jpeg';
        } else if ($ext == 'png') {
            $allowed_types[] = 'image/png';
        } else if ($ext == 'gif') {
            $allowed_types[] = 'image/gif';
        } else if ($ext == 'webp') {
            $allowed_types[] = 'image/webp';
        } else if ($ext == 'bmp') {
            $allowed_types[] = 'image/bmp';
        } else if ($ext == 'tiff') {
            $allowed_types[] = 'image/tiff';
        }
    }
    
    // 如果没有设置允许的类型，使用默认值
    if (empty($allowed_types)) {
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    }
    
    // 检查文件类型
    if (!in_array($_FILES['file']['type'], $allowed_types)) {
        wp_send_json_error('不支持的文件类型：' . $_FILES['file']['type'] . '。请上传以下格式图片：' . implode(', ', $custom_allowed_types));
        die();
    }
    
    // 检查文件大小
    $max_size = intval(get_option('imghosting_max_upload_size', 10)) * 1024 * 1024;
    if ($_FILES['file']['size'] > $max_size) {
        wp_send_json_error(sprintf('文件太大，最大允许大小为 %s', size_format($max_size)));
        die();
    }
    
    // 检查访客上传权限
    if (!is_user_logged_in() && !imghosting_can_guest_upload()) {
        wp_send_json_error('访客上传图片功能已禁用，请登录后再试。');
        die();
    }
    
    // 处理文件上传
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    // 获取访客ID
    $visitor_id = imghosting_get_visitor_id();
    
    try {
        // 设置上传参数，将图片与当前访客关联
        $attachment = array(
            'post_author' => is_user_logged_in() ? get_current_user_id() : 0
        );
        
        // 确保正确捕获并处理异常
        $file_id = media_handle_upload('file', 0, $attachment);
        
        if (is_wp_error($file_id)) {
            wp_send_json_error('上传失败: ' . $file_id->get_error_message());
            die();
        }
        
        // 添加访客ID到图片元数据中
        update_post_meta($file_id, '_imghosting_visitor_id', $visitor_id);
        
        // 获取图片链接
        $file_url = wp_get_attachment_url($file_id);
        $thumbnail_url = wp_get_attachment_thumb_url($file_id);
        
        // 获取图片尺寸
        $image_meta = wp_get_attachment_metadata($file_id);
        $dimensions = isset($image_meta['width']) && isset($image_meta['height']) ? 
                     $image_meta['width'] . ' × ' . $image_meta['height'] : '未知尺寸';
        
        // 获取文件大小
        $file_size = filesize(get_attached_file($file_id));
        $formatted_size = size_format($file_size);
        
        // 准备返回数据
        $response = array(
            'success' => true,
            'id' => $file_id,
            'url' => $file_url,
            'thumbnail' => $thumbnail_url,
            'html_link' => '<img src="' . esc_url($file_url) . '" alt="Uploaded Image">',
            'markdown_link' => '![Uploaded Image](' . esc_url($file_url) . ')',
            'bbcode_link' => '[img]' . esc_url($file_url) . '[/img]',
            'dimensions' => $dimensions,
            'size' => $formatted_size,
            'file_name' => basename($file_url),
            'type' => $_FILES['file']['type'],
            'date_uploaded' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'))
        );
        
        wp_send_json_success($response);
    } catch (Exception $e) {
        wp_send_json_error('上传处理时出错：' . $e->getMessage());
    }
    
    die();
}
add_action('wp_ajax_imghosting_upload', 'imghosting_handle_upload');
add_action('wp_ajax_nopriv_imghosting_upload', 'imghosting_handle_upload');

// 处理图片删除的AJAX请求
if (!function_exists('imghosting_delete_image')) {
    function imghosting_delete_image() {
        // 验证 nonce
        check_ajax_referer('imghosting_delete_image', 'nonce');

        // 验证用户权限
        if (!current_user_can('delete_posts')) {
            wp_send_json_error(array('message' => '权限不足'));
        }

        // 验证并删除图片
        $image_id = intval($_POST['id']);
        if (get_post_type($image_id) === 'attachment') {
            if (wp_delete_attachment($image_id, true)) {
                wp_send_json_success(array('message' => '图片已删除'));
            } else {
                wp_send_json_error(array('message' => '删除失败'));
            }
        } else {
            wp_send_json_error(array('message' => '无效的图片ID'));
        }
    }
    add_action('wp_ajax_imghosting_delete_image', 'imghosting_delete_image');
}
add_action('wp_ajax_nopriv_imghosting_delete_image', 'imghosting_delete_image');
