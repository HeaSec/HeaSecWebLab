/**
 * HeaSec天积安全团队 - JWT基础靶场JavaScript文件
 * 版本: v1.0.0
 * 创建日期: 2026-03-02
 * 团队: 天积安全 (HeavenlySecret)
 */

// ==================== 全局团队信息 ====================
window.HeaSec = {
    name: '天积安全',
    englishName: 'HeavenlySecret',
    abbreviation: 'HeaSec',
    slogan: '日积寸功，乐享安全',
    version: 'v1.0.0'
};

// ==================== Base64URL编解码函数 ====================

/**
 * Base64URL编码（支持Unicode字符）
 * @param {string} str - 要编码的字符串
 * @returns {string} Base64URL编码结果
 */
function base64UrlEncode(str) {
    // 先将字符串转换为UTF-8字节，再进行Base64编码，以支持Unicode字符
    return btoa(unescape(encodeURIComponent(str)))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=/g, '');
}

/**
 * Base64URL解码（支持Unicode字符）
 * @param {string} str - Base64URL编码的字符串
 * @returns {string} 解码结果
 */
function base64UrlDecode(str) {
    // 替换URL安全字符为标准Base64字符
    str = str.replace(/-/g, '+').replace(/_/g, '/');
    // 添加填充
    while (str.length % 4) {
        str += '=';
    }
    // 解码并支持Unicode字符（与编码函数对应）
    return decodeURIComponent(escape(atob(str)));
}

/**
 * ArrayBuffer转Base64URL
 * @param {ArrayBuffer} buffer - ArrayBuffer对象
 * @returns {string} Base64URL编码结果
 */
function arrayBufferToBase64Url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary)
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=/g, '');
}

// ==================== JWT签名函数 ====================

/**
 * HMAC签名
 * @param {string} message - 要签名的消息
 * @param {string} secret - 签名密钥
 * @param {string} algorithm - 算法（HS256/HS384/HS512）
 * @returns {Promise<string>} 签名结果
 */
async function hmacSign(message, secret, algorithm) {
    const hashMap = {
        'HS256': 'SHA-256',
        'HS384': 'SHA-384',
        'HS512': 'SHA-512'
    };

    const encoder = new TextEncoder();
    const keyData = encoder.encode(secret);
    const messageData = encoder.encode(message);

    try {
        const key = await crypto.subtle.importKey(
            'raw',
            keyData,
            { name: 'HMAC', hash: hashMap[algorithm] },
            false,
            ['sign']
        );

        const signature = await crypto.subtle.sign('HMAC', key, messageData);
        return arrayBufferToBase64Url(signature);
    } catch (error) {
        console.error('HMAC签名失败:', error);
        throw new Error('HMAC签名失败: ' + error.message);
    }
}

/**
 * RSA签名（仅支持RS256）
 * @param {string} message - 要签名的消息
 * @param {string} privateKeyPem - PEM格式私钥
 * @returns {Promise<string>} 签名结果
 */
async function rsaSign(message, privateKeyPem) {
    try {
        // 解析PEM格式私钥
        const pemHeader = '-----BEGIN PRIVATE KEY-----';
        const pemFooter = '-----END PRIVATE KEY-----';
        let pemContents = privateKeyPem;

        // 处理可能存在的PKCS#8格式
        if (privateKeyPem.includes(pemHeader)) {
            pemContents = privateKeyPem
                .replace(pemHeader, '')
                .replace(pemFooter, '')
                .replace(/\s/g, '');
        }

        // Base64解码
        const binaryKey = atob(pemContents);
        const keyBuffer = new Uint8Array(binaryKey.length);
        for (let i = 0; i < binaryKey.length; i++) {
            keyBuffer[i] = binaryKey.charCodeAt(i);
        }

        // 导入私钥（RS256固定使用SHA-256）
        const key = await crypto.subtle.importKey(
            'pkcs8',
            keyBuffer,
            { name: 'RSASSA-PKCS1-v1_5', hash: 'SHA-256' },
            false,
            ['sign']
        );

        // 签名
        const encoder = new TextEncoder();
        const signature = await crypto.subtle.sign(
            'RSASSA-PKCS1-v1_5',
            key,
            encoder.encode(message)
        );

        return arrayBufferToBase64Url(signature);
    } catch (error) {
        throw new Error('RSA签名失败: ' + error.message);
    }
}

