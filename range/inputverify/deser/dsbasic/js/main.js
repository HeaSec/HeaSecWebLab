/**
 * HeaSec天积安全团队 - 反序列化基础靶场前端脚本
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

/* ==================== 全局配置 ==================== */
window.HeaSec = {
    name: '天积安全',
    nameEn: 'HeavenlySecret',
    abbr: 'HeaSec',
    slogan: '日积寸功，乐享安全'
};

/**
 * HTML转义函数，防止XSS
 * @param {string} str 需要转义的字符串
 * @returns {string} 转义后的安全字符串
 */
function escapeHtml(str) {
    if (typeof str !== 'string') return String(str);
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}

/**
 * 切换可折叠区域的展开/收起状态
 * @param {string} sectionId 区域ID
 */
function toggleSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;
    section.classList.toggle('expanded');
}

/* ==================== 提示消息系统 ==================== */

/**
 * 显示成功提示消息
 * @param {string} message 消息内容
 */
function showSuccessMessage(message) {
    var alert = document.createElement('div');
    alert.className = 'heasec-alert alert-success';
    alert.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; align-items: center;';
    alert.innerHTML = '<i class="fa fa-check-circle"></i> ' + escapeHtml(message);
    document.body.appendChild(alert);
    setTimeout(function() {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.3s';
        setTimeout(function() { alert.remove(); }, 300);
    }, 3000);
}

/**
 * 显示错误提示消息
 * @param {string} message 消息内容
 */
function showErrorMessage(message) {
    var alert = document.createElement('div');
    alert.className = 'heasec-alert alert-danger';
    alert.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10000; display: flex; align-items: center;';
    alert.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + escapeHtml(message);
    document.body.appendChild(alert);
    setTimeout(function() {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.3s';
        setTimeout(function() { alert.remove(); }, 300);
    }, 5000);
}

/* ==================== 序列化练习功能 ==================== */

/**
 * 根据选择的数据类型更新输入表单
 */
function updateSerializeForm() {
    var typeSelect = document.getElementById('serializeType');
    var formContainer = document.getElementById('serializeFormFields');
    if (!typeSelect || !formContainer) return;

    var type = typeSelect.value;
    var html = '';

    switch (type) {
        case 'string':
            html = '<div class="form-group"><label>字符串内容</label>' +
                '<input type="text" id="serStrValue" class="form-control" placeholder="输入字符串内容" value="Hello"></div>';
            break;
        case 'integer':
            html = '<div class="form-group"><label>整数值</label>' +
                '<input type="number" id="serIntValue" class="form-control" placeholder="输入整数" value="42"></div>';
            break;
        case 'float':
            html = '<div class="form-group"><label>浮点数值</label>' +
                '<input type="number" step="0.01" id="serFloatValue" class="form-control" placeholder="输入浮点数" value="3.14"></div>';
            break;
        case 'boolean':
            html = '<div class="form-group"><label>布尔值</label>' +
                '<select id="serBoolValue" class="form-control"><option value="true">true</option><option value="false">false</option></select></div>';
            break;
        case 'null':
            html = '<div class="learning-tip"><i class="fa fa-info-circle"></i>' +
                '<span>NULL类型无需输入额外参数</span></div>';
            break;
        case 'assoc_array':
            html = '<div class="form-group"><label>关联数组（键值对）</label>' +
                '<div id="assocArrayFields">' +
                '<div class="key-value-pair"><input type="text" placeholder="键名" class="assoc-key" value="name"> ' +
                '<input type="text" placeholder="值" class="assoc-value" value="admin"> ' +
                '<button class="remove-pair-btn" onclick="this.parentElement.remove()" title="删除"><i class="fa fa-times"></i></button></div>' +
                '<div class="key-value-pair"><input type="text" placeholder="键名" class="assoc-key" value="age"> ' +
                '<input type="text" placeholder="值" class="assoc-value" value="25"> ' +
                '<button class="remove-pair-btn" onclick="this.parentElement.remove()" title="删除"><i class="fa fa-times"></i></button></div>' +
                '</div>' +
                '<button class="btn btn-secondary" style="margin-top:8px" onclick="addAssocPair()"><i class="fa fa-plus"></i> 添加键值对</button></div>';
            break;
        case 'index_array':
            html = '<div class="form-group"><label>索引数组（列表）</label>' +
                '<div id="indexArrayFields">' +
                '<div class="key-value-pair"><input type="text" placeholder="元素值" class="index-value" value="apple"> ' +
                '<button class="remove-pair-btn" onclick="this.parentElement.remove()" title="删除"><i class="fa fa-times"></i></button></div>' +
                '<div class="key-value-pair"><input type="text" placeholder="元素值" class="index-value" value="banana"> ' +
                '<button class="remove-pair-btn" onclick="this.parentElement.remove()" title="删除"><i class="fa fa-times"></i></button></div>' +
                '</div>' +
                '<button class="btn btn-secondary" style="margin-top:8px" onclick="addIndexItem()"><i class="fa fa-plus"></i> 添加元素</button></div>';
            break;
        case 'object':
            html = '<div class="form-group"><label>类名</label>' +
                '<input type="text" id="serClassName" class="form-control" placeholder="输入类名（如 User、MyClass、TestObj）" value="MyClass"></div>' +
                '<div class="form-group"><label>属性列表</label>' +
                '<div id="objectFieldsContainer"></div>' +
                '<button type="button" class="btn btn-secondary" style="margin-top:8px" onclick="addObjectProperty()">' +
                '<i class="fa fa-plus"></i> 添加属性</button></div>';
            break;
    }

    formContainer.innerHTML = html;

    // 对象类型需要初始化字段
    if (type === 'object') {
        updateObjectFields();
    }
}

/**
 * 添加关联数组键值对
 */
