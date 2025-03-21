<?php
/**
 * 主题设置页面和选项
 */

// 添加主菜单"图床设置"
function imghosting_add_admin_menu() {
    add_menu_page(
        '图床主题设置',     // 页面标题
        '图床设置',         // 菜单标题
        'manage_options',   // 所需权限
        'imghosting-settings', // 菜单slug
        'imghosting_theme_settings_page', // 回调函数
        'dashicons-images-alt2', // 图标
        60  // 菜单位置
    );
}
add_action('admin_menu', 'imghosting_add_admin_menu');

// 主题设置页面内容
function imghosting_theme_settings_page() {  
    ?>
    <div class="wrap">
        <h1>图床主题设置</h1>
        <form method="post" action="options.php">
            <?php
                settings_fields('imghosting_settings_group');
                do_settings_sections('imghosting-settings');
                submit_button();
            ?>
        </form>
    </div>
    <?php
}

// 注册设置选项
if (!function_exists('imghosting_register_settings')) {
    function imghosting_register_settings() {
        // 注册访客上传开关
        register_setting('imghosting_settings_group', 'imghosting_allow_guest_uploads');
        
        // 注册最大上传大小
        register_setting('imghosting_settings_group', 'imghosting_max_upload_size');
        
        // 注册允许的文件类型
        register_setting('imghosting_settings_group', 'imghosting_allowed_types');
        
        // 注册用户资料页面选择
        register_setting('imghosting_settings_group', 'imghosting_profile_page_id');
        
        // 添加设置部分
        add_settings_section(
            'imghosting_general_settings',
            '图床设置',
            function() {
                echo '<p>配置图床的基本功能。</p>';
            },
            'imghosting-settings'
        );

        // 添加访客上传开关字段
        add_settings_field(
            'imghosting_allow_guest_uploads',
            '允许访客上传图片',
            function() {
                $value = get_option('imghosting_allow_guest_uploads', 'no');
                ?>
                <label>
                    <input type="checkbox" name="imghosting_allow_guest_uploads" value="yes" <?php checked($value, 'yes'); ?>>
                    启用
                </label>
                <?php
            },
            'imghosting-settings',
            'imghosting_general_settings'
        );

        // 添加最大上传大小字段
        add_settings_field(
            'imghosting_max_upload_size',
            '最大上传大小 (MB)',
            function() {
                $value = get_option('imghosting_max_upload_size', 10);
                echo '<input type="number" id="imghosting_max_upload_size" name="imghosting_max_upload_size" value="' . esc_attr($value) . '" min="1" max="50"> MB';
            },
            'imghosting-settings',
            'imghosting_general_settings'
        );

        // 添加允许的文件类型字段
        add_settings_field(
            'imghosting_allowed_types',
            '允许的文件类型',
            function() {
                $allowed_types = get_option('imghosting_allowed_types', array('jpg', 'jpeg', 'png', 'gif', 'webp'));
                $types = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff');
                
                foreach ($types as $type) {
                    $checked = in_array($type, $allowed_types) ? 'checked' : '';
                    echo '<label style="margin-right:15px;"><input type="checkbox" name="imghosting_allowed_types[]" value="' . $type . '" ' . $checked . '> ' . strtoupper($type) . '</label>';
                }
            },
            'imghosting-settings',
            'imghosting_general_settings'
        );

        // 添加用户资料页面选择字段
        add_settings_field(
            'imghosting_profile_page_id',
            '用户资料页面',
            function() {
                $profile_page_id = get_option('imghosting_profile_page_id');
                $pages = get_pages();
                
                echo '<select name="imghosting_profile_page_id" id="imghosting_profile_page_id">';
                echo '<option value="">请选择页面</option>';
                
                foreach ($pages as $page) {
                    $selected = ($profile_page_id == $page->ID) ? 'selected' : '';
                    echo '<option value="' . $page->ID . '" ' . $selected . '>' . $page->post_title . '</option>';
                }
                
                echo '</select>';
                echo '<p class="description">选择一个页面作为用户资料页面。该页面应使用"用户资料"模板。</p>';
            },
            'imghosting-settings',
            'imghosting_general_settings'
        );
    }
    add_action('admin_init', 'imghosting_register_settings');
}

// 确保访客上传权限检查函数唯一
if (!function_exists('imghosting_can_guest_upload')) {
    function imghosting_can_guest_upload() {
        return get_option('imghosting_allow_guest_uploads', 'no') === 'yes';
    }
}
?>
