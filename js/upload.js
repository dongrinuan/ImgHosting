(function($) {
    'use strict';
    
    $(document).ready(function() {
        const uploadArea = $('#upload-area');
        const fileInput = $('#file-input');
        const progressBar = $('#progress-bar');
        const progressText = $('#progress-text');
        const uploadProgress = $('#upload-progress');
        const selectBtn = $('#select-image-btn');
        const uploadBtn = $('#upload-btn');
        const previewContainer = $('#image-preview-container');
        const previewImage = $('#preview-image');
        const fileName = $('#file-name');
        const modal = $('#image-modal');
        const closeModal = $('.close-modal');
        
        // 基础变量定义
        let selectedFiles = [];
        let currentFileIndex = 0;
        let batchUploadMode = false;
        
        // 确保文件输入框正确定位
        fileInput.css({
            'position': 'absolute',
            'width': '1px',
            'height': '1px',
            'overflow': 'hidden',
            'opacity': '0',
            'z-index': '-1'
        });
        
        // 增强选择图片按钮动画效果
        selectBtn.on('click', function(e) {
            e.preventDefault();
            $(this).addClass('btn-active');
            
            // 添加波纹点击效果
            const ripple = $('<span class="ripple"></span>');
            const btnOffset = $(this).offset();
            const xPos = e.pageX - btnOffset.left;
            const yPos = e.pageY - btnOffset.top;
            
            ripple.css({
                top: yPos,
                left: xPos
            }).appendTo($(this));
            
            setTimeout(function() {
                ripple.remove();
            }, 600);
            
            // 直接触发文件选择对话框
            fileInput[0].click();
            
            setTimeout(() => {
                $(this).removeClass('btn-active');
            }, 200);
        });
        
        // 点击上传区域也触发文件选择
        uploadArea.on('click', function() {
            selectBtn.addClass('btn-active');
            
            // 添加上传区域动画效果
            $(this).addClass('upload-area-clicked');
            setTimeout(() => {
                $(this).removeClass('upload-area-clicked');
            }, 300);
            
            fileInput.click();
            
            setTimeout(() => {
                selectBtn.removeClass('btn-active');
            }, 200);
        });
        
        // 支持多文件选择
        fileInput.attr('multiple', true);
        
        // 文件选择改变
        fileInput.on('change', function(e) {
            if (this.files && this.files.length > 0) {
                // 保存所有选择的文件
                selectedFiles = Array.from(this.files);
                batchUploadMode = selectedFiles.length > 1;
                
                // 显示文件信息和预览
                if (selectedFiles.length === 1) {
                    // 单文件预览逻辑
                    const selectedFile = selectedFiles[0];
                
                    // 显示文件信息和预览
                    const fileSize = formatFileSize(selectedFile.size);
                    const fileType = selectedFile.type.split('/')[1].toUpperCase();
                    
                    fileName.html(`
                        <div class="file-info-container">
                            <div class="file-icon ${fileType.toLowerCase()}"></div>
                            <div class="file-details">
                                <span class="file-name-text">${selectedFile.name}</span>
                                <span class="file-meta">${fileSize} · ${fileType}</span>
                            </div>
                        </div>
                    `);
                    
                    // 创建本地预览，添加加载动画
                    previewContainer.removeClass('hidden').addClass('loading');
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewImage.attr('src', e.target.result);
                        // 添加淡入动画
                        previewImage.css('opacity', 0).animate({opacity: 1}, 500);
                        
                        // 图片加载完成后移除加载状态
                        previewImage.on('load', function() {
                            previewContainer.removeClass('loading');
                        });
                    };
                    reader.readAsDataURL(selectedFile);
                    
                    // 启用上传按钮并添加动画效果
                    uploadBtn.prop('disabled', false)
                           .removeClass('btn-disabled')
                           .addClass('btn-ready')
                           .html(`
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                上传图片
                           `);
                } else {
                    // 多文件预览逻辑
                    fileName.html(`
                        <div class="file-info-container">
                            <div class="file-icon multiple"></div>
                            <div class="file-details">
                                <span class="file-name-text">已选择 ${selectedFiles.length} 个文件</span>
                                <span class="file-meta">将批量上传</span>
                            </div>
                        </div>
                    `);
                    
                    // 创建批量预览区域
                    previewContainer.removeClass('hidden').html(`
                        <h3>批量预览 (${selectedFiles.length}张图片)</h3>
                        <div class="batch-preview-container"></div>
                    `);
                    
                    // 生成前5个图片的预览
                    const previewMax = Math.min(5, selectedFiles.length);
                    const batchPreviewContainer = $('.batch-preview-container');
                    
                    for (let i = 0; i < previewMax; i++) {
                        const file = selectedFiles[i];
                        const previewItem = $(`<div class="preview-item" data-index="${i}">
                            <div class="preview-loading"></div>
                            <img class="preview-thumbnail">
                            <div class="preview-name">${truncateFilename(file.name, 15)}</div>
                        </div>`);
                        
                        batchPreviewContainer.append(previewItem);
                        
                        // 使用FileReader读取图片数据
                        const reader = new FileReader();
                        reader.onload = (function(item) {
                            return function(e) {
                                item.find('img').attr('src', e.target.result);
                                item.find('.preview-loading').remove();
                            };
                        })(previewItem);
                        reader.readAsDataURL(file);
                    }
                    
                    // 显示额外的图片数量提示
                    if (selectedFiles.length > previewMax) {
                        batchPreviewContainer.append(`
                            <div class="preview-more">
                                还有 ${selectedFiles.length - previewMax} 张图片...
                            </div>
                        `);
                    }
                }
                
                // 启用上传按钮并设置为批量上传文本
                uploadBtn.prop('disabled', false)
                       .removeClass('btn-disabled')
                       .addClass('btn-ready')
                       .html(batchUploadMode ? 
                           `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>批量上传 (${selectedFiles.length}张)` :
                           `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>上传图片`
                       );
            }
        });
        
        // 点击上传按钮 - 批量上传版本
        uploadBtn.on('click', function() {
            if (!$(this).prop('disabled') && selectedFiles.length > 0) {
                $(this).addClass('btn-active')
                       .html(`
                            <div class="spinner">
                                <div class="bounce1"></div>
                                <div class="bounce2"></div>
                                <div class="bounce3"></div>
                            </div>
                            准备上传...
                       `);
                
                // 重置上传状态
                currentFileIndex = 0;
                
                // 创建批量上传进度UI
                if (batchUploadMode) {
                    // 创建批量上传进度显示区域
                    if ($('#batch-upload-progress').length === 0) {
                        uploadProgress.before(`
                            <div id="batch-upload-progress" class="batch-progress-container">
                                <h3>批量上传进度</h3>
                                <div class="batch-progress-bar">
                                    <div class="batch-progress-fill"></div>
                                </div>
                                <div class="batch-files-status">
                                    <span id="current-file">1</span>/<span id="total-files">${selectedFiles.length}</span>
                                </div>
                                <p class="batch-status">准备上传...</p>
                            </div>
                        `);
                    } else {
                        // 重置已有的进度条
                        $('.batch-progress-fill').css('width', '0%');
                        $('#current-file').text('1');
                        $('#total-files').text(selectedFiles.length);
                        $('.batch-status').text('准备上传...');
                    }
                    
                    // 开始批量上传
                    uploadNextFile();
                } else {
                    // 单文件上传
                    uploadFile(selectedFiles[0]);
                }
            }
        });
        
        // 拖拽功能
        uploadArea.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('highlight');
        });
        
        uploadArea.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('highlight');
        });
        
        // 拖拽功能简化版本 - 添加批量处理
        uploadArea.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('highlight');
            
            // 添加放置动画
            $(this).addClass('dropped');
            setTimeout(() => {
                $(this).removeClass('dropped');
            }, 400);
            
            const dt = e.originalEvent.dataTransfer;
            if (dt.files && dt.files.length > 0) {
                // 保存所有选择的文件
                selectedFiles = Array.from(dt.files);
                batchUploadMode = selectedFiles.length > 1;
                
                // 触发相同的预览逻辑
                fileInput[0].files = dt.files;
                $(fileInput).trigger('change');
            }
        });
        
        // 改进的复制功能
        $('.copy-btn').on('click', function() {
            const targetId = $(this).data('clipboard-target');
            const targetInput = $(targetId);
            const originalText = $(this).text();
            
            $(this).addClass('btn-active');
            
            // 添加波纹点击效果
            const ripple = $('<span class="ripple"></span>');
            ripple.appendTo($(this));
            
            setTimeout(function() {
                ripple.remove();
            }, 600);
            
            // 使用现代的Clipboard API (如果可用)
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(targetInput.val())
                    .then(() => {
                        showCopySuccess($(this), originalText);
                    })
                    .catch(() => {
                        // 回退到旧方法
                        fallbackCopy(targetInput, $(this), originalText);
                    });
            } else {
                // 回退到旧方法
                fallbackCopy(targetInput, $(this), originalText);
            }
        });
        
        // 复制成功处理
        function showCopySuccess($btn, originalText) {
            // 添加成功视觉反馈
            $btn.text('已复制!')
                .removeClass('btn-active')
                .addClass('btn-success');
            
            // 显示悬浮提示消息
            const $tooltip = $('<div class="copy-tooltip">已复制到剪贴板!</div>');
            $btn.after($tooltip);
            
            // 延时后恢复原始状态
            setTimeout(function() {
                $btn.text(originalText)
                    .removeClass('btn-success');
                $tooltip.fadeOut(200, function() {
                    $(this).remove();
                });
            }, 1500);
        }
        
        // 回退复制方法
        function fallbackCopy(targetInput, $btn, originalText) {
            targetInput.select();
            try {
                const success = document.execCommand('copy');
                if (success) {
                    showCopySuccess($btn, originalText);
                } else {
                    $btn.text('复制失败')
                        .addClass('btn-error');
                    
                    setTimeout(() => {
                        $btn.text(originalText)
                            .removeClass('btn-error btn-active');
                    }, 1500);
                }
            } catch (err) {
                $btn.text('复制失败')
                    .addClass('btn-error');
                
                setTimeout(() => {
                    $btn.text(originalText)
                        .removeClass('btn-error btn-active');
                }, 1500);
            }
        }
        
        // 关闭弹窗
        closeModal.on('click', function() {
            modal.fadeOut(300);
        });
        
        // 点击弹窗外部关闭弹窗
        $(window).on('click', function(e) {
            if ($(e.target).is(modal)) {
                modal.fadeOut(300);
            }
        });
        
        // 链接格式标签切换
        $(document).on('click', '.link-tab', function() {
            const target = $(this).data('target');
            
            // 更新标签和面板状态
            $('.link-tab').removeClass('active');
            $(this).addClass('active');
            
            $('.link-pane').removeClass('active');
            $('#' + target).addClass('active');
        });
        
        // 上传文件函数
        function uploadFile(file, isBatch = false) {
            // 检查文件类型
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                showError('不支持的文件类型，请上传JPG, PNG, GIF或WEBP图片');
                resetButtonsState();
                return;
            }
            
            // 确保imghosting_vars已定义
            if (typeof imghosting_vars === 'undefined') {
                showError('上传配置未正确加载，请刷新页面重试');
                resetButtonsState();
                return;
            }
            
            // 检查文件大小与系统限制
            if (file.size > imghosting_vars.max_file_size) {
                const maxSizeMB = Math.round(imghosting_vars.max_file_size / (1024 * 1024));
                showError(`文件太大，最大允许上传 ${maxSizeMB}MB`);
                resetButtonsState();
                return;
            }
            
            // 显示上传进度
            if (!isBatch) {
                uploadProgress.removeClass('hidden');
            }
            uploadBtn.prop('disabled', true);
            
            // 创建FormData对象
            const formData = new FormData();
            formData.append('action', 'imghosting_upload');
            formData.append('nonce', imghosting_vars.nonce);
            formData.append('file', file);
            
            // 使用AJAX上传
            $.ajax({
                url: imghosting_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            progressBar.val(percentComplete);
                            progressText.text('上传中: ' + Math.round(percentComplete) + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (!isBatch) {
                        uploadProgress.addClass('hidden');
                    }
                    
                    if (response.success) {
                        if (isBatch) {
                            // 上传下一个文件
                            currentFileIndex++;
                            uploadNextFile();
                        } else {
                            // 单文件上传成功调用新的处理函数
                            handleUploadSuccess(response);
                        }
                    } else {
                        if (isBatch) {
                            // 批量上传中的错误处理
                            showError(`文件 ${file.name} 上传失败: ${response.data}`);
                            
                            // 继续上传下一个文件
                            currentFileIndex++;
                            uploadNextFile();
                        } else {
                            showError('上传失败: ' + response.data);
                            resetButtonsState();
                        }
                    }
                },
                error: function(xhr) {
                    if (!isBatch) {
                        uploadProgress.addClass('hidden');
                    }
                    let errorMsg = '上传过程中发生错误，请稍后重试';
                    
                    // 移除了调试日志
                    
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response && response.data) {
                            errorMsg = response.data;
                        }
                    } catch (e) {
                        // 解析失败，使用默认错误信息
                    }
                    
                    showError(errorMsg);
                    
                    if (isBatch) {
                        // 继续上传下一个文件
                        currentFileIndex++;
                        uploadNextFile();
                    } else {
                        resetButtonsState();
                    }
                }
            });
        }
        
        // 批量上传函数 - 递归上传所有文件
        function uploadNextFile() {
            if (currentFileIndex >= selectedFiles.length) {
                // 所有文件上传完成
                finishBatchUpload();
                return;
            }
            
            // 更新进度显示
            const file = selectedFiles[currentFileIndex];
            $('.batch-status').text(`正在上传: ${file.name}`);
            $('#current-file').text(currentFileIndex + 1);
            
            // 更新进度条
            const progressPercent = (currentFileIndex / selectedFiles.length) * 100;
            $('.batch-progress-fill').css('width', progressPercent + '%');
            
            // 单个文件上传
            uploadFile(file, true);
        }
        
        // 批量上传完成处理
        function finishBatchUpload() {
            $('.batch-progress-fill').css('width', '100%');
            $('.batch-status').text('全部上传完成!');
            
            // 重置上传按钮状态
            resetButtonsState();
            
            // 显示成功消息
            showSuccessMessage(`成功上传了 ${selectedFiles.length} 张图片!`);
            
            // 延迟隐藏进度条
            setTimeout(() => {
                $('#batch-upload-progress').slideUp(500, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        // 格式化文件大小
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        // 显示错误信息
        function showError(message) {
            // 创建错误消息元素
            const $errorMsg = $('<div class="error-message"></div>')
                .html(message)
                .hide();
            
            // 添加到上传容器之前
            $('.upload-container').prepend($errorMsg);
            
            // 显示错误信息
            $errorMsg.slideDown(300);
            
            // 5秒后自动隐藏
            setTimeout(() => {
                $errorMsg.slideUp(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // 显示成功信息
        function showSuccessMessage(message) {
            // 创建成功消息元素
            const $successMsg = $('<div class="success-message"></div>')
                .html(message)
                .hide();
            
            // 添加到上传容器之前
            $('.upload-container').prepend($successMsg);
            
            // 显示成功信息
            $successMsg.slideDown(300);
            
            // 5秒后自动隐藏
            setTimeout(() => {
                $successMsg.slideUp(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // 重置按钮状态
        function resetButtonsState() {
            uploadBtn.prop('disabled', false)
                   .removeClass('btn-active')
                   .html(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        上传图片
                   `);
        }
        
        // 重置上传界面
        function resetUploadInterface() {
            fileInput.val('');
            selectedFiles = [];
            previewContainer.addClass('hidden');
            uploadBtn.prop('disabled', true)
                   .removeClass('btn-active btn-ready')
                   .addClass('btn-disabled')
                   .html(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                        上传图片
                   `);
        }
        
        // 辅助函数：截断文件名
        function truncateFilename(filename, maxLength) {
            if (filename.length <= maxLength) return filename;
            
            const ext = filename.split('.').pop();
            const name = filename.substring(0, filename.length - ext.length - 1);
            
            // 确保名字部分至少保留3个字符
            if (maxLength <= ext.length + 4) {
                return filename.substr(0, maxLength - 3) + '...';
            }
            
            // 保留扩展名，截断中间部分
            const nameMaxLength = maxLength - ext.length - 4;
            return name.substr(0, nameMaxLength) + '...' + '.' + ext;
        }

        // 修改成功上传后的处理逻辑
        function handleUploadSuccess(response) {
            // 隐藏上传进度
            uploadProgress.addClass('hidden');
            
            // 只填充直接链接，不再显示预览和详细信息
            $('#direct-link').val(response.data.url);
            $('#html-link').val(response.data.html_link);
            $('#markdown-link').val(response.data.markdown_link);
            $('#bbcode-link').val(response.data.bbcode_link);
            
            // 显示弹窗
            modal.fadeIn(300);
            
            // 重置上传界面
            resetUploadInterface();
            
            // 显示成功消息
            showSuccessMessage('图片上传成功！');
        }
    });
})(jQuery);
