(function($) {
    'use strict';
    
    $(document).ready(function() {
        // 页头滚动效果
        const siteHeader = $('.site-header');
        let lastScrollTop = 0;
        
        $(window).scroll(function() {
            const scrollTop = $(this).scrollTop();
            
            // 添加滚动阴影效果
            if (scrollTop > 10) {
                siteHeader.addClass('scrolled');
            } else {
                siteHeader.removeClass('scrolled');
            }
            
            // 实现向上滚动时显示，向下滚动时隐藏
            if (scrollTop > lastScrollTop && scrollTop > 200) {
                // 向下滚动
                siteHeader.addClass('header-hidden');
            } else {
                // 向上滚动
                siteHeader.removeClass('header-hidden');
            }
            
            lastScrollTop = scrollTop;
        });
        
        // 移动菜单切换
        const menuToggle = $('.menu-toggle');
        const mainNav = $('.main-navigation');
        
        menuToggle.on('click', function() {
            mainNav.toggleClass('toggled');
            
            if (mainNav.hasClass('toggled')) {
                menuToggle.attr('aria-expanded', 'true');
            } else {
                menuToggle.attr('aria-expanded', 'false');
            }
        });
        
        // 添加下拉菜单箭头
        $('.main-navigation .menu-item-has-children > a').append('<span class="dropdown-toggle"></span>');
        
        // 处理移动端子菜单展开
        $('.main-navigation .dropdown-toggle').on('click', function(e) {
            if ($(window).width() <= 768) {
                e.preventDefault();
                $(this).toggleClass('toggled');
                $(this).closest('li').find('> ul').slideToggle();
            }
        });
        
        // 窗口调整大小时重置菜单状态
        $(window).on('resize', function() {
            if ($(window).width() > 768) {
                $('.main-navigation ul').removeAttr('style');
                menuToggle.attr('aria-expanded', 'false');
                mainNav.removeClass('toggled');
            }
        });
    });
})(jQuery);