function addAssocPair() {
    var container = document.getElementById('assocArrayFields');
    if (!container) return;
    var pair = document.createElement('div');
    pair.className = 'key-value-pair';
    pair.innerHTML = '<input type="text" placeholder="键名" class="assoc-key"> ' +
        '<input type="text" placeholder="值" class="assoc-value"> ' +
        '<button class="remove-pair-btn" onclick="this.parentElement.remove()" title="删除"><i class="fa fa-times"></i></button>';
    container.appendChild(pair);
}

/**
 * 添加索引数组元素
 */
function addIndexItem() {
    var container = document.getElementById('indexArrayFields');
    if (!container) return;
    var item = document.createElement('div');
    item.className = 'key-value-pair';
    item.innerHTML = '<input type="text" placeholder="元素值" class="index-value"> ' +
        '<button class="remove-pair-btn" onclick="this.parentElement.remove()" title="删除"><i class="fa fa-times"></i></button>';
    container.appendChild(item);
}

/**
 * 初始化对象属性输入字段（动态键值对行）
 */
function updateObjectFields() {
    var container = document.getElementById('objectFieldsContainer');
    if (!container) return;

    // 默认示例属性（包含不同访问修饰符，帮助用户理解）
    var defaultProps = [
        { name: 'name', value: 'admin', visibility: 'public' },
        { name: 'role', value: 'user', visibility: 'public' },
        { name: 'email', value: 'admin@test.com', visibility: 'protected' }
    ];

    var html = '';
    for (var i = 0; i < defaultProps.length; i++) {
        html += buildPropertyRow(defaultProps[i].name, defaultProps[i].value, defaultProps[i].visibility);
    }

    container.innerHTML = html;
}

/**
 * 构建单行属性输入的HTML（含访问修饰符选择器）
 * @param {string} propName 属性名默认值
 * @param {string} propValue 属性值默认值
 * @param {string} visibility 访问修饰符默认值：public/protected/private
 * @returns {string} HTML字符串
 */
function buildPropertyRow(propName, propValue, visibility) {
    visibility = visibility || 'public';
    return '<div class="obj-property-row" style="display:flex;align-items:center;gap:8px;margin-bottom:8px">' +
        '<select class="form-control obj-prop-vis" style="flex:0 0:auto;width:95px;font-size:12px;padding:6px 8px;font-weight:600;font-family:Consolas,monospace">' +
            '<option value="public"' + (visibility === 'public' ? ' selected' : '') + '>public</option>' +
            '<option value="protected"' + (visibility === 'protected' ? ' selected' : '') + '>protected</option>' +
            '<option value="private"' + (visibility === 'private' ? ' selected' : '') + '>private</option>' +
        '</select>' +
        '<input type="text" class="form-control obj-prop-name" placeholder="属性名" value="' + escapeHtml(propName || '') + '" style="flex:1;min-width:0">' +
        '<input type="text" class="form-control obj-prop-value" placeholder="属性值" value="' + escapeHtml(propValue || '') + '" style="flex:2;min-width:0">' +
        '<button type="button" class="remove-pair-btn" onclick="this.parentElement.remove()" title="删除此属性"><i class="fa fa-times"></i></button>' +
        '</div>';
}

/**
 * 动态添加一行属性输入
 */
function addObjectProperty() {
    var container = document.getElementById('objectFieldsContainer');
    if (!container) return;
    var row = document.createElement('div');
    row.innerHTML = buildPropertyRow('', '', 'public');
    container.appendChild(row.firstChild);
}

/**
 * 快速填充示例数据
 * @param {string} type 数据类型
 */
function quickFillExample(type) {
    var typeSelect = document.getElementById('serializeType');
    if (!typeSelect) return;
    typeSelect.value = type;
    updateSerializeForm();

    // 填充示例值
    switch (type) {
        case 'string':
            var el = document.getElementById('serStrValue');
            if (el) el.value = 'Hello World';
            break;
        case 'integer':
            var el = document.getElementById('serIntValue');
            if (el) el.value = '42';
            break;
        case 'float':
            var el = document.getElementById('serFloatValue');
            if (el) el.value = '3.14';
            break;
        case 'boolean':
            var el = document.getElementById('serBoolValue');
            if (el) el.value = 'true';
            break;
    }
}

/**
 * 执行序列化操作
 */
function doSerialize() {
    var typeSelect = document.getElementById('serializeType');
    if (!typeSelect) return;

    var type = typeSelect.value;
    var requestBody = { type: type };

    switch (type) {
        case 'string':
            var el = document.getElementById('serStrValue');
            requestBody.value = el ? el.value : '';
            break;
        case 'integer':
            var el = document.getElementById('serIntValue');
            requestBody.value = el ? parseInt(el.value) : 0;
            break;
        case 'float':
            var el = document.getElementById('serFloatValue');
            requestBody.value = el ? parseFloat(el.value) : 0;
            break;
        case 'boolean':
            var el = document.getElementById('serBoolValue');
            requestBody.value = el ? el.value : 'false';
            break;
        case 'null':
            break;
        case 'assoc_array':
            var keys = document.querySelectorAll('.assoc-key');
            var values = document.querySelectorAll('.assoc-value');
            var data = {};
            for (var i = 0; i < keys.length; i++) {
                if (keys[i].value.trim()) {
                    data[keys[i].value.trim()] = values[i] ? values[i].value : '';
                }
            }
            requestBody.data = data;
            break;
        case 'index_array':
            var items = document.querySelectorAll('.index-value');
            var data = [];
            for (var i = 0; i < items.length; i++) {
                if (items[i].value.trim()) {
                    data.push(items[i].value.trim());
                }
            }
            requestBody.data = data;
            break;
        case 'object':
            var classInput = document.getElementById('serClassName');
            requestBody.class = classInput ? classInput.value.trim() : 'MyClass';
            if (!requestBody.class) {
                requestBody.class = 'MyClass';
            }
            // 从动态属性行收集数据（含访问修饰符信息）
            var propNames = document.querySelectorAll('.obj-prop-name');
            var propValues = document.querySelectorAll('.obj-prop-value');
            var propVis = document.querySelectorAll('.obj-prop-vis');
            var propData = [];
            for (var i = 0; i < propNames.length; i++) {
                var pName = propNames[i].value.trim();
                if (pName) {
                    propData.push({
                        name: pName,
                        value: propValues[i] ? propValues[i].value : '',
                        visibility: propVis[i] ? propVis[i].value : 'public'
                    });
                }
            }
            requestBody.properties = propData;
            break;
    }

    fetch('./api/serialize.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(requestBody)
    })
    .then(function(resp) { return resp.json(); })
    .then(function(data) {
        var output = document.getElementById('serializeOutput');
        if (!output) return;

        if (data.success) {
            output.className = 'output-box success';
            output.innerHTML = '<span style="color:#27ae60">序列化结果：</span>\n' +
                highlightSerializedString(data.result) +
                '\n\n<span style="color:#718096">输入类型：' + escapeHtml(data.input_type) + '</span>';
            showFormatInterpretation(data.result, 'serialize');
        } else {
            output.className = 'output-box error';
            output.innerHTML = '<span style="color:#f56565">错误：</span>' + escapeHtml(data.error);
        }
    })
    .catch(function(err) {
        showErrorMessage('请求失败：' + err.message);
    });
}

