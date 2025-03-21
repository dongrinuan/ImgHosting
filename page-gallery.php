<?php
/**
 * Template Name: 图片画廊
 */
get_header();

// 获取当前用户/访客ID
$current_visitor_id = imghosting_get_visitor_id();
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">我的图片</h1>
        <p class="page-description">
            <?php 
            if (is_user_logged_in()) {
                echo '您的图片集合，可以管理和分享';
            } else {
                echo '通过Cookie记录的您上传的图片';
            }
            ?>
        </p>
        
        <?php if (!is_user_logged_in()): ?>
        <div class="info-message">
            <p><strong>提示：</strong> 当前您是通过浏览器Cookie识别的访客身份。
            <?php if (get_option('users_can_register')): ?>
                <a href="<?php echo wp_registration_url(); ?>">注册账号</a>可以永久保存和管理您上传的所有图片。
            <?php else: ?>
                登录账号可以永久保存和管理您上传的所有图片。
            <?php endif; ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="filter-tools">
        <div class="filter-controls">
            <div class="filter-search">
                <input type="text" id="gallery-search" placeholder="搜索图片...">
            </div>
            <div class="filter-actions">
                <!-- 增加批量选择按钮 -->
                <button id="batch-select-toggle" class="btn btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    批量选择
                </button>
                <!-- 删除了布局切换按钮 -->
            </div>
        </div>
        
        <!-- 批量操作工具栏 - 初始隐藏 -->
        <div id="batch-actions-toolbar" class="batch-actions-toolbar hidden">
            <div class="batch-counter">已选择 <span id="selected-count">0</span> 个项目</div>
            <div class="batch-buttons">
                <button id="batch-copy" class="btn btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                    批量复制
                </button>
                <button id="batch-delete" class="btn btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    批量删除
                </button>
                <button id="batch-select-all" class="btn btn-sm">全选</button>
                <button id="batch-cancel" class="btn btn-sm">取消</button>
            </div>
        </div>
    </div>
    
    <!-- 修改画廊容器，移除布局类 -->
    <div class="image-gallery" id="gallery-container">
        <?php
        // 获取当前用户/访客的图片附件
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => array('image/jpeg', 'image/png', 'image/gif', 'image/webp'),
            'post_status' => 'inherit',
            'posts_per_page' => 24,
            'orderby' => 'date',
            'order' => 'DESC',
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
            'meta_query' => array(
                array(
                    'key' => '_imghosting_visitor_id',
                    'value' => $current_visitor_id,
                    'compare' => '='
                )
            )
        );
        
        // 管理员可以查看所有图片的选项
        if (current_user_can('manage_options') && isset($_GET['view']) && $_GET['view'] === 'all') {
            unset($args['meta_query']);
        }
        
        $query = new WP_Query($args);
        
        if ($query->have_posts()) :
            while ($query->have_posts()) : $query->the_post();
                $attachment_id = get_the_ID();
                $image_url = wp_get_attachment_url();
                $image_thumb = wp_get_attachment_image_src($attachment_id, 'medium')[0];
                $image_title = get_the_title();
                $image_date = get_the_date();
                $image_meta = wp_get_attachment_metadata();
                $dimensions = '';
                
                if (isset($image_meta['width']) && isset($image_meta['height'])) {
                    $dimensions = $image_meta['width'] . ' × ' . $image_meta['height'];
                }
                
                // 生成随机偏移以创建瀑布流效果
                $delay = (get_the_ID() % 10) * 0.05;
        ?>
                <div class="gallery-item" data-id="<?php echo esc_attr($attachment_id); ?>" style="animation-delay: <?php echo $delay; ?>s">
                    <!-- 添加复选框用于批量选择 -->
                    <div class="item-checkbox hidden">
                        <input type="checkbox" class="select-checkbox" data-id="<?php echo esc_attr($attachment_id); ?>" data-url="<?php echo esc_url($image_url); ?>">
                        <span class="checkmark"></span>
                    </div>
                    
                    <img src="<?php echo esc_url($image_thumb); ?>" alt="<?php echo esc_attr($image_title); ?>" class="gallery-img">
                    <div class="gallery-overlay">
                        <h3 class="gallery-title"><?php echo esc_html($image_title); ?></h3>
                        <p class="gallery-meta"><?php echo esc_html($dimensions); ?> • <?php echo esc_html($image_date); ?></p>
                    </div>
                    <div class="gallery-actions">
                        <a href="<?php echo esc_url($image_url); ?>" class="btn btn-sm" title="查看原图" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                            查看
                        </a>
                        <button class="btn btn-sm copy-link" data-url="<?php echo esc_url($image_url); ?>" title="复制链接">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
                            复制
                        </button>
                        <button class="btn btn-sm delete-image" data-id="<?php echo esc_attr($attachment_id); ?>" title="删除图片">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                            删除
                        </button>
                    </div>
                </div>
        <?php
            endwhile;
            
            // 分页导航
            echo '<div class="pagination">';
            echo paginate_links(array(
                'total' => $query->max_num_pages,
                'current' => max(1, get_query_var('paged')),
                'prev_text' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>',
                'next_text' => '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>',
            ));
            echo '</div>';
            
            wp_reset_postdata();
        else :
        ?>
            <div class="empty-gallery">
                <div class="empty-icon"></div>
                <h3>暂无图片</h3>
                <p>您还没有上传任何图片，快去上传您的第一张图片吧！</p>
                <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">前往上传</a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if(current_user_can('manage_options')): ?>
    <div class="admin-options">
        <div class="admin-notice">
            <p><strong>管理员功能:</strong> 您正在查看
            <?php if(isset($_GET['view']) && $_GET['view'] === 'all'): ?>
                所有用户的图片。<a href="<?php echo esc_url(remove_query_arg('view')); ?>">只查看我的图片</a>
            <?php else: ?>
                您自己的图片。<a href="<?php echo esc_url(add_query_arg('view', 'all')); ?>">查看所有用户的图片</a>
            <?php endif; ?>
            </p>
        </div>
    </div>
    <?php endif; ?>
