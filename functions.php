<?php
// 引入用户资料页面相关文件
require_once get_template_directory() . '/inc/profile.php';

/**
 * ImgHosting 主题函数文件
 */

// 启动输出缓冲，防止"headers already sent"错误
if (!headers_sent() && !ob_get_level()) {
    ob_start();
}

// 引入所有子文件
require_once get_template_directory() . '/inc/setup.php';     // 主题设置
require_once get_template_directory() . '/inc/scripts.php';   // 脚本和样式
require_once get_template_directory() . '/inc/upload.php';    // 上传处理
require_once get_template_directory() . '/inc/cookies.php';   // Cookie和访客ID
require_once get_template_directory() . '/inc/batch-upload.php'; // 批量上传
require_once get_template_directory() . '/inc/settings.php';  // 主题设置页面
require_once get_template_directory() . '/inc/login.php';     // 登录/注册页面
require_once get_template_directory() . '/inc/batch-operations.php'; // 批量操作功能

// 确保允许用户注册 (如果管理员允许)
function imghosting_enable_user_registration() {
    // 此函数仅作为提示，实际控制是通过WordPress后台设置
    // 在WordPress管理后台 > 设置 > 常规 > 成员资格中启用"任何人都可以注册"
}

// 修改默认用户角色为订阅者，确保安全
function imghosting_set_default_role() {
    return 'subscriber';
}
add_filter('pre_option_default_role', 'imghosting_set_default_role');

// 加载自定义图床设置页面样式
function imghosting_custom_settings_styles($hook) {
    // 验证页面钩子，防止不必要的样式加载
    if ($hook === 'settings_page_imghosting-settings') {
        wp_enqueue_style('imghosting-settings-style', get_template_directory_uri() . '/css/settings-style.css', array(), '1.0.0');
    }
}
add_action('admin_enqueue_scripts', 'imghosting_custom_settings_styles');

?>
