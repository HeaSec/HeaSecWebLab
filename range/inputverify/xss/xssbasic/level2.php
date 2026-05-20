<?php
/**
 * HeaSec天积安全团队 - XSS基础靶场 - 第二关 (存储型XSS)
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec XSS基础靶场 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'XSS基础靶场 - 第二关';
$rangeName = 'XSS基础分类';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = __DIR__ . '/../database/init_database.sql';
$databaseName = 'heasec_inputverify';
$useDatabase = true;

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入公共组件
require_once $commonBasePath . 'includes/database.php';
require_once __DIR__ . '/includes/HeaSec_SessionManager.php';

try {
    // 获取数据库连接
    $db = heasec_db('heasec_inputverify');
    HeaSec_SessionManager::init($db);

    // 处理存储型XSS提交
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
        // 检查是否在重置后5秒内，防止竞态条件
        $recentReset = false;
        if (isset($_SESSION['heasec_last_reset_time'])) {
            $timeDiff = time() - $_SESSION['heasec_last_reset_time'];
            if ($timeDiff < 5) {
                $recentReset = true;
            }
        }

        if (!$recentReset) {
            $stmt = $db->prepare("INSERT INTO heasec_xssbasic_messages (content) VALUES (?)");
            $stmt->execute([$_POST['message']]);
            
            // 为了防止刷新时重新提交，重定向回当前页面
            header("Location: level2.php");
            exit;
        }
    }

    // 获取留言列表
    $stmt = $db->query("SELECT content, created_at FROM heasec_xssbasic_messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取星星数量
    $starCount = HeaSec_SessionManager::getStarCount();

} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
    $messages = [];
    $starCount = 0;
}

// 引入星星系统组件资源（包含恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['css' => true, 'js' => true, 'congrats' => true]);
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css?v=<?php echo $version; ?>">

<!-- XSS弹窗检测系统 -->
<script>
(function() {
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
        xhr.onreadystatechange = function() {
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
                        showNextLevelButton();

                        // 显示知识内容
                        var renderInterval = setInterval(function() {
                            if (typeof window.renderLearningContent === 'function') {
                                clearInterval(renderInterval);
                                window.renderLearningContent();
                            }
                        }, 100);


                    }
                } catch (e) {
                    console.log('[HeaSec XSS] 通关响应解析失败:', e);
                }
            }
        };
        xhr.send('level=' + currentLevel);
    }

    function showNextLevelButton() {
        var formActions = document.querySelector('.form-actions');
        if (formActions) {
            formActions.innerHTML =
                '<a href="level3.php" class="tech-btn tech-btn-success">' +
                '<i class="fa fa-arrow-right"></i> 下一关</a>';
        }
    }

    function addPageMessage(message, isSuccess) {
        var form = document.querySelector('.tech-form');
        if (!form) return;

        var oldMsg = document.getElementById('xss-detection-message');
        if (oldMsg) {
            oldMsg.remove();
        }

        var msgDiv = document.createElement('div');
        msgDiv.id = 'xss-detection-message';
        msgDiv.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-danger');
        msgDiv.style.marginTop = '15px';
        msgDiv.innerHTML = '<div><i class="fa fa-' + (isSuccess ? 'check-circle' : 'exclamation-triangle') + '"></i><strong>' + message + '</strong></div>';

        if (form.parentNode) {
            form.parentNode.insertBefore(msgDiv, form.nextSibling);
        }
    }

    // 重写alert函数
    window.alert = function(message) {
        console.log('[HeaSec XSS] 拦截到alert:', message);

        var isInTestArea = false;
        var isConsole = false;

        var stack = '';
        try {
            throw new Error();
        } catch (e) {
            stack = e.stack || '';
        }

        if (stack.indexOf('level2.php') === -1 && (stack.indexOf('<anonymous>') !== -1 || stack.indexOf('VM') !== -1)) {
            isConsole = true;
        }

        if (!isConsole) {
            isInTestArea = true;
        }

        if (!isInTestArea) {
            var errorMsg = '检测到控制台执行，请在正确的位置输入';
            console.log('[HeaSec XSS] ' + errorMsg);
            addPageMessage(errorMsg, false);
            return originalAlert.apply(this, arguments);
        }

        if (hasPassed) {
            return originalAlert.apply(this, arguments);
        }

        var successMsg = '成功实现了XSS注入攻击！';
        console.log('[HeaSec XSS] ' + successMsg);
        addPageMessage(successMsg, true);
        autoCompleteLevel();
        return originalAlert.apply(this, arguments);
    };

    // 清空留言板
    window.clearMessages = function() {
        if (!confirm('确定要清空所有留言吗？')) {
            return;
        }
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'api/clear_messages.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('留言板已清空');
                            location.reload();
                        } else {
                            alert(response.message || '清空失败');
                        }
                    } catch (e) {
                        alert('服务器返回异常');
                    }
                } else {
                    alert('网络或服务器错误');
                }
            }
        };
        xhr.send();
    };

})();
</script>

<!-- 靶场主要内容 -->
<div class="range-container">
    <div class="level-section">
        <div class="tech-container">
            <div class="tech-card">
                <div class="tech-card-header">
                    <h3>
                        <i class="fa fa-database"></i>
                        第二关 · 存储型 XSS
                    </h3>
                                    </div>
                <div class="tech-card-body">
                    <div class="level-tip">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>通关条件：</strong>触发alert弹窗
                    </div>

                    <form method="POST" action="" class="tech-form" id="xssForm">
                        <div class="search-container">
                            <div class="search-input-wrapper">
                                <textarea
                                    id="message"
                                    name="message"
                                    class="search-input auto-resize-textarea"
                                    placeholder="请输入留言内容..."
                                    rows="3"
                                    required
                                    autocomplete="off"></textarea>
                                <button type="submit" class="search-submit-btn" name="submit">
                                    <i class="fa fa-comment"></i>
                                    提交留言
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                        </div>
                    </form>

                    <div class="message-list" style="margin-top:20px;">
                        <div class="message-list-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:15px;">
                            <h4>留言列表：</h4>
                            <?php if (!empty($messages)): ?>
                                <button type="button" class="btn btn-danger btn-sm" onclick="clearMessages()">
                                    <i class="fa fa-trash"></i>
                                    清空留言板
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($messages)): ?>
                            <p class="no-messages" style="color:#999; text-align:center; padding:20px 0;">暂无留言，快来抢沙发吧！</p>
                        <?php else: ?>
                            <?php foreach ($messages as $message): ?>
                                <div class="message-item" style="background:#f8f9fa; padding:15px; border-radius:5px; margin-bottom:15px;">
                                    <div class="message-content" style="word-break:break-all;">
                                        <?php echo $message['content']; ?>
                                    </div>
                                    <div class="message-time" style="color:#999; font-size:12px; margin-top:10px; text-align:right;">
                                        <?php echo date('Y-m-d H:i:s', strtotime($message['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- 学习内容区域 -->
                    <div class="learning-content hidden" id="learning-content-stored" style="margin-top:20px;">
                        <div class="learning-content-header">
                            <h4>
                                <i class="fa fa-book"></i>
                                学习内容
                                <span class="learning-badge">已解锁</span>
                            </h4>
                        </div>
                        <div class="learning-content-body">
                            <!-- 内容将通过JavaScript动态加载 -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/learning-content.js?v=<?php echo $version; ?>"></script>
<script>
(function() {
    'use strict';
    var xssCodeInput = document.getElementById('message');

    // 渲染学习内容
    window.renderLearningContent = function() {
        if (typeof XSSLearningContent === 'undefined') return;
        var contentData = XSSLearningContent.stored;
        var container = document.getElementById('learning-content-stored');
        var bodyContainer = container.querySelector('.learning-content-body');
        
        bodyContainer.innerHTML = '';
        contentData.sections.forEach(function(section, index) {
            var sectionDiv = document.createElement('div');
            sectionDiv.className = 'learning-section';
            sectionDiv.style.animationDelay = (index * 0.1) + 's';
            sectionDiv.innerHTML = '<h5 class="learning-section-heading"><i class="fa fa-circle"></i> ' + section.heading + '</h5>' +
                                   '<div class="learning-section-content">' + section.content + '</div>';
            bodyContainer.appendChild(sectionDiv);
        });
        container.classList.remove('hidden');
    };

    var isLevelCompleted = <?php echo HeaSec_SessionManager::isLevelCompleted(2) ? 'true' : 'false'; ?>;
    if (isLevelCompleted) {
        window.renderLearningContent();
    }

    if (xssCodeInput && xssCodeInput.tagName === 'TEXTAREA') {
        function autoResize() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 300) + 'px';
        }
        xssCodeInput.addEventListener('input', autoResize);
        xssCodeInput.addEventListener('paste', function() {
            setTimeout(autoResize.bind(this), 0);
        });
        if (xssCodeInput.value) {
            autoResize.call(xssCodeInput);
        }
    }

    if (xssCodeInput) {
        xssCodeInput.addEventListener('focus', function() {
            this.closest('.search-input-wrapper').style.borderColor = '#007bff';
            this.closest('.search-input-wrapper').style.background = 'white';
        });

        xssCodeInput.addEventListener('blur', function() {
            this.closest('.search-input-wrapper').style.borderColor = '#dee2e6';
            this.closest('.search-input-wrapper').style.background = '#f8f9fa';
        });
    }
})();
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