/**
 * 生成RSA密钥对
 * @returns {Promise<{privateKey: string, publicKey: string}>} PEM格式密钥对
 */
async function generateRSAKeyPair() {
    try {
        const keyPair = await crypto.subtle.generateKey(
            {
                name: 'RSASSA-PKCS1-v1_5',
                modulusLength: 2048,
                publicExponent: new Uint8Array([1, 0, 1]),
                hash: 'SHA-256'
            },
            true,
            ['sign', 'verify']
        );

        // 导出为PKCS#8格式
        const privateKeyData = await crypto.subtle.exportKey('pkcs8', keyPair.privateKey);
        const publicKeyData = await crypto.subtle.exportKey('spki', keyPair.publicKey);

        return {
            privateKey: formatPem(privateKeyData, 'PRIVATE KEY'),
            publicKey: formatPem(publicKeyData, 'PUBLIC KEY')
        };
    } catch (error) {
        console.error('生成RSA密钥对失败:', error);
        throw new Error('生成RSA密钥对失败: ' + error.message);
    }
}

/**
 * 格式化为PEM格式
 * @param {ArrayBuffer} keyData - 密钥数据
 * @param {string} label - PEM标签
 * @returns {string} PEM格式字符串
 */
function formatPem(keyData, label) {
    const base64 = btoa(String.fromCharCode(...new Uint8Array(keyData)));
    const lines = base64.match(/.{1,64}/g) || [];
    return `-----BEGIN ${label}-----\n${lines.join('\n')}\n-----END ${label}-----`;
}

// ==================== JWT编码解码函数 ====================

/**
 * JWT编码
 * @param {Object} header - JWT头部
 * @param {Object} payload - JWT负载
 * @param {string} secretOrKey - 密钥或私钥
 * @param {string} algorithm - 算法
 * @returns {Promise<string>} JWT Token
 */
async function encodeJWT(header, payload, secretOrKey, algorithm) {
    // 1. 编码Header
    const encodedHeader = base64UrlEncode(JSON.stringify(header));

    // 2. 编码Payload
    const encodedPayload = base64UrlEncode(JSON.stringify(payload));

    // 3. 根据算法生成签名
    let signature = '';
    const message = encodedHeader + '.' + encodedPayload;

    if (algorithm === 'none') {
        signature = '';
    } else if (algorithm.startsWith('HS')) {
        // HMAC签名
        signature = await hmacSign(message, secretOrKey, algorithm);
    } else if (algorithm.startsWith('RS')) {
        // RSA签名
        signature = await rsaSign(message, secretOrKey, algorithm);
    }

    // 4. 组合JWT
    return encodedHeader + '.' + encodedPayload + '.' + signature;
}

/**
 * JWT解码
 * @param {string} token - JWT Token
 * @returns {Object} 解码结果 {header, payload, signature}
 */
function decodeJWT(token) {
    const parts = token.split('.');
    if (parts.length !== 3) {
        throw new Error('JWT格式错误：Token应包含三个用点号分隔的部分');
    }

    try {
        const header = JSON.parse(base64UrlDecode(parts[0]));
        const payload = JSON.parse(base64UrlDecode(parts[1]));
        const signature = parts[2];

        return { header, payload, signature };
    } catch (error) {
        if (error.message.includes('JSON')) {
            throw new Error('JSON解析失败：无效的JSON格式');
        }
        throw new Error('解码失败：无效的Base64URL编码');
    }
}

// ==================== UI交互函数 ====================

/**
 * HTML转义函数，防止XSS攻击
 * @param {string} str - 要转义的字符串
 * @returns {string} 转义后的字符串
 */
