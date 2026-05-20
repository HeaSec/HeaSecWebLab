<?php
/**
 * HeaSec天积安全团队 - HTML上下文XSS过滤绕过靶场 - 第三关
 * 版本: v1.2.0
 * 创建日期: 2026-01-14
 * 更新日期: 2026-01-22
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 说明: Referer注入反射型XSS - 用户点击来源链接可跳转
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec HTML上下文XSS过滤绕过 Range v1.2.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'HTML上下文XSS过滤绕过靶场 - 第三关';
$rangeName = 'HTML属性注入';
$showVersion = false;
$showResetButton = true;
$version = 'v1.2.0';

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

// 获取 Referer
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '(无来源)';

// 访问日志变量
$accessLogs = [];

// 初始化数据库连接
try {
    $db = heasec_db('heasec_inputverify');

    // 初始化会话管理器
    HeaSec_SessionManager::init($db);

    // 获取星星数量
    $starCount = HeaSec_SessionManager::getStarCount();

    // 记录访问日志
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $requestPage = '/html_context_filter/level3.php';
    $sessionId = session_id();

    // 插入日志
    $stmt = $db->prepare("INSERT INTO heasec_html_context_filter_access_logs (session_id, ip_address, request_page, referer) VALUES (?, ?, ?, ?)");
    $stmt->execute([$sessionId, $ipAddress, $requestPage, $referer]);

    // 获取最近的10条访问记录 (按IP查询以支持跨会话查看)
    $stmt = $db->prepare("SELECT * FROM heasec_html_context_filter_access_logs WHERE ip_address = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$ipAddress]);
    $accessLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
    $starCount = 0;
    
    // 数据库异常时的兼容处理：使用当前请求的数据构造一条假日志
    $accessLogs = [
        [
            'created_at' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'request_page' => '/html_context_filter/level3.php',
            'referer' => $referer
        ]
    ];
}

// 应用第三关过滤规则(兼容遗留变量)
$filteredReferer = HeaSec_HTMLContextFilter::applyFilter(3, $referer);

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

        // 设置事件捕获函数
        function setupEventCapture() {
            var eventTypes = ['click', 'dblclick', 'mousedown', 'mouseup', 'keydown', 'keyup', 'keypress', 'focus', 'blur'];

            eventTypes.forEach(function (eventType) {
                document.addEventListener(eventType, function (e) {
                    triggerElement = e.target;
                    eventCaptured = true;
                }, true);
            });
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

                            // 三关全部完成时的额外庆祝弹窗（应用户要求，第三关通关后直接弹出）
                            if (true) {
                                setTimeout(function () {
                                    if (typeof HeaSecCongratsModal !== 'undefined' && HeaSecCongratsModal.show) {
                                        HeaSecCongratsModal.show({
                                            title: '🏆 完美通关！',
                                            message: '恭喜你完成了HTML上下文XSS过滤绕过靶场的所有关卡！',
                                            buttonText: '继续学习',
                                            enableNextRangeButton: true,
                                            rangeCode: 'html_context_filter',
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
            var refererSection = document.querySelector('.referer-display-section');
            if (!refererSection) return;

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

            // 插入到 Referer 显示区域后面
            if (refererSection.parentNode) {
                refererSection.parentNode.insertBefore(msgDiv, refererSection.nextSibling);
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
            // 控制台调用的特征：stack中不包含level3.php，但包含<anonymous>或VM
            if (stack.indexOf('level3.php') === -1 && (stack.indexOf('<anonymous>') !== -1 || stack.indexOf('VM') !== -1 || stack.indexOf('eval') !== -1)) {
                isConsole = true;
                console.log('[HeaSec XSS] 检测到控制台直接调用');
            }

            // 检查触发元素是否在 Referer 链接上
            var triggerInRefererLink = false;
            if (triggerElement && eventCaptured) {
                var currentElement = triggerElement;
                while (currentElement) {
                    if (currentElement.id === 'xss-trigger-area' || (currentElement.classList && (currentElement.classList.contains('referer-link') || currentElement.classList.contains('view-source-link')))) {
                        triggerInRefererLink = true;
                        break;
                    }
                    currentElement = currentElement.parentNode;
                }
            }

            // 判断是否在测试区域内触发
            // 只要不是控制台调用，且是在 Referer 链接上触发的，就认为是在测试区域内
            if (!isConsole && triggerInRefererLink) {
                isInTestArea = true;
            }

            // 控制台输入检测
            if (isConsole) {
                var errorMsg = '⚠️ 检测到控制台输入，请通过点击页面链接触发弹窗';
                console.log('[HeaSec XSS] ' + errorMsg);
                addPageMessage(errorMsg, false);
                return originalAlert.call(window, message);
            }

            // 位置不对（不是在 Referer 链接上触发）
            if (!isInTestArea) {
                var wrongPositionMsg = '⚠️ 提示：alert被触发，但不是通过Referer链接实现的。请确保你的XSS代码被注入到Referer链接的href属性中。';
                console.log('[HeaSec XSS] ' + wrongPositionMsg);
                addPageMessage(wrongPositionMsg, false);
                return originalAlert.call(window, message);
            }

            // 成功实现XSS注入
            if (hasPassed) {
                return originalAlert.call(window, message);
            }

            var successMsg = '成功实现了XSS注入攻击！';
            console.log('[HeaSec XSS] ' + successMsg);
            addPageMessage(successMsg, true);
            autoCompleteLevel();
            return originalAlert.call(window, message);
        };

        // 初始化事件捕获
        setupEventCapture();

        console.log('[HeaSec XSS] 第三关弹窗检测系统已初始化');
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
                    <p class="level-description">"日志记录还能有XSS吗，反正全部都转义了，这代码肯定没漏洞"</p>
                </div>
                <div class="tech-card-body">
                    <!-- 关卡提示 -->
                    <div class="level-tip">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>通关条件：</strong>请点击下方日志记录中的”查看来源页面”触发 alert 弹窗
                    </div>

                    <!-- Referer 显示区域 - XSS注入点 -->
                    <div class="referer-display-section system-log-section">
                        <h4 class="system-log-title">
                            <i class="fa fa-list-alt"></i> 系统访问日志
                            <small style="font-size: 0.6em; color: #858796; margin-left: 10px; font-weight: normal;">(只显示最新的10条记录)</small>
                        </h4>
                        <div class="table-responsive">
                            <table class="system-log-table">
                                <thead>
                                    <tr>
                                        <th>访问时间</th>
                                        <th>来源 IP</th>
                                        <th>请求页面</th>
                                        <th>Referer</th>
                                        <th>操作</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($accessLogs)): ?>
                                    <?php foreach ($accessLogs as $index => $log): ?>
                                        <?php 
                                            // 应用过滤规则
                                            $filteredLogReferer = HeaSec_HTMLContextFilter::applyFilter(3, $log['referer']);
                                            // 仅给最新的（第一个）注入点加上ID，方便脚本检测
                                            $triggerId = ($index === 0) ? 'id="xss-trigger-area"' : '';
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                            <td><?php echo htmlspecialchars($log['request_page']); ?></td>
                                            <td>
                                                <span class="referer-text" title="<?php echo htmlspecialchars($log['referer']); ?>">
                                                    <?php echo htmlspecialchars($log['referer']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <!-- 注入点：在 href 属性中输出过滤后的 Referer -->
                                                <a href="<?php echo $filteredLogReferer; ?>"
                                                   class="view-source-link"
                                                   <?php echo $triggerId; ?>
                                                   title="点击查看来源页面">
                                                    <i class="fa fa-external-link"></i> 源页面
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">暂无访问日志</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 提交按钮和返回第一关按钮 -->
                    <div class="form-actions">
                    </div>
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

<!-- 第三关专用样式 -->
<style>
    /* 修复提示消息被遮挡的问题 */
    .tech-card {
        overflow: visible !important;
    }

    /* 仅针对第三关放宽外部容器限制，使表格能展开更多 */
    @media (min-width: 1024px) {
        .range-container {
            max-width: 1400px !important;
        }
        .level-section {
            max-width: 1200px !important;
        }
    }

