/**
 * HeaSec天积安全团队 - HTTP代理IP请求头靶场交互脚本
 * 版本: v1.0.0 - 采用httpua风格
 * 创建日期: 2025-11-07
 * 团队: 天积安全 (HeavenlySecret)
 */

// 全局变量
var heasecXFF = heasecXFF || {};
heasecXFF.isResetting = false; // 防止重复点击重置按钮

/**
 * 初始化函数
 */
heasecXFF.init = function () {
    this.bindEvents();
    this.addAnimations();
    this.initializeTooltips();
    this.addMicroInteractions();
};

/**
 * 绑定事件
 */
heasecXFF.bindEvents = function () {
    // 页面加载完成后的动画
    document.addEventListener('DOMContentLoaded', function () {
        heasecXFF.animateOnLoad();
    });

    // 重置按钮事件处理 - 覆盖公共组件的默认行为
    var resetBtn = document.getElementById('resetDatabaseBtn');
    if (resetBtn) {
        // 移除可能存在的现有事件监听器
        var newResetBtn = resetBtn.cloneNode(true);
        resetBtn.parentNode.replaceChild(newResetBtn, resetBtn);

        // 添加我们自己的事件监听器
        newResetBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            heasecXFF.handleResetClick(e);
        });
    }

    // 靶场说明按钮事件处理
    var infoBtn = document.getElementById('rangeInfoBtn');
    if (infoBtn) {
        infoBtn.addEventListener('click', function (e) {
            heasecXFF.handleInfoClick(e);
        });
    }

    // 添加键盘快捷键支持
    document.addEventListener('keydown', function (e) {
        heasecXFF.handleKeyboardShortcuts(e);
    });

    // 窗口大小改变时的响应
    window.addEventListener('resize', function () {
        heasecXFF.handleResize();
    });
};

/**
 * 页面加载动画
 */
heasecXFF.animateOnLoad = function () {
    // 卡片入场动画 - 禁用
    var cards = document.querySelectorAll('.tech-card');
    cards.forEach(function (card, index) {
        card.style.opacity = '1';
        card.style.transform = 'translateX(0)';
    });

    // 信息面板动画 - 只对成就进度面板启用
    var panels = document.querySelectorAll('.tech-info-panel');
    panels.forEach(function (panel) {
        // 检查是否是成就进度面板（第一个卡片内的第一个panel）
        var isAchievementPanel = panel.closest('.tech-card:nth-child(2)') &&
            panel.parentElement.querySelector('.tech-info-panel') === panel;

        if (isAchievementPanel) {
            panel.style.opacity = '0';
            panel.style.transform = 'translateY(20px)';

            setTimeout(function () {
                panel.style.transition = 'all 0.6s ease';
                panel.style.opacity = '1';
                panel.style.transform = 'translateY(0)';
            }, 300);
        } else {
            panel.style.opacity = '1';
            panel.style.transform = 'translateY(0)';
        }
    });

    // 五角星动画
    var stars = document.querySelectorAll('.star-blue');
    stars.forEach(function (star, index) {
        setTimeout(function () {
            star.style.animationDelay = (index * 0.2) + 's';
        }, 800);
    });

    // 徽章动画
    var badges = document.querySelectorAll('.badge');
    badges.forEach(function (badge, index) {
        setTimeout(function () {
            badge.style.transform = 'scale(1)';
            badge.style.opacity = '1';
        }, 1000 + (index * 100));
    });
};

/**
 * 添加动画效果
 */
heasecXFF.addAnimations = function () {
    // 卡片悬停效果（httpua风格）
    var cards = document.querySelectorAll('.tech-card');
    cards.forEach(function (card) {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-2px) scale(1.01)';
        });

        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });

    // 信息项悬停效果（httpua风格）
    var infoItems = document.querySelectorAll('.info-item');
    infoItems.forEach(function (item) {
        item.addEventListener('mouseenter', function () {
            this.style.background = 'rgba(255, 255, 255, 0.9)';
            this.style.transform = 'translateX(5px) scale(1.02)';
        });

        item.addEventListener('mouseleave', function () {
            this.style.background = 'rgba(255, 255, 255, 0.7)';
            this.style.transform = 'translateX(0) scale(1)';
        });
    });

    // 警告框悬停效果（httpua风格）
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        alert.addEventListener('mouseenter', function () {
            this.style.transform = 'translateX(3px)';
        });

        alert.addEventListener('mouseleave', function () {
            this.style.transform = 'translateX(0)';
        });
    });

    // 五角星点击效果（保留httpxf特色）
    var stars = document.querySelectorAll('.star');
    stars.forEach(function (star) {
        star.addEventListener('click', function () {
            heasecXFF.animateStarClick(this);
        });

        star.addEventListener('mouseenter', function () {
            if (this.classList.contains('star-blue')) {
                this.style.transform = 'scale(1.2)';
            }
        });

        star.addEventListener('mouseleave', function () {
            if (this.classList.contains('star-blue')) {
                this.style.transform = 'scale(1)';
            }
        });
    });

    // 代码块点击复制功能
    var codeBlocks = document.querySelectorAll('.info-value code');
    codeBlocks.forEach(function (code) {
        code.style.cursor = 'pointer';
        code.title = '点击复制到剪贴板';

        code.addEventListener('click', function () {
            heasecXFF.copyToClipboard(this.textContent);
        });
    });
};

