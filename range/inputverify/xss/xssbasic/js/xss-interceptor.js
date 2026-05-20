/**
 * HeaSec天积安全团队 - XSS弹窗检测系统
 * 版本: v1.0.0
 * 创建日期: 2025-12-14
 * 团队: 天积安全 (HeavenlySecret)
 * 说明: 必须在页面内容加载之前设置，拦截所有弹窗
 */

(function () {
    'use strict';

    // 保存原始弹窗函数
    var originalAlert = window.alert;
    var originalConfirm = window.confirm;
    var originalPrompt = window.prompt;

    // XSS触发状态 - 使用sessionStorage防止页面刷新后重复记录
    var sessionKey = 'heasec_xss_triggered_' + window.location.pathname;
    var xssTriggered = JSON.parse(sessionStorage.getItem(sessionKey) || '{}');
    if (!xssTriggered || typeof xssTriggered !== 'object') {
        xssTriggered = {
            reflected: false,
            stored: false,
            dom: false
        };
    }

    // 检测XSS类型（简化版本，完整版本在底部）
    window.detectXSSType = function (popupType) {
        var xssType = '';
        var urlParams = new URLSearchParams(window.location.search);

        console.log('[HeaSec] XSS弹窗检测:', popupType);
        console.log('[HeaSec] URL参数:', urlParams.toString());

        // 优先级判断：DOM型XSS
        if (urlParams.has('username') && urlParams.get('username')) {
            xssType = 'dom';
            console.log('[HeaSec] 检测到DOM型XSS, username:', urlParams.get('username'));
        }
        // 反射型XSS：检查是否有搜索结果
        else if (document.querySelector('.search-result')) {
            xssType = 'reflected';
            console.log('[HeaSec] 检测到反射型XSS');
        }
        // 存储型XSS：检查页面是否有留言内容
        else if (document.querySelector('.message-item')) {
            xssType = 'stored';
            console.log('[HeaSec] 检测到存储型XSS');
        }
        // 默认：根据当前活动标签判断
        else {
            var activeTab = document.querySelector('.tab.active');
            if (activeTab) {
                xssType = activeTab.getAttribute('data-tab');
                console.log('[HeaSec] 根据活动标签检测到XSS:', xssType);
            } else {
                console.log('[HeaSec] 无法确定XSS类型');
                return; // 无法确定类型，不记录
            }
        }

        // 记录XSS触发（每次弹窗都计数）
        console.log('[HeaSec] 记录XSS触发:', xssType);
        recordXSSTrigger(xssType);
    };

    // 记录XSS触发到后端
    function recordXSSTrigger(xssType) {
        console.log('[HeaSec] 开始记录XSS触发:', xssType);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/record_xss.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        console.log('[HeaSec] XSS记录成功:', response);

                        // 延迟触发成就更新事件，确保数据库操作完成
                        setTimeout(function () {
                            // 触发成就更新事件，包含更详细的信息
                            document.dispatchEvent(new CustomEvent('heasec:starUnlocked', {
                                detail: {
                                    xssType: xssType,
                                    success_count: response.success_count,
                                    timestamp: Date.now()
                                }
                            }));

                            // 同时触发成就刷新事件，强制更新成就卡片
                            document.dispatchEvent(new CustomEvent('heasec:achievementRefresh', {
                                detail: {
                                    xssType: xssType,
                                    forceUpdate: true
                                }
                            }));

                            console.log('[HeaSec] 成就更新事件已触发');
                        }, 300);
                    } else {
                        console.log('[HeaSec] XSS记录失败:', response.message);
                    }
                } catch (e) {
                    console.log('[HeaSec] API响应解析失败:', e);
                }
            }
        };
        xhr.send('xss_type=' + encodeURIComponent(xssType));
    }

    // 重写弹窗函数 - 立即拦截
    window.alert = function (message) {
        console.log('[HeaSec] 拦截到alert:', message);
        window.detectXSSType('alert');
        return originalAlert.apply(this, arguments);
    };

    window.confirm = function (message) {
        console.log('[HeaSec] 拦截到confirm:', message);
        window.detectXSSType('confirm');
        return originalConfirm.apply(this, arguments);
    };

    window.prompt = function (message, defaultValue) {
        console.log('[HeaSec] 拦截到prompt:', message);
        window.detectXSSType('prompt');
        return originalPrompt.apply(this, arguments);
    };

    // 监听重置事件，清空sessionStorage
    document.addEventListener('heasec:rangeReset', function () {
        console.log('[HeaSec] 检测到重置事件，清空XSS触发记录');
        sessionStorage.removeItem(sessionKey);
        xssTriggered = {
            reflected: false,
            stored: false,
            dom: false
        };
    });

    console.log('[HeaSec] XSS拦截系统已初始化');
})();
