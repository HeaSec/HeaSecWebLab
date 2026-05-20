/**
 * HeaSec天积安全团队 - HTTP User-Agent靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2025-11-05
 * 团队: 天积安全 (HeavenlySecret)
 */

// 页面加载完成后的初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeInteractions();
    initializeLengthIndicator();
    initializeFormValidation();
    initializeTooltips();
});

/**
 * 初始化交互功能
 */
function initializeInteractions() {
    // 自动聚焦到秘密输入框
    var secretInput = document.getElementById('secret');
    if (secretInput && !secretInput.value) {
        setTimeout(function() {
            secretInput.focus();
        }, 500);
    }

    // 为所有科技感卡片添加悬停效果
    var techCards = document.querySelectorAll('.tech-card');
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
 * 初始化字符长度指示器
 */
function initializeLengthIndicator() {
    var secretInput = document.getElementById('secret');
    var lengthIndicator = document.getElementById('lengthIndicator');

    if (secretInput && lengthIndicator) {
        // 初始化长度显示
        updateLengthIndicator(secretInput.value.length);

        // 监听输入事件
        secretInput.addEventListener('input', function(e) {
            var length = e.target.value.length;
            updateLengthIndicator(length);
            updateLengthIndicatorColor(length);
        });

        // 监听粘贴事件
        secretInput.addEventListener('paste', function(e) {
            setTimeout(function() {
                var length = secretInput.value.length;
                updateLengthIndicator(length);
                updateLengthIndicatorColor(length);
            }, 10);
        });
    }
}

/**
 * 更新长度指示器显示
 */
function updateLengthIndicator(length) {
    var lengthIndicator = document.getElementById('lengthIndicator');
    if (lengthIndicator) {
        lengthIndicator.textContent = length;

        // 添加动画效果
        lengthIndicator.style.transform = 'scale(1.2)';
        setTimeout(function() {
            lengthIndicator.style.transform = 'scale(1)';
        }, 200);
    }
}

/**
 * 更新长度指示器颜色
 */
function updateLengthIndicatorColor(length) {
    var lengthIndicator = document.getElementById('lengthIndicator');
    if (!lengthIndicator) return;

    // 移除所有颜色类
    lengthIndicator.classList.remove('length-error', 'length-warning', 'length-success');

    if (length === 0) {
        lengthIndicator.style.color = '#007BFF';
    } else if (length < 20) {
        lengthIndicator.style.color = '#ffc107';
    } else if (length === 20) {
        lengthIndicator.style.color = '#28a745';
    } else {
        lengthIndicator.style.color = '#dc3545';
    }
}

/**
 * 初始化表单验证
 */
function initializeFormValidation() {
    var secretForm = document.getElementById('secretForm');
    if (secretForm) {
        secretForm.addEventListener('submit', function(e) {
            var secretInput = document.getElementById('secret');
            var secret = secretInput ? secretInput.value.trim() : '';

            if (!validateSecretInput(secret)) {
                e.preventDefault();
                showValidationMessage('请输入有效的20位秘密字符串（字母和数字组合）', 'error');
                shakeElement(secretInput);
                return false;
            }

            // 显示加载状态
            showFormLoading(true);
        });
    }
}

/**
 * 验证秘密输入
 */
function validateSecretInput(secret) {
    if (!secret || typeof secret !== 'string') {
        return false;
    }

    // 检查长度
    if (secret.length !== 20) {
        return false;
    }

    // 检查字符格式
    var pattern = /^[A-Za-z0-9]{20}$/;
    return pattern.test(secret);
}

/**
 * 初始化工具提示
 */
function initializeTooltips() {
    var techInputs = document.querySelectorAll('.tech-input[title]');
    techInputs.forEach(function(input) {
        input.addEventListener('focus', function() {
            showTooltip(this, this.title);
        });

        input.addEventListener('blur', function() {
            hideTooltip(this);
        });
    });
}

/**
 * 重置表单
 */
function resetForm() {
    var secretForm = document.getElementById('secretForm');
    var secretInput = document.getElementById('secret');
    var lengthIndicator = document.getElementById('lengthIndicator');

    if (secretForm) {
        // 清除输入值
        if (secretInput) {
            secretInput.value = '';
            updateLengthIndicator(0);
            updateLengthIndicatorColor(0);
        }

        // 隐藏所有消息提示
        hideAllMessages();

        // 添加重置动画
        addResetAnimation(secretForm);

        // 重新聚焦输入框
        setTimeout(function() {
            if (secretInput) {
                secretInput.focus();
            }
        }, 300);
    }
}

/**
 * 显示验证消息
 */
function showValidationMessage(message, type) {
    hideAllMessages();

    var alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-' + (type || 'error');
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '100px';
    alertDiv.style.left = '50%';
    alertDiv.style.transform = 'translateX(-50%)';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.maxWidth = '400px';
    alertDiv.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.3)';

    // 设置与后台一致的红色渐变背景和白色字体
    alertDiv.style.background = 'linear-gradient(135deg, #dc3545, #c82333)';
    alertDiv.style.color = '#ffffff';
    alertDiv.style.border = 'none';
    alertDiv.style.borderRadius = '8px';
    alertDiv.style.fontSize = '14px';
    alertDiv.style.fontWeight = '500';

    var iconClass = type === 'success' ? 'check-circle' : 'times-circle';
    alertDiv.innerHTML = '<i class="fa fa-' + iconClass + '"></i><span>' + message + '</span>';

    document.body.appendChild(alertDiv);

    // 添加显示动画
    alertDiv.style.opacity = '0';
    alertDiv.style.transform = 'translateX(-50%) translateY(-20px)';

    setTimeout(function() {
        alertDiv.style.transition = 'all 0.3s ease';
        alertDiv.style.opacity = '1';
        alertDiv.style.transform = 'translateX(-50%) translateY(0)';
    }, 100);

    // 自动隐藏
    setTimeout(function() {
        hideValidationMessage(alertDiv);
    }, 3000);
}

