/**
 * HeaSec天积安全团队 - 成就卡片恭喜消息模块
 * Achievement Card Congrats Module
 * 版本: v2.1.0
 * 创建日期: 2025-11-19
 * 重构日期: 2026-02-11
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明：从 HeaSec_AchievementCard.php 内联脚本提取为独立模块
 * 功能：检测新成就解锁并显示恭喜弹窗
 */

(function () {
    'use strict';

    var HeaSecAchievementCongrats = {
        /**
         * 初始化恭喜功能
         * @param {string} containerId - 成就卡片容器的DOM ID
         * @param {Object} config - 配置对象
         */
        init: function (containerId, config) {
            if (!containerId || !config) return;

            var currentStarCount = config.achievedCount || 0;
            var storageKey = config.storageKey || 'achievement_previous_count';
            var previousStarCount = parseInt(localStorage.getItem(storageKey) || currentStarCount);

            localStorage.setItem(storageKey, currentStarCount.toString());
            var isNewAchievement = currentStarCount > previousStarCount;

            if (isNewAchievement) {
                this.showCongrats(currentStarCount, config);
            }

            this.bindResetEvent(storageKey);
            this.bindStarUnlockedEvent(containerId, config);
        },

        /**
         * 显示恭喜弹窗
         * @param {number} starCount - 当前星星数
         * @param {Object} config - 配置对象
         */
        showCongrats: function (starCount, config) {
            var defaultConfig = {
                thresholds: [1, 2, 3],
                totalStars: 3,
                isFullyCompleted: false,
                learningStatus: '学习中'
            };

            var finalConfig = this.mergeConfig(defaultConfig, config);
            finalConfig.achievedCount = starCount;
            finalConfig.isFullyCompleted = starCount >= finalConfig.totalStars;
            finalConfig.learningStatus = finalConfig.isFullyCompleted ? '已掌握' : '学习中';

            var congratsMessages = this.generateCongratsMessages(finalConfig);
            var congratsConfig = {
                title: congratsMessages.title,
                message: congratsMessages.message,
                buttonText: congratsMessages.buttonText,
                enableNextRangeButton: finalConfig.enableNextRangeButton || false,
                rangeCode: finalConfig.rangeCode || '',
                updateLearningStatus: finalConfig.updateLearningStatus || false,
                updateStatusApiUrl: finalConfig.updateStatusApiUrl,
                nextRangeApiUrl: finalConfig.nextRangeApiUrl,
                learningStatus: finalConfig.learningStatus,
                showParticles: finalConfig.showParticles !== false,
                particleCount: finalConfig.isFullyCompleted ? (finalConfig.particleCount || 12) : (finalConfig.particleCount || 8),
                animationDuration: finalConfig.isFullyCompleted ? (finalConfig.animationDuration || 3000) : (finalConfig.animationDuration || 2000),
                onShow: function () { },
                onClose: function () { }
            };

            if (typeof HeaSecCongratsModal !== 'undefined' && HeaSecCongratsModal.show) {
                HeaSecCongratsModal.show(congratsConfig);
            }
        },

        /**
         * 生成恭喜消息文案
         * @param {Object} config - 配置对象
         * @returns {Object} 包含 title、message、buttonText 的对象
         */
        generateCongratsMessages: function (config) {
            var messages = config.customMessages || {};
            var isComplete = config.isFullyCompleted;
            var count = config.achievedCount;
            var total = config.totalStars;

            var defaultMessages = {
                partial_title: '🎉 恭喜你掌握了一个新技能',
                complete_title: '🏆 恭喜你获得了全部成就！',
                partial: '你已经掌握了 %d/%d 种技能！继续努力，获得更多的成就！',
                complete: '太棒了！你已经掌握了所有%d种技能，成为了真正的安全大师！',
                buttonText: '继续学习'
            };

            var finalMessages = this.mergeConfig(defaultMessages, messages);

            return {
                title: isComplete ? finalMessages.complete_title : finalMessages.partial_title,
                message: isComplete ?
                    this.formatString(finalMessages.complete, total) :
                    this.formatString(finalMessages.partial, count, total),
                buttonText: finalMessages.buttonText
            };
        },

        /**
         * 格式化字符串，替换 %d 占位符
         * @param {string} str - 包含 %d 占位符的字符串
         * @returns {string} 替换后的字符串
         */
        formatString: function (str) {
            var args = Array.prototype.slice.call(arguments, 1);
            var index = 0;
            return str.replace(/%d/g, function () {
                return args[index++];
            });
        },

        /**
         * 合并配置对象
         * @param {Object} target - 目标对象（默认值）
         * @param {Object} source - 源对象（覆盖值）
         * @returns {Object} 合并后的对象
         */
        mergeConfig: function (target, source) {
            var result = {};
            for (var key in target) {
                if (target.hasOwnProperty(key)) {
                    result[key] = target[key];
                }
            }
            if (source) {
                for (var key in source) {
                    if (source.hasOwnProperty(key)) {
                        result[key] = source[key];
                    }
                }
            }
            return result;
        },

        /**
         * 绑定重置事件 - 监听 heasec:rangeReset
         * @param {string} storageKey - localStorage 键名
         */
        bindResetEvent: function (storageKey) {
            document.addEventListener('heasec:rangeReset', function () {
                localStorage.removeItem(storageKey);
            });
        },

        /**
         * 绑定星星解锁事件 - 监听 heasec:starUnlocked
         * @param {string} containerId - 容器 DOM ID
         * @param {Object} config - 配置对象
         */
        bindStarUnlockedEvent: function (containerId, config) {
            var self = this;
            document.addEventListener('heasec:starUnlocked', function (e) {
                setTimeout(function () {
                    var container = document.getElementById(containerId);
                    if (container) {
                        var storageKey = config.storageKey || 'achievement_previous_count';

                        // 尝试获取最新的 starCount
                        // 1. 从事件详情获取
                        // 2. 从 DOM data-config 重新读取 (因为前端可能已经更新了 attribute)
                        // 3. 回退到闭包 config (不支持动态更新)
                        var currentStarCount = 0;

                        if (e.detail && typeof e.detail.starCount !== 'undefined') {
                            currentStarCount = parseInt(e.detail.starCount);
                        } else {
                            // 尝试从 DOM 重新读取配置
                            var configAttr = container.getAttribute('data-config');
                            if (configAttr) {
                                try {
                                    var newConfig = JSON.parse(configAttr);
                                    if (newConfig && typeof newConfig.achievedCount !== 'undefined') {
                                        currentStarCount = parseInt(newConfig.achievedCount);
                                        // 更新闭包中的 config，保持同步
                                        config.achievedCount = currentStarCount;
                                    } else {
                                        currentStarCount = config.achievedCount || 0;
                                    }
                                } catch (err) {
                                    currentStarCount = config.achievedCount || 0;
                                }
                            } else {
                                currentStarCount = config.achievedCount || 0;
                            }
                        }

                        var previousStarCount = parseInt(localStorage.getItem(storageKey) || 0);

                        // 只有当当前星星数 > 历史最高记录时才显示恭喜
                        // 并且更新 localStorage
                        if (currentStarCount > previousStarCount) {
                            // 更新 config 对象以便 showCongrats 使用正确的数据
                            config.achievedCount = currentStarCount;
                            self.showCongrats(currentStarCount, config);
                            localStorage.setItem(storageKey, currentStarCount.toString());
                        }
                    }
                }, 500);
            });
        }
    };

    // 暴露到全局对象
    window.HeaSecAchievementCongrats = HeaSecAchievementCongrats;

    /**
     * 自动初始化：查找所有 .heasec-achievement-card 容器并初始化恭喜功能
     * 从容器的 data-config 和 data-storage-key 属性读取配置
     */
    document.addEventListener('DOMContentLoaded', function () {
        var cards = document.querySelectorAll('.heasec-achievement-card');
        for (var i = 0; i < cards.length; i++) {
            var card = cards[i];
            var containerId = card.id;
            if (!containerId) continue;

            // 从 data-config 属性读取配置
            var configAttr = card.getAttribute('data-config');
            if (!configAttr) continue;

            try {
                var fullConfig = JSON.parse(configAttr);
            } catch (e) {
                console.warn('[HeaSec AchievementCard] 解析 data-config 失败:', e);
                continue;
            }

            // 检查是否启用恭喜功能（默认启用）
            var congratsConfig = fullConfig.congratsConfig || {};
            // enableCongrats 默认为 true（精简配置中可能省略）
            var enableCongrats = congratsConfig.enableCongrats !== undefined ? congratsConfig.enableCongrats : true;
            if (!enableCongrats) continue;

            // 从 data-storage-key 获取存储键
            var storageKey = card.getAttribute('data-storage-key') || 'achievement_previous_count';

            // JS 侧默认值（与 PHP 侧保持一致，精简配置中省略的字段由此提供）
            var defaultCongratsMessages = {
                partial_title: '🎉 恭喜你掌握了一个新技能',
                complete_title: '🏆 恭喜你获得了全部成就！',
                partial: '你已经掌握了 %d/%d 种技能！继续努力，获得更多的成就！',
                complete: '太棒了！你已经掌握了所有%d种技能，成为了真正的安全大师！',
                buttonText: '继续学习'
            };

            // 合并自定义消息（data-config 中的覆盖默认值）
            var customMessages = congratsConfig.messages || {};
            var mergedMessages = {};
            for (var key in defaultCongratsMessages) {
                mergedMessages[key] = customMessages[key] || defaultCongratsMessages[key];
            }

            // 公共组件基础路径（由 PHP 传入，用于拼接 API URL）
            var commonBasePath = fullConfig.commonBasePath || '';

            // 构建初始化配置（使用默认值填充精简配置中缺失的字段）
            var initConfig = {
                achievedCount: fullConfig.achievedCount || 0,
                storageKey: storageKey,
                rangeCode: fullConfig.rangeCode || '',
                totalStars: fullConfig.thresholds ? fullConfig.thresholds.length : 3,
                thresholds: fullConfig.thresholds || [1, 2, 3],
                enableNextRangeButton: congratsConfig.enableNextRangeButton !== undefined ? congratsConfig.enableNextRangeButton : true,
                updateLearningStatus: congratsConfig.updateLearningStatus !== undefined ? congratsConfig.updateLearningStatus : true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: fullConfig.showParticles !== false,
                particleCount: congratsConfig.particleCount || 8,
                animationDuration: congratsConfig.animationDuration || 2000,
                customMessages: mergedMessages
            };

            HeaSecAchievementCongrats.init(containerId, initConfig);
        }
    });
})();