function escapeHtml(str) {
    const htmlEntities = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    };
    return String(str).replace(/[&<>"']/g, char => htmlEntities[char]);
}

/**
 * 可折叠区域切换
 * @param {string} sectionId - 区域ID
 */
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;

    section.classList.toggle('expanded');
}

/**
 * JWT流程图HTTP示例切换
 * @param {HTMLElement} btn - 触发切换的按钮
 */
function toggleJwtStep(btn) {
    if (!btn) return;
    btn.classList.toggle('active');
    const contentBox = btn.closest('.jwt-step-content');
    if (contentBox) {
        const detail = contentBox.querySelector('.jwt-step-detail');
        if (detail) {
            detail.classList.toggle('show');
        }
    }
}

/**
 * 防抖函数
 * @param {Function} func - 要执行的函数
 * @param {number} wait - 等待时间（毫秒）
 * @returns {Function} 防抖后的函数
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * 显示成功消息
 * @param {string} message - 消息内容
 */
function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success heasec-alert';
    alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 300px;';
    alertDiv.innerHTML = '<i class="fa fa-check-circle"></i> ' + message;
    document.body.appendChild(alertDiv);

    setTimeout(function() {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 3000);
}

/**
 * 显示错误消息
 * @param {string} message - 消息内容
 */
function showErrorMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger heasec-alert';
    alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 300px;';
    alertDiv.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + message;
    document.body.appendChild(alertDiv);

    setTimeout(function() {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

/**
 * 复制到剪贴板
 * @param {string} text - 要复制的文本
 * @returns {Promise<boolean>} 是否成功
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        showSuccessMessage('已复制到剪贴板');
        return true;
    } catch (error) {
        // 降级方案
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-9999px';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showSuccessMessage('已复制到剪贴板');
            return true;
        } catch (e) {
            showErrorMessage('复制失败');
            return false;
        } finally {
            document.body.removeChild(textArea);
        }
    }
}

// ==================== Base64URL编码实践 ====================

/**
 * 初始化Base64URL编码实践区域
 */
function initBase64UrlPractice() {
    const inputElement = document.getElementById('base64UrlInput');
    const standardOutput = document.getElementById('standardBase64Output');
    const urlOutput = document.getElementById('base64UrlOutput');

    if (!inputElement || !standardOutput || !urlOutput) return;

    const updateEncoding = debounce(function() {
        const input = inputElement.value;

        if (!input) {
            standardOutput.innerHTML = '<span class="text-muted">等待输入...</span>';
            urlOutput.innerHTML = '<span class="text-muted">等待输入...</span>';
            return;
        }

        try {
            // 标准Base64编码
            const standardBase64 = btoa(input);
            // Base64URL编码
            const base64Url = base64UrlEncode(input);

            // 高亮显示差异
            standardOutput.innerHTML = highlightBase64Differences(standardBase64, false);
            urlOutput.innerHTML = highlightBase64Differences(base64Url, true);
        } catch (error) {
            standardOutput.innerHTML = '<span class="text-danger">编码失败</span>';
            urlOutput.innerHTML = '<span class="text-danger">编码失败</span>';
        }
    }, 300);

    inputElement.addEventListener('input', updateEncoding);
}

/**
 * 高亮Base64差异字符
 * @param {string} str - 字符串
 * @param {boolean} isUrl - 是否是URL安全版本
 * @returns {string} 高亮后的HTML
 */
function highlightBase64Differences(str, isUrl) {
    let result = '';
    for (let i = 0; i < str.length; i++) {
        const char = str[i];
        if (char === '+') {
            result += '<span class="highlight-plus">' + char + '</span>';
        } else if (char === '/') {
            result += '<span class="highlight-slash">' + char + '</span>';
        } else if (char === '=') {
            result += '<span class="highlight-equals">' + char + '</span>';
        } else if (char === '-') {
            result += '<span class="highlight-slash">' + char + '</span>';
        } else if (char === '_') {
            result += '<span class="highlight-plus">' + char + '</span>';
        } else {
            result += char;
        }
    }
    return result;
}

// ==================== JWT编码器 ====================

// 存储RSA密钥对
let rsaKeyPair = null;

/**
 * 初始化JWT编码器
 */
function initJwtEncoder() {
    const headerInput = document.getElementById('jwtHeaderInput');
    const payloadInput = document.getElementById('jwtPayloadInput');
    const algorithmSelect = document.getElementById('jwtAlgorithmSelect');
    const secretInput = document.getElementById('jwtSecretInput');
    const privateKeyInput = document.getElementById('jwtPrivateKeyInput');
    const publicKeyInput = document.getElementById('jwtPublicKeyInput');
    const generateKeyBtn = document.getElementById('generateRsaKeyBtn');
    const hmacConfig = document.getElementById('hmacConfig');
    const rsaConfig = document.getElementById('rsaConfig');

    if (!headerInput || !payloadInput || !algorithmSelect) return;

    // 算法切换处理
    algorithmSelect.addEventListener('change', function() {
        const algorithm = this.value;

        // 更新Header中的alg字段
        try {
            const header = JSON.parse(headerInput.value);
            header.alg = algorithm;
            headerInput.value = JSON.stringify(header, null, 4);
        } catch (e) {
            // 忽略JSON解析错误
        }

        // 显示/隐藏配置区域
        if (hmacConfig && rsaConfig) {
            if (algorithm === 'none') {
                hmacConfig.style.display = 'none';
                rsaConfig.style.display = 'none';
            } else if (algorithm.startsWith('HS')) {
                hmacConfig.style.display = 'block';
                rsaConfig.style.display = 'none';
            } else if (algorithm.startsWith('RS')) {
                hmacConfig.style.display = 'none';
                rsaConfig.style.display = 'block';
            }
        }

        // 更新JWT
        updateJwtToken();
    });

    // 生成RSA密钥对
    if (generateKeyBtn) {
        generateKeyBtn.addEventListener('click', async function() {
            this.disabled = true;
            this.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 生成中...';

            try {
                rsaKeyPair = await generateRSAKeyPair();
                if (privateKeyInput) {
                    privateKeyInput.value = rsaKeyPair.privateKey;
                }
                if (publicKeyInput) {
                    publicKeyInput.value = rsaKeyPair.publicKey;
                }
                showSuccessMessage('RSA密钥对生成成功');
                updateJwtToken();
            } catch (error) {
                showErrorMessage('生成密钥对失败: ' + error.message);
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fa fa-key"></i> 生成密钥对';
            }
        });
    }

    // 实时更新JWT
    const updateJwtToken = debounce(async function() {
        const outputElement = document.getElementById('jwtTokenOutput');
        if (!outputElement) return;

        try {
            const header = JSON.parse(headerInput.value);
            const payload = JSON.parse(payloadInput.value);
            const algorithm = algorithmSelect.value;

            let secretOrKey = '';
            if (algorithm.startsWith('HS')) {
                secretOrKey = secretInput ? secretInput.value : '';
            } else if (algorithm.startsWith('RS')) {
                secretOrKey = privateKeyInput ? privateKeyInput.value : '';
            }

            const token = await encodeJWT(header, payload, secretOrKey, algorithm);
            renderJwtToken(token, outputElement);
        } catch (error) {
            outputElement.innerHTML = '<div class="output-box error">' + error.message + '</div>';
        }
    }, 300);

    // 绑定输入事件
    headerInput.addEventListener('input', updateJwtToken);
    payloadInput.addEventListener('input', updateJwtToken);
    if (secretInput) {
        secretInput.addEventListener('input', updateJwtToken);
    }
    if (privateKeyInput) {
        privateKeyInput.addEventListener('input', updateJwtToken);
    }

    // 复制按钮
    const copyBtn = document.getElementById('copyTokenBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', async function() {
            const outputElement = document.getElementById('jwtTokenOutput');
            if (outputElement) {
                const token = outputElement.getAttribute('data-token');
                if (token) {
                    await copyToClipboard(token);
                }
            }
        });
    }

    // 初始生成
    updateJwtToken();
}

/**
 * 渲染JWT Token（三段颜色）
 * @param {string} token - JWT Token
 * @param {HTMLElement} container - 容器元素
 */
function renderJwtToken(token, container) {
    const parts = token.split('.');
    if (parts.length !== 3) {
        container.innerHTML = '<div class="output-box error">无效的JWT格式</div>';
        return;
    }

    container.setAttribute('data-token', token);
    // 使用escapeHtml防止XSS攻击
    container.innerHTML = `
        <div class="jwt-token-display">
            <span class="jwt-token-segment header-segment">${escapeHtml(parts[0])}</span><span class="jwt-dot">.</span><span class="jwt-token-segment payload-segment">${escapeHtml(parts[1])}</span><span class="jwt-dot">.</span><span class="jwt-token-segment signature-segment">${escapeHtml(parts[2])}</span>
        </div>
        <div class="jwt-legend" style="margin-top: 10px; font-size: 12px;">
            <span style="margin-right: 15px;"><span style="display: inline-block; width: 12px; height: 12px; background: #e74c3c; border-radius: 2px; margin-right: 4px;"></span>Header</span>
            <span style="margin-right: 15px;"><span style="display: inline-block; width: 12px; height: 12px; background: #9b59b6; border-radius: 2px; margin-right: 4px;"></span>Payload</span>
            <span><span style="display: inline-block; width: 12px; height: 12px; background: #3498db; border-radius: 2px; margin-right: 4px;"></span>Signature</span>
        </div>
    `;
}

// ==================== JWT解码器 ====================

/**
 * 初始化JWT解码器
 */
function initJwtDecoder() {
    const inputElement = document.getElementById('jwtDecodeInput');
    const loadExampleBtn = document.getElementById('loadExampleToken');
    const headerOutput = document.getElementById('decodedHeader');
    const payloadOutput = document.getElementById('decodedPayload');
    const signatureOutput = document.getElementById('decodedSignature');

    if (!inputElement) return;

    // 预设示例Token
    const exampleToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJoZWFzZWMuY29tIiwic3ViIjoidXNlcjEyMyIsInVzZXJuYW1lIjoiYWRtaW4iLCJyb2xlIjoiYWRtaW5pc3RyYXRvciIsImlhdCI6MTczNTYwMzIwMH0.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

    // 加载示例按钮
    if (loadExampleBtn) {
        loadExampleBtn.addEventListener('click', function() {
            inputElement.value = exampleToken;
            updateDecoding();
        });
    }

    // 实时解码
    const updateDecoding = debounce(function() {
        const token = inputElement.value.trim();

        if (!token) {
            if (headerOutput) headerOutput.innerHTML = '<div class="text-muted">等待输入...</div>';
            if (payloadOutput) payloadOutput.innerHTML = '<div class="text-muted">等待输入...</div>';
            if (signatureOutput) signatureOutput.innerHTML = '<div class="text-muted">等待输入...</div>';
            return;
        }

        try {
            const decoded = decodeJWT(token);

            if (headerOutput) {
                headerOutput.innerHTML = `<div class="output-box json">${syntaxHighlightJSON(decoded.header)}</div>`;
            }
            if (payloadOutput) {
                payloadOutput.innerHTML = `<div class="output-box json">${syntaxHighlightJSON(decoded.payload)}</div>`;
            }
            if (signatureOutput) {
                signatureOutput.innerHTML = `<div class="output-box">${decoded.signature || '<em>无签名</em>'}</div>`;
            }
        } catch (error) {
            if (headerOutput) headerOutput.innerHTML = `<div class="output-box error">${error.message}</div>`;
            if (payloadOutput) payloadOutput.innerHTML = '';
            if (signatureOutput) signatureOutput.innerHTML = '';
        }
    }, 300);

    inputElement.addEventListener('input', updateDecoding);
}

/**
 * JSON语法高亮
 * @param {Object} json - JSON对象
 * @returns {string} 高亮后的HTML
 */
function syntaxHighlightJSON(json) {
    const str = JSON.stringify(json, null, 2);
    return str.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function(match) {
        let cls = 'number';
        if (/^"/.test(match)) {
            if (/:$/.test(match)) {
                cls = 'key';
            } else {
                cls = 'string';
            }
        } else if (/true|false/.test(match)) {
            cls = 'boolean';
        } else if (/null/.test(match)) {
            cls = 'null';
        }

        const colors = {
            'key': '#9cdcfe',
            'string': '#ce9178',
            'number': '#b5cea8',
            'boolean': '#569cd6',
            'null': '#569cd6'
        };

        return '<span style="color: ' + colors[cls] + ';">' + match + '</span>';
    });
}