/* ==================== 反序列化练习功能 ==================== */

/**
 * 加载反序列化示例
 */
function loadUnserializeExample() {
    var input = document.getElementById('unserializeInput');
    if (!input) return;
    input.value = 'a:3:{s:4:"name";s:5:"admin";s:3:"age";i:25;s:4:"role";s:4:"user";}';
}

/**
 * 执行反序列化操作
 */
function doUnserialize() {
    var input = document.getElementById('unserializeInput');
    if (!input || !input.value.trim()) {
        showErrorMessage('请输入序列化字符串');
        return;
    }

    fetch('./api/unserialize.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ data: input.value.trim() })
    })
    .then(function(resp) { return resp.json(); })
    .then(function(data) {
        var output = document.getElementById('unserializeOutput');
        if (!output) return;

        if (data.success) {
            output.className = 'output-box success';
            var result = data.result;
            var html = '<span style="color:#27ae60">反序列化结果：</span>\n';
            html += '<span style="color:#9b59b6;font-weight:bold">类型：' + escapeHtml(result.type) + '</span>\n\n';

            if (result.type === 'object') {
                html += '<span style="color:#3498db">类名：' + escapeHtml(result.class) + '</span>\n\n';
                html += escapeHtml(result.formatted);
            } else if (result.type === 'array') {
                html += escapeHtml(result.formatted);
            } else {
                html += '值：' + escapeHtml(result.formatted);
            }

            output.innerHTML = html;
            showFormatInterpretation(input.value.trim(), 'unserialize');
        } else {
            output.className = 'output-box error';
            output.innerHTML = '<span style="color:#f56565">错误：</span>' + escapeHtml(data.error);
        }
    })
    .catch(function(err) {
        showErrorMessage('请求失败：' + err.message);
    });
}

/* ==================== 格式高亮与解读 ==================== */

/**
 * 高亮显示序列化字符串
 * @param {string} str 序列化字符串
 * @returns {string} 高亮后的HTML
 */
function highlightSerializedString(str) {
    var html = '';
    var i = 0;
    while (i < str.length) {
        var c = str[i];
        if (c === 's' && str[i + 1] === ':') {
            // 字符串类型
            var match = str.substring(i).match(/^s:(\d+):"/);
            if (match) {
                var len = parseInt(match[1]);
                var strContent = str.substring(i + match[0].length, i + match[0].length + len);
                html += '<span class="ser-identifier">s</span><span class="ser-separator">:</span>';
                html += '<span class="ser-number">' + match[1] + '</span><span class="ser-separator">:"</span>';
                // 检查是否包含 null byte（protected/private 属性键），特殊高亮显示
                if (strContent.indexOf('\0') !== -1) {
                    var displayContent = escapeHtml(strContent).replace(/\x00/g, '<span class="ser-null-byte" title="null byte (\\0)">\\0</span>');
                    html += '<span class="ser-value ser-value-special">' + displayContent + '</span>';
                } else {
                    html += '<span class="ser-value">' + escapeHtml(strContent) + '</span>';
                }
                html += '<span class="ser-separator">";</span>';
                i += match[0].length + len + 2; // +2 for ";
            } else {
                html += escapeHtml(c);
                i++;
            }
        } else if (c === 'i' && str[i + 1] === ':') {
            var match = str.substring(i).match(/^i:(-?\d+);/);
            if (match) {
                html += '<span class="ser-identifier">i</span><span class="ser-separator">:</span>';
                html += '<span class="ser-number">' + match[1] + '</span><span class="ser-separator">;</span>';
                i += match[0].length;
            } else { html += escapeHtml(c); i++; }
        } else if (c === 'd' && str[i + 1] === ':') {
            var match = str.substring(i).match(/^d:([^;]+);/);
            if (match) {
                html += '<span class="ser-identifier">d</span><span class="ser-separator">:</span>';
                html += '<span class="ser-number">' + match[1] + '</span><span class="ser-separator">;</span>';
                i += match[0].length;
            } else { html += escapeHtml(c); i++; }
        } else if (c === 'b' && str[i + 1] === ':') {
            var match = str.substring(i).match(/^b:([01]);/);
            if (match) {
                html += '<span class="ser-identifier">b</span><span class="ser-separator">:</span>';
                html += '<span class="ser-number">' + match[1] + '</span><span class="ser-separator">;</span>';
                i += match[0].length;
            } else { html += escapeHtml(c); i++; }
        } else if (c === 'N' && str[i + 1] === ';') {
            html += '<span class="ser-null">N</span><span class="ser-separator">;</span>';
            i += 2;
        } else if (c === 'a' && str[i + 1] === ':') {
            var match = str.substring(i).match(/^a:(\d+):\{/);
            if (match) {
                html += '<span class="ser-identifier">a</span><span class="ser-separator">:</span>';
                html += '<span class="ser-number">' + match[1] + '</span><span class="ser-separator">:{</span>';
                i += match[0].length;
            } else { html += escapeHtml(c); i++; }
        } else if (c === 'O' && str[i + 1] === ':') {
            var match = str.substring(i).match(/^O:(\d+):"([^"]+)":(\d+):\{/);
            if (match) {
                html += '<span class="ser-identifier">O</span><span class="ser-separator">:</span>';
                html += '<span class="ser-number">' + match[1] + '</span><span class="ser-separator">:"</span>';
                html += '<span class="ser-class">' + escapeHtml(match[2]) + '</span><span class="ser-separator">":</span>';
                html += '<span class="ser-number">' + match[3] + '</span><span class="ser-separator">:{</span>';
                i += match[0].length;
            } else { html += escapeHtml(c); i++; }
        } else if (c === '}') {
            html += '<span class="ser-separator">}</span>';
            i++;
        } else {
            html += escapeHtml(c);
            i++;
        }
    }
    return html;
}

/* ==================== 格式解读面板（树形视图 v2） ==================== */

/** 当前视图模式：'tree' 或 'linear' */
var _fmtCurrentMode = 'tree';

/**
 * 显示格式解读面板（入口函数）
 * @param {string} serializedStr 序列化字符串
 * @param {string} source 触发来源：'serialize' 或 'unserialize'
 */
function showFormatInterpretation(serializedStr, source) {
    var panel = document.getElementById('formatInterpretation');
    if (!panel) return;

    // 默认来源为 unserialize（向后兼容）
    source = source || 'unserialize';

    fetch('./api/format_parse.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ data: serializedStr })
    })
    .then(function(resp) { return resp.json(); })
    .then(function(data) {
        if (!data.success || !data.tree) return;

        // 读取保存的视图偏好
        _fmtCurrentMode = localStorage.getItem('fmt_view_mode') || 'tree';

        var tree = data.tree;

        panel._fmtTreeData = tree;
        panel._fmtSource = source;
        renderFormatPanel(panel, tree);

        // 根据触发来源移动面板位置
        moveFormatPanel(panel, source);

        panel.style.display = 'block';
    })
    .catch(function() {
        // 静默处理
    });
}

