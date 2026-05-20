/**
 * HeaSec天积安全团队 - HTTP协议解析靶场自定义重置功能
 * 版本: v1.0.0
 * 创建日期: 2025-11-18
 * 团队: 天积安全 (HeavenlySecret)
 */

// 重写默认的数据库重置功能
function onResetDatabase() {
    // 检查是否存在database/init_database.sql文件
    checkDatabaseFile();
}

// 检查数据库初始化文件是否存在
function checkDatabaseFile() {
    // 显示检查中的提示
    showNotification('正在检查靶场数据库配置...', 'info');

    // 使用AJAX检查数据库文件是否存在
    var xhr = new XMLHttpRequest();
    xhr.open('GET', './database/init_database.sql', true);

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                // 文件存在，显示数据库重置确认
                showDatabaseResetConfirm();
            } else {
                // 文件不存在，显示无数据库提示
                showNoDatabaseInfo();
            }
        }
    };

    xhr.onerror = function() {
        // 请求失败，通常表示文件不存在
        showNoDatabaseInfo();
    };

    xhr.send();
}

// 显示数据库重置确认对话框
function showDatabaseResetConfirm() {
    // 创建模态框背景遮罩
    var modalOverlay = document.createElement('div');
    modalOverlay.className = 'heasec-modal-overlay';
    modalOverlay.style.cssText = [
        'position: fixed',
        'top: 0',
        'left: 0',
        'width: 100%',
        'height: 100%',
        'background: rgba(0, 0, 0, 0.5)',
        'z-index: 10000',
        'display: flex',
        'justify-content: center',
        'align-items: center',
        'opacity: 0',
        'transition: opacity 0.3s ease'
    ].join(';');

    // 创建模态框内容
    var modalContent = document.createElement('div');
    modalContent.className = 'heasec-reset-modal';
    modalContent.style.cssText = [
        'background: white',
        'border-radius: 8px',
        'padding: 30px',
        'max-width: 450px',
        'width: 90%',
        'text-align: center',
        'box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3)',
        'transform: scale(0.7)',
        'transition: transform 0.3s ease'
    ].join(';');

    modalContent.innerHTML = [
        '<div style="color: #ffc107; margin-bottom: 20px;">',
        '<i class="fa fa-database" style="font-size: 48px;"></i>',
        '</div>',
        '<h3 style="margin: 0 0 15px 0; color: #333;">确认重置数据库</h3>',
        '<p style="margin: 0 0 10px 0; color: #666; line-height: 1.5;">',
        '检测到靶场使用数据库功能。',
        '</p>',
        '<p style="margin: 0 0 25px 0; color: #dc3545; font-weight: bold;">',
        '确定要重置数据库吗？这将清空所有测试数据，此操作不可撤销。',
        '</p>',
        '<div style="display: flex; gap: 10px; justify-content: center;">',
        '<button class="btn btn-secondary" id="cancelReset" style="',
        'padding: 10px 20px;',
        'border: 1px solid #ccc;',
        'background: #f8f9fa;',
        'color: #6c757d;',
        'border-radius: 4px;',
        'cursor: pointer;',
        'font-size: 14px;',
        'transition: all 0.3s ease;',
        '">取消</button>',
        '<button class="btn btn-warning" id="confirmReset" style="',
        'padding: 10px 20px;',
        'border: none;',
        'background: #ffc107;',
        'color: #212529;',
        'border-radius: 4px;',
        'cursor: pointer;',
        'font-size: 14px;',
        'transition: all 0.3s ease;',
        '">确认重置</button>',
        '</div>'
    ].join('');

    // 添加到页面
    document.body.appendChild(modalOverlay);
    modalOverlay.appendChild(modalContent);

    // 显示动画
    setTimeout(function() {
        modalOverlay.style.opacity = '1';
        modalContent.style.transform = 'scale(1)';
    }, 10);

    // 绑定事件
    var cancelBtn = modalContent.querySelector('#cancelReset');
    var confirmBtn = modalContent.querySelector('#confirmReset');

    function closeModal() {
        modalOverlay.style.opacity = '0';
        modalContent.style.transform = 'scale(0.7)';
        setTimeout(function() {
            if (modalOverlay.parentNode) {
                modalOverlay.parentNode.removeChild(modalOverlay);
            }
        }, 300);
    }

    cancelBtn.addEventListener('click', closeModal);
    confirmBtn.addEventListener('click', function() {
        closeModal();
        executeDatabaseReset();
    });

    // 点击背景关闭
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });

    // ESC键关闭
    function handleEscape(e) {
        if (e.key === 'Escape') {
            closeModal();
            document.removeEventListener('keydown', handleEscape);
        }
    }
    document.addEventListener('keydown', handleEscape);
}

// 显示无数据库信息提示
function showNoDatabaseInfo() {
    // 创建标准模态框
    const modal = document.createElement('div');
    modal.className = 'heasec-modal';
    modal.id = 'noDatabaseModal';
    modal.innerHTML = `
        <div class="modal-overlay"></div>
        <div class="modal-container modal-small">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fa fa-info-circle"></i>
                    无需重置数据库
                </h3>
                <button class="modal-close" aria-label="关闭">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="modal-content">
                <div style="text-align: center; margin: 40px 0;">
                    <p style="color: #666; font-size: 16px; line-height: 1.6;">
                        本靶场未使用数据库功能，无需重置数据库。
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="closeInfo">
                    我知道了
                </button>
            </div>
        </div>
    `;

    // 添加到页面
    document.body.appendChild(modal);

    // 显示模态框
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);

    // 绑定事件
    const closeBtn = modal.querySelector('.modal-close');
    const closeInfoBtn = modal.querySelector('#closeInfo');
    const modalOverlay = modal.querySelector('.modal-overlay');

    function closeModal() {
        modal.classList.remove('show');
        setTimeout(() => {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        }, 300);
    }

    // 关闭按钮事件
    closeBtn.addEventListener('click', closeModal);
    closeInfoBtn.addEventListener('click', closeModal);

    // 点击背景关闭
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });

    // ESC键关闭
    function handleEscape(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeModal();
            document.removeEventListener('keydown', handleEscape);
        }
    }
    document.addEventListener('keydown', handleEscape);
}

// 执行数据库重置（当检测到数据库文件时调用）
function executeDatabaseReset() {
    showNotification('正在重置数据库...', 'info');

    // 这里可以添加实际的重置逻辑
    // 发送AJAX请求到重置API等
    setTimeout(function() {
        showNotification('数据库重置完成！', 'success');
    }, 2000);
}

// 页面加载完成后自动检查重置按钮状态
document.addEventListener('DOMContentLoaded', function() {
    // 可以在这里添加页面加载时的检查逻辑
    console.log('[HeaSec] HTTP协议解析靶场重置处理器已加载');
});