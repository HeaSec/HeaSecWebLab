/**
 * HeaSec天积安全团队 - 密码验证卡片交互脚本
 * Secret Card Interactive Script
 * 版本: v2.0.1
 * 创建日期: 2025-11-21
 * 修改日期: 2025-11-26
 * 团队: 天积安全 (HeavenlySecret)
 * 说明: 提供密码验证卡片的完整交互功能，使用MD5哈希验证避免直接暴露秘密
 */

/**
 * MD5哈希函数实现
 * 使用简单但经过验证的JavaScript MD5实现
 */
var HeaSecMD5 = {
    hash: function(string) {
        if (!string) return '';
        
        // 使用一个简化的MD5实现
        function md5(string) {
            function RotateLeft(lValue, iShiftBits) {
                return (lValue<<iShiftBits) | (lValue>>>(32-iShiftBits));
            }
            
            function AddUnsigned(lX,lY) {
                var lX4,lY4,lX8,lY8,lResult;
                lX8 = (lX & 0x80000000);
                lY8 = (lY & 0x80000000);
                lX4 = (lX & 0x40000000);
                lY4 = (lY & 0x40000000);
                lResult = (lX & 0x3FFFFFFF)+(lY & 0x3FFFFFFF);
                if (lX4 & lY4) {
                    return (lResult ^ 0x80000000 ^ lX8 ^ lY8);
                }
                if (lX4 | lY4) {
                    if (lResult & 0x40000000) {
                        return (lResult ^ 0xC0000000 ^ lX8 ^ lY8);
                    } else {
                        return (lResult ^ 0x40000000 ^ lX8 ^ lY8);
                    }
                } else {
                    return (lResult ^ lX8 ^ lY8);
                }
            }
            
            function F(x,y,z) { return (x & y) | ((~x) & z); }
            function G(x,y,z) { return (x & z) | (y & (~z)); }
            function H(x,y,z) { return (x ^ y ^ z); }
            function I(x,y,z) { return (y ^ (x | (~z))); }
            
            function FF(a,b,c,d,x,s,ac) {
                a = AddUnsigned(a, AddUnsigned(AddUnsigned(F(b, c, d), x), ac));
                return AddUnsigned(RotateLeft(a, s), b);
            };
            
            function GG(a,b,c,d,x,s,ac) {
                a = AddUnsigned(a, AddUnsigned(AddUnsigned(G(b, c, d), x), ac));
                return AddUnsigned(RotateLeft(a, s), b);
            };
            
            function HH(a,b,c,d,x,s,ac) {
                a = AddUnsigned(a, AddUnsigned(AddUnsigned(H(b, c, d), x), ac));
                return AddUnsigned(RotateLeft(a, s), b);
            };
            
            function II(a,b,c,d,x,s,ac) {
                a = AddUnsigned(a, AddUnsigned(AddUnsigned(I(b, c, d), x), ac));
                return AddUnsigned(RotateLeft(a, s), b);
            };
            
            function ConvertToWordArray(string) {
                var lWordCount;
                var lMessageLength = string.length;
                var lNumberOfWords_temp1=lMessageLength + 8;
                var lNumberOfWords_temp2=(lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64))/64;
                var lNumberOfWords = (lNumberOfWords_temp2 + 1)*16;
                var lWordArray=Array(lNumberOfWords-1);
                var lBytePosition = 0;
                var lByteCount = 0;
                while ( lByteCount < lMessageLength ) {
                    lWordCount = (lByteCount - (lByteCount % 4))/4;
                    lBytePosition = (lByteCount % 4)*8;
                    lWordArray[lWordCount] = (lWordArray[lWordCount] | (string.charCodeAt(lByteCount)<<lBytePosition));
                    lByteCount++;
                }
                lWordCount = (lByteCount - (lByteCount % 4))/4;
                lBytePosition = (lByteCount % 4)*8;
                lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80<<lBytePosition);
                lWordArray[lNumberOfWords-2] = lMessageLength<<3;
                lWordArray[lNumberOfWords-1] = lMessageLength>>>29;
                return lWordArray;
            };
            
            function WordToHex(lValue) {
                var WordToHexValue="",WordToHexValue_temp="",lByte,lCount;
                for (lCount = 0;lCount<=3;lCount++) {
                    lByte = (lValue>>>(lCount*8)) & 255;
                    WordToHexValue_temp = "0" + lByte.toString(16);
                    WordToHexValue = WordToHexValue + WordToHexValue_temp.substr(WordToHexValue_temp.length-2,2);
                }
                return WordToHexValue;
            };
            
            function Utf8Encode(string) {
                string = string.replace(/\r\n/g,"\n");
                var utftext = "";
                for (var n = 0; n < string.length; n++) {
                    var c = string.charCodeAt(n);
                    if (c < 128) {
                        utftext += String.fromCharCode(c);
                    }
                    else if((c > 127) && (c < 2048)) {
                        utftext += String.fromCharCode((c >> 6) | 192);
                        utftext += String.fromCharCode((c & 63) | 128);
                    }
                    else {
                        utftext += String.fromCharCode((c >> 12) | 224);
                        utftext += String.fromCharCode(((c >> 6) & 63) | 128);
                        utftext += String.fromCharCode((c & 63) | 128);
                    }
                }
                return utftext;
            };
            
            var x=Array();
            var k,AA,BB,CC,DD,a,b,c,d;
            var S11=7, S12=12, S13=17, S14=22;
            var S21=5, S22=9 , S23=14, S24=20;
            var S31=4, S32=11, S33=16, S34=23;
            var S41=6, S42=10, S43=15, S44=21;
            
            string = Utf8Encode(string);
            x = ConvertToWordArray(string);
            a = 0x67452301; b = 0xEFCDAB89; c = 0x98BADCFE; d = 0x10325476;
            
            for (k=0;k<x.length;k+=16) {
                AA=a; BB=b; CC=c; DD=d;
                a=FF(a,b,c,d,x[k+0], S11,0xD76AA478);
                d=FF(d,a,b,c,x[k+1], S12,0xE8C7B756);
                c=FF(c,d,a,b,x[k+2], S13,0x242070DB);
                b=FF(b,c,d,a,x[k+3], S14,0xC1BDCEEE);
                a=FF(a,b,c,d,x[k+4], S11,0xF57C0FAF);
                d=FF(d,a,b,c,x[k+5], S12,0x4787C62A);
                c=FF(c,d,a,b,x[k+6], S13,0xA8304613);
                b=FF(b,c,d,a,x[k+7], S14,0xFD469501);
                a=FF(a,b,c,d,x[k+8], S11,0x698098D8);
                d=FF(d,a,b,c,x[k+9], S12,0x8B44F7AF);
                c=FF(c,d,a,b,x[k+10],S13,0xFFFF5BB1);
                b=FF(b,c,d,a,x[k+11],S14,0x895CD7BE);
                a=FF(a,b,c,d,x[k+12],S11,0x6B901122);
                d=FF(d,a,b,c,x[k+13],S12,0xFD987193);
                c=FF(c,d,a,b,x[k+14],S13,0xA679438E);
                b=FF(b,c,d,a,x[k+15],S14,0x49B40821);
                a=GG(a,b,c,d,x[k+1], S21,0xF61E2562);
                d=GG(d,a,b,c,x[k+6], S22,0xC040B340);
                c=GG(c,d,a,b,x[k+11],S23,0x265E5A51);
                b=GG(b,c,d,a,x[k+0], S24,0xE9B6C7AA);
                a=GG(a,b,c,d,x[k+5], S21,0xD62F105D);
                d=GG(d,a,b,c,x[k+10],S22,0x02441453);
                c=GG(c,d,a,b,x[k+15],S23,0xD8A1E681);
                b=GG(b,c,d,a,x[k+4], S24,0xE7D3FBC8);
                a=GG(a,b,c,d,x[k+9], S21,0x21E1CDE6);
                d=GG(d,a,b,c,x[k+14],S22,0xC33707D6);
                c=GG(c,d,a,b,x[k+3], S23,0xF4D50D87);
                b=GG(b,c,d,a,x[k+8], S24,0x455A14ED);
                a=GG(a,b,c,d,x[k+13],S21,0xA9E3E905);
                d=GG(d,a,b,c,x[k+2], S22,0xFCEFA3F8);
                c=GG(c,d,a,b,x[k+7], S23,0x676F02D9);
                b=GG(b,c,d,a,x[k+12],S24,0x8D2A4C8A);
                a=HH(a,b,c,d,x[k+5], S31,0xFFFA3942);
                d=HH(d,a,b,c,x[k+8], S32,0x8771F681);
                c=HH(c,d,a,b,x[k+11],S33,0x6D9D6122);
                b=HH(b,c,d,a,x[k+14],S34,0xFDE5380C);
                a=HH(a,b,c,d,x[k+1], S31,0xA4BEEA44);
                d=HH(d,a,b,c,x[k+4], S32,0x4BDECFA9);
                c=HH(c,d,a,b,x[k+7], S33,0xF6BB4B60);
                b=HH(b,c,d,a,x[k+10],S34,0xBEBFBC70);
                a=HH(a,b,c,d,x[k+13],S31,0x289B7EC6);
                d=HH(d,a,b,c,x[k+0], S32,0xEAA127FA);
                c=HH(c,d,a,b,x[k+3], S33,0xD4EF3085);
                b=HH(b,c,d,a,x[k+6], S34,0x04881D05);
                a=HH(a,b,c,d,x[k+9], S31,0xD9D4D039);
                d=HH(d,a,b,c,x[k+12],S32,0xE6DB99E5);
                c=HH(c,d,a,b,x[k+15],S33,0x1FA27CF8);
                b=HH(b,c,d,a,x[k+2], S34,0xC4AC5665);
                a=II(a,b,c,d,x[k+0], S41,0xF4292244);
                d=II(d,a,b,c,x[k+7], S42,0x432AFF97);
                c=II(c,d,a,b,x[k+14],S43,0xAB9423A7);
                b=II(b,c,d,a,x[k+5], S44,0xFC93A039);
                a=II(a,b,c,d,x[k+12],S41,0x655B59C3);
                d=II(d,a,b,c,x[k+3], S42,0x8F0CCC92);
                c=II(c,d,a,b,x[k+10],S43,0xFFEFF47D);
                b=II(b,c,d,a,x[k+1], S44,0x85845DD1);
                a=II(a,b,c,d,x[k+8], S41,0x6FA87E4F);
                d=II(d,a,b,c,x[k+15],S42,0xFE2CE6E0);
                c=II(c,d,a,b,x[k+6], S43,0xA3014314);
                b=II(b,c,d,a,x[k+13],S44,0x4E0811A1);
                a=II(a,b,c,d,x[k+4], S41,0xF7537E82);
                d=II(d,a,b,c,x[k+11],S42,0xBD3AF235);
                c=II(c,d,a,b,x[k+2], S43,0x2AD7D2BB);
                b=II(b,c,d,a,x[k+9], S44,0xEB86D391);
                a=AddUnsigned(a,AA);
                b=AddUnsigned(b,BB);
                c=AddUnsigned(c,CC);
                d=AddUnsigned(d,DD);
            }
            
            var temp = WordToHex(a)+WordToHex(b)+WordToHex(c)+WordToHex(d);
            return temp.toLowerCase();
        }
        
        return md5(string);
    }
};

