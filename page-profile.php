<?php
/* Template Name: 用户资料 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

wp_enqueue_script('profile-js', get_template_directory_uri() . '/js/profile.js', array('jquery'), '1.0', true);
wp_localize_script('profile-js', 'wpApiSettings', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('update_user_profile')
));
?>

<div class="container">
    <div class="profile-container">
        <h1></h1>
        <p>补全您的资料，让您的资料更加完整！</p>
        
        <div class="profile-form">
            <div class="form-group">
                <label>用户名 <span class="not-editable">(不可修改)</span></label>
                <input type="text" value="<?php echo esc_attr(wp_get_current_user()->user_login); ?>" disabled>
            </div>
            
            <div class="form-group">
                <label>邮箱 <span class="not-editable">(不可修改)</span></label>
                <input type="email" value="<?php echo esc_attr(wp_get_current_user()->user_email); ?>" disabled>
            </div>
            
            <div class="form-group">
                <label>昵称</label>
                <input type="text" id="nickname" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
            </div>
            
            <div class="form-group">
                <label>网站</label>
                <input type="url" id="website" value="<?php echo esc_url(wp_get_current_user()->user_url); ?>">
            </div>
            
            <div class="form-group">
                <label>个性签名</label>
                <textarea id="description"><?php echo esc_textarea(get_user_meta(get_current_user_id(), 'description', true)); ?></textarea>
            </div>
            
            <button id="update-profile" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                保存更改
            </button>
        </div>
    </div>
</div>

<?php get_footer(); ?>