/**
 * 添加微交互效果
 */
heasecXFF.addMicroInteractions = function () {
    // 为卡片添加光波效果
    var cards = document.querySelectorAll('.tech-card');
    cards.forEach(function (card) {
        card.addEventListener('mouseenter', function () {
            var wave = document.createElement('div');
            wave.className = 'wave-effect';
            wave.style.cssText = [
                'position: absolute',
                'top: 0',
                'left: -100%',
                'width: 100%',
                'height: 100%',
                'background: linear-gradient(90deg, transparent, rgba(0, 123, 255, 0.1), transparent)',
                'transition: left 0.5s ease',
                'pointer-events: none',
                'z-index: 1'
            ].join(';');

            this.appendChild(wave);

            setTimeout(function () {
                wave.style.left = '100%';
            }, 10);

            setTimeout(function () {
                if (wave.parentNode) {
                    wave.parentNode.removeChild(wave);
                }
            }, 500);
        });
    });

    // 为徽章添加脉冲效果 - 避免内存泄漏
    var badges = document.querySelectorAll('.badge');
    badges.forEach(function (badge) {
        var interval = setInterval(function () {
            if (badge.parentNode) {
                badge.style.animation = 'pulse 2s ease-in-out';
                setTimeout(function () {
                    badge.style.animation = '';
                }, 2000);
            } else {
                clearInterval(interval);
            }
        }, 5000);
    });
};

/**
 * 五角星点击动画
 */
heasecXFF.animateStarClick = function (star) {
    star.style.transform = 'scale(1.5) rotate(360deg)';
    setTimeout(function () {
        star.style.transform = 'scale(1) rotate(0deg)';
    }, 300);
};

/**
 * 初始化工具提示
 */
heasecXFF.initializeTooltips = function () {
    var tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(function (tooltip) {
        tooltip.classList.add('tooltip');
    });
};

/**
 * 处理重置按钮点击
 */
heasecXFF.handleResetClick = function (e) {
    e.preventDefault();

    // 防止重复点击
    if (heasecXFF.isResetting) {
        return;
    }

    // 显示确认弹窗
    heasecXFF.showResetConfirmModal(function () {
        heasecXFF.performReset(e.target);
    });
};

/**
 * 显示重置确认弹窗 - 简化版本
 */
heasecXFF.showResetConfirmModal = function (onConfirm) {
    // 优先使用PHP创建的模态框
    var manualResetModal = document.getElementById('manualResetModal');
    if (manualResetModal) {
        heasecXFF.showModal('manualResetModal', onConfirm);
        return;
    }

    // 简化的动态模态框创建
    heasecXFF.showSimpleConfirmModal(
        '确认重置靶场',
        '确定要重置靶场吗？所有测试记录将被清空，此操作不可撤销。',
        onConfirm
    );
};

/**
 * 执行重置操作
 */
heasecXFF.performReset = function (btn) {
    heasecXFF.isResetting = true;

    // 显示加载状态
    var originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 重置中...';
    btn.disabled = true;

    // 发送重置请求
    fetch('./?action=reset', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'confirm=1'
    })
        .then(function (response) {
            return response.json();
        })
        .then(function (data) {
            if (data.success) {
                heasecXFF.showNotification('靶场已成功重置！', 'success');
                setTimeout(function () {
                    location.reload();
                }, 1500);
            } else {
                heasecXFF.showNotification('重置失败：' + (data.message || '未知错误'), 'error');
            }
        })
        .catch(function (error) {
            console.error('重置请求失败:', error);
            heasecXFF.showNotification('重置失败，请稍后重试', 'error');
        })
        .finally(function () {
            // 恢复按钮状态
            btn.innerHTML = originalText;
            btn.disabled = false;
            heasecXFF.isResetting = false;
        });
};

