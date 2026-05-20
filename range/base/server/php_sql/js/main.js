/**
 * HeaSec天积安全团队 - 服务端脚本基础靶场交互脚本
 * 版本: v1.0.0
 * 创建日期: 2025-12-30
 * 说明: 区域展开/收起、代码懒加载、代码放大、预览刷新、掌握按钮功能
 */

// 已加载代码的区域ID集合
var loadedSections = new Set();

/**
 * 可折叠区域切换（带懒加载）
 * @param {string} sectionId - 区域ID
 */
function toggleSection(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;

    var isExpanding = !section.classList.contains('expanded');

    // 展开区域时加载代码
    if (isExpanding) {
        loadCodeForSection(sectionId);
    }

    section.classList.toggle('expanded');

    // 更新全局布局状态
    updateGlobalLayoutState();
}

/**
 * 更新全局布局状态
 * 当任意区域展开时，所有区域都应变宽
 */
function updateGlobalLayoutState() {
    // 检查是否有任意区域处于展开状态
    var expandedSections = document.querySelectorAll('.collapsible-section.expanded');
    var body = document.body;

    if (expandedSections.length > 0) {
        body.classList.add('has-expanded-section');
    } else {
        body.classList.remove('has-expanded-section');
    }
}

/**
 * 加载指定区域的代码内容
 * @param {string} sectionId - 区域ID
 */
function loadCodeForSection(sectionId) {
    // 如果已经加载过，直接返回
    if (loadedSections.has(sectionId)) {
        return;
    }

    var section = document.getElementById(sectionId);
    if (!section) return;

    var codeContainer = section.querySelector('.code-content');
    if (!codeContainer) return;

    // 从 data 属性获取代码内容
    var codeContent = codeContainer.getAttribute('data-code');
    if (!codeContent) return;

    // 将 \n 转换成实际的换行符
    codeContent = codeContent.replace(/\\n/g, '\n');

    // 创建临时元素来正确处理 HTML 实体
    var tempDiv = document.createElement('div');
    tempDiv.textContent = codeContent;
    var decodedCode = tempDiv.innerHTML;

    // 设置代码内容
    codeContainer.innerHTML = '<pre class="line-numbers"><code class="language-' + codeContainer.getAttribute('data-language') + '">' + decodedCode + '</code></pre>';

    // 触发 Prism.js 高亮
    if (typeof Prism !== 'undefined') {
        Prism.highlightAllUnder(codeContainer);
    }

    // 标记为已加载
    loadedSections.add(sectionId);

    console.log('[HeaSec] 代码已加载: ' + sectionId);
}

/**
 * 代码区域放大/缩小
 * @param {string} sectionId - 区域ID (section3-section6)
 */
function toggleFullscreen(sectionId) {
    var section = document.getElementById(sectionId);
    if (!section) return;

    var codeDisplay = section.querySelector('.code-display');
    if (!codeDisplay) return;

    // 获取放大按钮
    var fullscreenBtn = section.querySelector('.code-display-header button');
    if (!fullscreenBtn) return;

    if (codeDisplay.classList.contains('fullscreen')) {
        // 退出放大模式
        codeDisplay.classList.remove('fullscreen');
        // 恢复按钮为"放大"状态
        fullscreenBtn.innerHTML = '<i class="fa fa-search-plus"></i> 放大';
    } else {
        // 进入放大模式
        codeDisplay.classList.add('fullscreen');
        // 切换按钮为"恢复"状态
        fullscreenBtn.innerHTML = '<i class="fa fa-search-minus"></i> 恢复';
    }
}

/**
 * 刷新预览iframe
 * @param {string} iframeId - iframe元素ID
 */
function refreshPreview(iframeId) {
    var iframe = document.getElementById(iframeId);
    if (iframe) {
        iframe.src = iframe.src;
    }
}

/**
 * 在新窗口打开iframe对应的页面
 * @param {string} iframeId - iframe元素ID
 */
function openPopup(iframeId) {
    var iframe = document.getElementById(iframeId);
    if (iframe && iframe.src) {
        window.open(iframe.src, '_blank', 'width=1200,height=800,scrollbars=yes,resizable=yes');
    }
}

/**
 * 显示掌握恭喜弹窗
 */
function showMasteryCongrats() {
    if (typeof HeaSecCongratsModal !== 'undefined') {
        HeaSecCongratsModal.show({
            title: '🎉 恭喜你掌握了一个新技能',
            message: '你理解了服务端脚本（PHP和SQL）的基本语法和常用操作！',
            enableNextRangeButton: true,
            rangeCode: 'php_sql',
            updateStatusApiUrl: window.heasecConfig.commonBasePath + 'api/update-learning-status.php',
            nextRangeApiUrl: window.heasecConfig.commonBasePath + 'api/next-range.php',
            onContinue: function () {
                updateLearningStatus();
            }
        });
    } else {
        alert('恭喜你掌握了服务端脚本基础！');
    }
}

/**
 * 更新学习状态
 */
function updateLearningStatus() {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', window.heasecConfig.commonBasePath + 'api/update-learning-status.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                console.log('[HeaSec] 学习状态更新成功');
            } else {
                console.error('[HeaSec] 学习状态更新失败');
            }
        }
    };

    var data = JSON.stringify({
        code: 'php_sql',
        status: '已掌握',
        timestamp: Date.now()
    });

    xhr.send(data);

    // 禁用掌握按钮
    var btn = document.getElementById('phpSqlMasteryBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-check-circle"></i> 已掌握';
    }
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function () {
    console.log('[HeaSec] 服务端脚本基础靶场已加载');

    // 检查学习状态
    checkLearningStatus();

    // 初始化布局状态
    updateGlobalLayoutState();
});

/**
 * 检查学习状态
 */
function checkLearningStatus() {
    // 这里可以添加检查本地存储或API的逻辑
    // 如果已经掌握，禁用掌握按钮
    var masteredRanges = localStorage.getItem('heasec_mastered_ranges');
    if (masteredRanges) {
        try {
            var mastered = JSON.parse(masteredRanges);
            if (mastered['php_sql']) {
                var btn = document.getElementById('phpSqlMasteryBtn');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa fa-check-circle"></i> 已掌握';
                }
            }
        } catch (e) {
            console.error('[HeaSec] 解析学习状态失败', e);
        }
    }
}
