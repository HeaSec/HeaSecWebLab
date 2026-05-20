<?php
/**
 * HeaSec天积安全团队 - XSS HTML内容过滤绕过靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2025-12-31
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec XSS过滤绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'XSS HTML内容过滤绕过靶场 - 第二关';
$rangeName = '注入Script标签';
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
require_once $commonBasePath . 'includes/header.php';

// 引入公共组件
require_once $commonBasePath . 'includes/database.php';
require_once __DIR__ . '/includes/HeaSec_XSSFilter.php';
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
        // 第二关：替换所有出现的script（使用正则表达式，大小写不敏感）
        // 学生需要使用双写绕过，如 <s<script>cript>alert(heasec)</script>
        // 过滤：<s<script>cript>alert(heasec)</script>
        // 结果：<script>alert(heasec)</script> （中间的<script>被替换成空）
        $filteredCode = preg_replace('/script/i', '', $inputCode);
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
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
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

            // 清空测试区域，移除XSS注入的内容
            var testArea = document.getElementById('xss-test-area');
            if (testArea) {
                testArea.innerHTML = '<textarea></textarea>';
            }

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

            // 尝试获取调用堆栈
            var stack = null;
            try {
                throw new Error();
            } catch (e) {
                stack = e.stack;
            }

            console.log('[HeaSec XSS] 调用堆栈:', stack);

            // 分析堆栈判断是否为控制台调用
            var isConsole = false;
            if (stack) {
                // level2.php在URL中显式出现，所以stack中肯定包含level2.php
                // 如果是页面内调用，level2.php通常会出现至少两次（一次是window.alert定义处，一次是调用处）
                // 如果是控制台调用，通常只出现一次（window.alert定义处）
                var matchCount = (stack.match(/level2\.php/g) || []).length;

                // 辅助判断：检查是否有 console/VM/anonymous 特征
                var hasConsoleSign = (stack.indexOf('<anonymous>') !== -1 || stack.indexOf('VM') !== -1 || stack.indexOf('debugger') !== -1);

                if (matchCount <= 1 || hasConsoleSign) {
                    // 再次确认：如果具有console特征且level2.php出现次数较少（允许偶尔的异常堆栈），或者明确只有一次引用
                    if (matchCount <= 1 && hasConsoleSign) {
                        isConsole = true;
                        console.log('[HeaSec XSS] 检测到控制台直接调用 (Strong Match)');
                    } else if (matchCount <= 1) {
                        isConsole = true;
                        console.log('[HeaSec XSS] 检测到控制台直接调用 (Count Match)');
                    } else if (hasConsoleSign && stack.indexOf('onclick') === -1) {
                        // 如果有console特征，且不是onclick事件（排除按钮直接调用的情况）
                        isConsole = true;
                        console.log('[HeaSec XSS] 检测到控制台直接调用 (Sign Match)');
                    }
                }
            }

            // 检查当前DOM中是否有script标签在xss-test-area内
            var testArea = document.getElementById('xss-test-area');
            var hasExecutableScript = false;

            if (testArea) {
                var scriptsInTestArea = testArea.getElementsByTagName('script');
                console.log('[HeaSec XSS] xss-test-area内script标签数量:', scriptsInTestArea.length);

                // 检查是否有不在textarea内的script标签（可执行的）
                for (var i = 0; i < scriptsInTestArea.length; i++) {
                    var parent = scriptsInTestArea[i].parentElement;
                    var isInTextarea = false;

                    // 向上遍历检查是否在textarea内
                    while (parent && parent !== testArea) {
                        if (parent.tagName === 'TEXTAREA') {
                            isInTextarea = true;
                            break;
                        }
                        parent = parent.parentElement;
                    }

                    if (!isInTextarea) {
                        hasExecutableScript = true;
                        console.log('[HeaSec XSS] 检测到可执行的script标签');
                        break;
                    }
                }
            }

            console.log('[HeaSec XSS] 检测结果 - hasExecutableScript:', hasExecutableScript, 'isConsole:', isConsole);

            // 如果已经通关，测试区域已被清空，任何alert调用都认为是控制台调用
            if (hasPassed) {
                var errorMsg = '检测到控制台执行，请在正确的位置输入';
                console.log('[HeaSec XSS] ' + errorMsg);
                addPageMessage(errorMsg, false);
                return originalAlert.apply(this, arguments);
            }

            // 判断是否通关：存在可执行的script标签且不是控制台调用
            if (!hasExecutableScript || isConsole) {
                // 判断是否为控制台调用，显示相应提示
                var errorMsg = isConsole
                    ? '检测到控制台执行，请在正确的位置输入'
                    : '检测到其他标签，请使用script标签弹窗';

                console.log('[HeaSec XSS] ' + errorMsg);
                addPageMessage(errorMsg, false);
                // 错误情况：仍然执行弹窗
                return originalAlert.apply(this, arguments);
            }

            // 成功实现XSS注入
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
                        第二关 · 过滤大师
                    </h3>
                    <p class="level-description">"这次我们直接删除危险关键词，你还能留下些什么？"</p>
                </div>
                <div class="tech-card-body">
                    <!-- 关卡提示 -->
                    <div class="level-tip">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>通关条件：</strong>请使用script标签触发 alert 弹窗
                    </div>
                    <!-- XSS代码输入表单 - 搜索样式 -->
                    <form method="POST" action="" class="tech-form" id="xssForm">
                        <div class="search-container">
                            <div class="search-input-wrapper">
                                
                                <input
                                    type="text"
                                    id="xss_code"
                                    name="xss_code"
                                    class="search-input"
                                    required
                                    autocomplete="off"
                                    <?php if (isset($_POST['xss_code'])) echo 'value="' . htmlspecialchars($_POST['xss_code']) . '"'; ?>>
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

                    <!-- XSS测试区域（输出过滤后的用户输入） -->
                    <?php if ($hasInput): ?>
                        <div class="search-result">
                            <div class="search-result-title">
                                <i class="fa fa-search"></i>
                                您搜索了：
                            </div>

                            <div id="xss-test-area" style="margin-top: 15px;">
                                <textarea><?php echo $filteredCode; ?></textarea>
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
require_once $commonBasePath . 'includes/footer.php';
?>