/**
 * 处理靶场说明按钮点击
 */
heasecXFF.handleInfoClick = function (e) {
    e.preventDefault();

    // 这里使用公共组件的弹窗功能
    if (typeof window.showRangeInfo === 'function') {
        window.showRangeInfo();
    } else {
        // 备用方案：显示简单的提示
        heasecXFF.showNotification('靶场说明功能正在加载中...', 'info');
    }
};

/**
 * 处理键盘快捷键
 */
heasecXFF.handleKeyboardShortcuts = function (e) {
    // 移除了 Ctrl+R 和 F5 的重置快捷键，避免与浏览器刷新冲突
    // 重置操作只能通过手动点击按钮触发

    // Ctrl+I 或 F1：显示靶场说明
    if ((e.ctrlKey && e.key === 'i') || e.key === 'F1') {
        e.preventDefault();
        var infoBtn = document.getElementById('rangeInfoBtn');
        if (infoBtn) {
            infoBtn.click();
        }
    }
};

/**
 * 处理窗口大小改变
 */
heasecXFF.handleResize = function () {
    // 重新计算布局
    var container = document.querySelector('.range-container');
    if (container) {
        var width = window.innerWidth;
        if (width < 768) {
            container.classList.add('mobile-layout');
        } else {
            container.classList.remove('mobile-layout');
        }
    }
};

/**
 * 显示通知消息
 */
heasecXFF.showNotification = function (message, type) {
    type = type || 'info';

    // 创建通知元素
    var notification = document.createElement('div');
    notification.className = 'heasec-notification heasec-notification-' + type;
    notification.innerHTML = message;

    // 设置样式
    notification.style.cssText = [
        'position: fixed',
        'top: 20px',
        'right: 20px',
        'background: ' + heasecXFF.getNotificationColor(type),
        'color: white',
        'padding: 15px 20px',
        'border-radius: 8px',
        'box-shadow: 0 4px 12px rgba(0,0,0,0.15)',
        'z-index: 10000',
        'max-width: 300px',
        'font-size: 14px',
        'opacity: 0',
        'transform: translateX(100%)',
        'transition: all 0.3s ease'
    ].join(';');

    // 添加到页面
    document.body.appendChild(notification);

    // 显示动画
    setTimeout(function () {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 100);

    // 自动隐藏
    setTimeout(function () {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(function () {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
};

/**
 * 获取通知颜色
 */
heasecXFF.getNotificationColor = function (type) {
    var colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    };
    return colors[type] || colors.info;
};

/**
 * 复制文本到剪贴板
 */
heasecXFF.copyToClipboard = function (text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () {
            heasecXFF.showNotification('已复制到剪贴板', 'success');
        }).catch(function () {
            heasecXFF.fallbackCopyToClipboard(text);
        });
    } else {
        heasecXFF.fallbackCopyToClipboard(text);
    }
};

/**
 * 备用复制方法
 */
heasecXFF.fallbackCopyToClipboard = function (text) {
    var textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.cssText = [
        'position: fixed',
        'top: -9999px',
        'left: -9999px'
    ].join(';');

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            heasecXFF.showNotification('已复制到剪贴板', 'success');
        } else {
            heasecXFF.showNotification('复制失败，请手动复制', 'error');
        }
    } catch (err) {
        heasecXFF.showNotification('复制失败，请手动复制', 'error');
    }

    document.body.removeChild(textArea);
};

/**
 * 显示模态框 - 通用方法
 */
heasecXFF.showModal = function (modalId, onConfirm) {
    var modal = document.getElementById(modalId);
    if (!modal) return;

    modal.style.display = 'block';

    // 绑定确认事件
    var confirmBtn = modal.querySelector('[id*="Confirm"]');
    if (confirmBtn) {
        var newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        newConfirmBtn.addEventListener('click', function () {
            modal.style.display = 'none';
            if (onConfirm) onConfirm();
        });
    }

    // 绑定关闭事件
    // 绑定关闭事件
    // [HeaSec Update] 排除 Overlay，禁用点击遮罩层关闭
    var closeElements = modal.querySelectorAll('[id*="Close"], [id*="Cancel"]'); // Removed [id*="Overlay"]
    closeElements.forEach(function (element) {
        element.addEventListener('click', function () {
            modal.style.display = 'none';
        });
    });

    // ESC键关闭
    function handleEscape(e) {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            modal.style.display = 'none';
            document.removeEventListener('keydown', handleEscape);
        }
    }
    document.addEventListener('keydown', handleEscape);
};