/**
 * 根据触发来源将格式解读面板移动到对应区域下方
 * @param {HTMLElement} panel 面板元素
 * @param {string} source 'serialize' | 'unserialize'
 */
function moveFormatPanel(panel, source) {
    // 序列化区域：插入到序列化结果框下方
    // 反序列化区域：插入到反序列化结果框下方

    var targetArea;
    if (source === 'serialize') {
        targetArea = document.getElementById('serializeOutput');
    } else {
        targetArea = document.getElementById('unserializeOutput');
    }

    if (!targetArea) return;

    // 如果面板已经在正确位置则不移动
    if (panel.nextElementSibling === targetArea.nextElementSibling &&
        panel.previousElementSibling === targetArea) {
        return;
    }

    // 移动面板到目标区域的后面
    targetArea.parentNode.insertBefore(panel, targetArea.nextSibling);
}

/**
 * 渲染格式解读面板
 * @param {HTMLElement} panel 面板元素
 * @param {Object} tree 树形数据
 */
function renderFormatPanel(panel, tree) {
    var isTree = _fmtCurrentMode === 'tree';

    var html = '<div class="fmt-panel-header">';
    html += '<h5><i class="fa fa-search"></i> 格式解读</h5>';
    html += '<div class="fmt-view-switcher">';
    html += '<button class="fmt-view-btn' + (isTree ? ' active' : '') + '" data-mode="tree" onclick="switchFormatView(\'tree\')">';
    html += '<i class="fa fa-sitemap"></i> 树形</button>';
    html += '<button class="fmt-view-btn' + (!isTree ? ' active' : '') + '" data-mode="linear" onclick="switchFormatView(\'linear\')">';
    html += '<i class="fa fa-stream"></i> 线性</button>';
    html += '</div></div>';
    html += '<div class="fmt-panel-body">';

    if (isTree) {
        html += '<div class="fmt-tree-view">' + renderTreeMode(tree, 0) + '</div>';
    } else {
        html += '<div class="fmt-linear-view">' + renderLinearMode(tree) + '</div>';
    }

    html += '</div>';

    panel.innerHTML = html;
}

/**
 * 判断是否为简单类型（无嵌套或嵌套层级很浅）
 * @param {Object} tree 树节点
 * @returns {boolean}
 */
function isSimpleType(tree) {
    var leafTypes = ['string', 'integer', 'float', 'boolean', 'null'];
    if (leafTypes.indexOf(tree.type) !== -1) return true;

    // 容器类型但所有子节点都是叶子
    if (tree.type === 'array' || tree.type === 'object') {
        var children = tree.children || [];
        for (var i = 0; i < children.length; i++) {
            var val = children[i].value;
            if (val.type === 'array' || val.type === 'object') return false;
        }
        return children.length <= 6;
    }

    return false;
}

/**
 * 切换视图模式
 * @param {string} mode 'tree' 或 'linear'
 */
function switchFormatView(mode) {
    _fmtCurrentMode = mode;
    localStorage.setItem('fmt_view_mode', mode);

    // 更新按钮状态
    var btns = document.querySelectorAll('.fmt-view-btn');
    for (var i = 0; i < btns.length; i++) {
        btns[i].classList.toggle('active', btns[i].getAttribute('data-mode') === mode);
    }

    // 重新渲染内容区
    var panel = document.getElementById('formatInterpretation');
    if (panel && panel._fmtTreeData) {
        renderFormatPanel(panel, panel._fmtTreeData);
    }
}

// ==================== 树形视图渲染 ====================

