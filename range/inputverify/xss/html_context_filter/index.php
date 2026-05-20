<?php
/**
 * HeaSec天积安全团队 - HTML上下文XSS过滤绕过靶场 - 第一关
 * 版本: v1.0.0
 * 创建日期: 2026-01-14
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec HTML上下文XSS过滤绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'HTML上下文XSS过滤绕过靶场 - 第一关';
$rangeName = 'HTML属性注入';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_inputverify';
$useDatabase = true;

// 引入公共头部
require_once __DIR__ . '/../../../common/includes/header.php';

// 引入公共组件
require_once $commonBasePath . 'includes/database.php';
require_once __DIR__ . '/includes/HeaSec_HTMLContextFilter.php';
require_once __DIR__ . '/includes/HeaSec_SessionManager.php';

// 初始化数据库连接
try {
    $db = heasec_db('heasec_inputverify');

    // 初始化会话管理器
    HeaSec_SessionManager::init($db);

    // 处理表单提交
    $hasInput = false;
    $inputCode = '';
    $filteredCode = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xss_code'])) {
        $hasInput = true;
        $inputCode = trim($_POST['xss_code']);

        // 第一关：应用过滤规则
        $filteredCode = HeaSec_HTMLContextFilter::filterLevel1($inputCode);
    }

    // 获取星星数量
    $starCount = HeaSec_SessionManager::getStarCount();

} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
    $filteredCode = '';
    $starCount = 0;
}

// 引入星星系统组件资源（包含恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="../../../common/css/heasec_range.css">
<!-- 引入站点特定样式 -->
<link rel="stylesheet" href="css/style.css">

<!-- XSS弹窗检测系统 -->
<script>
    (function () {
        'use strict';

        // 保存原始弹窗函数
        var originalAlert = window.alert;
        var currentLevel = 1;
        var hasPassed = false;

        console.log('[HeaSec XSS] 第一关弹窗检测系统已初始化');

        // 自动通关
        function autoCompleteLevel() {
            if (hasPassed) return;
            hasPassed = true;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/complete_level.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            console.log('[HeaSec XSS] 通关成功');

                            // 更新星星数量
                            if (window.updateStarCount) {
                                window.updateStarCount(response.star_count);
                            }

                            // 显示下一关按钮
                            showNextLevelButton(currentLevel);

                            // 单关通关恭喜弹窗 - 已按要求移除
                        }
                    } catch (e) {
                        console.log('[HeaSec XSS] 通关响应解析失败:', e);
                    }
                }
            };
            xhr.send('level=' + currentLevel);
        }

        // 显示下一关按钮
        function showNextLevelButton(level) {
            var formActions = document.querySelector('.form-actions');
            if (formActions) {
                formActions.innerHTML =
                    '<a href="level2.php" class="tech-btn tech-btn-success">' +
                    '<i class="fa fa-arrow-right"></i> 下一关</a>';
            }
        }

        // 添加提示消息到页面
        function addPageMessage(message, isSuccess) {
            var form = document.querySelector('.tech-form');
            if (!form) return;

            // 移除旧的提示消息
            var oldMsg = document.getElementById('xss-detection-message');
            if (oldMsg) {
                oldMsg.remove();
            }

            // 创建新的提示消息
            var msgDiv = document.createElement('div');
            msgDiv.id = 'xss-detection-message';
            msgDiv.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-danger');
            msgDiv.style.marginTop = '15px';
            msgDiv.innerHTML = '<div><i class="fa fa-' + (isSuccess ? 'check-circle' : 'exclamation-triangle') + '"></i><strong>' + message + '</strong></div>';

            // 插入到表单后面
            if (form.parentNode) {
                form.parentNode.insertBefore(msgDiv, form.nextSibling);
            }
        }

        // 重写alert函数
        window.alert = function (message) {
            console.log('[HeaSec XSS] 拦截到alert:', message);

            var isInTestArea = false;
            var isConsole = false;

            // 尝试获取调用堆栈
            var stack = '';
            try {
                throw new Error();
            } catch (e) {
                stack = e.stack || '';
            }

            console.log('[HeaSec XSS] 调用堆栈:', stack);

            // 分析堆栈判断是否为控制台调用
            if (stack.indexOf('index.php') === -1 && (stack.indexOf('<anonymous>') !== -1 || stack.indexOf('VM') !== -1)) {
                isConsole = true;
                console.log('[HeaSec XSS] 检测到控制台直接调用');
            }

            // 检查是否在测试区域内触发（只要不是控制台直接调用即可）
            if (!isConsole) {
                isInTestArea = true;
            }

            // 如果条件不满足，显示错误但仍执行弹窗
            if (!isInTestArea) {
                var errorMsg = '检测到控制台执行，请在正确的位置输入';

                console.log('[HeaSec XSS] ' + errorMsg);
                addPageMessage(errorMsg, false);
                return originalAlert.apply(this, arguments);
            }

            // 成功实现XSS注入
            if (hasPassed) {
                return originalAlert.apply(this, arguments);
            }

            var successMsg = '成功实现了XSS注入攻击！';
            console.log('[HeaSec XSS] ' + successMsg);
            addPageMessage(successMsg, true);
            autoCompleteLevel();
            return originalAlert.apply(this, arguments);
        };

        console.log('[HeaSec XSS] 弹窗拦截函数已设置');
    })();
</script>

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- 关卡区域 -->
    <div class="level-section">
        <div class="tech-container">
            <div class="tech-card">
                <div class="tech-card-header">
                    <h3>
                        <i class="fa fa-code"></i>
                        第一关
                    </h3>
                    <p class="level-description">"做了点转义，嘿嘿"</p>
                </div>
                <div class="tech-card-body">
                    <!-- 关卡提示 -->
                    <div class="level-tip">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>通关条件：</strong>请触发 alert 弹窗
                    </div>

                    <!-- XSS代码输入表单 - 搜索样式 -->
                    <form method="POST" action="" class="tech-form" id="xssForm">
                        <input type="hidden" name="level" value="1">

                        <div class="search-container">
                            <div class="search-input-wrapper">
                                <textarea
                                    id="xss_code"
                                    name="xss_code"
                                    class="search-input auto-resize-textarea"
                                    
                                    rows="2"
                                    required
                                    autocomplete="off"><?php if (isset($_POST['xss_code']) && $hasInput) echo htmlspecialchars($_POST['xss_code']); ?></textarea>
                                <button type="submit" class="search-submit-btn" name="submit">
                                    <i class="fa fa-search"></i>
                                    搜索
                                </button>
                            </div>
                        </div>

                        <!-- 提交按钮和下一关按钮 -->
                        <div class="form-actions">
                        </div>
                    </form>

                    <!-- XSS测试区域（输出过滤后的代码） -->
                    <?php if ($hasInput): ?>
                        <div class="search-result">
                            <div class="search-result-title">
                                <i class="fa fa-search"></i>
                                您搜索了：
                            </div>
                            <div id="xss-test-area" style="margin-top: 15px;">
                                <input type="text" value=<?php echo $filteredCode; ?> readonly>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 引入模态框组件脚本 -->
<script src="js/modal.js?v=<?php echo $version; ?>"></script>

<!-- 前端验证脚本 -->
<script>
    (function () {
        'use strict';

        var xssForm = document.getElementById('xssForm');
        var xssCodeInput = document.getElementById('xss_code');

        if (!xssForm) return;

        // 表单提交验证
        xssForm.addEventListener('submit', function (e) {
            var code = xssCodeInput.value.trim();

            // 空输入检查
            if (!code) {
                e.preventDefault();
                HeaSecModal.showError('输入错误', '请输入搜索内容');
                xssCodeInput.focus();
                return false;
            }

            // 显示提交中状态
            var submitBtn = xssForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                var originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 搜索中...';

                // 3秒后恢复按钮状态
                setTimeout(function () {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 3000);
            }
        });

        // Textarea自适应高度
        if (xssCodeInput && xssCodeInput.tagName === 'TEXTAREA') {
            function autoResize() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 300) + 'px';
            }

            xssCodeInput.addEventListener('input', autoResize);
            xssCodeInput.addEventListener('paste', function () {
                setTimeout(autoResize.bind(this), 0);
            });

            // 页面加载时调整高度
            if (xssCodeInput.value) {
                autoResize.call(xssCodeInput);
            }
        }

        // 输入框获得焦点时高亮
        if (xssCodeInput) {
            xssCodeInput.addEventListener('focus', function () {
                this.closest('.search-input-wrapper').style.borderColor = '#007bff';
                this.closest('.search-input-wrapper').style.background = 'white';
            });

            xssCodeInput.addEventListener('blur', function () {
                this.closest('.search-input-wrapper').style.borderColor = '#dee2e6';
                this.closest('.search-input-wrapper').style.background = '#f8f9fa';
            });
        }
    })();
</script>

<?php
// 引入公共底部
require_once __DIR__ . '/../../../common/includes/footer.php';
?>