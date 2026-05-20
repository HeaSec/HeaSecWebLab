<?php
/**
 * HeaSec天积安全团队 - JavaScript上下文XSS过滤绕过靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2025-01-29
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JS上下文XSS过滤绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'JavaScript上下文XSS过滤绕过靶场 - 第二关';
$rangeName = 'JS上下文逃逸';
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

        // 转义 <, > 并过滤 ;() // /* */
        $filteredCode = $inputCode;
        $filteredCode = str_replace('<', '\x3c', $filteredCode);
        $filteredCode = str_replace('>', '\x3e', $filteredCode);

        // 过滤分号和括号
        $filteredCode = str_replace(';', '', $filteredCode);
        $filteredCode = str_replace('(', '', $filteredCode);
        $filteredCode = str_replace(')', '', $filteredCode);

        // 过滤注释符号
        $filteredCode = str_replace('//', '', $filteredCode);
        $filteredCode = str_replace('/*', '', $filteredCode);
        $filteredCode = str_replace('*/', '', $filteredCode);
    }

    // 获取星星数量
    $starCount = HeaSec_SessionManager::getStarCount();

} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
    $starCount = 0;
}

// 引入星星系统组件资源
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['js' => false]);
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

        var currentLevel = 2;
        var hasPassed = false;

        console.log('[HeaSec XSS] 第二关弹窗检测系统已初始化');

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
                            var formActions = document.querySelector('.form-actions');
                            if (formActions) {
                                formActions.innerHTML = '<a href="level3.php" class="tech-btn tech-btn-success"><i class="fa fa-arrow-right"></i> 下一关</a>';
                            }
                        }
                    } catch (e) {
                        console.log('[HeaSec XSS] 通关响应解析失败:', e);
                    }
                }
            };
            xhr.send('level=' + currentLevel);
        }

        // 添加提示消息到页面
        function addPageMessage(message, isSuccess) {
            var xssForm = document.getElementById('xssForm');
            if (!xssForm) return;

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
            xssForm.parentNode.insertBefore(msgDiv, xssForm.nextSibling);
        }

        // 重写alert函数
        window.alert = function (message) {
            console.log('[HeaSec XSS] 拦截到alert:', message);

            // 检查是否存在输出元素（说明用户提交了表单）
            var outputElement = document.getElementById('output');
            var hasOutputElement = !!outputElement;
            console.log('[HeaSec XSS] outputElement exists:', hasOutputElement);

            if (hasPassed) {
                return originalAlert.apply(this, arguments);
            }

            // 如果不存在输出元素，说明不是通过正常XSS注入触发的（可能是控制台调用）
            if (!hasOutputElement) {
                var errorMsg = '检测到控制台执行，请在正确的位置输入';
                console.log('[HeaSec XSS] ' + errorMsg);
                addPageMessage(errorMsg, false);
                return originalAlert.apply(this, arguments);
            }

            // 如果存在输出元素且alert被触发，说明XSS成功
            var successMsg = '成功实现了XSS注入攻击！';
            console.log('[HeaSec XSS] ' + successMsg);
            addPageMessage(successMsg, true);
            autoCompleteLevel();

            // 调用原始弹窗
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
                        第二关
                    </h3>
                    <p class="level-description">"这次我们过滤了很多危险字符，你连基本的语法都写不出来。"</p>
                </div>
                <div class="tech-card-body">
                    <!-- 关卡提示 -->
                    <div class="level-tip">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>通关条件：</strong>请使用alert函数进行弹窗
                    </div>
                    <!-- XSS代码输入表单 - 搜索样式 -->
                    <form method="POST" action="" class="tech-form" id="xssForm">
                        <div class="search-container">
                            <div class="search-input-wrapper">

                                <textarea
                                    id="xss_code"
                                    name="xss_code"
                                    class="search-input auto-resize-textarea"
                                    
                                    rows="2"
                                    required
                                    autocomplete="off"><?php if (isset($_POST['xss_code'])) echo htmlspecialchars($_POST['xss_code']); ?></textarea>
                                <button type="submit" class="search-submit-btn" name="submit">
                                    <i class="fa fa-search"></i>
                                    提交
                                </button>
                            </div>
                        </div>

                        <!-- 提交按钮和下一关按钮 -->
                        <div class="form-actions">
                        </div>
                    </form>

                    <!-- XSS测试区域（JavaScript上下文输出） -->
                    <?php if ($hasInput): ?>
                        <div class="search-result">
                            <div class="search-result-title">
                                <i class="fa fa-code"></i>
                                输出结果：
                            </div>
                            <div id="xss-test-area" style="margin-top: 15px;">
                                <div id="output"></div>
                                <script>



                                    <?php if ($hasInput): ?>
                                        var userInput = "<?php echo $filteredCode; ?>";
                                        if (document.getElementById('output')) {
                                            document.getElementById('output').innerHTML = '输入内容: ' + userInput;
                                        }
                                    <?php endif; ?>
                                </script>
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
                    HeaSecModal.showError('输入错误', '请输入XSS代码');
                    xssCodeInput.focus();
                    return false;
                }

                // 显示提交中状态
                var submitBtn = xssForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    var originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 提交中...';

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