/**
 * 渲染树形模式的根节点
 * @param {Object} node 树节点
 * @param {number} depth 当前深度
 * @param {number} index 在父容器中的序号（从1开始）
 * @param {string} parentType 父容器类型 'object' | 'array' | undefined
 * @returns {string} HTML
 */
function renderTreeMode(node, depth, index, parentType) {
    if (!node) return '';

    if (node.type === 'object') {
        return renderContainerNode(node, depth, 'object');
    } else if (node.type === 'array') {
        return renderContainerNode(node, depth, 'array');
    } else if (node.type === 'key_value_pair') {
        return renderKVPNode(node, depth, index, parentType);
    } else {
        return renderLeafNode(node, depth);
    }
}

/**
 * 渲染容器节点（对象或数组）
 * @param {Object} node 节点数据
 * @param {number} depth 深度
 * @param {string} containerType 'object' | 'array'
 * @returns {string} HTML
 */
function renderContainerNode(node, depth, containerType) {
    var isObj = containerType === 'object';
    var nodeClass = isObj ? 'fmt-node-object' : 'fmt-node-array';
    var icon = isObj ? 'fa-cube' : 'fa-list';
    var label = isObj ? 'Object' : 'Array';

    // 构建元信息
    var metaInfo = '';
    if (isObj) {
        metaInfo = '<span class="fmt-node-class">' + escapeHtml(node.class_name || '') + '</span>';
        metaInfo += '<span class="fmt-node-meta">' + (node.property_count || 0) + ' 个属性</span>';
    } else {
        metaInfo = '<span class="fmt-node-meta">' + (node.element_count || 0) + ' 个元素</span>';
    }

    var childCount = (node.children || []).length;
    var collapsedHint = childCount > 0 ? ' <span class="fmt-node-meta">(' + childCount + ' 项已折叠)</span>' : '';

    var html = '<div class="fmt-node ' + nodeClass + '" data-depth="' + depth + '">';

    // 节点头部
    html += '<div class="fmt-node-header" onclick="toggleFmtNode(this)">';
    html += '<span class="fmt-node-toggle"><i class="fa fa-chevron-down"></i></span>';
    html += '<span class="fmt-node-icon"><i class="fa ' + icon + '"></i></span>';
    html += '<span class="fmt-node-label">' + label + '</span>';
    html += metaInfo;
    html += '</div>';

    // 原始代码区
    if (node.header_segments) {
        html += '<div class="fmt-node-raw">' + renderSegments(node.header_segments) + '</div>';
    }

    // 子节点区域
    html += '<div class="fmt-node-children">';
    var children = node.children || [];
    for (var i = 0; i < children.length; i++) {
        html += renderTreeMode(children[i], depth + 1, i + 1, containerType);
    }
    html += '</div>';

    // 结束标记
    if (node.footer_segment) {
        html += '<div class="fmt-node-footer"><span class="fmt-seg ser-separator" title="' + escapeHtml(node.footer_segment.description) + '">' + escapeHtml(node.footer_segment.value) + '</span></div>';
    }

    html += '</div>';
    return html;
}

/**
 * 渲染键值对节点（key和value分组展示，带序号）
 * @param {Object} kvp 键值对数据
 * @param {number} depth 深度
 * @param {number} index 序号（从1开始）
 * @param {string} parentType 父容器类型 'object' | 'array'
 * @returns {string} HTML
 */
function renderKVPNode(kvp, depth, index, parentType) {
    var keyHtml = '';
    var valueHtml = '';
    var keyTypeLabel = '';
    var valueTypeLabel = '';

    // 渲染 key
    if (kvp.key && kvp.key.segments) {
        keyHtml = renderSegments(kvp.key.segments);
        keyTypeLabel = kvp.key.label || getTypeLabelCN(kvp.key.type);
    } else if (kvp.key) {
        keyHtml = renderTreeMode(kvp.key, depth);
        keyTypeLabel = kvp.key.label || getTypeLabelCN(kvp.key.type);
    }

    // 渲染 value
    if (kvp.value && (kvp.value.type === 'object' || kvp.value.type === 'array')) {
        valueHtml = renderContainerNode(kvp.value, depth, kvp.value.type);
        valueTypeLabel = kvp.value.label || getTypeLabelCN(kvp.value.type);
    } else if (kvp.value && kvp.value.segments) {
        valueHtml = renderSegments(kvp.value.segments);
        valueTypeLabel = kvp.value.label || getTypeLabelCN(kvp.value.type);
    } else if (kvp.value) {
        valueHtml = renderTreeMode(kvp.value, depth);
        valueTypeLabel = kvp.value.label || getTypeLabelCN(kvp.value.type);
    }

    // 构建序号标签：数组用"元素N"，对象用"属性N"
    var indexLabel = '';
    var indexClass = '';
    if (parentType === 'array') {
        indexLabel = '元素' + (index || 0);
        indexClass = 'fmt-kvp-index-array';
    } else if (parentType === 'object') {
        indexLabel = '属性' + (index || 0);
        indexClass = 'fmt-kvp-index-object';
    } else {
        indexLabel = '#' + (index || 0);
        indexClass = 'fmt-kvp-index-default';
    }

    // 构建可见性标签（仅对象属性显示）
    var visibilityBadge = '';
    if (parentType === 'object' && kvp.property_visibility) {
        var vis = kvp.property_visibility;
        var visClass = 'fmt-vis-public';
        var visText = 'public';
        if (vis === 'protected') { visClass = 'fmt-vis-protected'; visText = 'protected'; }
        else if (vis === 'private') { visClass = 'fmt-vis-private'; visText = 'private'; }
        visibilityBadge = '<span class="fmt-visibility-badge ' + visClass + '">' + escapeHtml(visText) + '</span>';
    }

    // 如果value是内嵌容器，用紧凑的横向布局；否则用上下分组的布局
    var isInlineValue = kvp.value && (kvp.value.type === 'object' || kvp.value.type === 'array');

    if (isInlineValue) {
        // 容器类型：序号 + key横排 => 内嵌容器卡片
        return '<div class="fmt-node-kvp">' +
            '<span class="fmt-kvp-idx ' + indexClass + '">' + escapeHtml(indexLabel) + '</span>' +
            visibilityBadge +
            '<div class="fmt-kvp-inline-body">' +
                '<div class="fmt-kvp-section fmt-kvp-section-key-inline">' +
                    '<span class="fmt-kvp-section-label">键 (' + escapeHtml(keyTypeLabel) + ')</span>' +
                    '<div class="fmt-kvp-section-body">' + keyHtml + '</div>' +
                '</div>' +
                '<span class="fmt-kvp-arrow">=&gt;</span>' +
                '<div class="fmt-kvp-section-value-inline">' + valueHtml + '</div>' +
            '</div>' +
            '</div>';
    }

    // 普通类型：序号 + 上下分组布局
    return '<div class="fmt-node-kvp">' +
        '<span class="fmt-kvp-idx ' + indexClass + '">' + escapeHtml(indexLabel) + '</span>' +
        visibilityBadge +
        '<div class="fmt-kvp-grouped-body">' +
            '<div class="fmt-kvp-section fmt-kvp-section-key">' +
                '<span class="fmt-kvp-section-label">键名 (' + escapeHtml(keyTypeLabel) + ')</span>' +
                '<div class="fmt-kvp-section-body">' + keyHtml + '</div>' +
            '</div>' +
            '<div class="fmt-kvp-section fmt-kvp-section-value">' +
                '<span class="fmt-kvp-section-label">键值 (' + escapeHtml(valueTypeLabel) + ')</span>' +
                '<div class="fmt-kvp-section-body">' + valueHtml + '</div>' +
            '</div>' +
        '</div>' +
        '</div>';
}

