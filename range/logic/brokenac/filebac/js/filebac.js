/**
 * HeaSec天积安全团队 - 文件越权访问靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2026-03-06
 * 团队: 天积安全 (HeavenlySecret)
 */

(function () {
    'use strict';

    var currentLevel = 1;
    var commonBasePath = '';
    var currentUserData = null;

    /**
     * 初始化靶场
     * @param {number} level - 关卡编号
     * @param {string} basePath - 公共组件的基础路径
     */
    window.initFilebac = function (level, basePath) {
        currentLevel = level || 1;
        commonBasePath = basePath || '';
        bindLoginForm();
        bindVerifyForm();

        // 如果页面已经是登录状态，加载用户信息
        if (currentLevel === 1) {
            var userInfoContainer = document.getElementById('userInfoContainer');
            if (userInfoContainer && userInfoContainer.getAttribute('data-logged-in') === 'true') {
                loadStudentInfo();
            }
        } else if (currentLevel === 2) {
            var orderListContainer = document.getElementById('orderListContainer');
            if (orderListContainer && orderListContainer.getAttribute('data-logged-in') === 'true') {
                loadOrderList();
            }
        } else if (currentLevel === 3) {
            var userInfoContainer = document.getElementById('userInfoContainer');
            if (userInfoContainer && userInfoContainer.getAttribute('data-logged-in') === 'true') {
                loadProfileInfo();
            }
        }
    };

    /**
     * 绑定登录表单
     */
    function bindLoginForm() {
        var loginForm = document.getElementById('loginForm');
        if (!loginForm) return;

        loginForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var account, password;
            if (currentLevel === 3) {
                account = document.getElementById('phone').value.trim();
                password = document.getElementById('password').value.trim();
            } else {
                account = document.getElementById('account').value.trim();
                password = document.getElementById('password').value.trim();
            }

            if (!account || !password) {
                showResult('loginResultArea', false, '请输入账号和密码');
                return;
            }

            var submitBtn = loginForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.classList.add('loading');

            var loginUrl = getApiUrl('login.php');
            var requestData = currentLevel === 3
                ? { phone: account, password: password }
                : { account: account, password: password };

            fetch(loginUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestData)
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success) {
                        currentUserData = data.data;
                        showResult('loginResultArea', true, data.message);

                        setTimeout(function () {
                            location.reload();
                        }, 500);
                    } else {
                        showResult('loginResultArea', false, data.message);
                    }
                })
                .catch(function (error) {
                    showResult('loginResultArea', false, '请求失败，请稍后重试');
                })
                .finally(function () {
                    if (submitBtn) submitBtn.classList.remove('loading');
                });
        });
    }

    /**
     * 绑定通关密码验证表单
     */
    function bindVerifyForm() {
        var verifyForm = document.getElementById('verifyForm');
        if (!verifyForm) return;

        verifyForm.addEventListener('submit', function (e) {
            e.preventDefault();

            var passcode = document.getElementById('passcode').value.trim();
            if (!passcode) {
                showResult('verifyResultArea', false, '请输入通关密码');
                return;
            }

            var submitBtn = verifyForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.classList.add('loading');

            var verifyUrl = getApiUrl('verify-passcode.php');

            fetch(verifyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ passcode: passcode })
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.passed) {
                        showResult('verifyResultArea', true, data.message);

                        // 显示下一关按钮
                        var nextLevelBtn = document.getElementById('nextLevelBtn');
                        if (nextLevelBtn) {
                            nextLevelBtn.style.display = 'inline-flex';
                        }

                        // 如果是第三关（最后一关），显示恭喜弹窗
                        if (currentLevel === 3) {
                            showCongratsModal({
                                title: '🎉 恭喜你掌握了一个新技能',
                                message: '你掌握了文件越权访问攻击的实现方式',
                                buttonText: '继续学习',
                                enableNextRangeButton: true,
                                rangeCode: 'filebac',
                                updateLearningStatus: true,
                                updateStatusApiUrl: commonBasePath + 'api/update-learning-status.php',
                                learningStatus: '已掌握',
                                nextRangeApiUrl: commonBasePath + 'api/next-range.php',
                                showParticles: true,
                                particleCount: 8,
                                animationDuration: 2000
                            });
                        }
                    } else {
                        showResult('verifyResultArea', false, data.message);
                    }
                })
                .catch(function (error) {
                    showResult('verifyResultArea', false, '验证失败，请稍后重试');
                })
                .finally(function () {
                    if (submitBtn) submitBtn.classList.remove('loading');
                });
        });
    }

    /**
     * 加载学生信息（第一关）
     */
    function loadStudentInfo() {
        fetch(getApiUrl('student.php'), {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    displayStudentInfo(data.data);
                }
            })
            .catch(function (error) {
                console.error('[HeaSec] 获取学生信息失败');
            });
    }

    /**
     * 显示学生信息（第一关）
     * @param {object} userData - 学生数据
     */
    function displayStudentInfo(userData) {
        var displayEl = document.getElementById('userInfoDisplay');
        var loadingEl = document.getElementById('userInfoLoading');
        var viewBtn = document.getElementById('viewTranscriptBtn');

        if (!displayEl) return;

        var html = '';
        html += '<div class="info-row"><span class="info-label"><i class="fa fa-user"></i> 姓名：</span><span class="info-value">' + escapeHtml(userData.name) + '</span></div>';
        html += '<div class="info-row"><span class="info-label"><i class="fa fa-calendar"></i> 年级：</span><span class="info-value">' + escapeHtml(userData.grade) + '</span></div>';
        html += '<div class="info-row"><span class="info-label"><i class="fa fa-users"></i> 班级：</span><span class="info-value">' + escapeHtml(userData.class) + '</span></div>';
        html += '<div class="info-row"><span class="info-label"><i class="fa fa-hashtag"></i> 学号：</span><span class="info-value" title="学号格式：3(固定) + 18(入学年份) + 24(学院) + 27(专业) + 5(班级) + 20(座位号)">' + escapeHtml(userData.student_id) + '</span></div>';
        html += '<div class="info-row"><span class="info-label"><i class="fa fa-star"></i> 成绩：</span><span class="info-value">' + escapeHtml(userData.score) + ' 分</span></div>';

        displayEl.innerHTML = html;
        displayEl.style.display = 'block';

        if (loadingEl) loadingEl.style.display = 'none';

        // 显示查看成绩单按钮
        if (viewBtn && userData.student_id) {
            viewBtn.style.display = 'inline-flex';
            viewBtn.onclick = function () {
                window.open('transcript/' + userData.student_id + '.png', '_blank');
            };
        }
    }

    /**
     * 加载订单列表（第二关）
     */
    function loadOrderList() {
        fetch(getApiUrl('orders.php'), {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    displayOrderList(data.data);
                }
            })
            .catch(function (error) {
                console.error('[HeaSec] 获取订单列表失败');
            });
    }

    /**
     * 显示订单列表（第二关）
     * @param {object} data - 订单数据
     */
    function displayOrderList(data) {
        var displayEl = document.getElementById('orderListDisplay');
        var loadingEl = document.getElementById('orderListLoading');

        if (!displayEl) return;

        var html = '<div class="order-list">';
        html += '<h4 style="margin: 0 0 15px 0;"><i class="fa fa-user"></i> 客户：' + escapeHtml(data.name) + '</h4>';

        if (data.orders && data.orders.length > 0) {
            data.orders.forEach(function (order) {
                var monthDir = order.order_id.substring(4, 10);
                var fileSuffix = order.order_id.substring(order.order_id.length - 4);
                var imageUrl = 'order/' + monthDir + '/FJDLkhdd' + fileSuffix + '.png';

                html += '<div class="order-item" style="padding: 15px; margin-bottom: 10px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">';
                html += '<div style="font-weight: bold; display: flex; align-items: center;">';
                html += '<span><i class="fa fa-shopping-bag"></i> 订单号：' + escapeHtml(order.order_id) + '</span>';
                html += '<button class="tech-btn tech-btn-primary tech-btn-sm" style="margin-left: 20px;" onclick="window.open(\'' + imageUrl + '\', \'_blank\')">';
                html += '<i class="fa fa-file-image-o"></i> 订单详情</button>';
                html += '</div>';
                html += '<div style="color: #666; margin-top: 8px;">客户：' + escapeHtml(order.customer) + ' | 金额：¥' + order.amount.toFixed(2) + ' | 状态：' + escapeHtml(order.status) + '</div>';
                html += '</div>';
            });
        }

        html += '</div>';

        displayEl.innerHTML = html;
        displayEl.style.display = 'block';

        if (loadingEl) loadingEl.style.display = 'none';
    }

    /**
     * 加载用户信息（第三关）
     */
    function loadProfileInfo() {
        fetch(getApiUrl('profile.php'), {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    displayProfileInfo(data.data);
                }
            })
            .catch(function (error) {
                console.error('[HeaSec] 获取用户信息失败');
            });
    }

    /**
     * 显示用户信息（第三关）
     * @param {object} userData - 用户数据
     */
    function displayProfileInfo(userData) {
        var displayEl = document.getElementById('userInfoDisplay');
        var loadingEl = document.getElementById('userInfoLoading');
        var uploadSection = document.getElementById('uploadSection');

        if (!displayEl) return;

        var phoneMd5 = md5(userData.phone);

        var html = '';
        html += '<div class="info-row"><span class="info-label"><i class="fa fa-user"></i> 姓名：</span><span class="info-value">' + escapeHtml(userData.name) + '</span></div>';
        html += '<div class="info-row"><span class="info-label"><i class="fa fa-mobile"></i> 手机号：</span><span class="info-value" title="手机号格式：138(天积移动前缀) + 0591(福州区号) + XXXX(用户号)">' + escapeHtml(userData.phone) + '</span></div>';
        html += '<div class="info-row"><span class="info-label"><i class="fa fa-id-card"></i> 身份证号：</span><span class="info-value">' + escapeHtml(userData.idcard) + '</span></div>';

        displayEl.innerHTML = html;
        displayEl.style.display = 'block';

        if (loadingEl) loadingEl.style.display = 'none';

        // 显示上传区域
        if (uploadSection) {
            uploadSection.style.display = 'block';

            // 显示当前身份证预览
            var idcardPreview = document.getElementById('idcardPreview');
            var idcardImageContainer = document.getElementById('idcardImageContainer');
            if (idcardPreview && idcardImageContainer) {
                idcardPreview.style.display = 'block';
                idcardImageContainer.innerHTML = '<img src="idcard/' + phoneMd5 + '.png" alt="身份证照片" style="width: 100%; height: auto;" onerror="this.parentElement.innerHTML=\'<p style=\\\'padding: 20px; color: #999;\\\'>暂无身份证照片</p>\'">';
            }
        }

        // 绑定上传事件
        bindUploadEvents();
    }

    /**
     * 绑定上传事件（第三关）
     */
    function bindUploadEvents() {
        var fileInput = document.getElementById('idcard_image');
        var fileNameSpan = document.getElementById('fileName');

        if (!fileInput) return;

        fileInput.addEventListener('change', function () {
            if (this.files && this.files.length > 0) {
                var file = this.files[0];
                if (fileNameSpan) {
                    fileNameSpan.textContent = file.name;
                }

                // 自动上传
                var formData = new FormData();
                formData.append('idcard_image', file);

                var uploadResult = document.getElementById('uploadResult');
                if (uploadResult) {
                    uploadResult.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 正在上传...';
                }

                fetch(getApiUrl('upload.php'), {
                    method: 'POST',
                    body: formData
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (uploadResult) {
                            if (data.success) {
                                uploadResult.innerHTML = '<span style="color: #28a745;"><i class="fa fa-check-circle"></i> ' + escapeHtml(data.message) + '</span>';
                                // 刷新预览
                                setTimeout(function () {
                                    location.reload();
                                }, 1000);
                            } else {
                                uploadResult.innerHTML = '<span style="color: #dc3545;"><i class="fa fa-exclamation-circle"></i> ' + escapeHtml(data.message) + '</span>';
                            }
                        }
                    })
                    .catch(function (error) {
                        if (uploadResult) {
                            uploadResult.innerHTML = '<span style="color: #dc3545;"><i class="fa fa-exclamation-circle"></i> 上传失败</span>';
                        }
                    });
            }
        });
    }

    /**
     * 获取API URL
     * @param {string} endpoint - API端点
     * @returns {string} 完整URL
     */
    function getApiUrl(endpoint) {
        if (currentLevel === 1) {
            return 'api/' + endpoint;
        } else {
            return 'api/' + endpoint;
        }
    }

    /**
     * 显示结果
     * @param {string} elementId - 元素ID
     * @param {boolean} success - 是否成功
     * @param {string} message - 消息
     */
    function showResult(elementId, success, message) {
        var element = document.getElementById(elementId);
        if (!element) return;

        if (success) {
            element.innerHTML = '<div class="alert-success"><i class="fa fa-check-circle"></i><span>' + escapeHtml(message) + '</span></div>';
        } else {
            element.innerHTML = '<div class="alert-error"><i class="fa fa-exclamation-triangle"></i><span>' + escapeHtml(message) + '</span></div>';
        }
        element.style.display = 'block';
    }

    /**
     * HTML转义函数，防止XSS
     * @param {string} text - 需要转义的文本
     * @returns {string} 转义后的文本
     */
    function escapeHtml(text) {
        if (typeof text !== 'string') {
            return String(text);
        }
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * 简单的MD5实现（仅用于前端显示）
     * @param {string} string - 输入字符串
     * @returns {string} MD5哈希值
     */
    function md5(string) {
        function md5cycle(x, k) {
            var a = x[0], b = x[1], c = x[2], d = x[3];
            a = ff(a, b, c, d, k[0], 7, -680876936);
            d = ff(d, a, b, c, k[1], 12, -389564586);
            c = ff(c, d, a, b, k[2], 17, 606105819);
            b = ff(b, c, d, a, k[3], 22, -1044525330);
            a = ff(a, b, c, d, k[4], 7, -176418897);
            d = ff(d, a, b, c, k[5], 12, 1200080426);
            c = ff(c, d, a, b, k[6], 17, -1473231341);
            b = ff(b, c, d, a, k[7], 22, -45705983);
            a = ff(a, b, c, d, k[8], 7, 1770035416);
            d = ff(d, a, b, c, k[9], 12, -1958414417);
            c = ff(c, d, a, b, k[10], 17, -42063);
            b = ff(b, c, d, a, k[11], 22, -1990404162);
            a = ff(a, b, c, d, k[12], 7, 1804603682);
            d = ff(d, a, b, c, k[13], 12, -40341101);
            c = ff(c, d, a, b, k[14], 17, -1502002290);
            b = ff(b, c, d, a, k[15], 22, 1236535329);
            a = gg(a, b, c, d, k[1], 5, -165796510);
            d = gg(d, a, b, c, k[6], 9, -1069501632);
            c = gg(c, d, a, b, k[11], 14, 643717713);
            b = gg(b, c, d, a, k[0], 20, -373897302);
            a = gg(a, b, c, d, k[5], 5, -701558691);
            d = gg(d, a, b, c, k[10], 9, 38016083);
            c = gg(c, d, a, b, k[15], 14, -660478335);
            b = gg(b, c, d, a, k[4], 20, -405537848);
            a = gg(a, b, c, d, k[9], 5, 568446438);
            d = gg(d, a, b, c, k[14], 9, -1019803690);
            c = gg(c, d, a, b, k[3], 14, -187363961);
            b = gg(b, c, d, a, k[8], 20, 1163531501);
            a = gg(a, b, c, d, k[13], 5, -1444681467);
            d = gg(d, a, b, c, k[2], 9, -51403784);
            c = gg(c, d, a, b, k[7], 14, 1735328473);
            b = gg(b, c, d, a, k[12], 20, -1926607734);
            a = hh(a, b, c, d, k[5], 4, -378558);
            d = hh(d, a, b, c, k[8], 11, -2022574463);
            c = hh(c, d, a, b, k[11], 16, 1839030562);
            b = hh(b, c, d, a, k[14], 23, -35309556);
            a = hh(a, b, c, d, k[1], 4, -1530992060);
            d = hh(d, a, b, c, k[4], 11, 1272893353);
            c = hh(c, d, a, b, k[7], 16, -155497632);
            b = hh(b, c, d, a, k[10], 23, -1094730640);
            a = hh(a, b, c, d, k[13], 4, 681279174);
            d = hh(d, a, b, c, k[0], 11, -358537222);
            c = hh(c, d, a, b, k[3], 16, -722521979);
            b = hh(b, c, d, a, k[6], 23, 76029189);
            a = hh(a, b, c, d, k[9], 4, -640364487);
            d = hh(d, a, b, c, k[12], 11, -421815835);
            c = hh(c, d, a, b, k[15], 16, 530742520);
            b = hh(b, c, d, a, k[2], 23, -995338651);
            a = ii(a, b, c, d, k[0], 6, -198630844);
            d = ii(d, a, b, c, k[7], 10, 1126891415);
            c = ii(c, d, a, b, k[14], 15, -1416354905);
            b = ii(b, c, d, a, k[5], 21, -57434055);
            a = ii(a, b, c, d, k[12], 6, 1700485571);
            d = ii(d, a, b, c, k[3], 10, -1894986606);
            c = ii(c, d, a, b, k[10], 15, -1051523);
            b = ii(b, c, d, a, k[1], 21, -2054922799);
            a = ii(a, b, c, d, k[8], 6, 1873313359);
            d = ii(d, a, b, c, k[15], 10, -30611744);
            c = ii(c, d, a, b, k[6], 15, -1560198380);
            b = ii(b, c, d, a, k[13], 21, 1309151649);
            a = ii(a, b, c, d, k[4], 6, -145523070);
            d = ii(d, a, b, c, k[11], 10, -1120210379);
            c = ii(c, d, a, b, k[2], 15, 718787259);
            b = ii(b, c, d, a, k[9], 21, -343485551);
            x[0] = add32(a, x[0]);
            x[1] = add32(b, x[1]);
            x[2] = add32(c, x[2]);
            x[3] = add32(d, x[3]);
        }

        function cmn(q, a, b, x, s, t) {
            a = add32(add32(a, q), add32(x, t));
            return add32((a << s) | (a >>> (32 - s)), b);
        }

        function ff(a, b, c, d, x, s, t) {
            return cmn((b & c) | ((~b) & d), a, b, x, s, t);
        }

        function gg(a, b, c, d, x, s, t) {
            return cmn((b & d) | (c & (~d)), a, b, x, s, t);
        }

        function hh(a, b, c, d, x, s, t) {
            return cmn(b ^ c ^ d, a, b, x, s, t);
        }

        function ii(a, b, c, d, x, s, t) {
            return cmn(c ^ (b | (~d)), a, b, x, s, t);
        }

        function md5blk(s) {
            var md5blks = [], i;
            for (i = 0; i < 64; i += 4) {
                md5blks[i >> 2] = s.charCodeAt(i) + (s.charCodeAt(i + 1) << 8) + (s.charCodeAt(i + 2) << 16) + (s.charCodeAt(i + 3) << 24);
            }
            return md5blks;
        }

        var hex_chr = '0123456789abcdef'.split('');

        function rhex(n) {
            var s = '', j = 0;
            for (; j < 4; j++)
                s += hex_chr[(n >> (j * 8 + 4)) & 0x0F] + hex_chr[(n >> (j * 8)) & 0x0F];
            return s;
        }

        function hex(x) {
            for (var i = 0; i < x.length; i++)
                x[i] = rhex(x[i]);
            return x.join('');
        }

        function md5(s) {
            var n = s.length,
                state = [1732584193, -271733879, -1732584194, 271733878], i;
            for (i = 64; i <= n; i += 64) {
                md5cycle(state, md5blk(s.substring(i - 64, i)));
            }
            s = s.substring(i - 64);
            var tail = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            for (i = 0; i < s.length; i++)
                tail[i >> 2] |= s.charCodeAt(i) << ((i % 4) << 3);
            tail[i >> 2] |= 0x80 << ((i % 4) << 3);
            if (i > 55) {
                md5cycle(state, tail);
                for (i = 0; i < 16; i++) tail[i] = 0;
            }
            tail[14] = n * 8;
            md5cycle(state, tail);
            return state;
        }

        function add32(a, b) {
            return (a + b) & 0xFFFFFFFF;
        }

        return hex(md5(string));
    }

    /**
     * 显示恭喜弹窗
     * @param {object} config - 弹窗配置对象
     */
    function showCongratsModal(config) {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show(config);
        } else {
            console.error('[HeaSec] 恭喜弹窗组件未加载');
            alert(config.message || '恭喜通关！');
        }
    }

    // 暴露md5函数给全局
    window.filebacMd5 = md5;
})();
