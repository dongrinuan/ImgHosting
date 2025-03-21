/**
 * 批量操作相关功能
 */

(function($) {
    'use strict';
    
    // 确保没有图片预览相关代码
    // 这个文件主要处理批量操作，确保不包含图片预览相关功能

    $(document).ready(function() {
        // 批量选择功能变量
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
            
            // 添加动画效果
            $(this).addClass('pulse-animation');
            setTimeout(() => {
                $(this).removeClass('pulse-animation');
            }, 400);
            
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
            const galleryItem = $(this).closest('.gallery-item');
            
            if ($(this).is(':checked')) {
                // 添加到选中列表
                selectedItems.push({ id, url });
                galleryItem.addClass('selected');
            } else {
                // 从选中列表移除
                selectedItems = selectedItems.filter(item => item.id != id);
                galleryItem.removeClass('selected');
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
            if ($(e.target).closest('.gallery-actions').length || 
                $(e.target).is('button') || 
                $(e.target).parent().is('button') || 
                $(e.target).is('svg') || 
                $(e.target).is('path') ||
                $(e.target).is('.select-checkbox') ||
                $(e.target).closest('.item-checkbox').length) {
                return;
            }
            
            const checkbox = $(this).find('.select-checkbox');
            const isChecked = checkbox.prop('checked');
            const id = checkbox.data('id');
            const url = checkbox.data('url');
            
            // 添加动画效果
            $(this).addClass('pulse-animation');
            setTimeout(() => {
                $(this).removeClass('pulse-animation');
            }, 400);
            
            if (isChecked) {
                // 取消选择
                selectedItems = selectedItems.filter(item => item.id != id);
                $(this).removeClass('selected');
                checkbox.prop('checked', false);
            } else {
                // 选择
                selectedItems.push({ id, url });
                $(this).addClass('selected');
                checkbox.prop('checked', true);
            }
            
            updateSelectedCount();
        });
        
        // 直接点击复选框时的处理
        $(document).on('click', '.select-checkbox', function(e) {
            e.stopPropagation();
            
            const galleryItem = $(this).closest('.gallery-item');
            const id = $(this).data('id');
            const url = $(this).data('url');
            const isChecked = $(this).prop('checked');
            
            if (isChecked) {
                selectedItems.push({ id, url });
                galleryItem.addClass('selected');
            } else {
                selectedItems = selectedItems.filter(item => item.id != id);
                galleryItem.removeClass('selected');
            }
            
            updateSelectedCount();
        });
        
        // 批量删除功能
        $('#batch-delete').on('click', function() {
            if (selectedItems.length === 0) return;
            
            $('#delete-count').text(selectedItems.length);
            $('#batch-delete-modal').fadeIn(300);
        });

        // 关闭批量删除确认弹窗
        $(document).on('click', '#batch-delete-modal .close-modal, #batch-delete-modal .cancel-delete', function() {
            $('#batch-delete-modal').fadeOut(300);
        });

        // 点击弹窗背景关闭
        $('#batch-delete-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).fadeOut(300);
            }
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
            const ids = selectedItems.map(item => parseInt(item.id));
            
            // 防止重复显示消息的标志
            let messageDisplayed = false;
            
            $.ajax({
                url: imghosting_vars.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'imghosting_batch_delete_images',
                    ids: ids,
                    nonce: imghosting_vars.batch_nonce || ''
                },
                success: function(response) {
                    $('#batch-delete-modal').fadeOut(300);
                    
                    if (response.success && !messageDisplayed) {
                        messageDisplayed = true;
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
                                            <a href="${imghosting_vars.home_url}" class="btn btn-primary">前往上传</a>
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
                    } else if (!messageDisplayed) {
                        messageDisplayed = true;
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
                    $('#batch-delete-modal').fadeOut(300);
                    
                    if (!messageDisplayed) {
                        messageDisplayed = true;
                        // 显示错误消息
                        let errorMessage = '批量删除请求失败，请稍后重试';
                        if (xhr.responseText) {
                            try {
                                const jsonResponse = JSON.parse(xhr.responseText);
                                if (jsonResponse && jsonResponse.data && jsonResponse.data.message) {
                                    errorMessage = jsonResponse.data.message;
                                }
                            } catch (e) {
                                // 解析失败，使用默认错误消息
                            }
                        }
                        
                        const errorMsg = $('<div class="error-message">' + errorMessage + '</div>');
                        $('.page-header').after(errorMsg);
                        
                        setTimeout(() => {
                            errorMsg.fadeOut(500, function() {
                                $(this).remove();
                            });
                        }, 3000);
                    }
                    
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
        $('#copy-selected-links').on('click', async function() {
            // 获取当前活动的链接面板
            const activePane = $('.link-pane.active');
            const textarea = activePane.find('textarea');
            
            // 使用现代Clipboard API复制文本
            try {
                await navigator.clipboard.writeText(textarea.val());
                // 显示成功消息
                const originalText = $(this).text();
                $(this).text('已复制！').addClass('btn-success');
                
                setTimeout(() => {
                    $(this).text(originalText).removeClass('btn-success');
                }, 1500);
            } catch (err) {
                // 复制出错
                $(this).text('复制失败').addClass('btn-error');
                
                setTimeout(() => {
                    $(this).text('复制所选格式').removeClass('btn-error');
                }, 1500);
            }
        });
    });
})(jQuery);
