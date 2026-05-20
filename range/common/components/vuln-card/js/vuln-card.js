/**
 * HeaSec天积安全团队 - 漏洞挖掘卡片交互模块
 * Vulnerability Card Interaction Module
 * 版本: v1.0.0
 * 创建日期: 2026-03-07
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 功能：
 * - 漏洞提交表单处理
 * - 分数变化检测和星星解锁
 * - 恭喜消息触发
 */

(function () {
    'use strict';

    // HTML转义函数，防止XSS攻击
    function escapeHtml(text) {
        if (typeof text !== 'string') return text;
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    var HeaSecVulnCard = {
        /**
         * 获取 localStorage 存储键
         * @param {Object} config - 配置对象
         * @returns {string} 存储键
         */
        getStorageKey: function (config) {
            return (config.rangeCode || 'heasec') + '_unlocked_stars';
        },

        /**
         * 初始化漏洞卡片
         * @param {string} containerId - 卡片容器的DOM ID
         * @param {Object} config - 配置对象
         */
        init: function (containerId, config) {
            if (!containerId || !config) return;

            var container = document.getElementById(containerId);
            if (!container) return;

            // 存储配置到容器
            container._heasecVulnCardConfig = config;

            // 绑定表单提交事件
            this.bindFormSubmit(containerId, config);

            // 初始化星星状态（不弹出恭喜消息）
            this.initStarStatus(containerId, config);

            // 绑定重置事件
            this.bindResetEvent(containerId, config);

            // 绑定帮助按钮事件
            this.bindHelpButton(containerId, config);
        },

        /**
         * 绑定表单提交事件
         * @param {string} containerId - 容器ID
         * @param {Object} config - 配置对象
         */
        bindFormSubmit: function (containerId, config) {
            var self = this;
            var form = document.getElementById(containerId + '-form');
            if (!form) return;

            // 绑定动态添加参数按钮
            var addParamBtn = form.querySelector('.vuln-param-add');
            if (addParamBtn) {
                addParamBtn.addEventListener('click', function () {
                    self.addParamItem(containerId);
                });
            }

            // 绑定删除参数按钮（事件委托）
            var paramsContainer = document.getElementById(containerId + '-params');
            if (paramsContainer) {
                paramsContainer.addEventListener('click', function (e) {
                    var removeBtn = e.target.closest('.vuln-param-remove');
                    if (removeBtn) {
                        var paramItem = removeBtn.closest('.vuln-param-item');
                        if (paramItem && paramsContainer.children.length > 1) {
                            paramItem.remove();
                            self.reindexParams(containerId);
                        }
                    }
                });
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                self.submitVuln(containerId, config, form);
            });
        },

        /**
         * 添加参数项
         * @param {string} containerId - 容器ID
         */
        addParamItem: function (containerId) {
            var paramsContainer = document.getElementById(containerId + '-params');
            if (!paramsContainer) return;

            var index = paramsContainer.children.length;
            var paramItem = document.createElement('div');
            paramItem.className = 'vuln-param-item';
            paramItem.setAttribute('data-index', index);

            paramItem.innerHTML =
                '<select name="params[' + index + '][location]" class="form-control vuln-select vuln-param-location">' +
                '<option value="GET">GET</option>' +
                '<option value="POST">POST</option>' +
                '<option value="HEAD">HEAD</option>' +
                '</select>' +
                '<input type="text" name="params[' + index + '][name]" class="form-control vuln-input vuln-param-name" placeholder="参数名（可选）">' +
                '<button type="button" class="tech-btn tech-btn-danger vuln-param-remove" style="padding: 0 10px; height: 38px; width: auto;" title="删除参数">' +
                '<i class="fa fa-times"></i>' +
                '</button>' +
                '<button type="button" class="tech-btn tech-btn-info vuln-param-add" style="padding: 0 10px; height: 38px; width: auto;" title="添加参数">' +
                '<i class="fa fa-plus"></i>' +
                '</button>';

            paramsContainer.appendChild(paramItem);
        },

        /**
         * 重新索引参数
         * @param {string} containerId - 容器ID
         */
        reindexParams: function (containerId) {
            var paramsContainer = document.getElementById(containerId + '-params');
            if (!paramsContainer) return;

            var items = paramsContainer.querySelectorAll('.vuln-param-item');
            items.forEach(function (item, index) {
                item.setAttribute('data-index', index);
                var nameInput = item.querySelector('.vuln-param-name');
                var locationSelect = item.querySelector('.vuln-param-location');
                if (nameInput) nameInput.name = 'params[' + index + '][name]';
                if (locationSelect) locationSelect.name = 'params[' + index + '][location]';
            });
        },

        /**
         * 提交漏洞验证
         * @param {string} containerId - 容器ID
         * @param {Object} config - 配置对象
         * @param {HTMLFormElement} form - 表单元素
         */
        submitVuln: function (containerId, config, form) {
            var self = this;
            var submitBtn = form.querySelector('.vuln-submit-btn');
            var messageEl = document.getElementById(containerId + '-message');

            // 获取表单数据
            var vulnUrl = form.querySelector('[name="vuln_url"]').value.trim();
            var vulnType = form.querySelector('[name="vuln_type"]').value;

            // 收集参数列表
            var params = [];
            var paramItems = form.querySelectorAll('.vuln-param-item');
            paramItems.forEach(function (item) {
                var nameInput = item.querySelector('.vuln-param-name');
                var locationSelect = item.querySelector('.vuln-param-location');
                if (nameInput && locationSelect) {
                    var name = nameInput.value.trim();
                    var location = locationSelect.value;
                    if (name) {
                        params.push({
                            name: name,
                            location: location
                        });
                    }
                }
            });

            // 验证必填项
            if (!vulnUrl || !vulnType) {
                self.showMessage(messageEl, 'error', '请填写必填项');
                return;
            }

            // 显示loading状态
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');

            // 准备请求数据
            var requestData = {
                vuln_url: vulnUrl,
                vuln_type: vulnType,
                params: params,
                range_code: config.rangeCode
            };

            // 发送请求
            var xhr = new XMLHttpRequest();
            xhr.open(config.submitMethod || 'POST', config.validateApiUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('loading');

                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success && response.data && response.data.valid) {
                            // 验证成功
                            self.showMessage(messageEl, 'success', '漏洞审核通过');

                            // 更新记录列表
                            self.addRecord(containerId, response.data);

                            // 更新分数和星星状态（使用后端返回的数据）
                            var newScore = response.data.totalScore || (config.totalScore + response.data.score);
                            var newStars = response.data.unlockedStars || 0;
                            self.updateScoreWithStars(containerId, config, newScore, newStars);

                            // 清空表单
                            form.reset();
                        } else if (response.data && response.data.already_submitted) {
                            // 已提交过
                            self.showMessage(messageEl, 'warning', '该漏洞已提交过');
                        } else {
                            // 验证失败
                            var errorMsg = '漏洞审核失败';
                            // 如果有具体错误提示，追加显示
                            if (response.data && response.data.hint) {
                                errorMsg += '：' + response.data.hint;
                            }
                            self.showMessage(messageEl, 'error', errorMsg);
                        }
                    } catch (e) {
                        self.showMessage(messageEl, 'error', '请求处理失败，请稍后重试');
                    }
                }
            };

            xhr.onerror = function () {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                self.showMessage(messageEl, 'error', '网络请求失败，请检查连接');
            };

            xhr.send(JSON.stringify(requestData));
        },

        /**
         * 显示消息提示
         * @param {HTMLElement} messageEl - 消息元素
         * @param {string} type - 消息类型 (success/error/warning)
         * @param {string} message - 消息内容
         */
        showMessage: function (messageEl, type, message) {
            if (!messageEl) return;

            var iconMap = {
                success: 'fa-check-circle',
                error: 'fa-times-circle',
                warning: 'fa-exclamation-circle'
            };

            messageEl.className = 'vuln-submit-message show ' + type;
            messageEl.innerHTML = '<i class="fa ' + iconMap[type] + '"></i>' + message;

            // 5秒后自动隐藏
            setTimeout(function () {
                messageEl.classList.remove('show');
            }, 5000);
        },

        /**
         * 添加漏洞记录到列表
         * @param {string} containerId - 容器ID
         * @param {Object} vulnInfo - 漏洞信息
         */
        addRecord: function (containerId, vulnInfo) {
            var recordsList = document.getElementById(containerId + '-records');
            if (!recordsList || !vulnInfo || !vulnInfo.vulnInfo) return;

            var info = vulnInfo.vulnInfo;

            // 移除空状态提示
            var emptyEl = recordsList.querySelector('.vuln-record-empty');
            if (emptyEl) {
                emptyEl.remove();
            }

            // 创建记录元素
            var recordItem = document.createElement('div');
            recordItem.className = 'vuln-record-item new-record';

            var time = new Date().toLocaleString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });

            // 构建参数标签HTML
            var paramsHtml = '';
            if (info.params && info.params.length > 0) {
                paramsHtml = '<div class="vuln-record-params">';
                info.params.forEach(function (param) {
                    var locationClass = (param.location || 'GET').toLowerCase();
                    paramsHtml += '<span class="vuln-param-tag ' + escapeHtml(locationClass) + '">' +
                        '<span class="param-location">' + escapeHtml(param.location) + '</span>' +
                        '<span class="param-name">' + escapeHtml(param.name) + '</span>' +
                        '</span>';
                });
                paramsHtml += '</div>';
            } else if (info.param) {
                // 兼容旧格式
                paramsHtml = '<span class="vuln-record-param">参数: ' + escapeHtml(info.param) + '</span>';
            }

            recordItem.innerHTML =
                '<div class="vuln-record-info">' +
                '<span class="vuln-record-type">' +
                '<i class="fa fa-tag"></i>' + escapeHtml(info.type) +
                '</span>' +
                '<span class="vuln-record-url">' + escapeHtml(info.url) + '</span>' +
                paramsHtml +
                '</div>' +
                '<div class="vuln-record-meta">' +
                '<span class="vuln-record-score">+' + escapeHtml(vulnInfo.score) + '分</span>' +
                '<span class="vuln-record-time">' + escapeHtml(time) + '</span>' +
                '</div>';

            // 插入到列表顶部
            recordsList.insertBefore(recordItem, recordsList.firstChild);

            // 移除动画类
            setTimeout(function () {
                recordItem.classList.remove('new-record');
            }, 1000);

            // 触发漏洞提交成功事件
            document.dispatchEvent(new CustomEvent('heasec:vulnSubmitted', {
                detail: {
                    rangeCode: vulnInfo.rangeCode,
                    vulnType: info.type,
                    score: vulnInfo.score
                }
            }));
        },

        /**
         * 更新分数（保留兼容性，自动计算星星）
         * @param {string} containerId - 容器ID
         * @param {Object} config - 配置对象
         * @param {number} newScore - 新分数
         */
        updateScore: function (containerId, config, newScore) {
            var container = document.getElementById(containerId);
            if (!container) return;

            // 计算新的解锁星星数量
            var newUnlockedStars = 0;
            for (var i = 0; i < config.scoreThresholds.length; i++) {
                if (newScore >= config.scoreThresholds[i]) {
                    newUnlockedStars++;
                } else {
                    break;
                }
            }

            this.updateScoreWithStars(containerId, config, newScore, newUnlockedStars);
        },

        /**
         * 更新分数和星星状态（核心方法）
         * @param {string} containerId - 容器ID
         * @param {Object} config - 配置对象
         * @param {number} newScore - 新分数
         * @param {number} newStars - 新的解锁星星数量
         */
        updateScoreWithStars: function (containerId, config, newScore, newStars) {
            var container = document.getElementById(containerId);
            if (!container) return;

            // 获取之前存储的星星数
            var storageKey = this.getStorageKey(config);
            var oldStars = parseInt(localStorage.getItem(storageKey)) || 0;

            // 更新配置中的分数和星星数
            config.totalScore = newScore;
            config.unlockedStars = newStars;
            container._heasecVulnCardConfig.totalScore = newScore;
            container._heasecVulnCardConfig.unlockedStars = newStars;

            // 更新分数显示
            var scoreDisplay = container.querySelector('.score-current');
            if (scoreDisplay) {
                scoreDisplay.textContent = newScore;
            }

            // 更新 localStorage 中的星星状态
            localStorage.setItem(storageKey, newStars.toString());

            // 更新星星显示
            this.updateStarDisplay(container, newStars, config);

            // 检查是否有新星星解锁（只有星星增加时才弹恭喜）
            if (newStars > oldStars) {
                // 触发星星解锁事件
                document.dispatchEvent(new CustomEvent('heasec:starUnlocked', {
                    detail: {
                        starCount: newStars,
                        previousCount: oldStars,
                        rangeCode: config.rangeCode
                    }
                }));

                // 显示恭喜弹窗
                this.showCongrats(newStars, config);

                // 检查是否全部完成
                if (newStars >= config.totalStars) {
                    document.dispatchEvent(new CustomEvent('heasec:vulnAllFound', {
                        detail: {
                            rangeCode: config.rangeCode,
                            totalScore: newScore
                        }
                    }));
                }
            }
        },

        /**
         * 更新星星显示
         * @param {HTMLElement} container - 容器元素
         * @param {number} unlockedCount - 解锁数量
         * @param {Object} config - 配置对象
         */
        updateStarDisplay: function (container, unlockedCount, config) {
            var starContainer = container.querySelector('.heasec-star-system');
            if (starContainer && starContainer._heasecStarInstance) {
                starContainer._heasecStarInstance.unlockMultipleStars(unlockedCount, true);
            }
        },

        /**
         * 初始化星星状态（页面加载时调用）
         * 仅更新星星显示，不弹出恭喜消息
         * 直接使用 PHP 传来的 config.unlockedStars 初始化，不再需要调用 API
         * @param {string} containerId - 容器ID
         * @param {Object} config - 配置对象
         */
        initStarStatus: function (containerId, config) {
            var container = document.getElementById(containerId);
            if (!container) return;

            var storageKey = this.getStorageKey(config);
            var storedStars = localStorage.getItem(storageKey);

            if (storedStars !== null) {
                // localStorage 有数据，直接使用并更新显示
                config.unlockedStars = parseInt(storedStars);
                this.updateStarDisplay(container, config.unlockedStars, config);
            } else {
                // localStorage 无数据，直接使用 PHP 传来的配置值
                // PHP 端已从数据库获取最新状态并计算了 unlockedStars
                config.unlockedStars = config.unlockedStars || 0;
                localStorage.setItem(storageKey, config.unlockedStars.toString());
                this.updateStarDisplay(container, config.unlockedStars, config);
            }
        },

        /**
         * 显示恭喜弹窗
         * @param {number} starCount - 当前星星数
         * @param {Object} config - 配置对象
         */
        showCongrats: function (starCount, config) {
            var congratsConfig = config.congratsConfig || {};
            if (congratsConfig.enableCongrats === false) return;

            var isComplete = starCount >= config.totalStars;
            var messages = congratsConfig.messages || {};

            var defaultMessages = {
                partial_title: '🎉 恭喜你解锁了一颗新星星！',
                complete_title: '🏆 恭喜你解锁了全部星星！',
                partial: '你已解锁 %d/%d 颗星星，继续挖掘漏洞提升等级！',
                complete: '太棒了！你已解锁全部 %d 颗星星，成为真正的漏洞挖掘专家！',
                buttonText: '继续学习'
            };

            var title = isComplete ?
                (messages.complete_title || defaultMessages.complete_title) :
                (messages.partial_title || defaultMessages.partial_title);

            var message = isComplete ?
                this.formatString(messages.complete || defaultMessages.complete, config.totalStars) :
                this.formatString(messages.partial || defaultMessages.partial, starCount, config.totalStars);

            var modalConfig = {
                title: title,
                message: message,
                buttonText: messages.buttonText || defaultMessages.buttonText,
                enableNextRangeButton: congratsConfig.enableNextRangeButton !== false,
                rangeCode: config.rangeCode,
                updateLearningStatus: congratsConfig.updateLearningStatus !== false,
                updateStatusApiUrl: config.commonBasePath + 'api/update-learning-status.php',
                nextRangeApiUrl: config.commonBasePath + 'api/next-range.php',
                learningStatus: isComplete ? '已掌握' : '学习中',
                showParticles: true,
                particleCount: congratsConfig.particleCount || (isComplete ? 12 : 8),
                animationDuration: congratsConfig.animationDuration || (isComplete ? 3000 : 2000)
            };

            if (typeof HeaSecCongratsModal !== 'undefined' && HeaSecCongratsModal.show) {
                HeaSecCongratsModal.show(modalConfig);
            }
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
                return args[index++] !== undefined ? args[index - 1] : '%d';
            });
        },

        /**
         * 绑定重置事件
         * @param {string} containerId - 容器ID
         * @param {Object} config - 配置对象
         */
        bindResetEvent: function (containerId, config) {
            var self = this;
            var storageKey = this.getStorageKey(config);

            document.addEventListener('heasec:rangeReset', function () {
                // 清除 localStorage 中的星星状态
                localStorage.removeItem(storageKey);

                // 重置配置中的星星数
                config.unlockedStars = 0;
                config.totalScore = 0;

                // 更新星星显示
                var container = document.getElementById(containerId);
                if (container) {
                    self.updateStarDisplay(container, 0, config);
                }
            });
        },

        /**
         * 绑定帮助按钮事件
         * @param {string} containerId - 容器ID
         * @param {Object} config - 配置对象
         */
        bindHelpButton: function (containerId, config) {
            var self = this;
            var container = document.getElementById(containerId);
            if (!container) return;

            var helpBtn = container.querySelector('.vuln-help-btn');
            if (helpBtn) {
                helpBtn.addEventListener('click', function () {
                    self.showHelpModal(containerId);
                });
            }
        },

        /**
         * 显示填写说明模态框
         * @param {string} containerId - 容器ID
         */
        showHelpModal: function (containerId) {
            var existingModal = document.getElementById('vulnHelpModal');
            if (existingModal) {
                existingModal.style.display = 'flex';
                return;
            }

            var modalHtml =
                '<div id="vulnHelpModal" class="heasec-vuln-help-modal" style="display: flex;">' +
                '  <div class="modal-overlay"></div>' +
                '  <div class="modal-container" style="max-width: 600px;">' +
                '    <div class="modal-header">' +
                '      <h3 class="modal-title"><i class="fa fa-question-circle"></i> 填写说明</h3>' +
                '      <button type="button" class="modal-close vuln-help-modal-close">&times;</button>' +
                '    </div>' +
                '    <div class="modal-content" style="padding: 20px;">' +
                '      <div class="vuln-help-section">' +
                '        <h4><i class="fa fa-link"></i> 漏洞路径</h4>' +
                '        <p>漏洞路径从当前靶场目录的下级目录开始填写。</p>' +
                '        <p>例如：漏洞URL为 <code>http://localhost/heasecdev/range/logic/brokenac/privesc/api/vul.php</code></p>' +
                '        <p>则填写：<code>/api/vul.php</code></p>' +
                '      </div>' +
                '      <div class="vuln-help-section">' +
                '        <h4><i class="fa fa-code"></i> 漏洞参数</h4>' +
                '        <ul>' +
                '          <li>漏洞参数可以添加多个，每个选项只能填写一个参数，并选择正确的参数位置</li>' +
                '          <li>如果漏洞参数在HTTP头部，则选择 <strong>HEAD</strong></li>' +
                '          <li>如果漏洞参数在Cookie中，则参数名使用 <code>cookie:参数名</code> 的格式填写</li>' +
                '        </ul>' +
                '      </div>' +
                '      <div class="vuln-help-section">' +
                '        <h4><i class="fa fa-lightbulb-o"></i> 示例</h4>' +
                '        <p>假设一个漏洞的路径为 <code>/api/vul.php</code>，需要在Cookie的 <code>admin</code> 字段为1时，篡改POST请求中 <code>username</code> 字段可以修改其他用户的信息。</p>' +
                '        <p>则需要按以下格式提交：</p>' +
                '        <div class="vuln-help-example">' +
                '          <p><strong>路径：</strong><code>/api/vul.php</code></p>' +
                '          <p><strong>参数1：</strong> POST username</p>' +
                '          <p><strong>参数2：</strong> HEAD cookie:admin</p>' +
                '        </div>' +
                '      </div>' +
                '    </div>' +
                '    <div class="modal-footer">' +
                '      <button type="button" class="tech-btn tech-btn-primary vuln-help-modal-close">我知道了</button>' +
                '    </div>' +
                '  </div>' +
                '</div>';

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // 绑定关闭事件
            var modal = document.getElementById('vulnHelpModal');
            var closeBtns = modal.querySelectorAll('.vuln-help-modal-close');
            closeBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    modal.style.display = 'none';
                });
            });

            var overlay = modal.querySelector('.modal-overlay');
            if (overlay) {
                overlay.addEventListener('click', function () {
                    modal.style.display = 'none';
                });
            }
        }
    };

    // 暴露到全局对象
    window.HeaSecVulnCard = HeaSecVulnCard;

    /**
     * 自动初始化：查找所有 .heasec-vuln-card 容器并初始化
     */
    document.addEventListener('DOMContentLoaded', function () {
        var cards = document.querySelectorAll('.heasec-vuln-card');
        for (var i = 0; i < cards.length; i++) {
            var card = cards[i];
            var containerId = card.id;
            if (!containerId) continue;

            // 从 data-config 属性读取配置
            var configAttr = card.getAttribute('data-config');
            if (!configAttr) continue;

            try {
                var config = JSON.parse(configAttr);
            } catch (e) {
                console.warn('[HeaSec VulnCard] 解析 data-config 失败:', e);
                continue;
            }

            // 从 data-storage-key 获取存储键
            config.storageKey = card.getAttribute('data-storage-key') || config.rangeCode + '_vuln_card_star_count';

            // 初始化
            HeaSecVulnCard.init(containerId, config);
        }
    });
})();
