<?php
/**
 * 加载脚本和样式
 */

// 加载脚本和样式
function imghosting_scripts() {
    // 主样式
    wp_enqueue_style('imghosting-style', get_stylesheet_uri(), array(), '1.0');
    wp_enqueue_style('imghosting-custom-style', get_template_directory_uri() . '/css/style.css', array(), '1.0');

    // 特定页面增强样式
    if (is_page_template('page-gallery.php')) {
        wp_enqueue_style('imghosting-animations-extended', get_template_directory_uri() . '/css/styles/animations-extended.css', array(), '1.0');
    }
    
    // 核心JavaScript文件
    wp_enqueue_script('imghosting-navigation', get_template_directory_uri() . '/js/navigation.js', array('jquery'), '1.0', true);
    wp_enqueue_script('imghosting-upload', get_template_directory_uri() . '/js/upload.js', array('jquery'), '1.0', true);

    // 条件加载画廊和批量操作脚本
    if (is_page_template('page-gallery.php')) {
        wp_enqueue_script('imghosting-gallery', get_template_directory_uri() . '/js/gallery.js', array('jquery'), '1.0', true);
        wp_enqueue_script('imghosting-batch-operations', get_template_directory_uri() . '/js/batch-operations.js', array('jquery'), '1.0', true);
    }

    // 本地化脚本变量 - 保留主要变量，删除调试相关变量
    wp_localize_script('imghosting-upload', 'imghosting_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('imghosting_upload_nonce'),
        'max_file_size' => wp_max_upload_size(),
        'home_url' => home_url(),
    ));
    
    // 批量操作变量 - 删除调试相关变量
    if (is_page_template('page-gallery.php')) {
        wp_localize_script('imghosting-batch-operations', 'imghosting_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'batch_nonce' => wp_create_nonce('imghosting_batch_operation'),
            'home_url' => home_url(),
        ));
    }
}
add_action('wp_enqueue_scripts', 'imghosting_scripts');

/**
 * 加载管理员脚本和样式
 */
function imghosting_admin_scripts() {
    $screen = get_current_screen();
    
    // 只在主题设置页面加载
    if ($screen && $screen->id == 'appearance_page_imghosting-settings') {
        wp_enqueue_style('imghosting-admin-style', get_template_directory_uri() . '/css/admin-style.css', array(), '1.0');
        wp_enqueue_script('imghosting-admin-script', get_template_directory_uri() . '/js/admin.js', array('jquery'), '1.0', true);
    }
}
add_action('admin_enqueue_scripts', 'imghosting_admin_scripts');
