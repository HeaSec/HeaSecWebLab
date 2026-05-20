/**
 * HeaSec天积安全团队 - JavaScript 绕过靶场公共脚本
 * 版本: v1.0.0
 * 创建日期: 2025-12-24
 * 团队: 天积安全 (HeavenlySecret)
 * 说明: 关卡公共逻辑
 */

(function() {
    'use strict';

    /**
     * 显示验证结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 结果消息
     */
    function showResult(success, message) {
        var container = document.getElementById('result-container');
        if (!container) return;

        container.style.display = 'block';
        container.className = 'detection-result';

        var alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-' + (success ? 'success' : 'error');

        var icon = success ? 'check-circle' : 'exclamation-triangle';
        var content = '<div>' +
            '<i class="fa fa-' + icon + '"></i>' +
            '<strong>' + message + '</strong>' +
        '</div>';

        alertDiv.innerHTML = content;
        container.innerHTML = '';
        container.appendChild(alertDiv);
    }

    /**
     * 关卡完成处理
     * @param {string} buttonUrl - 下一关按钮链接URL
     * @param {string} buttonText - 按钮文字
     */
    function onLevelComplete(buttonUrl, buttonText) {
        var container = document.getElementById('result-container');
        if (!container) return;

        var alertDiv = container.querySelector('.alert');
        if (!alertDiv) return;

        var hintDiv = document.createElement('p');
        hintDiv.className = 'alert-hint';

        var buttonHtml = '<div style="margin-top: 15px;">' +
            '<a href="' + buttonUrl + '" class="tech-btn tech-btn-success">' +
            '<i class="fa fa-arrow-right"></i> ' + buttonText +
            '</a>' +
        '</div>';

        hintDiv.innerHTML = buttonHtml;
        alertDiv.appendChild(hintDiv);
    }

    /**
     * 第三关完成处理 - 显示恭喜弹窗
     * @param {Object} congratsConfig - 恭喜弹窗配置
     */
    function onLevel3Complete(congratsConfig) {
        var container = document.getElementById('result-container');
        if (!container) return;

        var alertDiv = container.querySelector('.alert');
        if (!alertDiv) return;

        var hintDiv = document.createElement('p');
        hintDiv.className = 'alert-hint';

        var buttonHtml = '<div style="margin-top: 15px;">' +
            '<a href="index.php" class="tech-btn tech-btn-success">' +
            '<i class="fa fa-arrow-left"></i> 返回第一关' +
            '</a>' +
        '</div>';

        hintDiv.innerHTML = buttonHtml;
        alertDiv.appendChild(hintDiv);

        // 显示恭喜弹窗
        if (typeof HeaSecCongratsModal !== 'undefined') {
            setTimeout(function() {
                HeaSecCongratsModal.show(congratsConfig);
            }, 500);
        }
    }

    // 将函数暴露到全局作用域（使用 HeaSec 命名空间）
    window.HeaSec = window.HeaSec || {};
    window.HeaSec.LevelCommon = {
        showResult: showResult,
        onLevelComplete: onLevelComplete,
        onLevel3Complete: onLevel3Complete
    };

})();
