/**
 * 统一的图片画廊操作功能
 */
(function($) {
    'use strict';
    
    // 工具函数
    const utils = {
        // 复制到剪贴板
        copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text).then(() => true);
            }
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            try {
                const result = document.execCommand('copy');
                document.body.removeChild(textarea);
                return result;
            } catch (e) {
                document.body.removeChild(textarea);
                return false;
            }
        },

        // 显示消息提示
        showMessage(message, type = 'success') {
            const msgClass = type === 'error' ? 'error-message' : 'success-message';
            const $msg = $(`<div class="${msgClass}">${message}</div>`);
            $('.page-header').after($msg);
            setTimeout(() => {
                $msg.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 3000);
        },

        // 节流函数
        throttle(func, wait) {
            let timeout = null;
            let previous = 0;
            return function() {
                const now = Date.now();
                const remaining = wait - (now - previous);
                const context = this;
                const args = arguments;
                
                if (remaining <= 0) {
                    clearTimeout(timeout);
                    timeout = null;
                    previous = now;
                    func.apply(context, args);
                } else if (!timeout) {
                    timeout = setTimeout(function() {
                        previous = Date.now();
                        timeout = null;
                        func.apply(context, args);
                    }, remaining);
                }
            };
        },

        // 检查元素是否在视窗内
        isElementInViewport(el) {
            const rect = el.getBoundingClientRect();
            return (
                rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.bottom >= 0
            );
        }
    };

    // 批量操作管理器
    const BatchManager = {
        selectedItems: [],
        mode: false,

        init() {
            this.bindEvents();
            this.updateSelectedCount();
        },

        bindEvents() {
            // 切换批量选择模式
            $('#batch-select-toggle').on('click', () => {
                this.mode = !this.mode;
                this.mode ? this.enable() : this.disable();
            });

            // 取消选择
            $('#batch-cancel').on('click', () => {
                this.disable();
                this.mode = false;
            });

            // 全选/取消全选
            $('#batch-select-all').on('click', () => {
                const checkboxes = $('.select-checkbox');
                const allChecked = checkboxes.length === this.selectedItems.length;
                
                checkboxes.prop('checked', !allChecked);
                this.selectedItems = !allChecked ? 
                    Array.from(checkboxes).map(cb => ({
                        id: $(cb).data('id'),
                        url: $(cb).data('url')
                    })) : [];
                
                this.updateSelectedCount();
            });

            // 复选框变化事件
            $(document).on('change', '.select-checkbox', (e) => {
                const $cb = $(e.target);
                const id = $cb.data('id');
                const url = $cb.data('url');
                
                if ($cb.is(':checked')) {
                    this.selectedItems.push({ id, url });
                } else {
                    this.selectedItems = this.selectedItems.filter(item => item.id != id);
                }
                
                this.updateSelectedCount();
            });

            // 批量删除
            $('#batch-delete').on('click', () => this.handleBatchDelete());

            // 批量复制
            $('#batch-copy').on('click', () => this.handleBatchCopy());
        },

        enable() {
            $('#batch-select-toggle').addClass('btn-active').text('取消选择');
            $('.item-checkbox').removeClass('hidden');
            $('#batch-actions-toolbar').removeClass('hidden');
            $('.gallery-item').addClass('select-mode');
        },

        disable() {
            $('#batch-select-toggle')
                .removeClass('btn-active')
                .html(`<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>批量选择`);
            
            $('.item-checkbox').addClass('hidden');
            $('#batch-actions-toolbar').addClass('hidden');
            $('.gallery-item').removeClass('select-mode');
            $('.select-checkbox').prop('checked', false);
            this.selectedItems = [];
            this.updateSelectedCount();
        },

        updateSelectedCount() {
            const count = this.selectedItems.length;
            $('#selected-count').text(count);
            $('#copy-count').text(count);
            $('#batch-copy, #batch-delete').prop('disabled', count === 0);
        },

        async handleBatchDelete() {
            if (this.selectedItems.length === 0) return;
            
            if (!confirm(`确定要删除选中的 ${this.selectedItems.length} 张图片吗？`)) {
                return;
            }
            
            const ids = this.selectedItems.map(item => item.id);
            const $btn = $('#batch-delete');
            const originalText = $btn.text();
            
            $btn.prop('disabled', true)
                .html('<div class="spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>删除中...');
            
            try {
                const response = await $.ajax({
                    url: imghosting_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'imghosting_batch_delete_images',
                        ids: ids,
                        nonce: imghosting_vars.batch_nonce
                    }
                });

                if (response.success) {
                    ids.forEach(id => {
                        $(`.gallery-item[data-id="${id}"]`).fadeOut(300, function() {
                            $(this).remove();
                            if ($('.gallery-item').length === 0) {
                                GalleryManager.showEmptyGallery();
                            }
                        });
                    });
                    
                    this.disable();
                    this.mode = false;
                    utils.showMessage(response.data.message, 'success');
                } else {
                    utils.showMessage(response.data.message || '批量删除失败', 'error');
                }
            } catch (error) {
                utils.showMessage('请求失败，请检查网络连接并重试', 'error');
            } finally {
                $btn.prop('disabled', false).text(originalText);
            }
        },

        handleBatchCopy() {
            if (this.selectedItems.length === 0) return;
            
            $('#copy-count').text(this.selectedItems.length);
            const urls = this.selectedItems.map(item => item.url);
            ModalManager.generateLinks(urls);
            $('#batch-copy-modal').fadeIn(300);
        }
    };

    // 画廊管理器
    const GalleryManager = {
        init() {
            this.initLazyLoading();
            this.bindEvents();
            this.setupAnimations();
        },

        bindEvents() {
            // 单个图片复制链接
            $('.copy-link').on('click', function() {
                const url = $(this).data('url');
                if (utils.copyToClipboard(url)) {
                    utils.showMessage('链接已复制到剪贴板');
                }
            });

            // 单个图片删除
            $('.delete-image').on('click', function() {
                if (!confirm('确定要删除这张图片吗？')) return;
                
                const id = $(this).data('id');
                const $item = $(this).closest('.gallery-item');
                
                $.ajax({
                    url: imghosting_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'imghosting_batch_delete_images',
                        nonce: imghosting_vars.batch_nonce,
                        ids: [id]
                    },
                    success: function(response) {
                        if (response.success) {
                            $item.fadeOut(300, function() {
                                $(this).remove();
                                if ($('.gallery-item').length === 0) {
                                    GalleryManager.showEmptyGallery();
                                }
                            });
                        } else {
                            utils.showMessage(response.data.message || '删除失败，请稍后重试', 'error');
                        }
                    },
                    error: function() {
                        utils.showMessage('删除失败，请稍后重试', 'error');
                    }
                });
            });

            // 图片项点击处理
            $(document).on('click', '.gallery-item', function(e) {
                if (!BatchManager.mode || 
                    $(e.target).closest('.gallery-actions').length || 
                    $(e.target).is('button, a, input')) {
                    return;
                }
                
                const checkbox = $(this).find('.select-checkbox');
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            });

            // 阻止复选框点击事件冒泡
            $(document).on('click', '.select-checkbox, .item-checkbox', function(e) {
                e.stopPropagation();
                
                if($(this).hasClass('item-checkbox')) {
                    const checkbox = $(this).find('.select-checkbox');
                    checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
                }
            });
        },

        setupAnimations() {
            $('.gallery-item').each(function(index) {
                $(this).css({
                    'animation-delay': (index * 0.05) + 's'
                }).addClass('fade-in-animation');
            });
        },

        initLazyLoading() {
            const lazyImages = document.querySelectorAll('.gallery-img[data-src]');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                
                lazyImages.forEach(image => imageObserver.observe(image));
            } else {
                const lazyLoad = () => {
                    lazyImages.forEach(lazyImage => {
                        if (utils.isElementInViewport(lazyImage)) {
                            lazyImage.src = lazyImage.dataset.src;
                            lazyImage.removeAttribute('data-src');
                        }
                    });
                };
                
                lazyLoad();
                window.addEventListener('scroll', utils.throttle(lazyLoad, 200));
                window.addEventListener('resize', utils.throttle(lazyLoad, 200));
            }
        },

        showEmptyGallery() {
            $('#gallery-container').html(`
                <div class="empty-gallery">
                    <div class="empty-icon"></div>
                    <h3>暂无图片</h3>
                    <p>您还没有上传任何图片，快去上传您的第一张图片吧！</p>
                    <a href="${imghosting_vars.home_url}" class="btn btn-primary">前往上传</a>
                </div>
            `);
        }
    };

    // 模态框管理器
    const ModalManager = {
        init() {
            this.bindEvents();
        },

        bindEvents() {
            // 链接格式标签切换
            $('.link-tab').on('click', function() {
                const target = $(this).data('target');
                $('.link-tab').removeClass('active');
                $(this).addClass('active');
                $('.link-pane').removeClass('active');
                $('#' + target).addClass('active');
            });

            // 复制链接
            $('#copy-selected-links').on('click', function() {
                const activePane = $('.link-pane.active');
                const textarea = activePane.find('textarea');
                const text = textarea.val();
                
                if (utils.copyToClipboard(text)) {
                    const $btn = $(this);
                    const originalText = $btn.text();
                    
                    $btn.text('已复制!').addClass('btn-success');
                    setTimeout(() => $btn.text(originalText).removeClass('btn-success'), 1500);
                } else {
                    utils.showMessage('复制失败，请手动复制文本', 'error');
                }
            });

            // 关闭模态框
            $(document).on('click', '.close-modal', function() {
                $(this).closest('.modal').fadeOut(300);
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
        },

        generateLinks(urls) {
            const directLinks = urls.join('\n');
            const htmlLinks = urls.map(url => `<img src="${url}" alt="Uploaded Image">`).join('\n');
            const markdownLinks = urls.map(url => `![Uploaded Image](${url})`).join('\n');
            const bbcodeLinks = urls.map(url => `[img]${url}[/img]`).join('\n');
            
            $('#batch-direct-links').val(directLinks);
            $('#batch-html-links').val(htmlLinks);
            $('#batch-markdown-links').val(markdownLinks);
            $('#batch-bbcode-links').val(bbcodeLinks);
        }
    };

    // 初始化
    $(document).ready(function() {
        BatchManager.init();
        GalleryManager.init();
        ModalManager.init();
    });
    
})(jQuery);