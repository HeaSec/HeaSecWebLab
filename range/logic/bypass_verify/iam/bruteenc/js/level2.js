/**
 * HeaSec天积安全团队 - 暴力破解前端加密靶场（第二关）
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

(function() {
  
    var _kp = 'SGVhU2VjQUVTSklBTUk2Ng==';  
    var _iv = 'SGVhU2VjQUVTSXNDb29sMQ=='; 

    /**
     * @param {string} _p 
     * @returns {string} 
     */
    function _0x1a2b(_p) {
        var _k = CryptoJS.enc.Utf8.parse(atob(_kp));
        var _v = CryptoJS.enc.Utf8.parse(atob(_iv));
        return _0x3c4d(_p, _k, _v);
    }

    /**
     * @param {string} _d 
     * @param {CryptoJS.lib.WordArray} _k 
     * @param {CryptoJS.lib.WordArray} _v
     * @returns {string}
     */
    function _0x3c4d(_d, _k, _v) {
        var _e = CryptoJS.AES.encrypt(
            _d,
            _k,
            { iv: _v, mode: CryptoJS.mode.CBC, padding: CryptoJS.pad.Pkcs7 }
        );
        return _e.toString();
    }


    window._heasec_enc = function(p) {
        return _0x1a2b(p);
    };

    /**
     * 处理登录表单提交
     * @param {Event} e - 表单提交事件
     */
    function _0x5e6f(e) {
        e.preventDefault();

        // 获取表单数据
        var _u = document.getElementById('username').value.trim();
        var _p = document.getElementById('password').value.trim();

        // 验证输入
        if (!_u || !_p) {
            _0x7a8b(false, '请输入用户名和密码');
            return;
        }


        var _ep = window._heasec_enc(_p);

        // 显示加载状态
        var _btn = document.getElementById('submitBtn');
        var _orig = _btn.innerHTML;
        _btn.disabled = true;
        _btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 验证中...';

        // 发送AJAX请求
        fetch(window.HeaSecPageConfig.loginApiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username: _u,
                password: _ep,
                level: window.HeaSecPageConfig.level
            })
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                _0x7a8b(true, data.message);
                document.getElementById('nextBtn').style.display = 'inline-flex';
            } else {
                _0x7a8b(false, data.message);
            }
        })
        .catch(function(error) {
            _0x7a8b(false, '网络错误，请稍后重试');
        })
        .finally(function() {
            _btn.disabled = false;
            _btn.innerHTML = _orig;
        });
    }

    /**
     * 显示登录结果
     * @param {boolean} success - 是否成功
     * @param {string} message - 结果消息
     */
    function _0x7a8b(success, message) {
        var _rd = document.getElementById('loginResult');
        var _ra = document.getElementById('resultAlert');
        var _ri = document.getElementById('resultIcon');
        var _rm = document.getElementById('resultMessage');
        var _rh = document.getElementById('resultHint');

        _ra.className = 'alert alert-' + (success ? 'success' : 'error');
        _ri.className = 'fa fa-' + (success ? 'check-circle' : 'exclamation-triangle');
        _rm.textContent = message;
        _rh.style.display = success ? 'none' : 'block';
        _rd.style.display = 'block';
    }

    // 页面加载完成后初始化
    document.addEventListener('DOMContentLoaded', function() {
        var _form = document.getElementById('loginForm');
        if (_form) {
            _form.addEventListener('submit', _0x5e6f);
        }

        var _pi = document.getElementById('password');
        if (_pi) {
            setTimeout(function() {
                _pi.focus();
            }, 300);
        }
    });
})();
