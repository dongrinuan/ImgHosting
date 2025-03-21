<?php get_header(); ?>

<div class="container">
    <div class="error-404-container">
        <div class="error-icon">404</div>
        <h1>页面未找到</h1>
        <p>很抱歉，您访问的页面不存在或已被删除。</p>
        <div class="error-actions">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                返回首页
            </a>
            <a href="<?php echo esc_url(home_url('/gallery/')); ?>" class="btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                    <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                查看图库
            </a>
        </div>
    </div>
</div>

<?php get_footer(); ?>