// ==================== 学习完成功能 ====================

/**
 * 显示掌握恭喜弹窗
 */
function showMasteryCongrats() {
    if (typeof HeaSecCongratsModal !== 'undefined') {
        HeaSecCongratsModal.show({
            title: '🎉 恭喜你掌握了一个新技能',
            message: '你已掌握JWT的基本结构和编解码原理',
            buttonText: '继续学习',
            showParticles: true,
            particleCount: 10,
            animationDuration: 2500,
            enableNextRangeButton: true,
            rangeCode: 'jwtbasic',
            updateStatusApiUrl: heasecConfig.commonBasePath + 'api/update-learning-status.php',
            nextRangeApiUrl: heasecConfig.commonBasePath + 'api/next-range.php',
            onClose: function() {
                // 弹窗关闭
            },
            onContinue: function() {
                updateLearningStatus();
            }
        });
    } else {
        alert('🎉 恭喜你掌握了一个新技能\n\n你已掌握JWT的基本结构和编解码原理！\n\n系统正在记录你的学习状态...');
        updateLearningStatus();
    }
}

/**
 * 更新学习状态
 */
function updateLearningStatus() {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', heasecConfig.commonBasePath + 'api/update-learning-status.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showSuccessMessage('学习状态已更新为"已掌握"');

                        const masteryBtn = document.getElementById('masteryBtn');
                        if (masteryBtn) {
                            masteryBtn.disabled = true;
                            masteryBtn.innerHTML = '<i class="fa fa-check"></i> 已掌握';
                            masteryBtn.style.opacity = '0.7';
                            masteryBtn.style.cursor = 'not-allowed';
                        }
                    } else {
                        console.error('学习状态更新失败:', response.message);
                        showErrorMessage('学习状态更新失败，请稍后重试');
                    }
                } catch (e) {
                    console.error('响应解析失败:', e);
                    showErrorMessage('服务器响应异常，请稍后重试');
                }
            } else {
                console.error('请求失败，状态码:', xhr.status);
                showErrorMessage('请求失败，请检查网络连接');
            }
        }
    };
    xhr.onerror = function() {
        console.error('请求发送失败');
        showErrorMessage('网络连接失败，请稍后重试');
    };

    const data = JSON.stringify({
        code: 'jwtbasic',
        status: '已掌握',
        timestamp: Date.now()
    });
    xhr.send(data);
}

