/**
 * Custom UI Utilities
 * Contains custom modal and toast notification functions
 */

// 创建自定义模态框函数，模拟 blocking behavior 但使用 overlay
var isModalOpen = false;
function showModal(message) {
    if (isModalOpen) return;
    isModalOpen = true;

    // 创建遮罩层
    var overlay = document.createElement('div');
    overlay.className = 'heasec-modal-overlay';
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 100000; display: flex; align-items: flex-start; justify-content: center; padding-top: 20px; backdrop-filter: blur(2px);';

    // 创建对话框
    var modal = document.createElement('div');
    modal.className = 'heasec-modal-box';
    modal.style.cssText = 'background-color: white; padding: 20px 30px; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); max-width: 400px; width: 90%; text-align: center; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; animation: heasecPopIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);';

    // 消息内容
    var content = document.createElement('p');
    content.textContent = message;
    content.style.cssText = 'margin: 0 0 20px 0; color: #333; font-size: 16px; line-height: 1.5;';

    // 确认按钮
    var btn = document.createElement('button');
    btn.textContent = '确定';
    btn.style.cssText = 'background-color: #0d6efd; color: white; border: none; padding: 10px 25px; border-radius: 4px; cursor: pointer; font-size: 14px; transition: background-color 0.2s;';
    btn.onmouseover = function () { this.style.backgroundColor = '#0b5ed7'; };
    btn.onmouseout = function () { this.style.backgroundColor = '#0d6efd'; };

    // 点击关闭
    btn.onclick = function () {
        // 添加关闭动画
        modal.style.transform = 'scale(0.8)';
        modal.style.opacity = '0';
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.2s';

        setTimeout(function () {
            if (overlay.parentNode) {
                document.body.removeChild(overlay);
            }
            isModalOpen = false;
        }, 200);
    };

    modal.appendChild(content);
    modal.appendChild(btn);
    overlay.appendChild(modal);

    // 添加简单的动画样式
    if (!document.getElementById('heasec-modal-style')) {
        var style = document.createElement('style');
        style.id = 'heasec-modal-style';
        style.textContent = '@keyframes heasecPopIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }';
        document.head.appendChild(style);
    }

    document.body.appendChild(overlay);
}

// 创建自定义提示框函数，替换原生的alert，避免阻塞
function showToast(message) {
    // 简单的防抖，如果提示框已存在则不重复创建
    if (document.querySelector('.heasec-warning-toast')) return;

    var toast = document.createElement('div');
    toast.className = 'heasec-warning-toast';
    toast.innerHTML = '<i class="fa fa-exclamation-circle" style="margin-right:8px;"></i>' + message;

    // 内联样式确保显示效果
    toast.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); background-color: #ff4444; color: white; padding: 12px 24px; border-radius: 4px; z-index: 99999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; display: flex; align-items: center; opacity: 0; transition: opacity 0.3s ease; pointer-events: none;';

    document.body.appendChild(toast);

    // 强制重绘后显示
    requestAnimationFrame(function () {
        toast.style.opacity = '1';
    });

    // 2秒后自动消失
    setTimeout(function () {
        toast.style.opacity = '0';
        setTimeout(function () {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 2000);
}
