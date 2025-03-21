<?php
/**
 * 登录页面自定义
 */

// 使用主题登录页样式
function imghosting_custom_login_stylesheet() {
    wp_enqueue_style('imghosting-login-style', get_template_directory_uri() . '/css/login-style.css');
}
add_action('login_enqueue_scripts', 'imghosting_custom_login_stylesheet');

// 修改登录页LOGO链接
function imghosting_login_logo_url() {
    return home_url();
}
add_filter('login_headerurl', 'imghosting_login_logo_url');

// 修改登录页LOGO标题
function imghosting_login_logo_url_title() {
    return get_bloginfo('name') . ' - ' . get_bloginfo('description');
}
add_filter('login_headertext', 'imghosting_login_logo_url_title');

// 登录后重定向到首页
function imghosting_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        // 管理员重定向到管理后台
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        } else {
            // 普通用户重定向到首页
            return home_url('/');
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'imghosting_login_redirect', 10, 3);

// 注册完成后重定向到首页
function imghosting_registration_redirect() {
    return home_url('/');
}
add_filter('registration_redirect', 'imghosting_registration_redirect');

// 注意: 保持WordPress默认注册表单完整，不添加干扰代码
// WordPress默认注册表单已包含密码字段
