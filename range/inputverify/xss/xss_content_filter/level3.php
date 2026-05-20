<?php
/**
 * HeaSec天积安全团队 - XSS HTML内容过滤绕过靶场 - 第三关
 * 版本: v1.0.0
 * 创建日期: 2025-12-31
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec XSS过滤绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'XSS HTML内容过滤绕过靶场 - 第三关';
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
    $isBlocked = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['xss_code'])) {
        $hasInput = true;
        $inputCode = trim($_POST['xss_code']);

        // 第三关：编码绕过 - 前后端校验函数和执行函数处理不一致
        // 前端和后端都检测明文 "script" 和 "alert" 关键词，不解码任何编码
        // 常规输入：<script>alert(1)</script> → 被拦截
        // 编码绕过示例：
        //   HTML实体：&#x3c;&#x73;cript&#x3e;&#x61;lert(1)&#x3c;/&#x73;cript&#x3e;
        //   URL编码：%3cscript%3ealert(1)%3c/script%3e （需编码关键字符）
        //   16进制：\x3c, 0x3c, \x003c, 0x003c 等前缀格式
        //   Unicode：\u003cscript\u003ealert(1)\u003c/script\u003e
        // 关键：PHP输出时使用多编码解码函数，浏览器执行解码后的HTML
        $lowerCode = strtolower($inputCode);
        if (strpos($lowerCode, 'script') !== false || strpos($lowerCode, 'alert') !== false) {
            // 检测到script或alert，标记为被拦截
            $isBlocked = true;
            $filteredCode = '';
        } else {
            // 通过验证，输出原始代码（包含编码内容）
            $filteredCode = $inputCode;
        }
    }

    // 获取星星数量
    $starCount = HeaSec_SessionManager::getStarCount();

} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
    $starCount = 0;
}

// 引入星星系统组件资源（包含恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);
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

        var currentLevel = 3;
        var hasPassed = false;

        // 检查三关是否全部完成
        var allLevelsCompleted = <?php
        $allCompleted = HeaSec_SessionManager::isLevelCompleted(1) &&
            HeaSec_SessionManager::isLevelCompleted(2) &&
            HeaSec_SessionManager::isLevelCompleted(3);
        echo $allCompleted ? 'true' : 'false';
        ?>;
        // 事件捕获和追踪变量
        var triggerElement = null;
        var eventCaptured = false;
        var storedResult = { triggerElementInXssTestArea: false };

        // 设置事件捕获函数
        function setupEventCapture() {
            // 捕获可能触发alert的事件（排除mousemove和mouseover/mouseout以减少日志噪音）
            var eventTypes = ['click', 'dblclick', 'mousedown', 'mouseup', 'keydown', 'keyup', 'keypress', 'focus', 'blur', 'submit', 'load', 'DOMContentLoaded'];

            eventTypes.forEach(function (eventType) {
                document.addEventListener(eventType, function (e) {
                    // 保存触发事件的元素
                    triggerElement = e.target;
                    eventCaptured = true;

                    // 检查触发元素或其父元素是否在xss-test-area中
                    checkElementInTestArea(triggerElement);
                }, true); // 使用捕获阶段
            });
        }

        // 检查元素是否在xss-test-area中
        function checkElementInTestArea(element) {
            var testArea = document.getElementById('xss-test-area');
            if (!testArea) {
                storedResult.triggerElementInXssTestArea = false;
                return false;
            }

            // 检查元素或其父元素是否在testArea中
            var currentElement = element;
            while (currentElement) {
                if (currentElement === testArea) {
                    storedResult.triggerElementInXssTestArea = true;
                    return true;
                }
                currentElement = currentElement.parentNode;
            }

            storedResult.triggerElementInXssTestArea = false;
            return false;
        }

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
                            // 更新星星数量
                            if (window.updateStarCount) {
                                window.updateStarCount(response.star_count);
                            }

                            // 显示返回按钮
                            var formActions = document.querySelector('.form-actions');
                            if (formActions) {
                                formActions.innerHTML = '<a href="index.php" class="tech-btn tech-btn-success"><i class="fa fa-arrow-left"></i> 返回第一关</a>';
                            }

                            // 单关通关恭喜弹窗 - 已按要求移除

                            // 三关全部完成时的庆祝弹窗
                            if (true) {
                                setTimeout(function () {
                                    if (typeof HeaSecCongratsModal !== 'undefined' && HeaSecCongratsModal.show) {
                                        HeaSecCongratsModal.show({
                                            title: '🏆 完美通关！',
                                            message: '恭喜你完成了XSS HTML内容过滤绕过靶场的所有关卡！',
                                            buttonText: '继续学习',
                                            enableNextRangeButton: true,
                                            rangeCode: 'xss_content_filter',
                                            updateLearningStatus: true,
                                            nextRangeApiUrl: '<?php echo $commonBasePath; ?>api/next-range.php',
                                            updateStatusApiUrl: '<?php echo $commonBasePath; ?>api/update-learning-status.php',
                                            learningStatus: '已掌握',
                                            fallbackButtonText: '返回首页',
                                            fallbackUrl: '<?php echo $commonBasePath; ?>../index.php',
                                            showParticles: true,
                                            particleCount: 15,
                                            animationDuration: 2000
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
            msgDiv.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-warning');
            msgDiv.style.marginTop = '15px';
            msgDiv.innerHTML = '<div><i class="fa fa-' + (isSuccess ? 'check-circle' : 'exclamation-triangle') + '"></i><strong>' + message + '</strong></div>';

            // 插入到表单后面
            xssForm.parentNode.insertBefore(msgDiv, xssForm.nextSibling);
        }

        // 重写alert函数
        window.alert = function (message) {
            var isScriptTrigger = false;
            var triggerInXssTestArea = false;

            // 检查xss-test-area是否存在
            var testArea = document.getElementById('xss-test-area');
            if (testArea) {
                // 检查xss-test-area内是否有script标签
                var scriptsInTestArea = testArea.getElementsByTagName('script');
                if (scriptsInTestArea.length > 0) {
                    isScriptTrigger = true;
                    triggerInXssTestArea = true;
                }

                // 如果没有找到script标签，检查触发元素是否在xss-test-area中（用于检测非script标签的注入）
                if (!isScriptTrigger && triggerElement) {
                    var currentElement = triggerElement;
                    while (currentElement) {
                        if (currentElement === testArea) {
                            triggerInXssTestArea = true;
                            break;
                        }
                        currentElement = currentElement.parentNode;
                    }
                }
            }

            if (isScriptTrigger && triggerInXssTestArea) {
                // 成功：script标签触发，且在xss-test-area中
                var successMsg = '成功实现了XSS注入攻击！';
                addPageMessage(successMsg, true);
                autoCompleteLevel();
            } else if (!isScriptTrigger && triggerInXssTestArea) {
                // 没有使用script标签（使用了其他事件如onclick/onfocus）
                var noScriptMsg = '⚠️ 检测失败：没有使用script标签实现注入。请使用&lt;script&gt;标签触发alert(heasec)。';
                addPageMessage(noScriptMsg, false);
            } else {
                // 位置不对或没有实现注入（触发元素不在xss-test-area中）
                var wrongPositionMsg = '提示：检测到控制台执行，请在正确的位置输入。';
                addPageMessage(wrongPositionMsg, false);
            }

            // 调用原始弹窗
            return originalAlert.apply(this, arguments);
        };

        // 初始化事件捕获
        setupEventCapture();
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
                        第三关 · 文本清洗官
                    </h3>
                    <p class="level-description">"我们已经过滤了所有危险函数，你还能执行代码吗？"</p>
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
                                <i class="fa fa-search search-icon"></i>
                                <input
                                    type="text"
                                    id="xss_code"
                                    name="xss_code"
                                    class="search-input"
                                    placeholder="输入XSS代码"
                                    required
                                    autocomplete="off"
                                    <?php if (isset($_POST['xss_code'])) echo 'value="' . htmlspecialchars($_POST['xss_code']) . '"'; ?>>
                                <button type="submit" class="search-submit-btn" name="submit">
                                    <i class="fa fa-search"></i>
                                    搜索
                                </button>
                            </div>
                        </div>

                        <!-- 提交按钮和返回按钮 -->
                        <div class="form-actions">
                        </div>
                    </form>

                    <!-- XSS测试区域（输出用户输入） -->
                    <?php if ($hasInput): ?>
                        <div class="search-result">
                            <div class="search-result-title">
                                <i class="fa fa-search"></i>
                                您搜索了：
                            </div>
                            <?php if ($isBlocked): ?>
                                <!-- 被拦截时显示错误提示 -->
                                <div class="alert alert-danger" style="margin-top: 15px;">
                                    <div>
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <strong>检测到非法字符，请重新输入</strong>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- 通过验证时输出代码 -->
                                <!-- 关键：使用多编码解码函数，支持HTML实体/URL/16进制/Unicode编码 -->
                                <!-- 解码后浏览器接收到的就是真正的HTML标签，会执行JavaScript -->
                                <div id="xss-test-area"
                                    style="padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; min-height: 100px;">
                                    <?php
                                    /**
                                     * [HeaSec] 多编码解码函数
                                     * 依次解码：URL编码 → 前缀16进制 → Unicode → HTML实体
                                     * 16进制支持格式：\xNN, \x00NN, 0xNN, 0x00NN
                                     * 这样无论用户使用哪种编码方式，最终都会被解码为原始HTML
                                     */
                                    function heasec_multi_decode($input)
                                    {
                                        $decoded = $input;
                                        
                                        // 1. URL编码解码：%3c → <
                                        $decoded = rawurldecode($decoded);
                                        
                                        // 2. 16进制编码解码（支持多种格式）
                                        //    \x3c, \x003c, \x0003c → < （反斜杠x + 2~4位hex）
                                        //    0x3c, 0x003c, 0x0003c → < （0x前缀 + 2~4位hex）
                                        $decoded = preg_replace_callback('/(?:\\\\x|0x)(0{0,2}[0-9a-fA-F]{2})/', function($matches) {
                                            return chr(hexdec($matches[1]));
                                        }, $decoded);

                                        // 3. Unicode编码解码：\u003c → <
                                        $decoded = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
                                            return mb_convert_encoding('&#x' . $matches[1] . ';', 'UTF-8', 'HTML-ENTITIES');
                                        }, $decoded);

                                        // 4. HTML实体解码：&#x3c; → < 或 &lt; → <
                                        $decoded = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                                        return $decoded;
                                    }

                                    // 使用多编码解码后输出，让浏览器能识别并执行
                                    echo heasec_multi_decode($filteredCode);
                                    ?>
                                </div>
                            <?php endif; ?>
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
                alert('请输入搜索内容');
                xssCodeInput.focus();
                return false;
            }

            // 前端校验：检测明文 "script" 和 "alert" 关键词（不解码任何编码）
            // 使用HTML实体/URL/16进制/Unicode编码均可绕过前端检测
            var lowerCode = code.toLowerCase();
            if (lowerCode.indexOf('script') !== -1 || lowerCode.indexOf('alert') !== -1) {
                e.preventDefault();

                // 在表单后面显示警告提示（与后端拦截样式一致）
                var formActions = xssForm.querySelector('.form-actions');
                var oldAlert = document.getElementById('frontend-alert-message');
                if (oldAlert) {
                    oldAlert.remove();
                }

                var alertDiv = document.createElement('div');
                alertDiv.id = 'frontend-alert-message';
                alertDiv.className = 'alert alert-danger';
                alertDiv.style.marginTop = '15px';
                alertDiv.innerHTML = '<div><i class="fa fa-exclamation-triangle"></i><strong>检测到非法字符，请重新输入</strong></div>';

                if (formActions) {
                    formActions.parentNode.insertBefore(alertDiv, formActions);
                }

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

        // 注意：恭喜弹窗现在由弹窗检测系统触发，不再使用PHP的$triggerCongrats标志
    })();
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>