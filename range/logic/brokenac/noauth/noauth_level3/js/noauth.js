/**
 * HeaSec天积安全团队 - 未授权访问靶场交互脚本（第三关混淆版）
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 代码已加密保护
 */

(function () {
    'use strict';

    // 混淆后的变量名
    var _0x1a2b3c = window._0xConfig || {};
    var _0x4d5e6f = _0x1a2b3c.commonBasePath || '';
    var _0x7g8h9i = _0x1a2b3c.isLoggedIn || false;
    var _0xEncData = _0x1a2b3c._0xEnc || '';

    // 十六进制编码的字符串
    var _0xStr = {
        login: '\x6c\x6f\x67\x69\x6e', // 'login'
        passcode: '\x70\x61\x73\x73\x63\x6f\x64\x65', // 'passcode'
        success: '\x73\x75\x63\x63\x65\x73\x73', // 'success'
        message: '\x6d\x65\x73\x73\x61\x67\x65', // 'message'
        data: '\x64\x61\x74\x61', // 'data'
        passed: '\x70\x61\x73\x73\x65\x64', // 'passed'
        api: '\x61\x70\x69', // 'api'
        php: '\x2e\x70\x68\x70' // '.php'
    };

    // Base64解码函数
    function _0xDecode(_0xstr) {
        try {
            return atob(_0xstr);
        } catch (e) {
            return '';
        }
    }

    // 解码后的API路径
    var _0xApiPath = _0xDecode(_0xEncData);

    /**
     * 初始化
     */
    function _0xInit() {
        _0xBindLoginForm();
        _0xBindPasscodeForm();
        _0xBindLogout();

        if (_0x7g8h9i) {
            _0xLoadConfig();
        }
    }

    /**
     * 绑定登录表单
     */
    function _0xBindLoginForm() {
        var _0xform = document.getElementById('loginForm');
        if (!_0xform) return;

        _0xform.addEventListener('submit', function (e) {
            e.preventDefault();
            var _0xaccount = document.getElementById('account').value.trim();
            var _0xpassword = document.getElementById('password').value.trim();

            if (!_0xaccount || !_0xpassword) {
                _0xShowLoginResult('error', '\u8bf7\u8f93\u5165\u8d26\u53f7\u548c\u5bc6\u7801');
                return;
            }

            var _0xbtn = _0xform.querySelector('button[type="submit"]');
            if (_0xbtn) _0xbtn.disabled = true;

            fetch(_0xStr.api + '/' + _0xStr.login + _0xStr.php, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    account: _0xaccount,
                    password: _0xpassword
                })
            })
                .then(function (res) { return res.json(); })
                .then(function (_0xdata) {
                    if (_0xdata[_0xStr.success]) {
                        document.getElementById('loginSection').style.display = 'none';
                        document.getElementById('adminSection').style.display = 'block';
                        _0x7g8h9i = true;
                        _0xLoadConfig();
                    } else {
                        _0xShowLoginResult('error', _0xdata[_0xStr.message]);
                        if (_0xbtn) _0xbtn.disabled = false;
                    }
                })
                .catch(function () {
                    _0xShowLoginResult('error', '\u8bf7\u6c42\u5931\u8d25\uff0c\u8bf7\u7a0d\u540e\u91cd\u8bd5');
                    if (_0xbtn) _0xbtn.disabled = false;
                });
        });
    }

    /**
     * 显示登录结果
     */
    function _0xShowLoginResult(_0xtype, _0xmsg) {
        var _0xarea = document.getElementById('loginResultArea');
        if (!_0xarea) return;

        _0xarea.className = 'result-area visible result-' + _0xtype;
        _0xarea.innerHTML = '<i class="fa fa-' + (_0xtype === 'success' ? 'check-circle' : 'exclamation-triangle') + '"></i> ' + _0x0xEscape(_0xmsg);
    }

    /**
     * 加载配置数据
     */
    function _0xLoadConfig() {
        var _0xloading = document.getElementById('configLoading');
        var _0xdisplay = document.getElementById('configDisplay');

        // 使用混淆后的API路径
        var _0xfetchPath = _0xStr.api + '/' + _0xApiPath;

        fetch(_0xfetchPath, {
            method: 'GET',
            headers: { 'Content-Type': 'application/json' }
        })
            .then(function (res) { return res.json(); })
            .then(function (_0xdata) {
                if (_0xloading) _0xloading.style.display = 'none';

                if (_0xdata[_0xStr.success] && _0xdata[_0xStr.data]) {
                    _0xDisplayConfig(_0xdata[_0xStr.data]);
                    if (_0xdisplay) _0xdisplay.style.display = 'block';
                } else {
                    if (_0xdisplay) {
                        _0xdisplay.innerHTML = '<div class="result-area visible result-error"><i class="fa fa-exclamation-triangle"></i> ' + _0x0xEscape(_0xdata[_0xStr.message] || '\u52a0\u8f7d\u914d\u7f6e\u5931\u8d25') + '</div>';
                        _0xdisplay.style.display = 'block';
                    }
                }
            })
            .catch(function () {
                if (_0xloading) _0xloading.style.display = 'none';
                if (_0xdisplay) {
                    _0xdisplay.innerHTML = '<div class="result-area visible result-error"><i class="fa fa-exclamation-triangle"></i> \u8bf7\u6c42\u5931\u8d25\uff0c\u8bf7\u7a0d\u540e\u91cd\u8bd5</div>';
                    _0xdisplay.style.display = 'block';
                }
            });
    }

    /**
     * 显示配置数据
     */
    function _0xDisplayConfig(_0xconfig) {
        var _0xdisplay = document.getElementById('configDisplay');
        if (!_0xdisplay) return;

        var _0xhtml = '';

        if (_0xconfig.device_name) {
            _0xhtml += '<div class="config-item"><span class="config-label">\u8bbe\u5907\u540d\u79f0</span><span class="config-value">' + _0x0xEscape(_0xconfig.device_name) + '</span></div>';
        }
        if (_0xconfig.firmware_version) {
            _0xhtml += '<div class="config-item"><span class="config-label">\u56fa\u4ef6\u7248\u672c</span><span class="config-value">' + _0x0xEscape(_0xconfig.firmware_version) + '</span></div>';
        }
        if (_0xconfig.mac_address) {
            _0xhtml += '<div class="config-item"><span class="config-label">MAC\u5730\u5740</span><span class="config-value">' + _0x0xEscape(_0xconfig.mac_address) + '</span></div>';
        }
        if (_0xconfig.uptime) {
            _0xhtml += '<div class="config-item"><span class="config-label">\u8fd0\u884c\u65f6\u95f4</span><span class="config-value">' + _0x0xEscape(_0xconfig.uptime) + '</span></div>';
        }
        if (_0xconfig.online_devices !== undefined) {
            _0xhtml += '<div class="config-item"><span class="config-label">\u5728\u7ebf\u8bbe\u5907\u6570</span><span class="config-value">' + _0x0xEscape(_0xconfig.online_devices) + '</span></div>';
        }
        if (_0xconfig.wan_status) {
            _0xhtml += '<div class="config-item"><span class="config-label">WAN\u72b6\u6001</span><span class="config-value">' + _0x0xEscape(_0xconfig.wan_status) + '</span></div>';
        }
        if (_0xconfig.lan_status) {
            _0xhtml += '<div class="config-item"><span class="config-label">LAN\u72b6\u6001</span><span class="config-value">' + _0x0xEscape(_0xconfig.lan_status) + '</span></div>';
        }

        // 通关密码
        if (_0xconfig.passcode) {
            _0xhtml += '<div class="config-item highlight"><span class="config-label"><i class="fa fa-key"></i> \u901a\u5173\u5bc6\u7801</span><span class="config-value">' + _0x0xEscape(_0xconfig.passcode) + '</span></div>';
        }

        _0xdisplay.innerHTML = _0xhtml;
    }

    /**
     * 绑定通关密码验证表单
     */
    function _0xBindPasscodeForm() {
        var _0xform = document.getElementById('passcodeForm');
        if (!_0xform) return;

        _0xform.addEventListener('submit', function (e) {
            e.preventDefault();
            var _0xpasscode = document.getElementById('passcode').value.trim();

            if (!_0xpasscode) {
                _0xShowPasscodeResult('error', '\u8bf7\u8f93\u5165\u901a\u5173\u5bc6\u7801');
                return;
            }

            var _0xbtn = _0xform.querySelector('button[type="submit"]');
            if (_0xbtn) _0xbtn.disabled = true;

            fetch(_0xStr.api + '/' + 'verify-passcode' + _0xStr.php, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ passcode: _0xpasscode })
            })
                .then(function (res) { return res.json(); })
                .then(function (_0xdata) {
                    if (_0xdata[_0xStr.passed]) {
                        _0xShowPasscodeResult('success', _0xdata[_0xStr.message]);
                        _0xShowCongrats({
                            title: '\u606d\u559c\u4f60\u638c\u63e1\u4e86\u4e00\u4e2a\u65b0\u6280\u80fd',
                            message: '\u4f60\u638c\u63e1\u4e86\u672a\u6388\u6743\u8bbf\u95ee\u6f0f\u6d1e\u7684\u5b9e\u73b0\u65b9\u5f0f',
                            buttonText: '\u7ee7\u7eed\u5b66\u4e60',
                            enableNextRangeButton: true,
                            rangeCode: 'noauth',
                            updateLearningStatus: true,
                            updateStatusApiUrl: _0x4d5e6f + 'api/update-learning-status.php',
                            learningStatus: '\u5df2\u638c\u63e1',
                            nextRangeApiUrl: _0x4d5e6f + 'api/next-range.php',
                            showParticles: true,
                            particleCount: 8,
                            animationDuration: 2000
                        });
                    } else {
                        _0xShowPasscodeResult('error', _0xdata[_0xStr.message]);
                    }
                    if (_0xbtn) _0xbtn.disabled = false;
                })
                .catch(function () {
                    _0xShowPasscodeResult('error', '\u9a8c\u8bc1\u5931\u8d25\uff0c\u8bf7\u7a0d\u540e\u91cd\u8bd5');
                    if (_0xbtn) _0xbtn.disabled = false;
                });
        });
    }

    /**
     * 显示通关密码验证结果
     */
    function _0xShowPasscodeResult(_0xtype, _0xmsg) {
        var _0xarea = document.getElementById('passcodeResultArea');
        if (!_0xarea) return;

        _0xarea.className = 'result-area visible result-' + _0xtype;
        _0xarea.innerHTML = '<i class="fa fa-' + (_0xtype === 'success' ? 'check-circle' : 'exclamation-triangle') + '"></i> ' + _0x0xEscape(_0xmsg);
    }

    /**
     * 绑定退出登录
     */
    function _0xBindLogout() {
        var _0xbtn = document.getElementById('logoutBtn');
        if (!_0xbtn) return;

        _0xbtn.addEventListener('click', function () {
            // 调用后端登出接口清理会话
            fetch(_0xStr.api + '/logout' + _0xStr.php, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .catch(function () { })
                .finally(function () {
                    // 无论后端是否成功，都清理前端状态并刷新页面
                    window.location.reload();
                });
        });
    }

    /**
     * HTML转义
     */
    function _0x0xEscape(_0xtext) {
        if (typeof _0xtext !== 'string') {
            return String(_0xtext);
        }
        var _0xdiv = document.createElement('div');
        _0xdiv.textContent = _0xtext;
        return _0xdiv.innerHTML;
    }

    /**
     * 显示恭喜弹窗
     */
    function _0xShowCongrats(_0xconfig) {
        if (typeof HeaSecCongratsModal !== 'undefined' && typeof HeaSecCongratsModal.show === 'function') {
            HeaSecCongratsModal.show(_0xconfig);
        }
        // 移除alert兜底，避免非必要的弹窗输出
    }

    // 页面加载完成后初始化
    document.addEventListener('DOMContentLoaded', _0xInit);
})();
