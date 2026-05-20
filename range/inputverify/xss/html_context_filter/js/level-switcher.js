/**
 * HeaSec天积安全团队 - HTML上下文XSS过滤靶场 - 关卡切换模块
 * 版本: v1.0.0
 * 创建日期: 2026-01-14
 * 团队: 天积安全 (HeavenlySecret)
 */

(function() {
    'use strict';

    var currentLevel = 1;
    var totalLevels = 3;

    /**
     * 切换到指定关卡
     * @param {number} level - 关卡编号
     */
    function switchLevel(level) {
        if (level < 1 || level > totalLevels) {
            console.error('[HeaSec] 无效的关卡编号:', level);
            return;
        }

        // 隐藏所有关卡
        var levels = document.querySelectorAll('.level-content');
        for (var i = 0; i < levels.length; i++) {
            levels[i].style.display = 'none';
        }

        // 显示目标关卡
        var targetLevel = document.getElementById('level-' + level);
        if (targetLevel) {
            targetLevel.style.display = 'block';
            currentLevel = level;

            // 更新弹窗检测器的当前关卡
            if (window.HeaSecXSSDetector) {
                window.HeaSecXSSDetector.setCurrentLevel(level);
            }

            // 清空之前的提示消息
            clearMessages();

            // 更新Tab状态
            updateTabStatus(level);
        } else {
            console.error('[HeaSec] 找不到关卡:', level);
        }
    }

    /**
     * 更新Tab状态
     * @param {number} activeLevel - 当前激活的关卡
     */
    function updateTabStatus(activeLevel) {
        var tabs = document.querySelectorAll('.level-tab');
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].classList.remove('active');
        }
        var activeTab = document.querySelector('.level-tab[data-level="' + activeLevel + '"]');
        if (activeTab) {
            activeTab.classList.add('active');
        }
    }

    /**
     * 清空所有提示消息
     */
    function clearMessages() {
        var messages = document.querySelectorAll('.alert');
        for (var i = 0; i < messages.length; i++) {
            messages[i].remove();
        }
    }

    /**
     * 显示下一关按钮
     */
    function showNextLevelButton() {
        var formActions = document.querySelector('#level-' + currentLevel + ' .form-actions');
        if (formActions) {
            if (currentLevel < 3) {
                var nextLevel = currentLevel + 1;
                formActions.innerHTML =
                    '<button type="button" class="tech-btn tech-btn-success" onclick="HeaSecLevelSwitcher.switchLevel(' + nextLevel + ')">' +
                    '<i class="fa fa-arrow-right"></i> 下一关</button>';
            } else {
                formActions.innerHTML =
                    '<button type="button" class="tech-btn tech-btn-success" onclick="HeaSecLevelSwitcher.switchLevel(1)">' +
                    '<i class="fa fa-refresh"></i> 返回第一关</button>';
            }
        }
    }

    // 绑定Tab点击事件
    function bindTabEvents() {
        var tabs = document.querySelectorAll('.level-tab');
        for (var i = 0; i < tabs.length; i++) {
            tabs[i].addEventListener('click', function() {
                var level = parseInt(this.getAttribute('data-level'));
                switchLevel(level);
            });
        }
    }

    // 初始化
    function init() {
        bindTabEvents();
        console.log('[HeaSec] 关卡切换模块已初始化');
    }

    // DOM加载完成后初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // 暴露到全局
    window.HeaSecLevelSwitcher = {
        switchLevel: switchLevel,
        getCurrentLevel: function() { return currentLevel; },
        showNextLevelButton: showNextLevelButton
    };

})();