/**
 * 密码验证卡片管理器
 */
var HeaSecSecretCard = (function() {
    'use strict';

    // 存储所有卡片实例
    var cardInstances = {};

    /**
     * 初始化密码验证卡片
     *
     * @param {string} containerId 容器ID
     * @param {object} config 配置参数
     */
    function init(containerId, config) {
        if (!containerId || !config) {
            return;
        }

        // 存储配置
        cardInstances[containerId] = {
            containerId: containerId,
            config: config,
            formId: config.formId,
            inputId: config.inputId,
            maxLength: config.maxLength || 20,
            secretHash: config.secretHash, // 改为使用MD5哈希值
            isSubmitted: false,
            validationPattern: config.validationPattern || '/^[A-Za-z0-9]{20}$/',
            submitText: config.submitText || '验证秘密',
            submitIcon: config.submitIcon || 'fa fa-sign-in'
        };

        // 初始化功能
        initializeLengthIndicator(containerId);
        initializeFormValidation(containerId);
        initializeInteractions(containerId);
    }

    /**
     * 初始化长度指示器
     *
     * @param {string} containerId 容器ID
     */
    function initializeLengthIndicator(containerId) {
        var instance = cardInstances[containerId];
        if (!instance) return;

        var input = document.getElementById(instance.inputId);
        var indicator = document.getElementById('length-indicator-' + containerId);

        if (input && indicator) {
            // 初始化长度显示
            updateLengthIndicator(containerId, input.value.length);

            // 监听输入事件
            input.addEventListener('input', function(e) {
                var length = e.target.value.length;
                updateLengthIndicator(containerId, length);
                updateLengthIndicatorColor(containerId, length);
            });

            // 监听粘贴事件
            input.addEventListener('paste', function(e) {
                setTimeout(function() {
                    var length = input.value.length;
                    updateLengthIndicator(containerId, length);
                    updateLengthIndicatorColor(containerId, length);
                }, 10);
            });
        }
    }

    /**
     * 更新长度指示器显示
     *
     * @param {string} containerId 容器ID
     * @param {number} length 当前长度
     */
    function updateLengthIndicator(containerId, length) {
        var indicator = document.getElementById('length-indicator-' + containerId);
        if (indicator) {
            indicator.textContent = length;

            // 添加缩放动画效果
            indicator.style.transform = 'scale(1.2)';
            setTimeout(function() {
                indicator.style.transform = 'scale(1)';
            }, 200);
        }
    }

    /**
     * 更新长度指示器颜色
     *
     * @param {string} containerId 容器ID
     * @param {number} length 当前长度
     */
    function updateLengthIndicatorColor(containerId, length) {
        var indicator = document.getElementById('length-indicator-' + containerId);
        if (!indicator) return;

        var instance = cardInstances[containerId];
        var maxLength = instance ? instance.maxLength : 20;

        // 根据用户要求优化颜色：0为红色，1-19为橙色，20为蓝色
        if (length === 0) {
            indicator.style.color = '#dc3545'; // 红色 - 空值状态
        } else if (length >= 1 && length <= maxLength - 1) {
            indicator.style.color = '#fd7e14'; // 橙色 - 进行中状态
        } else if (length === maxLength) {
            indicator.style.color = '#007BFF'; // 蓝色 - 完成状态
        } else {
            indicator.style.color = '#dc3545'; // 红色 - 超出状态
        }
    }

    /**
     * 初始化表单验证
     *
     * @param {string} containerId 容器ID
     */
    function initializeFormValidation(containerId) {
        var instance = cardInstances[containerId];
        if (!instance) return;

        var form = document.getElementById(instance.formId);
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (instance.isSubmitted) {
                return; // 防止重复提交
            }

            // 清空之前的验证结果
            clearPreviousResult(containerId);

            var input = document.getElementById(instance.inputId);
            var secret = input ? input.value.trim() : '';

            if (!validateSecretInput(containerId, secret)) {
                shakeElement(input);
                return false;
            }

            // 显示加载状态
            showFormLoading(containerId, true);

            // 模拟验证过程
            setTimeout(function() {
                processSecretValidation(containerId, secret);
            }, 800);
        });
    }

    /**
     * 清空之前的验证结果
     *
     * @param {string} containerId 容器ID
     */
    function clearPreviousResult(containerId) {
        var resultContainer = document.getElementById('validation-result-' + containerId);
        if (resultContainer) {
            resultContainer.innerHTML = '';
        }
    }

    /**
     * 验证秘密输入
     *
     * @param {string} containerId 容器ID
     * @param {string} secret 输入的秘密
     * @returns {boolean} 验证结果
     */
    function validateSecretInput(containerId, secret) {
        var instance = cardInstances[containerId];
        if (!instance) return false;

        // 检查空值
        if (!secret || typeof secret !== 'string') {
            showResult(containerId, instance.config.messages.empty, 'error');
            return false;
        }

        // 检查长度
        if (secret.length !== instance.maxLength) {
            showResult(containerId, instance.config.messages.invalidLength || '请输入' + instance.maxLength + '位的秘密字符串', 'error');
            return false;
        }

        // 检查格式
        try {
            var patternStr = instance.validationPattern;
            var pattern;

            if (typeof patternStr === 'string' && patternStr.startsWith('/') && patternStr.endsWith('/')) {
                pattern = eval(patternStr);
            } else {
                pattern = new RegExp(patternStr);
            }

            if (!pattern.test(secret)) {
                showResult(containerId, instance.config.messages.invalidFormat || '秘密格式不正确', 'error');
                return false;
            }
        } catch (e) {
            return false;
        }

        return true;
    }

    /**
     * 处理秘密验证（使用MD5哈希比对）
     *
     * @param {string} containerId 容器ID
     * @param {string} secret 输入的秘密
     */
    function processSecretValidation(containerId, secret) {
        var instance = cardInstances[containerId];
        if (!instance) return;

        instance.isSubmitted = true; // 标记为已提交

        // 计算用户输入的MD5哈希值并与存储的哈希值比对
        var userSecretHash = HeaSecMD5.hash(secret);
        var isValid = (userSecretHash === instance.secretHash);

        if (isValid) {
            // 验证成功
            showResult(containerId, instance.config.messages.success, 'success', instance.config.messages.successHint);

            // 派发验证成功事件，供外部监听使用
            var successEvent = new CustomEvent('heasec:secretcard:success', {
                detail: {
                    containerId: containerId,
                    config: instance.config
                },
                bubbles: true
            });
            document.dispatchEvent(successEvent);

            // 验证成功时立即显示恭喜弹窗
            if (typeof HeaSecCongratsModal !== 'undefined' && instance.config.enableCongrats) {
                HeaSecCongratsModal.show(instance.config.congratsConfig);
            }

            // 成功后也重置提交状态，允许用户继续使用
            instance.isSubmitted = false;
        } else {
            // 验证失败
            showResult(containerId, instance.config.messages.error, 'error');
            instance.isSubmitted = false; // 重置提交状态
        }

        // 恢复表单状态
        showFormLoading(containerId, false);
    }

    /**
     * 显示验证结果
     *
     * @param {string} containerId 容器ID
     * @param {string} message 消息内容
     * @param {string} type 消息类型 (success|error)
     * @param {string} hint 提示信息（可选）
     */
    function showResult(containerId, message, type, hint) {
        var resultContainer = document.getElementById('validation-result-' + containerId);
        if (!resultContainer) return;

        var alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        var iconClass = type === 'success' ? 'check-circle' : 'times-circle';

        var html = '<div class="alert ' + alertClass + '">' +
            '<div>' +
            '<i class="fa fa-' + iconClass + '"></i>' +
            '<strong>' + message + '</strong>' +
            '</div>';

        if (hint) {
            html += '<p class="alert-hint"><small>' + hint + '</small></p>';
        }

        html += '</div>';

        resultContainer.innerHTML = html;

        // 滚动到结果区域
        resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    /**
     * 显示表单加载状态
     *
     * @param {string} containerId 容器ID
     * @param {boolean} show 是否显示加载状态
     */
    function showFormLoading(containerId, show) {
        var instance = cardInstances[containerId];
        if (!instance) return;

        var submitButton = document.querySelector('#' + instance.formId + ' button[type="submit"]');
        if (!submitButton) return;

        if (show) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 验证中...';
            submitButton.style.opacity = '0.7';
        } else {
            submitButton.disabled = false;
            submitButton.innerHTML = '<i class="' + instance.submitIcon + '"></i> ' + instance.submitText;
            submitButton.style.opacity = '1';
        }
    }

    /**
     * 初始化交互功能
     *
     * @param {string} containerId 容器ID
     */
    function initializeInteractions(containerId) {
        var instance = cardInstances[containerId];
        if (!instance) return;

        var input = document.getElementById(instance.inputId);
        var form = document.getElementById(instance.formId);

        // 自动聚焦已禁用
        // if (input && !input.value && document.activeElement !== input) {
        //     setTimeout(function() {
        //         input.focus();
        //     }, 500);
        // }

        // 添加卡片悬停效果
        var card = document.getElementById(containerId);
        if (card) {
            card.addEventListener('mouseenter', function() {
                addCardGlowEffect(this);
            });

            card.addEventListener('mouseleave', function() {
                removeCardGlowEffect(this);
            });
        }

        // 键盘快捷键支持
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                if (document.activeElement === input && form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }

            if (e.key === 'Escape') {
                reset(containerId);
            }
        });
    }

    /**
     * 重置密码验证卡片
     *
     * @param {string} containerId 容器ID
     */
    function reset(containerId) {
        var instance = cardInstances[containerId];
        if (!instance) return;

        var form = document.getElementById(instance.formId);
        var input = document.getElementById(instance.inputId);
        var resultContainer = document.getElementById('validation-result-' + containerId);

        if (form) {
            instance.isSubmitted = false;

            if (input) {
                input.value = '';
                input.disabled = false;
                input.style.background = '';
                input.style.cursor = '';
                updateLengthIndicator(containerId, 0);
                updateLengthIndicatorColor(containerId, 0);
            }

            if (resultContainer) {
                resultContainer.innerHTML = '';
            }

            var submitButton = document.querySelector('#' + instance.formId + ' button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.style.opacity = '1';
                submitButton.style.cursor = '';
                submitButton.innerHTML = '<i class="' + instance.submitIcon + '"></i> ' + instance.submitText;
            }

            addResetAnimation(form);

            setTimeout(function() {
                if (input) {
                    input.focus();
                }
            }, 300);
        }
    }

    /**
     * 添加卡片发光效果
     *
     * @param {HTMLElement} card 卡片元素
     */
    function addCardGlowEffect(card) {
        card.style.transition = 'all 0.3s ease';
        card.style.boxShadow = '0 12px 40px rgba(0, 123, 255, 0.2), 0 4px 12px rgba(0, 0, 0, 0.15), inset 0 1px 0 rgba(255, 255, 255, 0.9)';
    }

    /**
     * 移除卡片发光效果
     *
     * @param {HTMLElement} card 卡片元素
     */
    function removeCardGlowEffect(card) {
        card.style.boxShadow = '';
    }

    /**
     * 元素震动效果
     *
     * @param {HTMLElement} element 要震动的元素
     */
    function shakeElement(element) {
        if (!element) return;

        element.style.animation = 'shake 0.5s ease-in-out';

        setTimeout(function() {
            element.style.animation = '';
        }, 500);
    }

    /**
     * 添加重置动画
     *
     * @param {HTMLElement} form 表单元素
     */
    function addResetAnimation(form) {
        if (!form) return;

        form.style.animation = 'fadeInScale 0.3s ease-out';

        setTimeout(function() {
            form.style.animation = '';
        }, 300);
    }

    // 公开API
    return {
        init: init,
        showResult: showResult,
        reset: reset,
        cardInstances: cardInstances
    };
})();

// 添加CSS动画
(function() {
    var style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        @keyframes fadeInScale {
            0% { opacity: 0.8; transform: scale(0.98); }
            100% { opacity: 1; transform: scale(1); }
        }
    `;
    document.head.appendChild(style);
})();

// 全局错误处理
window.addEventListener('error', function(e) {
    // 静默处理错误
});

// 暴露全局重置函数（保持向后兼容）
window.resetSecretCard = function(containerId) {
    if (HeaSecSecretCard && HeaSecSecretCard.reset) {
        HeaSecSecretCard.reset(containerId);
    }
};

// 页面可见性变化时的处理（自动聚焦已禁用）
// document.addEventListener('visibilitychange', function() {
//     if (document.visibilityState === 'visible') {
//         Object.keys(HeaSecSecretCard.cardInstances || {}).forEach(function(containerId) {
//             var input = document.getElementById(HeaSecSecretCard.cardInstances[containerId].inputId);
//             if (input && document.activeElement !== input && !input.value) {
//                 setTimeout(function() {
//                     input.focus();
//                 }, 500);
//             }
//         });
//     }
// });

// 暴露到全局作用域（确保兼容性）
window.HeaSecSecretCard = HeaSecSecretCard;