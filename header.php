<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <meta name="description" content="<?php bloginfo('description'); ?>">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="site-header">
    <div class="header-container">
        <div class="site-branding">
            <?php if (has_custom_logo()): ?>
                <div class="site-logo"><?php the_custom_logo(); ?></div>
            <?php else: ?>
                <h1 class="site-title">
                    <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                        <?php bloginfo('name'); ?>
                    </a>
                </h1>
            <?php endif; ?>

            <!-- 移除副标题的显示 -->
        </div>

        <nav id="site-navigation" class="main-navigation">
            <button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
                <span class="menu-icon"></span>
                <span class="menu-text">菜单</span>
            </button>
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'menu_id'        => 'primary-menu',
                'container'      => false,
                'fallback_cb'    => function() {
                    echo '<ul id="primary-menu" class="menu"><li><a href="' . admin_url('nav-menus.php') . '">创建菜单</a></li></ul>';
                },
            ));
            ?>
        </nav>

        <div class="user-navigation">
            <?php if (is_user_logged_in()): ?>
                <?php $current_user = wp_get_current_user(); ?>
                <div class="user-dropdown">
                    <button class="user-dropdown-btn">
                        <span class="username"><?php echo esc_html($current_user->display_name); ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="dropdown-arrow">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="user-dropdown-content">
                        <a href="<?php echo get_permalink(get_option('imghosting_profile_page_id')); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            用户资料
                        </a>
                        <?php if (current_user_can('manage_options')): ?>
                        <a href="<?php echo admin_url(); ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>
                            控制面板
                        </a>
                        <?php endif; ?>

                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout-link">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            注销
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="<?php echo wp_login_url(); ?>" class="btn btn-sm">登录</a>
                    <a href="<?php echo wp_registration_url(); ?>" class="btn btn-sm btn-primary">注册</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 移除错误的body和html闭合标签，这些应该在footer.php文件中 -->
