/**
 * HeaSec天积安全团队 - HTML上下文XSS过滤靶场 - 模态框组件
 * 版本: v1.0.0
 * 创建日期: 2026-01-14
 * 团队: 天积安全 (HeavenlySecret)
 */

var HeaSecModal = (function() {
    'use strict';

    /**
     * 创建模态框
     */
    function createModal() {
        var modal = document.createElement('div');
        modal.id = 'heasec-modal';
        modal.className = 'heasec-modal';
        modal.innerHTML =
            '<div class="heasec-modal-content">' +
                '<div class="heasec-modal-header">' +
                    '<h3 id="heasec-modal-title"></h3>' +
                    '<button class="heasec-modal-close" onclick="HeaSecModal.hide()">&times;</button>' +
                '</div>' +
                '<div class="heasec-modal-body" id="heasec-modal-body"></div>' +
            '</div>';
        document.body.appendChild(modal);
    }

    /**
     * 显示模态框
     */
    function show(title, message) {
        var modal = document.getElementById('heasec-modal');
        if (!modal) {
            createModal();
            modal = document.getElementById('heasec-modal');
        }

        document.getElementById('heasec-modal-title').textContent = title;
        document.getElementById('heasec-modal-body').textContent = message;
        modal.style.display = 'block';
    }

    /**
     * 隐藏模态框
     */
    function hide() {
        var modal = document.getElementById('heasec-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    /**
     * 显示错误消息
     */
    function showError(title, message) {
        show(title, message);
        var modal = document.querySelector('.heasec-modal-content');
        if (modal) {
            modal.style.borderTop = '4px solid #dc3545';
        }
    }

    /**
     * 显示成功消息
     */
    function showSuccess(title, message) {
        show(title, message);
        var modal = document.querySelector('.heasec-modal-content');
        if (modal) {
            modal.style.borderTop = '4px solid #28a745';
        }
    }

    return {
        show: show,
        hide: hide,
        showError: showError,
        showSuccess: showSuccess
    };

})();

// 点击模态框外部关闭
window.onclick = function(event) {
    var modal = document.getElementById('heasec-modal');
    if (modal && event.target === modal) {
        HeaSecModal.hide();
    }
};
