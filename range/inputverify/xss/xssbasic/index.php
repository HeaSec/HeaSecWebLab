<?php
/**
 * HeaSec天积安全团队 - XSS基础靶场 - 第一关 (反射型XSS)
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec XSS基础靶场 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'XSS基础靶场 - 第一关';
$rangeName = 'XSS基础分类';
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

try {
    // 获取数据库连接
    $db = heasec_db('heasec_inputverify');

    // 初始化会话管理器
    HeaSec_SessionManager::init($db);

    // 处理表单提交
    $hasInput = false;
    $searchResult = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
        $hasInput = true;
        $searchResult = $_POST['search'];
    }

    // 获取星星数量
    $starCount = HeaSec_SessionManager::getStarCount();

} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
    $searchResult = '';
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
                        showNextLevelButton(currentLevel);

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

        if (stack.indexOf('index.php') === -1 && (stack.indexOf('<anonymous>') !== -1 || stack.indexOf('VM') !== -1)) {
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
                        <i class="fa fa-flash"></i>
                        第一关 · 反射型 XSS
                    </h3>
                                    </div>
                <div class="tech-card-body">
                    <!-- 关卡提示 -->
                    <div class="level-tip">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>通关条件：</strong>触发alert弹窗
                    </div>

                    <!-- XSS代码输入表单 -->
                    <form method="POST" action="" class="tech-form" id="xssForm">
                        <div class="search-container">
                            <div class="search-input-wrapper">
                                <textarea
                                    id="search"
                                    name="search"
                                    class="search-input auto-resize-textarea"
                                    placeholder="请输入搜索内容..."
                                    rows="2"
                                    required
                                    autocomplete="off"><?php if (isset($_POST['search']) && $hasInput) echo htmlspecialchars($_POST['search'], ENT_QUOTES); ?></textarea>
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

                    <!-- XSS测试区域（输出结果） -->
                    <?php if ($searchResult !== ''): ?>
                        <div class="search-result">
                            <div class="search-result-title">
                                <i class="fa fa-search"></i>
                                您搜索了：
                            </div>
                            <div class="result-content" id="xss-test-area" style="margin-top: 15px;">
                                <!-- （输出未过滤的代码） -->
                                <?php echo $searchResult; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 学习内容区域 -->
                    <div class="learning-content hidden" id="learning-content-reflected">
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
    var xssCodeInput = document.getElementById('search');

    // 渲染学习内容
    window.renderLearningContent = function() {
        if (typeof XSSLearningContent === 'undefined') return;
        var contentData = XSSLearningContent.reflected;
        var container = document.getElementById('learning-content-reflected');
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

    var isLevelCompleted = <?php echo HeaSec_SessionManager::isLevelCompleted(1) ? 'true' : 'false'; ?>;
    if (isLevelCompleted) {
        window.renderLearningContent();
    }


    // Textarea自适应高度
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
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>