/**
 * 隐藏验证消息
 */
function hideValidationMessage(element) {
    if (element) {
        element.style.transition = 'all 0.3s ease';
        element.style.opacity = '0';
        element.style.transform = 'translateX(-50%) translateY(-20px)';

        setTimeout(function() {
            if (element.parentNode) {
                element.parentNode.removeChild(element);
            }
        }, 300);
    }
}

/**
 * 隐藏所有消息提示
 */
function hideAllMessages() {
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        // 检查alert元素是否在detection-result区域内
        var detectionResult = alert.closest('.detection-result');
        if (!detectionResult) {
            hideValidationMessage(alert);
        }
    });
}

/**
 * 显示表单加载状态
 */
function showFormLoading(show) {
    var submitButton = document.querySelector('#secretForm button[type="submit"]');
    if (submitButton) {
        if (show) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 验证中...';
            submitButton.style.opacity = '0.7';
        } else {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="fa fa-sign-in"></i> 验证秘密';
            submitButton.style.opacity = '1';
        }
    }
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
 * 元素震动效果
 */
function shakeElement(element) {
    if (!element) return;

    element.style.animation = 'shake 0.5s ease-in-out';

    setTimeout(function() {
        element.style.animation = '';
    }, 500);
}

/**
 * 添加重置动画
 */
function addResetAnimation(form) {
    if (!form) return;

    form.style.animation = 'fadeInScale 0.3s ease-out';

    setTimeout(function() {
        form.style.animation = '';
    }, 300);
}

/**
 * 显示工具提示
 */
function showTooltip(element, text) {
    if (!element || !text) return;

    var tooltip = document.createElement('div');
    tooltip.className = 'tech-tooltip';
    tooltip.textContent = text;
    tooltip.style.cssText = `
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 1000;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
        margin-bottom: 5px;
    `;

    element.style.position = 'relative';
    element.appendChild(tooltip);

    setTimeout(function() {
        tooltip.style.opacity = '1';
    }, 100);
}

/**
 * 隐藏工具提示
 */
function hideTooltip(element) {
    if (!element) return;

    var tooltip = element.querySelector('.tech-tooltip');
    if (tooltip) {
        tooltip.style.opacity = '0';
        setTimeout(function() {
            if (tooltip.parentNode) {
                tooltip.parentNode.removeChild(tooltip);
            }
        }, 300);
    }
}

// 添加CSS动画
var heasecStyle = document.createElement('style');
heasecStyle.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-10px); }
        75% { transform: translateX(10px); }
    }

    @keyframes fadeInScale {
        0% { opacity: 0.8; transform: scale(0.98); }
        100% { opacity: 1; transform: scale(1); }
    }

    .tech-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: rgba(0, 0, 0, 0.8);
    }
`;
document.head.appendChild(heasecStyle);

// 全局错误处理
window.addEventListener('error', function(e) {
    console.error('[HeaSec] JavaScript Error:', e.message);
});

// 页面可见性变化时的处理
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        // 页面重新可见时，重新初始化某些功能
        var secretInput = document.getElementById('secret');
        if (secretInput && document.activeElement !== secretInput) {
            setTimeout(function() {
                if (!secretInput.value) {
                    secretInput.focus();
                }
            }, 500);
        }
    }
});

// 键盘快捷键支持
document.addEventListener('keydown', function(e) {
    // Ctrl+Enter 或 Cmd+Enter 提交表单
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        var secretForm = document.getElementById('secretForm');
        if (secretForm) {
            secretForm.dispatchEvent(new Event('submit'));
        }
    }

    // Escape 键重置表单
    if (e.key === 'Escape') {
        resetForm();
    }
});