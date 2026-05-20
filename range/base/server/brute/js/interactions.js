/**
 * HeaSec天积安全团队 - 暴力破解靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2025-12-09
 * 团队: 天积安全 (HeavenlySecret)
 */

// 页面加载完成后的初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    initializeCardEffects();
});

/**
 * 初始化表单交互
 */
function initializeForm() {
    const passwordInput = document.getElementById('password');
    const loginForm = document.querySelector('.tech-form');

    if (passwordInput) {
        // 自动聚焦到密码输入框
        setTimeout(function() {
            if (!passwordInput.value) {
                passwordInput.focus();
            }
        }, 300);

        // 清除表单时重置状态
        passwordInput.addEventListener('input', function() {
            // 移除可能存在的错误状态
            this.classList.remove('input-error');
        });
    }

    if (loginForm) {
        // 表单提交时的简单验证
        loginForm.addEventListener('submit', function(e) {
            const password = passwordInput ? passwordInput.value.trim() : '';

            if (!password) {
                e.preventDefault();
                showInputError('请输入密码');
                return false;
            }

            // 检查密码长度（六位字符串）
            if (password.length !== 6) {
                showHint('密码是六位字符串（包含字母和数字）');
            }

            // 显示加载状态
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 验证中...';
                submitBtn.style.opacity = '0.7';

                // 如果表单提交失败，恢复按钮状态
                setTimeout(function() {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa fa-sign-in"></i> 登录';
                    submitBtn.style.opacity = '1';
                }, 3000);
            }
        });
    }
}

/**
 * 初始化卡片视觉效果
 */
function initializeCardEffects() {
    const techCards = document.querySelectorAll('.tech-card');

    techCards.forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            addCardGlowEffect(this);
        });

        card.addEventListener('mouseleave', function() {
            removeCardGlowEffect(this);
        });
    });
}

/**
 * 添加卡片发光效果
 */
function addCardGlowEffect(card) {
    card.style.transition = 'all 0.3s ease';
    card.style.boxShadow = '0 12px 40px rgba(0, 123, 255, 0.2), 0 4px 12px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.9)';
}

/**
 * 移除卡片发光效果
 */
function removeCardGlowEffect(card) {
    card.style.boxShadow = '0 8px 32px rgba(0, 123, 255, 0.1), 0 2px 8px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.8)';
}

/**
 * 显示输入错误提示
 */
function showInputError(message) {
    const passwordInput = document.getElementById('password');
    if (!passwordInput) return;

    // 添加错误样式
    passwordInput.classList.add('input-error');

    // 创建错误提示
    let errorDiv = document.getElementById('password-error-hint');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.id = 'password-error-hint';
        errorDiv.className = 'input-error-hint';
        errorDiv.style.cssText = 'color: #dc3545; font-size: 12px; margin-top: 5px;';

        const parent = passwordInput.parentNode;
        parent.appendChild(errorDiv);
    }

    errorDiv.textContent = message;

    // 震动效果
    shakeElement(passwordInput);

    // 自动清除
    setTimeout(function() {
        passwordInput.classList.remove('input-error');
        if (errorDiv && errorDiv.parentNode) {
            errorDiv.parentNode.removeChild(errorDiv);
        }
    }, 3000);
}

/**
 * 显示提示信息
 */
function showHint(message) {
    // 查找提示区域
    const alertHint = document.querySelector('.alert-hint');
    if (alertHint) {
        const small = alertHint.querySelector('small');
        if (small) {
            small.textContent = message;
            small.style.color = '#ffc107';
        }
    }
}

/**
 * 元素震动效果
 */
function shakeElement(element) {
    if (!element) return;

    element.style.animation = 'shake 0.5s ease-in-out';

    setTimeout(function() {
        element.style.animation = '';
    }, 500);
}

// 添加CSS动画
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-8px); }
        75% { transform: translateX(8px); }
    }

    .input-error {
        border-color: #dc3545 !important;
        background-color: rgba(220, 53, 69, 0.05) !important;
    }

    .input-error:focus {
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
    }
`;
document.head.appendChild(style);

// 全局错误处理
window.addEventListener('error', function(e) {
    console.error('[HeaSec] JavaScript Error:', e.message);
});

// 键盘快捷键支持
document.addEventListener('keydown', function(e) {
    // Ctrl+Enter 或 Cmd+Enter 提交表单
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        const loginForm = document.querySelector('.tech-form');
        if (loginForm) {
            loginForm.dispatchEvent(new Event('submit'));
        }
    }
});