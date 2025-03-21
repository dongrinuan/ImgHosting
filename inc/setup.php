<?php
// 主题设置
function imghosting_setup() {
    // 添加主题支持
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height' => 60,
        'width' => 200,
        'flex-height' => true,
        'flex-width' => true,
    ));

    // 注册菜单
    register_nav_menus(array(
        'primary' => __('主导航菜单', 'imghosting'),
        'footer' => __('页脚菜单', 'imghosting'),
    ));
}
add_action('after_setup_theme', 'imghosting_setup');
