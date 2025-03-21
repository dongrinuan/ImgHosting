/**
 * 图片画廊页面功能脚本 - 移除布局切换功能
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // 移除了布局切换相关代码
        
        // 复制链接功能
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
        
        // 单个图片复制链接
        $('.copy-link').on('click', function() {
            const url = $(this).data('url');
            copyToClipboard(url);
            alert('链接已复制到剪贴板');
        });
        
        // 单个图片删除
        $('.delete-image').on('click', function() {
            if (!confirm('确定要删除这张图片吗？')) {
                return;
            }
            
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
                        });
                    } else {
                        alert(response.data.message || '删除失败，请稍后重试');
                    }
                },
                error: function() {
                    alert('删除失败，请稍后重试');
                }
            });
        });
        
        // 批量选择功能
        $('#batch-select-toggle').on('click', function() {
            $('.item-checkbox').toggleClass('hidden');
            $('#batch-actions-toolbar').toggleClass('hidden');
            if ($('.item-checkbox').hasClass('hidden')) {
                $('.select-checkbox').prop('checked', false);
                updateSelectedCount();
            }
        });
        
        // 更新选中数量
        function updateSelectedCount() {
            const count = $('.select-checkbox:checked').length;
            $('#selected-count').text(count);
            $('#copy-count').text(count);
        }
        
        // 选择框变化时更新数量
        $(document).on('change', '.select-checkbox', updateSelectedCount);
        
        // 全选功能
        $('#batch-select-all').on('click', function() {
            const isAllSelected = $('.select-checkbox:checked').length === $('.select-checkbox').length;
            $('.select-checkbox').prop('checked', !isAllSelected);
            updateSelectedCount();
        });
        
        // 取消批量选择
        $('#batch-cancel').on('click', function() {
            $('.item-checkbox').addClass('hidden');
            $('#batch-actions-toolbar').addClass('hidden');
            $('.select-checkbox').prop('checked', false);
            updateSelectedCount();
        });
        
        // 批量删除
        $('#batch-delete').on('click', function() {
            const selectedIds = $('.select-checkbox:checked').map(function() {
                return $(this).data('id');
            }).get();
            
            if (selectedIds.length === 0) {
                alert('请先选择要删除的图片');
                return;
            }
            
            if (!confirm(`确定要删除选中的 ${selectedIds.length} 张图片吗？`)) {
                return;
            }
            
            $.ajax({
                url: imghosting_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'imghosting_batch_delete_images',
                    nonce: imghosting_vars.batch_nonce,
                    ids: selectedIds
                },
                success: function(response) {
                    if (response.success) {
                        selectedIds.forEach(function(id) {
                            $(`.gallery-item[data-id="${id}"]`).fadeOut(300, function() {
                                $(this).remove();
                            });
                        });
                        $('#batch-cancel').click();
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || '删除失败，请稍后重试');
                    }
                },
                error: function() {
                    alert('删除失败，请稍后重试');
                }
            });
        });
        
        // 批量复制
        $('#batch-copy').on('click', function() {
            const selectedUrls = $('.select-checkbox:checked').map(function() {
                return $(this).data('url');
            }).get();
            
            if (selectedUrls.length === 0) {
                alert('请先选择要复制的图片');
                return;
            }
            
            $.ajax({
                url: imghosting_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'imghosting_batch_export_links',
                    nonce: imghosting_vars.batch_nonce,
                    urls: selectedUrls,
                    format: 'direct'
                },
                success: function(response) {
                    if (response.success) {
                        copyToClipboard(response.data.links.join('\n'));
                        alert('已复制 ' + response.data.count + ' 个链接到剪贴板');
                    } else {
                        alert(response.data.message || '复制失败，请稍后重试');
                    }
                },
                error: function() {
                    alert('复制失败，请稍后重试');
                }
            });
        });

        // 图片加载动画效果
        $('.gallery-item').each(function(index) {
            $(this).css({
                'animation-delay': (index * 0.05) + 's'
            }).addClass('fade-in-animation');
        });
        
        // 删除了图片预览功能相关代码
        
        // 实现图片懒加载
        function initLazyLoading() {
            const lazyImages = document.querySelectorAll('.gallery-img[data-src]');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    });
                });
                
                lazyImages.forEach(function(image) {
                    imageObserver.observe(image);
                });
            } else {
                // 回退到传统懒加载方法
                function lazyLoad() {
                    lazyImages.forEach(function(lazyImage) {
                        if (isElementInViewport(lazyImage)) {
                            lazyImage.src = lazyImage.dataset.src;
                            lazyImage.removeAttribute('data-src');
                        }
                    });
                }
                
                // 初次加载和滚动时检查
                lazyLoad();
                window.addEventListener('scroll', throttle(lazyLoad, 200));
                window.addEventListener('resize', throttle(lazyLoad, 200));
            }
        }
        
        // 检查元素是否在视窗内
        function isElementInViewport(el) {
            const rect = el.getBoundingClientRect();
            return (
                rect.top <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.bottom >= 0
            );
        }
        
        // 节流函数，提高性能
        function throttle(func, wait) {
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
        }
        
        // 初始化懒加载
        initLazyLoading();

        // 更新图库计数
        function updateGalleryCounter() {
            const totalImages = $('.gallery-item').length;
            $('.gallery-counter').text(totalImages + ' 张图片');
        }
    });
})(jQuery);