/**
 * 获取类型的中文标签名
 */
function getTypeLabelCN(type) {
    var map = {
        'string': '字符串', 'integer': '整数', 'float': '浮点数',
        'boolean': '布尔值', 'null': 'NULL', 'object': '对象',
        'array': '数组', 'unknown': '未知'
    };
    return map[type] || type || '';
}

/**
 * 渲染叶子节点（原子类型）
 * @param {Object} node 叶子节点
 * @param {number} depth 深度
 * @returns {string} HTML
 */
function renderLeafNode(node, depth) {
    if (!node || !node.segments) return '';

    var typeLabel = node.label || node.type || '';
    var cnLabel = getTypeLabelCN(node.type);

    var html = '<div class="fmt-node-kvp" style="border-left-color: #94a3b8;">';
    // 类型标签行
    html += '<div class="fmt-kvp-section-label" style="color:#64748b;"><i class="fa ' + getTypeIcon(node.type) + '"></i> ' + escapeHtml(typeLabel) + ' (' + escapeHtml(cnLabel) + ')</div>';
    // 内容区
    html += '<div class="fmt-kvp-section-body">' + renderSegments(node.segments) + '</div>';
    html += '</div>';

    return html;
}

/**
 * 获取类型对应的 Font Awesome 图标
 */
function getTypeIcon(type) {
    var map = {
        'string': 'fa-font', 'integer': 'fa-hashtag', 'float': 'fa-calculator',
        'boolean': 'fa-toggle-on', 'null': 'fa-ban'
    };
    return map[type] || 'fa-code';
}

/**
 * 将 segments 数组渲染为一行内联色块
 * @param {Array} segments 段数组
 * @returns {string} HTML
 */
function renderSegments(segments) {
    if (!segments || !segments.length) return '';

    var html = '';
    for (var i = 0; i < segments.length; i++) {
        var seg = segments[i];
        var cssClass = seg.css_class || '';
        var title = seg.description ? escapeHtml(seg.description) : '';
        var value = seg.value !== undefined && seg.value !== null ? String(seg.value) : '';

        html += '<span class="fmt-seg ' + cssClass + '" title="' + title + '">' + escapeHtml(value) + '</span>';
    }
    return html;
}

// ==================== 线性视图渲染 ====================

/**
 * 渲染线性分组视图
 * @param {Object} tree 树节点
 * @returns {string} HTML
 */
function renderLinearMode(tree) {
    var groups = [];

    // 收集所有顶层逻辑单元
    collectLinearGroups(tree, groups, 0, 0, null);

    if (!groups.length) return '<p style="color:#94a3b8;">无法解析该序列化字符串</p>';

    var html = '';
    for (var i = 0; i < groups.length; i++) {
        var g = groups[i];
        html += '<div class="fmt-group">';
        html += '<div class="fmt-group-title"><i class="fa fa-' + (g.icon || 'fa-code') + '"></i> ' + escapeHtml(g.title) + '</div>';
        html += '<div class="fmt-group-body">';
        for (var j = 0; j < g.segments.length; j++) {
            var seg = g.segments[j];
            html += '<span class="fmt-seg-inline ' + (seg.css_class || '') + '" title="' + escapeHtml(seg.description || '') + '">';
            html += escapeHtml(String(seg.value || ''));
            if (seg.description) {
                html += '<span class="seg-desc">(' + escapeHtml(seg.description) + ')</span>';
            }
            html += '</span>';
        }
        html += '</div></div>';
    }

    return html;
}

/**
 * 收集线性分组的递归辅助函数
 * @param {Object} node 当前节点
 * @param {Array} groups 组数组（累加）
 * @param {number} depth 深度
 * @param {number} idx 在父容器中的序号（从1开始）
 * @param {string} parentType 父容器类型 'object' | 'array'
 */
