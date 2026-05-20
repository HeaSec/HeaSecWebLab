<?php
/**
 * HeaSec天积安全团队 - JavaScript上下文XSS过滤绕过靶场 - 第三关
 * 版本: v1.0.0
 * 创建日期: 2025-01-29
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JS上下文XSS过滤绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'JavaScript上下文XSS过滤绕过靶场 - 第三关';
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

        // 第三关过滤规则：拦截 alert, eval 关键字（大小写不敏感）
        $isBlocked = false;
        if (preg_match('/alert/i', $inputCode) || preg_match('/eval/i', $inputCode)) {
            $isBlocked = true;
            $filteredCode = '';
        } else {
            // 通过关键字验证后，进行其他过滤
            $filteredCode = $inputCode;
            $filteredCode = str_replace('<', '\x3c', $filteredCode);
            $filteredCode = str_replace('>', '\x3e', $filteredCode);
            $filteredCode = str_replace('"', '\\"', $filteredCode); // 转义双引号
            $filteredCode = str_replace("\n", '', $filteredCode); // 过滤换行符
            $filteredCode = str_replace("\r", '', $filteredCode); // 过滤回车符
        }
    }

    // 获取星星数量
    $starCount = HeaSec_SessionManager::getStarCount();

} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
    $starCount = 0;
}

// 引入星星系统组件资源（第三关需要引入恭喜弹窗）
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
        var originalConfirm = window.confirm;
        var originalPrompt = window.prompt;

        var currentLevel = 3;
        var hasPassed = false;

        // 检查三关是否全部完成
        var allLevelsCompleted = <?php
        $allCompleted = HeaSec_SessionManager::isLevelCompleted(1) &&
            HeaSec_SessionManager::isLevelCompleted(2) &&
            HeaSec_SessionManager::isLevelCompleted(3);
        echo $allCompleted ? 'true' : 'false';
        ?>;

        console.log('[HeaSec XSS] 第三关弹窗检测系统已初始化，allLevelsCompleted:', allLevelsCompleted);

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

                            // 显示返回按钮
                            var formActions = document.querySelector('.form-actions');
                            if (formActions) {
                                formActions.innerHTML = '<a href="index.php" class="tech-btn tech-btn-success"><i class="fa fa-arrow-left"></i> 返回第一关</a>';
                            }

                            // 三关全部完成时的庆祝弹窗
                            if (true) {
                                setTimeout(function () {
                                    if (typeof HeaSecCongratsModal !== 'undefined' && HeaSecCongratsModal.show) {
                                        HeaSecCongratsModal.show({
                                            title: '🏆 完美通关！',
                                            message: '恭喜你完成了JavaScript上下文XSS过滤绕过靶场的所有关卡！',
                                            buttonText: '继续学习',
                                            enableNextRangeButton: true,
                                            rangeCode: 'js_context_filter',
                                            updateLearningStatus: true,
                                            nextRangeApiUrl: '<?php echo $commonBasePath; ?>api/next-range.php',
                                            updateStatusApiUrl: '<?php echo $commonBasePath; ?>api/update-learning-status.php',
                                            learningStatus: '已掌握',
                                            fallbackButtonText: '返回首页',
                                            fallbackUrl: '<?php echo $commonBasePath; ?>../index.php',
                                            showParticles: true,
                                            particleCount: 15,
                                            animationDuration: 3000
                                        });
                                    }
                                }, 500);
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

            // 检查URL是否包含提交的代码（说明用户提交了表单并且是在页面加载时触发的）
            // 或者是通过DOM操作触发的
            var isFromUserSubmit = document.referrer !== '' || window.location.search !== '' || document.readyState === 'loading' || document.readyState === 'interactive' || document.readyState === 'complete';
            
            if (hasPassed) {
                return originalAlert.apply(this, arguments);
            }

            // 如果不是通过正常XSS注入触发的（可能是控制台调用，一般控制台调用时readyState是complete且没有在处理表单提交，但为了准确可以用Error stack）
            // 尝试获取调用堆栈
            var stack = '';
            try {
                throw new Error();
            } catch (e) {
                stack = e.stack || '';
            }

            var isConsole = false;
            if (stack.indexOf('level3.php') === -1 && (stack.indexOf('<anonymous>') !== -1 || stack.indexOf('VM') !== -1 || stack.indexOf('eval') !== -1)) {
                isConsole = true;
            }

            if (isConsole) {
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

        // 重写confirm函数 - 不触发通关，提示使用alert
        window.confirm = function (message) {
            console.log('[HeaSec XSS] 拦截到confirm，但本关要求使用alert(heasec)弹窗');
            addPageMessage('本关要求使用 alert(heasec) 弹窗，请尝试其他方式', false);
            return originalConfirm.apply(this, arguments);
        };

        // 重写prompt函数 - 不触发通关，提示使用alert
        window.prompt = function (message, defaultText) {
            console.log('[HeaSec XSS] 拦截到prompt，但本关要求使用alert(heasec)弹窗');
            addPageMessage('本关要求使用 alert(heasec) 弹窗，请尝试其他方式', false);
            return originalPrompt.apply(this, arguments);
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
                        第三关
                    </h3>
                    <p class="level-description">"这次我们完美处理了，绝对安全了。"</p>
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
                        <?php if ($isBlocked): ?>
                            <div class="alert alert-danger" style="margin-top: 15px;">
                                <i class="fa fa-exclamation-triangle"></i>
                                <strong>输入被拦截！</strong> 检测到危险关键字（alert/eval），请尝试其他方式绕过。
                            </div>
                        <?php else: ?>
                            <div class="search-result">
                                <div class="search-result-title">
                                    <i class="fa fa-code"></i>
                                    输出结果：
                                </div>
                                <div id="xss-test-area" style="margin-top: 15px;">
                                    <div id="output"></div>
                                    <script>
                                        // 程序员的错误实现：使用ES6模板字符串，存在模板注入漏洞
                                        // 转义了反引号但没有转义$符号，用户可以通过${...}注入代码
                                        <?php if ($hasInput): ?>
                                            var userInput = `<?php echo $filteredCode; ?>`;
                                            if (document.getElementById('output')) {
                                                document.getElementById('output').innerHTML = '输入内容: ' + userInput;
                                            }
                                        <?php endif; ?>
                                    </script>
                                </div>
                            </div>
                        <?php endif; ?>
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