<?php get_header(); ?>

<div class="container">
    <div class="upload-container">
        <h1>图片上传工具</h1>
        <p>快速上传图片并获取各种格式的链接</p>
        
        <input type="file" id="file-input" class="hidden" accept="image/*" multiple aria-label="选择图片文件">
        
        <div id="upload-area" class="upload-area" role="button" tabindex="0" aria-label="点击选择图片或拖拽图片到此处">
            <p>点击选择图片或拖拽图片到这里</p>
        </div>
        
        <div class="upload-actions">
            <button id="select-image-btn" class="btn" aria-label="选择图片">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7"></path>
                    <line x1="16" y1="5" x2="22" y2="5"></line>
                    <line x1="19" y1="2" x2="19" y2="8"></line>
                    <circle cx="9" cy="9" r="2"></circle>
                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                </svg>
                选择图片
            </button>
            <button id="upload-btn" class="btn btn-primary btn-disabled" disabled aria-label="上传图片">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                上传图片
            </button>
        </div>
        
        <div id="image-preview-container" class="image-preview-container hidden">
            <h2>图片预览</h2>
            <img id="preview-image" src="" alt="预览" class="preview-thumbnail">
            <p id="file-name" class="file-info"></p>
        </div>
        
        <div id="upload-progress" class="hidden">
            <progress value="0" max="100" id="progress-bar" aria-label="上传进度"></progress>
            <p id="progress-text">上传中: 0%</p>
        </div>
        
        <?php 
        // 检查用户是否已有上传的图片
        $current_visitor_id = imghosting_get_visitor_id();
        $has_uploads = false;
        
        $args = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_imghosting_visitor_id',
                    'value' => $current_visitor_id,
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        $has_uploads = $query->have_posts();
        wp_reset_postdata();
        
        if ($has_uploads) {
            $gallery_page = get_page_by_path('gallery');
            if ($gallery_page) {
                $gallery_url = get_permalink($gallery_page->ID);
                echo '<div class="gallery-link">';
                echo '<p>您已经有上传的图片，可以在图库中查看它们。</p>';
                echo '<a href="' . esc_url($gallery_url) . '" class="btn btn-secondary">';
                echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>';
                echo '查看我的图库</a>';
                echo '</div>';
            }
        }
        ?>
        
        <?php 
        // 检查用户是否已登录
        if (!is_user_logged_in()) : ?>
            <div class="login-prompt">
                <div class="info-message">
                    <p><strong>提示：</strong> 
                    <?php if (get_option('users_can_register')) : ?>
                        登录或<a href="<?php echo wp_registration_url(); ?>">注册账号</a>后可以管理和查看您上传的所有图片
                    <?php else : ?>
                        登录后可以管理和查看您上传的所有图片
                    <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<!-- 美化的图片上传成功弹窗 -->
<div id="image-modal" class="modal" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
    <div class="modal-content simplified-modal">
        <span class="close-modal" aria-label="关闭">&times;</span>
        <div class="modal-header">
            <h2 id="modal-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="success-icon">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                上传成功!
            </h2>
        </div>
        
        <div class="link-tabs">
            <button class="link-tab active" data-target="direct-link-tab">直接链接</button>
            <button class="link-tab" data-target="html-link-tab">HTML代码</button>
            <button class="link-tab" data-target="markdown-link-tab">Markdown</button>
            <button class="link-tab" data-target="bbcode-link-tab">BBCode</button>
        </div>
        
        <div class="link-panes">
            <div class="link-pane active" id="direct-link-tab">
                <div class="link-item">
                    <input type="text" id="direct-link" readonly aria-label="直接图片链接">
                    <button class="copy-btn" data-clipboard-target="#direct-link" aria-label="复制直接链接">复制</button>
                </div>
            </div>
            
            <div class="link-pane" id="html-link-tab">
                <div class="link-item">
                    <input type="text" id="html-link" readonly aria-label="HTML图片代码">
                    <button class="copy-btn" data-clipboard-target="#html-link" aria-label="复制HTML代码">复制</button>
                </div>
            </div>
            
            <div class="link-pane" id="markdown-link-tab">
                <div class="link-item">
                    <input type="text" id="markdown-link" readonly aria-label="Markdown图片代码">
                    <button class="copy-btn" data-clipboard-target="#markdown-link" aria-label="复制Markdown代码">复制</button>
                </div>
            </div>
            
            <div class="link-pane" id="bbcode-link-tab">
                <div class="link-item">
                    <input type="text" id="bbcode-link" readonly aria-label="BBCode图片代码">
                    <button class="copy-btn" data-clipboard-target="#bbcode-link" aria-label="复制BBCode代码">复制</button>
                </div>
            </div>
        </div>
        
        <!-- 移除底部操作按钮 -->
    </div>
</div>

<?php get_footer(); ?>
