<?php
/**
 * 处理访客ID和Cookie功能
 */

// 生成或获取访客唯一ID
function imghosting_get_visitor_id() {
    // 已登录用户使用用户ID
    if (is_user_logged_in()) {
        return 'user_' . get_current_user_id();
    }
    
    // 使用Cookie存储访客ID
    $cookie_name = 'imghosting_visitor_id';
    
    if (isset($_COOKIE[$cookie_name])) {
        return sanitize_text_field($_COOKIE[$cookie_name]);
    } else {
        // 创建唯一访客ID
        $visitor_id = 'visitor_' . uniqid() . '_' . substr(md5(time() . rand()), 0, 10);
        
        // 设置Cookie，有效期30天
        if (!headers_sent()) {
            setcookie(
                $cookie_name,
                $visitor_id,
                time() + (30 * DAY_IN_SECONDS),
                COOKIEPATH,
                COOKIE_DOMAIN,
                is_ssl(),
                true
            );
        }
        
        return $visitor_id;
    }
}

// 检查用户是否有权访问特定图片
function imghosting_can_access_image($attachment_id) {
    // 管理员可以访问所有图片
    if (current_user_can('manage_options')) {
        return true;
    }
    
    $current_visitor_id = imghosting_get_visitor_id();
    $image_owner = get_post_meta($attachment_id, '_imghosting_visitor_id', true);
    
    // 图片所有者可以访问
    return $image_owner === $current_visitor_id;
}

// 清除访客cookie
function imghosting_clear_visitor_cookie() {
    if (isset($_COOKIE['imghosting_visitor_id']) && !headers_sent()) {
        setcookie(
            'imghosting_visitor_id',
            '',
            time() - 3600,
            COOKIEPATH,
            COOKIE_DOMAIN,
            is_ssl(),
            true
        );
    }
}

// 用户登录时将访客上传的图片关联到用户账号
function imghosting_associate_uploads_on_login($user_login, $user) {
    if (!$user || !$user->ID) return;
    
    // 检查是否有访客ID
    if (!isset($_COOKIE['imghosting_visitor_id'])) return;
    
    $visitor_id = sanitize_text_field($_COOKIE['imghosting_visitor_id']);
    
    // 查找与此访客ID关联的所有图片
    $args = array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_imghosting_visitor_id',
                'value' => $visitor_id,
                'compare' => '='
            )
        )
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        foreach ($query->posts as $attachment) {
            // 更新元数据，关联到用户
            update_post_meta($attachment->ID, '_imghosting_visitor_id', 'user_' . $user->ID);
            
            // 设置附件的作者为该用户
            wp_update_post(array(
                'ID' => $attachment->ID,
                'post_author' => $user->ID
            ));
        }
    }
    
    // 清除访客cookie，将使用用户ID代替
    imghosting_clear_visitor_cookie();
}
add_action('wp_login', 'imghosting_associate_uploads_on_login', 10, 2);

// 添加前端访客ID脚本，确保JavaScript也能获取访客ID
function imghosting_visitor_id_script() {
    $visitor_id = imghosting_get_visitor_id();
    ?>
    <script>
    // 确保访客ID在前端可用
    var imghosting_visitor = {
        id: '<?php echo esc_js($visitor_id); ?>',
        isLoggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>
    };
    </script>
    <?php
}
add_action('wp_head', 'imghosting_visitor_id_script');