function collectLinearGroups(node, groups, depth, idx, parentType) {
    if (!node) return;

    var leafTypes = ['string', 'integer', 'float', 'boolean', 'null'];

    // 构建序号前缀
    var idxPrefix = '';
    if (parentType === 'array') {
        idxPrefix = '元素' + (idx || 0) + ' ';
    } else if (parentType === 'object') {
        idxPrefix = '属性' + (idx || 0) + ' ';
        // 附加可见性标记
        if (node.property_visibility) {
            idxPrefix += '[' + node.property_visibility + '] ';
        }
    }

    if (node.type === 'object') {
        // 对象作为一个大组
        var objGroup = {
            title: 'Object' + (node.class_name ? ': ' + node.class_name : ''),
            icon: 'fa-cube',
            segments: node.header_segments || []
        };
        groups.push(objGroup);

        // 子属性作为子组（传递parentType='object'和序号）
        var children = node.children || [];
        for (var i = 0; i < children.length; i++) {
            collectLinearGroups(children[i], groups, depth + 1, i + 1, 'object');
        }

        // 结束标记
        if (node.footer_segment) {
            groups.push({
                title: '结束标记',
                icon: 'fa-chevron-right',
                segments: [node.footer_segment]
            });
        }
    } else if (node.type === 'array') {
        // 数组作为一个组
        var arrGroup = {
            title: 'Array (' + (node.element_count || 0) + ' 个元素)',
            icon: 'fa-list',
            segments: node.header_segments || []
        };
        groups.push(arrGroup);

        // 子元素（传递parentType='array'和序号）
        var arrChildren = node.children || [];
        for (var j = 0; j < arrChildren.length; j++) {
            collectLinearGroups(arrChildren[j], groups, depth + 1, j + 1, 'array');
        }

        if (node.footer_segment) {
            groups.push({
                title: '结束标记',
                icon: 'fa-chevron-right',
                segments: [node.footer_segment]
            });
        }
    } else if (node.type === 'key_value_pair') {
        // 键值对：key 和 value 分别作为组（带序号前缀）
        if (node.key) {
            if (leafTypes.indexOf(node.key.type) !== -1) {
                groups.push({
                    title: idxPrefix + '键名 (' + getTypeLabelCN(node.key.type) + ')',
                    icon: 'fa-key',
                    segments: node.key.segments || []
                });
            } else {
                collectLinearGroups(node.key, groups, depth + 1, 0, parentType);
            }
        }
        if (node.value) {
            if (leafTypes.indexOf(node.value.type) !== -1) {
                groups.push({
                    title: idxPrefix + '键值 (' + getTypeLabelCN(node.value.type) + ')',
                    icon: 'fa-tag',
                    segments: node.value.segments || []
                });
            } else {
                collectLinearGroups(node.value, groups, depth + 1, 0, parentType);
            }
        }
    } else if (leafTypes.indexOf(node.type) !== -1) {
        // 叶子节点直接作为一组
        groups.push({
            title: (node.label || node.type),
            icon: getTypeIcon(node.type),
            segments: node.segments || []
        });
    }
}

/**
 * 获取类型对应的图标名
 */
function getTypeIcon(type) {
    var map = {
        'string': 'fa-font',
        'integer': 'fa-hashtag',
        'float': 'fa-calculator',
        'boolean': 'fa-toggle-on',
        'null': 'fa-ban'
    };
    return map[type] || 'fa-code';
}

// ==================== 交互功能 ====================

/**
 * 折叠/展开容器节点
 * @param {HTMLElement} headerEl 头部元素
 */
function toggleFmtNode(headerEl) {
    var nodeEl = headerEl.parentElement;
    if (!nodeEl) return;

    nodeEl.classList.toggle('collapsed');
}

/* ==================== POP链练习功能 ==================== */

/**
 * 验证引导式表单配置
 */
function verifyGuidedConfig() {
    var q1 = document.getElementById('guidedQ1');
    var q2 = document.getElementById('guidedQ2');
    var q3 = document.getElementById('guidedQ3');
    var q4 = document.getElementById('guidedQ4');
    if (!q1 || !q2 || !q3 || !q4) return;

    var allCorrect = true;

    // FileLogger的writer应该是HtmlRenderer
    var f1 = document.getElementById('guidedFeedback1');
    if (q1.value === 'HtmlRenderer') {
        f1.className = 'guided-feedback correct';
        f1.textContent = '正确！__destruct()中调用了writer的write()方法';
    } else {
        f1.className = 'guided-feedback wrong';
        f1.textContent = '$writer应该是中间跳板类HtmlRenderer，因为__destruct()中调用了它的write()方法';
        allCorrect = false;
    }

    // HtmlRenderer的engine应该是TemplateExecutor
    var f2 = document.getElementById('guidedFeedback2');
    if (q2.value === 'TemplateExecutor') {
        f2.className = 'guided-feedback correct';
        f2.textContent = '正确！write()中调用了engine的render()方法';
    } else {
        f2.className = 'guided-feedback wrong';
        f2.textContent = '$engine应该是TemplateExecutor，因为write()中调用了它的render()方法';
        allCorrect = false;
    }

    // TemplateExecutor的cacheDir应该是目标路径
    var f3 = document.getElementById('guidedFeedback3');
    if (q3.value.trim() === './uploads') {
        f3.className = 'guided-feedback correct';
        f3.textContent = '正确！这是文件写入的目标路径';
    } else {
        f3.className = 'guided-feedback wrong';
        f3.textContent = '提示：目标路径应该是 ./uploads';
        allCorrect = false;
    }

    // HtmlRenderer的template应该是要写入的内容
    var f4 = document.getElementById('guidedFeedback4');
    if (q4.value.trim().indexOf('HeaSec Test') !== -1) {
        f4.className = 'guided-feedback correct';
        f4.textContent = '正确！这是要写入文件的内容';
    } else {
        f4.className = 'guided-feedback wrong';
        f4.textContent = '提示：内容应包含 "HeaSec Test"';
        allCorrect = false;
    }

    if (allCorrect) {
        showSuccessMessage('配置正确！已自动填充到Payload构造区');
        // 自动填充到步骤2
        document.getElementById('popPayloadInput').value =
            'O:10:"FileLogger":1:{s:6:"writer";O:12:"HtmlRenderer":2:{s:8:"template";s:11:"HeaSec Test";s:6:"engine";O:16:"TemplateExecutor":1:{s:8:"cacheDir";s:9:"./uploads";}}}';
        showSuccessMessage('配置正确，Payload已自动填充');
    }
}