</div>


        </div>
    </div>
</div>

<!-- 添加批量复制链接格式选择弹窗 -->
<div id="batch-copy-modal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3>批量复制链接</h3>
        <p>为所选的 <span id="copy-count"></span> 张图片选择链接格式：</p>
        
        <div class="link-tabs">
            <button class="link-tab active" data-target="direct-links">直接链接</button>
            <button class="link-tab" data-target="html-links">HTML代码</button>
            <button class="link-tab" data-target="markdown-links">Markdown</button>
            <button class="link-tab" data-target="bbcode-links">BBCode</button>
        </div>
        
        <div class="link-pane active" id="direct-links">
            <textarea id="batch-direct-links" class="batch-links-textarea" readonly></textarea>
        </div>
        <div class="link-pane" id="html-links">
            <textarea id="batch-html-links" class="batch-links-textarea" readonly></textarea>
        </div>
        <div class="link-pane" id="markdown-links">
            <textarea id="batch-markdown-links" class="batch-links-textarea" readonly></textarea>
        </div>
        <div class="link-pane" id="bbcode-links">
            <textarea id="batch-bbcode-links" class="batch-links-textarea" readonly></textarea>
        </div>
        
        <div class="modal-actions">
            <button id="copy-selected-links" class="btn btn-primary">复制所选格式</button>
            <button class="btn close-modal">关闭</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // 为所有图片项添加淡入动画
    $('.gallery-item').each(function() {
        $(this).addClass('fade-in-animation');
    });
    
    // 清除所有可能的事件冲突
    $(document).off('click.modalClose');
    $(document).off('click.previewHandler');
    $('.gallery-item').off('click');
    $('.select-checkbox').off('click');
    $('.item-checkbox').off('click');
    $('.copy-url').off('click');
    $('.delete-image').off('click');
    
    // 批量选择功能定义
    let batchSelectMode = false;
    let selectedItems = [];
    
    // 确保图片点击事件仅在批量选择模式下有效
    $(document).on('click', '.gallery-item', function(e) {
        // 只在批量选择模式下处理点击事件
        if (!batchSelectMode || 
            $(e.target).closest('.gallery-actions').length || 
            $(e.target).is('button') ||
            $(e.target).is('a') ||
            $(e.target).is('input')) {
            return;
        }
        
        // 在选择模式下切换选择状态
        const checkbox = $(this).find('.select-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });
    
    // 复选框事件处理
    $(document).on('click', '.select-checkbox', function(e) {
        e.stopPropagation();
    });
    
    // 复选框容器的点击事件
    $(document).on('click', '.item-checkbox', function(e) {
        e.stopPropagation();
        
        // 手动切换复选框状态
        const checkbox = $(this).find('.select-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });
    
    // 模态框相关事件处理
    $(document).on('click.modalClose', '.close-modal', function(e) {
        $(this).closest('.modal').fadeOut(300);
        e.stopPropagation();
    });
    
    $(document).on('click', '.modal', function(e) {
        if ($(e.target).is('.modal')) {
            $(this).fadeOut(300);
        }
    });
    
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.modal:visible').fadeOut(300);
        }
    });

    // 搜索功能
    $('#gallery-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.gallery-item').each(function() {
            const title = $(this).find('.gallery-title').text().toLowerCase();
            if (title.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
    
    // 修复：使用直接事件绑定确保复制URL功能正常工作
    $(document).on('click', '.copy-url', function() {
        const url = $(this).data('url');
        const $btn = $(this);
        const originalText = $btn.html();
        
        console.log('复制按钮被点击，URL:', url); // 调试日志
        
        // 使用现代的Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url)
                .then(() => {
                    $btn.html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><polyline points="20 6 9 17 4 12"></polyline></svg>已复制!').addClass('btn-success');
                    
                    setTimeout(() => {
                        $btn.html(originalText).removeClass('btn-success');
                    }, 1500);
                })
                .catch(() => {
                    // 如果剪贴板API失败，回退到传统方法
                    fallbackCopy(url, $btn, originalText);
                });
        } else {
            fallbackCopy(url, $btn, originalText);
        }
    });
    
    // 传统复制方法
    function fallbackCopy(text, $btn, originalText) {
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val(text).select();
        
        try {
            const success = document.execCommand('copy');
            if (success) {
                $btn.html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><polyline points="20 6 9 17 4 12"></polyline></svg>已复制!').addClass('btn-success');
            } else {
                $btn.html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>复制失败').addClass('btn-error');
            }
        } catch (err) {
            $btn.html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>复制失败').addClass('btn-error');
        }
        
        setTimeout(() => {
            $btn.html(originalText).removeClass('btn-success btn-error');
        }, 1500);
        
        $temp.remove();
    }
    
    // 修复：使用直接事件绑定确保删除图片功能正常工作
    $(document).on('click', '.delete-image', function() {
        currentDeleteId = $(this).data('id');
        console.log('删除按钮被点击，ID:', currentDeleteId); // 调试日志
        $('#delete-modal').fadeIn(300);
    });
    
    // 删除图片功能
    let currentDeleteId = null;
    
    // 确认删除 - 修复AJAX请求
    $('#confirm-delete').on('click', function() {
        if (!currentDeleteId) {
            console.log('错误：currentDeleteId为空'); // 调试日志
            return;
        }
        
        const $btn = $(this);
        const originalText = $btn.text();
        const $galleryItem = $(`.gallery-item[data-id="${currentDeleteId}"]`);
        
        // 显示加载状态
        $btn.prop('disabled', true).html(`
            <div class="spinner">
                <div class="bounce1"></div>
                <div class="bounce2"></div>
                <div class="bounce3"></div>
            </div>
            删除中...
        `);
        $galleryItem.addClass('deleting').css('opacity', 0.5);
        
        // 发送删除请求
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'imghosting_delete_image',
                id: currentDeleteId,
                nonce: '<?php echo wp_create_nonce('imghosting_delete_image'); ?>'
            },
            success: function(response) {
                console.log('删除请求响应:', response); // 调试日志
                
                $('#delete-modal').fadeOut(300);
                $btn.prop('disabled', false).text(originalText);
                
                if (response.success) {
                    // 成功删除 - 从界面上移除图片
                    $galleryItem.slideUp(300, function() {
                        $(this).remove();
                        
                        // 如果所有图片都已删除，显示空画廊信息
                        if ($('.gallery-item').length === 0) {
                            $('#gallery-container').html(`
                                <div class="empty-gallery">
                                    <div class="empty-icon"></div>
                                    <h3>暂无图片</h3>
                                    <p>您还没有上传任何图片，快去上传您的第一张图片吧！</p>
                                    <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">前往上传</a>
                                </div>
                            `);
                        }
                    });
                    
                    // 显示成功消息
                    const successMsg = $('<div class="success-message">图片已成功删除</div>');
                    $('.page-header').after(successMsg);
                    
                    setTimeout(() => {
                        successMsg.fadeOut(500, function() {
                            $(this).remove();
                        });
                    }, 3000);
                } else {
                    $galleryItem.removeClass('deleting').css('opacity', 1);
                    
                    // 显示错误消息并包含更多详细信息
                    let errorMessage = '删除失败';
                    if (response.data) {
                        errorMessage += ': ' + (typeof response.data === 'string' ? response.data : JSON.stringify(response.data));
                    }
                    
                    const errorMsg = $('<div class="error-message">' + errorMessage + '</div>');
                    $('.page-header').after(errorMsg);
                    
                    setTimeout(() => {
                        errorMsg.fadeOut(500, function() {
                            $(this).remove();
                        });
                    }, 3000);
                }
                
                currentDeleteId = null;
            },
            error: function(xhr, status, error) {
                console.error('删除请求失败:', xhr.responseText); // 调试日志
                
                $('#delete-modal').fadeOut(300);
                $galleryItem.removeClass('deleting').css('opacity', 1);
                $btn.prop('disabled', false).text(originalText);
                
                // 显示详细的错误信息
                let errorMessage = '删除时发生错误，请稍后重试';
                if (xhr.responseText) {
                    errorMessage += ' (服务器响应: ' + xhr.status + ' ' + error + ')';
                }
                
                const errorMsg = $('<div class="error-message">' + errorMessage + '</div>');
                $('.page-header').after(errorMsg);
                
                setTimeout(() => {
                    errorMsg.fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 3000);
                
                currentDeleteId = null;
            }
        });
    });
    
    // 图片加载错误处理
    $('.gallery-img').on('error', function() {
        // 当图片加载失败时，使用默认占位图
        $(this).attr('src', '<?php echo get_template_directory_uri(); ?>/images/placeholder.png');
    });
    
    // 图片加载完成后处理
    $('.gallery-img').on('load', function() {
        // 移除加载状态类
        $(this).parent('.gallery-item').addClass('loaded');
    });
    
    // 批量选择功能
    let batchSelectMode = false;
    let selectedItems = [];
    
    // 切换批量选择模式
    $('#batch-select-toggle').on('click', function() {
        batchSelectMode = !batchSelectMode;
        
        if (batchSelectMode) {
            // 启用批量选择模式
            $(this).addClass('btn-active').text('取消选择');
            $('.item-checkbox').removeClass('hidden');
            $('#batch-actions-toolbar').removeClass('hidden');
            $('.gallery-item').addClass('select-mode');
        } else {
            // 禁用批量选择模式
            $(this).removeClass('btn-active').html(`
        // 添加过渡动画效果
        $('#gallery-container').addClass('layout-transition');
        setTimeout(() => {
            $('#gallery-container').removeClass('layout-transition');
        }, 500);
    });
    
    // 加载用户的布局偏好
    const savedLayout = localStorage.getItem('preferred_gallery_layout');
    // 仅加载有效的布局类型
    if(savedLayout && (savedLayout === 'compact-layout' || savedLayout === 'list-layout')) {
        $(`.layout-button[data-layout="${savedLayout}"]`).click();
    }
    
    // 图片加载错误处理
    $('.gallery-img').on('error', function() {
        // 当图片加载失败时，使用默认占位图
        $(this).attr('src', '<?php echo get_template_directory_uri(); ?>/images/placeholder.png');
    });
    
    // 图片加载完成后处理
    $('.gallery-img').on('load', function() {
        // 移除加载状态类
        $(this).parent('.gallery-item').addClass('loaded');
    });
    
    // 批量选择功能
    let batchSelectMode = false;
    let selectedItems = [];
    
    // 切换批量选择模式
    $('#batch-select-toggle').on('click', function() {
        batchSelectMode = !batchSelectMode;
        
        if (batchSelectMode) {
            // 启用批量选择模式
            $(this).addClass('btn-active').text('取消选择');
            $('.item-checkbox').removeClass('hidden');
            $('#batch-actions-toolbar').removeClass('hidden');
            $('.gallery-item').addClass('select-mode');
        } else {
            // 禁用批量选择模式
            $(this).removeClass('btn-active').html(`
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                批量选择
            `);
            $('.item-checkbox').addClass('hidden');
            $('#batch-actions-toolbar').addClass('hidden');
            $('.gallery-item').removeClass('select-mode');
            
            // 清除所有选择
            $('.select-checkbox').prop('checked', false);
            selectedItems = [];
            updateSelectedCount();
        }
    });
    
    // 取消批量选择
    $('#batch-cancel').on('click', function() {
        $('#batch-select-toggle').click();
    });
    
    // 全选按钮
    $('#batch-select-all').on('click', function() {
        const checkboxes = $('.select-checkbox');
        const allChecked = checkboxes.length === selectedItems.length;
        
        checkboxes.prop('checked', !allChecked);
        
        if (!allChecked) {
            // 全选
            selectedItems = [];
            checkboxes.each(function() {
                selectedItems.push({
                    id: $(this).data('id'),
                    url: $(this).data('url')
                });
            });
        } else {
            // 取消全选
            selectedItems = [];
        }
        
        updateSelectedCount();
    });
    
    // 点击复选框选择图片
    $(document).on('change', '.select-checkbox', function() {
        const id = $(this).data('id');
        const url = $(this).data('url');
        
        if ($(this).is(':checked')) {
            // 添加到选中列表
            selectedItems.push({ id, url });
        } else {
            // 从选中列表移除
            selectedItems = selectedItems.filter(item => item.id != id);
        }
        
        updateSelectedCount();
    });
    
    // 更新已选择数量显示
    function updateSelectedCount() {
        const count = selectedItems.length;
        $('#selected-count').text(count);
        
        // 根据选择数量启用或禁用批量操作按钮
        if (count > 0) {
            $('#batch-copy-links, #batch-delete').prop('disabled', false);
        } else {
            $('#batch-copy-links, #batch-delete').prop('disabled', true);
        }
    }
    
    // 点击图片项的任意区域可以切换选择状态（在选择模式下）
    $(document).on('click', '.gallery-item.select-mode', function(e) {
        // 不触发gallery-actions内按钮的点击
        if ($(e.target).closest('.gallery-actions').length || $(e.target).is('button')) {
            return;
        }
        
        const checkbox = $(this).find('.select-checkbox');
        checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
    });
    
    // 批量删除功能
    $('#batch-delete').on('click', function() {
        if (selectedItems.length === 0) return;
        
        $('#delete-count').text(selectedItems.length);
        $('#batch-delete-modal').fadeIn(300);
    });
    
    // 确认批量删除
    $('#confirm-batch-delete').on('click', function() {
        if (selectedItems.length === 0) return;
        
        $(this).prop('disabled', true).html(`
            <div class="spinner">
                <div class="bounce1"></div>
                <div class="bounce2"></div>
                <div class="bounce3"></div>
            </div>
            删除中...
        `);
        
        // 创建需要删除的ID数组
        const ids = selectedItems.map(item => item.id);
        
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'imghosting_batch_delete_images',
                ids: ids,
                nonce: '<?php echo wp_create_nonce('imghosting_batch_operation'); ?>'
            },
            success: function(response) {
                console.log('批量删除响应', response);
                $('#batch-delete-modal').fadeOut(300);
                
                if (response.success) {
                    // 成功删除
                    const successMsg = $('<div class="success-message">' + response.data.message + '</div>');
                    $('.page-header').after(successMsg);
                    
                    // 从界面上移除已删除的图片
                    ids.forEach(id => {
                        $(`.gallery-item[data-id="${id}"]`).fadeOut(300, function() {
                            $(this).remove();
                            
                            // 如果所有图片都已删除，显示空画廊信息
                            if ($('.gallery-item').length === 0) {
                                $('#gallery-container').html(`
                                    <div class="empty-gallery">
                                        <div class="empty-icon"></div>
                                        <h3>暂无图片</h3>
                                        <p>您还没有上传任何图片，快去上传您的第一张图片吧！</p>
                                        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn-primary">前往上传</a>
                                    </div>
                                `);
                            }
                        });
                    });
                    
                    // 退出批量选择模式
                    $('#batch-select-toggle').click();
                    
                    setTimeout(() => {
                        successMsg.fadeOut(500, function() {
                            $(this).remove();
                        });
                    }, 3000);
                } else {
                    // 显示错误消息
                    const errorMsg = $('<div class="error-message">批量删除失败: ' + response.data.message + '</div>');
                    $('.page-header').after(errorMsg);
                    
                    setTimeout(() => {
                        errorMsg.fadeOut(500, function() {
                            $(this).remove();
                        });
                    }, 3000);
                }
                
                // 重置按钮状态
                $('#confirm-batch-delete').prop('disabled', false).text('确认删除');
            },
            error: function(xhr, status, error) {
                console.error('批量删除请求失败', error);
                $('#batch-delete-modal').fadeOut(300);
                
                // 显示错误消息
                const errorMsg = $('<div class="error-message">批量删除请求失败，请稍后重试</div>');
                $('.page-header').after(errorMsg);
                
                setTimeout(() => {
                    errorMsg.fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 3000);
                
                // 重置按钮状态
                $('#confirm-batch-delete').prop('disabled', false).text('确认删除');
            }
        });
    });
    
    // 批量复制链接功能
    $('#batch-copy-links').on('click', function() {
        if (selectedItems.length === 0) return;
        
        $('#copy-count').text(selectedItems.length);
        
        // 生成不同格式的链接
        const directLinks = selectedItems.map(item => item.url).join('\n');
        const htmlLinks = selectedItems.map(item => `<img src="${item.url}" alt="Uploaded Image">`).join('\n');
        const markdownLinks = selectedItems.map(item => `![Uploaded Image](${item.url})`).join('\n');
        const bbcodeLinks = selectedItems.map(item => `[img]${item.url}[/img]`).join('\n');
        
        // 填充textarea
        $('#batch-direct-links').val(directLinks);
        $('#batch-html-links').val(htmlLinks);
        $('#batch-markdown-links').val(markdownLinks);
        $('#batch-bbcode-links').val(bbcodeLinks);
        
        // 显示弹窗
        $('#batch-copy-modal').fadeIn(300);
    });
    
    // 链接格式标签切换
    $('.link-tab').on('click', function() {
        const target = $(this).data('target');
        
        // 更新标签和面板状态
        $('.link-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.link-pane').removeClass('active');
        $('#' + target).addClass('active');
    });
    
    // 复制所选格式的链接
    $('#copy-selected-links').on('click', function() {
        // 获取当前活动的链接面板
        const activePane = $('.link-pane.active');
        const textarea = activePane.find('textarea');
        
        // 复制文本
        textarea.select();
        
        // 尝试复制
        try {
            const success = document.execCommand('copy');
            if (success) {
                // 显示成功消息
                const originalText = $(this).text();
                $(this).text('已复制！').addClass('btn-success');
                
                setTimeout(() => {
                    $(this).text(originalText).removeClass('btn-success');
                }, 1500);
            } else {
                // 复制失败
                $(this).text('复制失败').addClass('btn-error');
                
                setTimeout(() => {
                    $(this).text('复制所选格式').removeClass('btn-error');
                }, 1500);
            }
        } catch (err) {
            // 复制出错
            $(this).text('复制失败').addClass('btn-error');
            
            setTimeout(() => {
                $(this).text('复制所选格式').removeClass('btn-error');
            }, 1500);
        }
    });
    
    // 为复选框添加单独的事件处理程序，阻止事件冒泡
    $(document).on('click', '.select-checkbox, .item-checkbox', function(e) {
        e.stopPropagation();
    });
});
</script>

<?php get_footer(); ?>
