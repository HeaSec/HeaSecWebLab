/**
 * HeaSec天积安全团队 - URL任意跳转靶场前端交互
 * 版本: v1.0.0
 * 创建日期: 2026-04-03
 * 团队: 天积安全 (HeavenlySecret)
 */

(function() {
    'use strict';

    // 监听重置事件，清除localStorage中的成就状态
    document.addEventListener('heasec:rangeReset', function() {
        var keys = Object.keys(localStorage);
        for (var i = 0; i < keys.length; i++) {
            if (keys[i].indexOf('urlredirect_') === 0) {
                localStorage.removeItem(keys[i]);
            }
        }
    });
})();