.system-log-section {
    background: #fff;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 20px;
    margin: 25px 0;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
}

.system-log-title {
    margin-top: 0;
    margin-bottom: 20px;
    color: #4e73df;
    font-size: 1.1rem;
    font-weight: 600;
    border-bottom: 2px solid #f8f9fc;
    padding-bottom: 10px;
}

.table-responsive {
    width: 100%;
    overflow: hidden; /* 防止出现水平滚动条 */
}

.system-log-table {
    width: 100%;
    margin-bottom: 1rem;
    color: #858796;
    border-collapse: collapse;
    table-layout: fixed; /* 采用固定布局强制约束列宽 */
}

/* 设定固定列的推荐宽度 */
.system-log-table th:nth-child(1) { width: 95px; } /* 访问时间允许折行显示 */
.system-log-table th:nth-child(2) { width: 110px; } /* 来源IP */
.system-log-table th:nth-child(5) { width: 85px; text-align: center; } /* 操作按钮宽度调窄，适应两行每行三字 */

.system-log-table th,
.system-log-table td {
    padding: 0.6rem 0.4rem;
    vertical-align: middle;
    border-top: 1px solid #e3e6f0;
    word-break: break-all; /* 强制所有长文本（如URL）均可换行 */
}

.system-log-table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #e3e6f0;
    background-color: #f8f9fc;
    color: #4e73df;
    font-weight: 600;
}

.system-log-table tbody tr:hover {
    background-color: #eaecf4;
}

.referer-text {
    display: inline-block;
    width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
}

.view-source-link {
    display: flex;
    flex-wrap: wrap; /* 允许折行 */
    justify-content: center;
    align-items: center;
    padding: 0.35rem 0.2rem; /* 调整内边距 */
    font-size: 0.875rem;
    line-height: 1.4;
    border-radius: 0.2rem;
    background-color: #4e73df;
    color: #fff;
    text-decoration: none;
    transition: background-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    width: 58px; /* 3个14px汉字约为42px宽，加上两边padding保证第三个字后面必然折行 */
    white-space: normal !important; /* 允许换行 */
    word-wrap: break-word; /* 允许在单词内换行 */
    word-break: normal; /* 对中文字符更友好的换行 */
    text-align: center;
    margin: 0 auto;
}

.view-source-link i {
    width: 100%;
    margin-right: 0;
    margin-bottom: 4px;
    font-size: 1rem;
}

.view-source-link:hover {
    color: #fff;
    background-color: #2e59d9;
    border-color: #2653d4;
    text-decoration: none;
}
</style>

<?php
// 引入公共底部
require_once __DIR__ . '/../../../common/includes/footer.php';
?>