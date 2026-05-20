/**
 * HeaSec天积安全团队 - HTML前端校验绕过靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2025-12-13
 * 团队: 天积安全 (HeavenlySecret)
 */

// 页面加载完成后的初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    initializeCardEffects();
    initializeHints();
});

/**
 * 初始化表单交互
 */
function initializeForm() {
    const form = document.getElementById('applicationForm');

    // 表单提交处理 - 使用AJAX
    if (form) {
        // 移除现有的onsubmit属性
        form.removeAttribute('onsubmit');

        // 获取提交按钮
      const submitBtn = form.querySelector('button[type="submit"]');

      // 为提交按钮添加点击事件，立即清理结果
      if (submitBtn) {
          submitBtn.addEventListener('click', function() {
              // 先清除已存在的结果提示
              clearSubmitResult();
          });
      }

      form.addEventListener('submit', function(e) {
            e.preventDefault(); // 阻止默认的表单提交行为

            // 收集表单数据
            const formData = new FormData(form);

            // 发送AJAX请求
            fetch('api/submit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // 显示结果
                displaySubmitResult(data);

                // 如果成功，显示恭喜弹窗
                if (data.success && data.showCongrats) {
                    showCongratsModal();
                }
            })
            .catch(error => {
                console.error('[HeaSec] 提交错误:', error);
                displaySubmitResult({
                    success: false,
                    message: '提交失败，请检查网络连接或稍后重试！',
                    type: 'error'
                });
            })
            .finally(() => {
                // 恢复按钮状态
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa fa-paper-plane"></i> 提交申请';
                    submitBtn.style.opacity = '1';
                }
            });
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
 * 初始化提示功能
 */
function initializeHints() {
    // 为所有表单元素添加悬停提示
    const formGroups = document.querySelectorAll('.form-group');
    formGroups.forEach(function(group) {
        const label = group.querySelector('.form-label');
        const input = group.querySelector('.tech-input, .radio-group');
        const hint = group.querySelector('.form-hint');

        if (label && input && hint) {
            input.addEventListener('mouseenter', function() {
                hint.style.color = '#ffc107';
                hint.style.fontWeight = '500';
            });

            input.addEventListener('mouseleave', function() {
                hint.style.color = '#6c757d';
                hint.style.fontWeight = 'normal';
            });
        }
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
 * 显示输入提示
 */
function showInputHint(input, message) {
    // 创建提示元素
    let hintDiv = input.parentNode.querySelector('.dynamic-hint');
    if (!hintDiv) {
        hintDiv = document.createElement('small');
        hintDiv.className = 'dynamic-hint';
        hintDiv.style.cssText = 'color: #ffc107; font-size: 12px; margin-top: 5px; display: block; animation: fadeInHint 0.3s ease;';
        input.parentNode.appendChild(hintDiv);
    }

    hintDiv.textContent = message;

    // 自动清除
    setTimeout(function() {
        if (hintDiv && hintDiv.parentNode) {
            hintDiv.style.animation = 'fadeOutHint 0.3s ease';
            setTimeout(function() {
                if (hintDiv && hintDiv.parentNode) {
                    hintDiv.parentNode.removeChild(hintDiv);
                }
            }, 300);
        }
    }, 3000);
}

/**
 * 清除提交结果
 */
function clearSubmitResult() {
    const resultContainer = document.getElementById('submitResult');
    if (resultContainer) {
        resultContainer.style.display = 'none';
        resultContainer.style.animation = 'none';
    }
}

/**
 * 显示提交结果
 */
function displaySubmitResult(data) {
    const resultContainer = document.getElementById('submitResult');
    const resultAlert = document.getElementById('resultAlert');
    const resultMessage = document.getElementById('resultMessage');
    const resultHint = document.getElementById('resultHint');

    if (!resultContainer) return;

    // 设置结果类型样式
    resultAlert.className = 'alert alert-' + (data.type || 'error');

    // 设置图标
    const icon = document.querySelector('#resultContent i');
    if (icon) {
        icon.className = 'fa fa-' + (data.success ? 'check-circle' : 'exclamation-triangle');
    }

    // 设置消息
    if (resultMessage) {
        resultMessage.textContent = data.message || '';
    }

    // 显示/隐藏提示
    if (resultHint) {
        resultHint.style.display = (!data.success && data.type === 'error') ? 'block' : 'none';
    }

    // 显示结果容器
    resultContainer.style.display = 'block';

    // 滚动到结果位置
    resultContainer.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
    });

    // 添加动画效果
    resultContainer.style.animation = 'fadeInResult 0.5s ease';
}

/**
 * 显示恭喜弹窗
 */
function showCongratsModal() {
    // 等待一段时间再显示，让用户先看到成功消息
    setTimeout(function() {
        if (typeof HeaSecCongratsModal !== 'undefined') {
            HeaSecCongratsModal.show({
                title: '🎉 恭喜你掌握了一个新技能',
                message: '你掌握了前端限制绕过攻击的实现方式',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'htmlbypass',
                updateLearningStatus: true,
                updateStatusApiUrl: heasecConfig.commonBasePath + 'api/update-learning-status.php',
                nextRangeApiUrl: heasecConfig.commonBasePath + 'api/next-range.php'
            });
        } else {
            console.error('[HeaSec] 恭喜弹窗组件未加载');
            // 降级处理：显示简单提示
            alert('恭喜你掌握了前端限制绕过攻击的实现方式！');
        }
    }, 1000); // 延迟1秒显示
}

/**
 * 显示全局提示消息
 */
function showGlobalHint(message) {
    // 创建提示条
    let hintBar = document.createElement('div');
    hintBar.className = 'global-hint-bar';
    hintBar.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        color: #000;
        padding: 15px 20px;
        border-radius: 4px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
        font-weight: 500;
        max-width: 300px;
    `;

    hintBar.innerHTML = `
        <i class="fa fa-lightbulb-o"></i>
        <span style="margin-left: 8px;">${message}</span>
    `;

    document.body.appendChild(hintBar);

    // 自动移除
    setTimeout(function() {
        hintBar.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(function() {
            if (hintBar.parentNode) {
                hintBar.parentNode.removeChild(hintBar);
            }
        }, 300);
    }, 4000);
}

// 添加CSS动画
const heasecStyle = document.createElement('style');
heasecStyle.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-8px); }
        75% { transform: translateX(8px); }
    }

    @keyframes fadeInHint {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeOutHint {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-5px);
        }
    }

    @keyframes fadeInResult {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100px);
        }
    }

    .input-error {
        border-color: #dc3545 !important;
        background-color: rgba(220, 53, 69, 0.05) !important;
    }

    .input-error:focus {
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
    }

    .tech-btn:disabled {
        cursor: not-allowed;
        pointer-events: none;
    }

    #submitResult {
        animation: fadeInResult 0.5s ease;
    }
`;
document.head.appendChild(heasecStyle);

// 全局错误处理
window.addEventListener('error', function(e) {
    console.error('[HeaSec] JavaScript Error:', e.message);
});

// 键盘快捷键支持
document.addEventListener('keydown', function(e) {
    // Ctrl+Enter 或 Cmd+Enter 提交表单
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        const form = document.getElementById('applicationForm');
        if (form) {
            form.dispatchEvent(new Event('submit'));
        }
    }

    // Alt+H 显示提示
    if (e.altKey && e.key === 'h') {
        e.preventDefault();
        showGlobalHint('前端限制绕过技巧：双击只读字段、使用开发者工具、修改HTML属性等');
    }
});
