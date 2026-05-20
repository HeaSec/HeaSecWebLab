/**
 * HeaSec天积安全团队 - HTTP Cookie操作靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2025-11-06
 * 团队: 天积安全 (HeavenlySecret)
 */

// 页面加载完成后的初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeInteractions();
    initializeTooltips();
});

/**
 * 初始化交互功能
 */
function initializeInteractions() {
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

// 添加CSS样式
var heasecStyle = document.createElement('style');
heasecStyle.textContent = `
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