/**
 * 显示简单确认框
 */
heasecXFF.showSimpleConfirmModal = function (title, message, onConfirm) {
    var modalOverlay = document.createElement('div');
    modalOverlay.className = 'heasec-modal-overlay';
    modalOverlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:10000;display:flex;justify-content:center;align-items:center;opacity:0;transition:opacity 0.3s ease';

    var modalContent = document.createElement('div');
    modalContent.className = 'heasec-reset-modal';
    modalContent.style.cssText = 'background:white;border-radius:8px;padding:30px;max-width:400px;width:90%;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.3);transform:scale(0.7);transition:transform 0.3s ease';

    modalContent.innerHTML = '<div style="color:#dc3545;margin-bottom:20px;"><i class="fa fa-exclamation-triangle" style="font-size:48px;"></i></div><h3 style="margin:0 0 15px 0;color:#333;">' + title + '</h3><p style="margin:0 0 25px 0;color:#666;line-height:1.5;">' + message + '</p><div style="display:flex;gap:10px;justify-content:center;"><button class="btn btn-secondary" style="padding:10px 20px;border:1px solid #ccc;background:#f8f9fa;color:#6c757d;border-radius:4px;cursor:pointer;font-size:14px;">取消</button><button class="btn btn-danger" style="padding:10px 20px;border:none;background:#dc3545;color:white;border-radius:4px;cursor:pointer;font-size:14px;">确认</button></div>';

    document.body.appendChild(modalOverlay);
    modalOverlay.appendChild(modalContent);

    setTimeout(function () {
        modalOverlay.style.opacity = '1';
        modalContent.style.transform = 'scale(1)';
    }, 10);

    var buttons = modalContent.querySelectorAll('button');
    function closeModal() {
        modalOverlay.style.opacity = '0';
        modalContent.style.transform = 'scale(0.7)';
        setTimeout(function () {
            if (modalOverlay.parentNode) {
                modalOverlay.parentNode.removeChild(modalOverlay);
            }
        }, 300);
    }

    buttons[0].addEventListener('click', closeModal);
    buttons[1].addEventListener('click', function () {
        closeModal();
        if (onConfirm) onConfirm();
    });

    /* [HeaSec Update] 禁用点击遮罩层关闭
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) closeModal();
    });
    */
};

/**
 * 处理数据库状态模态框
 */
heasecXFF.handleDatabaseModal = function (dbStatus) {
    if (dbStatus === 'normal') return;

    var modal = document.getElementById('databaseStatusModal');
    if (!modal) return;

    this.showModal('databaseStatusModal', function () {
        // 执行数据库初始化/修复
        var confirmBtn = document.getElementById('statusModalConfirm');
        if (confirmBtn) {
            confirmBtn.disabled = true;
            var originalText = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中...';

            // 根据状态选择操作类型
            var action = (dbStatus === 'table_missing' || dbStatus === 'database_missing') ? 'init' : 'reset';

            fetch('?action=' + action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=' + action
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        heasecXFF.showNotification(data.message + '！页面即将刷新', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        heasecXFF.showNotification('操作失败：' + data.message, 'error');
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    heasecXFF.showNotification('请求失败，请重试', 'error');
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = originalText;
                });
        }
    });
};

/**
 * 格式化时间
 */
heasecXFF.formatTime = function (timestamp) {
    var date = new Date(timestamp);
    var now = new Date();
    var diff = now - date;

    var minutes = Math.floor(diff / 60000);
    var hours = Math.floor(diff / 3600000);
    var days = Math.floor(diff / 86400000);

    if (minutes < 1) {
        return '刚刚';
    } else if (minutes < 60) {
        return minutes + '分钟前';
    } else if (hours < 24) {
        return hours + '小时前';
    } else if (days < 7) {
        return days + '天前';
    } else {
        return date.toLocaleDateString('zh-CN');
    }
};

// 添加CSS动画样式
var xffStyle = document.createElement('style');
xffStyle.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    .wave-effect {
        border-radius: 15px;
        overflow: hidden;
    }
`;
document.head.appendChild(xffStyle);

/**
 * 初始化
 */
heasecXFF.init();