/**
 * 生成POP链Payload
 */
function generatePopPayload() {
    // 直接构造正确的序列化字符串
    var payload = 'O:10:"FileLogger":1:{s:6:"writer";O:12:"HtmlRenderer":2:{s:8:"template";s:11:"HeaSec Test";s:6:"engine";O:16:"TemplateExecutor":1:{s:8:"cacheDir";s:9:"./uploads";}}}';
    var el = document.getElementById('popPayloadInput');
    if (el) {
        el.value = payload;
        showSuccessMessage('Payload已生成');
    }
}

/**
 * 发送POP链Payload执行攻击
 */
function sendPopPayload() {
    var input = document.getElementById('popPayloadInput');
    if (!input || !input.value.trim()) {
        showErrorMessage('请先构造或生成Payload');
        return;
    }

    fetch('./api/pop_chain.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ payload: input.value.trim() })
    })
    .then(function(resp) { return resp.json(); })
    .then(function(data) {
        var resultEl = document.getElementById('popResult');
        var chainEl = document.getElementById('popChainDisplay');
        if (!resultEl || !chainEl) return;

        if (data.success) {
            resultEl.className = 'output-box success';
            resultEl.innerHTML = '<span style="color:#27ae60;font-weight:bold">攻击成功！</span>\n' +
                escapeHtml(data.message) + '\n' +
                escapeHtml(data.result);

            // 显示调用链动画
            if (data.call_chain) {
                showPopChainAnimation(data.call_chain);
            }
        } else {
            resultEl.className = 'output-box error';
            resultEl.innerHTML = '<span style="color:#f56565;font-weight:bold">攻击失败</span>\n' +
                escapeHtml(data.error);
            if (data.hint) {
                resultEl.innerHTML += '\n<span style="color:#e67e22">提示：' + escapeHtml(data.hint) + '</span>';
            }
        }
    })
    .catch(function(err) {
        showErrorMessage('请求失败：' + err.message);
    });
}

/**
 * 显示POP链调用动画
 * @param {Array} callChain 调用链数组
 */
function showPopChainAnimation(callChain) {
    var steps = document.querySelectorAll('.pop-step');
    // 重置所有步骤
    steps.forEach(function(step) {
        step.classList.remove('active', 'completed');
    });

    var delay = 800;
    callChain.forEach(function(chain, index) {
        setTimeout(function() {
            // 标记前面的步骤为已完成
            if (index > 0 && steps[index - 1]) {
                steps[index - 1].classList.add('completed');
                steps[index - 1].classList.remove('active');
            }
            // 激活当前步骤
            if (steps[index]) {
                steps[index].classList.add('active');
            }

            // 最后一步完成后
            if (index === callChain.length - 1) {
                setTimeout(function() {
                    if (steps[index]) {
                        steps[index].classList.add('completed');
                    }
                    showSuccessMessage('POP链执行完成！文件写入成功');
                }, delay);
            }
        }, delay * (index + 1));
    });
}

/**
 * 重置POP链练习环境
 */
function resetPopEnvironment() {
    fetch('./api/pop_chain.php?action=reset')
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
            if (data.success) {
                showSuccessMessage('环境已重置');
                // 重置显示
                var resultEl = document.getElementById('popResult');
                var chainEl = document.getElementById('popChainDisplay');
                var payloadEl = document.getElementById('popPayloadInput');
                if (resultEl) { resultEl.innerHTML = '<span style="color:#718096">等待发送Payload...</span>'; resultEl.className = 'output-box'; }
                if (payloadEl) payloadEl.value = '';
                // 重置调用链动画
                var steps = document.querySelectorAll('.pop-step');
                steps.forEach(function(step) { step.classList.remove('active', 'completed'); });
            }
        })
        .catch(function(err) {
            showErrorMessage('重置失败：' + err.message);
        });
}

/* ==================== 学习完成功能 ==================== */

/**
 * 显示恭喜弹窗
 */
function showMasteryCongrats() {
    if (typeof HeaSecCongratsModal === 'undefined') {
        showSuccessMessage('恭喜你掌握了PHP反序列化漏洞的基本原理和利用方式！');
        return;
    }

    HeaSecCongratsModal.show({
        title: '恭喜你掌握了一个新技能',
        message: '你已掌握PHP反序列化漏洞的基本原理和利用方式',
        buttonText: '继续学习',
        enableNextRangeButton: true,
        rangeCode: 'dsbasic',
        updateLearningStatus: true,
        updateStatusApiUrl: window.heasecConfig ? window.heasecConfig.commonBasePath + 'api/update-learning-status.php' : '',
        nextRangeApiUrl: window.heasecConfig ? window.heasecConfig.commonBasePath + 'api/next-range.php' : ''
    });
}

/**
 * 更新学习状态
 */
function updateLearningStatus() {
    var btn = document.getElementById('masteryBtn');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 提交中...';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', window.heasecConfig ? window.heasecConfig.commonBasePath + 'api/update-learning-status.php' : '', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                showMasteryCongrats();
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-check-circle"></i> 我已掌握';
                showErrorMessage('更新学习状态失败');
            }
        }
    };
    xhr.send(JSON.stringify({
        code: 'dsbasic',
        status: '已掌握',
        timestamp: new Date().toISOString()
    }));
}

/* ==================== 页面初始化 ==================== */
document.addEventListener('DOMContentLoaded', function() {
    // 初始化序列化类型选择器
    var typeSelect = document.getElementById('serializeType');
    if (typeSelect) {
        updateSerializeForm();
    }
});
