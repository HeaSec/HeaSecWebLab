/**
 * HeaSec天积安全团队 - SQL盲注进阶靶场交互脚本
 * 版本: v1.0.0
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件基础路径
     */
    window.initBSIAdv = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';

        bindBusinessForm();
        bindVerifyForm();
    };

    /**
     * 绑定业务表单事件
     */
    function bindBusinessForm() {
        if (currentLevel === 1) {
            bindQueryForm();
        } else if (currentLevel === 2) {
            bindVerifyMemberForm();
        } else if (currentLevel === 3) {
            bindTokenForm();
        }
    }

    /**
     * 绑定第一关订单查询表单
     */
    function bindQueryForm() {
        var form = document.getElementById('queryForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = document.getElementById('orderId');
            if (!input) return;

            var value = input.value.trim();
            if (!value) {
                showBusinessResult(false, '请输入订单号');
                return;
            }

            var btn = document.getElementById('queryBtn');
            setButtonLoading(btn, true);

            var formData = new FormData();
            formData.append('order_id', value);

            fetch('api/process-level1.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success) {
                    if (res.data && res.data.found) {
                        showBusinessResult(true, res.message, 'success');
                    } else {
                        showBusinessResult(true, res.message, 'warning');
                    }
                } else {
                    showBusinessResult(false, res.message);
                }
            })
            .catch(function () {
                showBusinessResult(false, '请求失败，请稍后重试');
            })
            .finally(function () {
                setButtonLoading(btn, false);
            });
        });
    }

    /**
     * 绑定第二关成员验证表单
     */
    function bindVerifyMemberForm() {
        var form = document.getElementById('verifyMemberForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = document.getElementById('memberId');
            if (!input) return;

            var value = input.value.trim();
            if (!value) {
                showBusinessResult(false, '请输入成员ID');
                return;
            }

            var btn = document.getElementById('verifyBtn');
            setButtonLoading(btn, true);

            var formData = new FormData();
            formData.append('member_id', value);

            fetch('api/process-level2.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success) {
                    if (res.data && res.data.found) {
                        showBusinessResult(true, res.message, 'success');
                    } else {
                        showBusinessResult(true, res.message, 'warning');
                    }
                } else {
                    showBusinessResult(false, res.message);
                }
            })
            .catch(function () {
                showBusinessResult(false, '请求失败，请稍后重试');
            })
            .finally(function () {
                setButtonLoading(btn, false);
            });
        });
    }

    /**
     * 绑定第三关令牌校验表单
     */
    function bindTokenForm() {
        var form = document.getElementById('tokenForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var input = document.getElementById('tokenId');
            if (!input) return;

            var value = input.value.trim();
            if (!value) {
                showBusinessResult(false, '请输入令牌ID');
                return;
            }

            var btn = document.getElementById('checkBtn');
            setButtonLoading(btn, true);

            var formData = new FormData();
            formData.append('token_id', value);

            fetch('api/process-level3.php', {
                method: 'POST',
                body: formData
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success) {
                    if (res.data && res.data.valid) {
                        showBusinessResult(true, res.message, 'success');
                    } else {
                        showBusinessResult(true, res.message, 'warning');
                    }
                } else {
                    showBusinessResult(false, res.message);
                }
            })
            .catch(function () {
                showBusinessResult(false, '请求失败，请稍后重试');
            })
            .finally(function () {
                setButtonLoading(btn, false);
            });
        });
    }

    /**
     * 显示业务操作结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
     * @param {string} [type] - 结果类型（success/warning）
     */
    function showBusinessResult(success, message, type) {
        var resultArea = getResultArea();
        if (!resultArea) return;

        var html = '';
        if (success) {
            var cssClass = 'alert-success';
            var icon = 'fa-check-circle';
            if (type === 'warning') {
                cssClass = 'alert-error';
                icon = 'fa-times-circle';
            }
            html = '<div class="' + cssClass + '"><i class="fa ' + icon + '"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            html = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }

        resultArea.innerHTML = html;
        resultArea.style.display = 'block';
    }

    /**
     * 获取当前关卡的结果展示区域
     * @returns {HTMLElement|null}
     */
    function getResultArea() {
        if (currentLevel === 1) return document.getElementById('queryResultArea');
        if (currentLevel === 2) return document.getElementById('verifyMemberArea');
        if (currentLevel === 3) return document.getElementById('tokenResultArea');
        return null;
    }

    /**
     * 绑定通关密码验证表单
     */
    function bindVerifyForm() {
        var form = document.getElementById('verifyForm');
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var passcode = document.getElementById('passcode').value.trim();
            if (!passcode) {
                showVerifyResult(false, '请输入通关密码');
                return;
            }

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.classList.add('loading');

            fetch('api/verify-passcode.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    level: currentLevel,
                    passcode: passcode
                })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success) {
                    showVerifyResult(true, data.message);
                    if (currentLevel === 3) {
                        showCongratsModal();
                    } else {
                        updateLearningStatus('学习中');
                        var nextBtn = document.getElementById('nextLevelBtn');
                        if (nextBtn) nextBtn.style.display = 'inline-flex';
                    }
                } else {
                    showVerifyResult(false, data.message || '通关密码错误');
                }
            })
            .catch(function () {
                showVerifyResult(false, '验证失败，请稍后重试');
            })
            .finally(function () {
                if (submitBtn) submitBtn.classList.remove('loading');
            });
        });
    }

    /**
     * 显示验证结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息文本
     */
    function showVerifyResult(success, message) {
        var resultArea = document.getElementById('verifyResultArea');
        if (!resultArea) return;

        if (success) {
            resultArea.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            resultArea.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }
        resultArea.style.display = 'block';
    }

    /**
     * 显示恭喜弹窗
     */
    function showCongratsModal() {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show({
                title: '恭喜你掌握了一个新技能',
                message: '你掌握了SQL盲注进阶技术的核心利用技巧',
                buttonText: '继续学习',
                enableNextRangeButton: true,
                rangeCode: 'bsiadv',
                updateLearningStatus: true,
                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                learningStatus: '已掌握',
                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                showParticles: true,
                particleCount: 10,
                animationDuration: 2500
            });
        } else {
            alert('恭喜你掌握了一个新技能\n\n你掌握了SQL盲注进阶技术的核心利用技巧！');
        }
    }

    /**
     * 更新学习进度状态
     * @param {string} status - 学习状态值
     */
    function updateLearningStatus(status) {
        if (!commonBasePath) return;

        fetch(commonBasePath + 'api/update-learning-status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: 'bsiadv',
                status: status
            })
        })
        .then(function () {})
        .catch(function () {});
    }

    /**
     * 设置按钮加载状态
     * @param {HTMLElement} btn - 按钮元素
     * @param {boolean} loading - 是否加载中
     */
    function setButtonLoading(btn, loading) {
        if (!btn) return;
        if (loading) {
            btn.classList.add('loading');
            btn.dataset.originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 处理中';
        } else {
            btn.classList.remove('loading');
            if (btn.dataset.originalHtml) {
                btn.innerHTML = btn.dataset.originalHtml;
                delete btn.dataset.originalHtml;
            }
        }
    }

    /**
     * HTML转义函数
     * @param {string} text - 原始文本
     * @returns {string} 转义后的文本
     */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }
})();
