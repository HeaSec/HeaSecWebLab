/**
 * HeaSec天积安全团队 - 密码重置凭证可猜测靶场 - 密码重置页面验证脚本
 * 版本: v1.0.0
 * 创建日期: 2026-01-23
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    /**
     * 初始化密码重置表单验证
     */
    function initResetPasswordForm() {
        var form = document.querySelector('.tech-form');

        if (!form) {
            return;
        }

        // 绑定表单提交事件
        form.addEventListener('submit', function (e) {
            if (!validatePasswordMatch()) {
                e.preventDefault();
                return false;
            }
        });

        // 绑定确认密码输入框的实时验证
        var newPasswordInput = document.getElementById('new_password');
        var confirmPasswordInput = document.getElementById('confirm_password');

        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function () {
                clearPasswordMatchError();
            });

            confirmPasswordInput.addEventListener('blur', function () {
                if (this.value && newPasswordInput && newPasswordInput.value) {
                    validatePasswordMatch();
                }
            });
        }

        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function () {
                clearPasswordMatchError();
            });
        }
    }

    /**
     * 验证两次输入的密码是否一致
     * @returns {boolean} 密码是否一致
     */
    function validatePasswordMatch() {
        var newPassword = document.getElementById('new_password');
        var confirmPassword = document.getElementById('confirm_password');

        if (!newPassword || !confirmPassword) {
            return true;
        }

        var newPasswordValue = newPassword.value.trim();
        var confirmPasswordValue = confirmPassword.value.trim();

        // 如果确认密码为空，不显示错误
        if (!confirmPasswordValue) {
            return false;
        }

        // 检查密码是否一致
        if (newPasswordValue !== confirmPasswordValue) {
            showPasswordMatchError('两次输入的密码不一致，请重新输入');
            confirmPassword.focus();
            return false;
        }

        clearPasswordMatchError();
        return true;
    }

    /**
     * 显示密码不匹配错误
     * @param {string} message - 错误消息
     */
    function showPasswordMatchError(message) {
        var form = document.querySelector('.tech-form');
        if (!form) {
            return;
        }

        // 移除已存在的错误提示
        var existingError = document.getElementById('password-match-error');
        if (existingError) {
            existingError.remove();
        }

        // 创建新的错误提示
        var errorDiv = document.createElement('div');
        errorDiv.id = 'password-match-error';
        errorDiv.className = 'alert-error';
        errorDiv.style.marginBottom = '15px';
        errorDiv.innerHTML = '<i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span>';

        // 插入到表单开头
        form.insertBefore(errorDiv, form.firstChild);
    }

    /**
     * 清除密码不匹配错误
     */
    function clearPasswordMatchError() {
        var errorDiv = document.getElementById('password-match-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    /**
     * HTML转义函数，防止XSS
     * @param {string} text - 需要转义的文本
     * @returns {string} 转义后的文本
     */
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // 页面加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initResetPasswordForm);
    } else {
        initResetPasswordForm();
    }
})();