// ==================== JWT编解码互转功能 ====================

/**
 * 将编码器生成的Token发送到解码器
 */
function sendTokenToDecoder() {
    const outputElement = document.getElementById('jwtTokenOutput');
    if (!outputElement) {
        showErrorMessage('未找到Token输出区域');
        return;
    }

    const token = outputElement.getAttribute('data-token');
    if (!token) {
        showErrorMessage('请先生成JWT Token');
        return;
    }

    // 获取解码器输入框
    const decoderInput = document.getElementById('jwtDecodeInput');
    if (!decoderInput) {
        showErrorMessage('未找到解码器输入区域');
        return;
    }

    // 填入Token
    decoderInput.value = token;

    // 展开解码器区域
    const decoderSection = document.getElementById('section5');
    if (decoderSection && !decoderSection.classList.contains('expanded')) {
        decoderSection.classList.add('expanded');
    }

    // 触发解码（通过input事件）
    decoderInput.dispatchEvent(new Event('input'));

    // 滚动到解码器区域
    setTimeout(function() {
        decoderSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);

    showSuccessMessage('Token已发送到解码器');
}

/**
 * 将解码器的Header和Payload发送到编码器
 */
function sendDecodedToEncoder() {
    // 获取解码后的Header和Payload
    const headerOutput = document.getElementById('decodedHeader');
    const payloadOutput = document.getElementById('decodedPayload');

    if (!headerOutput || !payloadOutput) {
        showErrorMessage('未找到解码结果区域');
        return;
    }

    // 从输出区域提取JSON数据
    const headerText = headerOutput.querySelector('.output-box.json');
    const payloadText = payloadOutput.querySelector('.output-box.json');

    if (!headerText || !payloadText) {
        showErrorMessage('请先解码一个有效的JWT Token');
        return;
    }

    // 获取解码器输入的Token以提取算法信息
    const decoderInput = document.getElementById('jwtDecodeInput');
    let algorithm = 'HS256';

    if (decoderInput && decoderInput.value.trim()) {
        try {
            const decoded = decodeJWT(decoderInput.value.trim());
            if (decoded.header && decoded.header.alg) {
                algorithm = decoded.header.alg;
            }
        } catch (e) {
            // 使用默认算法
        }
    }

    // 尝试从输出框中解析JSON
    let headerJson, payloadJson;

    try {
        // 获取纯文本内容（去除HTML标签）
        const headerContent = headerText.textContent || headerText.innerText;
        const payloadContent = payloadText.textContent || payloadText.innerText;

        headerJson = JSON.parse(headerContent);
        payloadJson = JSON.parse(payloadContent);
    } catch (e) {
        showErrorMessage('解码结果格式无效');
        return;
    }

    // 获取编码器输入框
    const headerInput = document.getElementById('jwtHeaderInput');
    const payloadInput = document.getElementById('jwtPayloadInput');
    const algorithmSelect = document.getElementById('jwtAlgorithmSelect');

    if (!headerInput || !payloadInput) {
        showErrorMessage('未找到编码器输入区域');
        return;
    }

    // 填入Header和Payload
    headerInput.value = JSON.stringify(headerJson, null, 4);
    payloadInput.value = JSON.stringify(payloadJson, null, 4);

    // 设置算法
    if (algorithmSelect) {
        // 检查算法是否在选项中
        const options = algorithmSelect.options;
        let algorithmExists = false;
        for (let i = 0; i < options.length; i++) {
            if (options[i].value === algorithm) {
                algorithmExists = true;
                break;
            }
        }
        if (algorithmExists) {
            algorithmSelect.value = algorithm;
            // 触发change事件以更新配置区域显示
            algorithmSelect.dispatchEvent(new Event('change'));
        }
    }

    // 展开编码器区域
    const encoderSection = document.getElementById('section4');
    if (encoderSection && !encoderSection.classList.contains('expanded')) {
        encoderSection.classList.add('expanded');
    }

    // 滚动到编码器区域
    setTimeout(function() {
        encoderSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);

    showSuccessMessage('Header和Payload已发送到编码器');
}

// ==================== 页面初始化 ====================

document.addEventListener('DOMContentLoaded', function() {
    // 初始化Base64URL编码实践
    initBase64UrlPractice();

    // 初始化JWT编码器
    initJwtEncoder();

    // 初始化JWT解码器
    initJwtDecoder();

    // 绑定掌握按钮
    const masteryBtn = document.getElementById('masteryBtn');
    if (masteryBtn) {
        masteryBtn.addEventListener('click', showMasteryCongrats);
    }

    // 绑定编解码互转按钮
    const sendToDecoderBtn = document.getElementById('sendToDecoderBtn');
    if (sendToDecoderBtn) {
        sendToDecoderBtn.addEventListener('click', sendTokenToDecoder);
    }

    const sendToEncoderBtn = document.getElementById('sendToEncoderBtn');
    if (sendToEncoderBtn) {
        sendToEncoderBtn.addEventListener('click', sendDecodedToEncoder);
    }

    // JWT基础靶场初始化完成
});
