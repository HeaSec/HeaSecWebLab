<?php
/**
 * HeaSec天积安全团队 - XSS基础靶场 - 第三关 (DOM型XSS)
 * 版本: v1.0.0
 * 创建日期: 2026-02-25
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec XSS基础靶场 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'XSS基础靶场 - 第三关';
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
    HeaSec_SessionManager::init($db);

    // 获取星星数量
    $starCount = HeaSec_SessionManager::getStarCount();

} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
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
    var currentLevel = 3;
    var hasPassed = false;

    console.log('[HeaSec XSS] 第三关弹窗检测系统已初始化');

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

                        // 显示全通关按钮
                        showNextLevelButton();

                        // 显示知识内容
                        var renderInterval = setInterval(function() {
                            if (typeof window.renderLearningContent === 'function') {
                                clearInterval(renderInterval);
                                window.renderLearningContent();
                            }
                        }, 100);

                        // 靶场全通关恭喜弹窗
                        setTimeout(function() {
                            if (typeof HeaSecCongratsModal !== 'undefined' && HeaSecCongratsModal.show) {
                                HeaSecCongratsModal.show({
                                    title: '🏆 恭喜获得全部成就！',
                                    message: '你已经掌握了所有的基础XSS利用技能，继续保持！',
                                    buttonText: '继续学习',
                                    enableNextRangeButton: true,
                                    rangeCode: 'xssbasic',
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
                '<a href="index.php" class="tech-btn tech-btn-success">' +
                '<i class="fa fa-refresh"></i> 重新开始</a>';
        }
    }

    function addPageMessage(message, isSuccess) {
        var domDemo = document.querySelector('.dom-demo');
        if (!domDemo) return;

        var oldMsg = document.getElementById('xss-detection-message');
        if (oldMsg) {
            oldMsg.remove();
        }

        var msgDiv = document.createElement('div');
        msgDiv.id = 'xss-detection-message';
        msgDiv.className = 'alert ' + (isSuccess ? 'alert-success' : 'alert-danger');
        msgDiv.style.marginBottom = '20px'; // 调整外边距
        msgDiv.innerHTML = '<div><i class="fa fa-' + (isSuccess ? 'check-circle' : 'exclamation-triangle') + '"></i><strong>' + message + '</strong></div>';

        // 插入在 dom-demo 的后面（也就是学习内容和 action 按钮的前方下方）
        if (domDemo.parentNode) {
            domDemo.parentNode.insertBefore(msgDiv, domDemo.nextSibling);
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

        if (stack.indexOf('level3.php') === -1 && (stack.indexOf('<anonymous>') !== -1 || stack.indexOf('VM') !== -1)) {
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

})();
</script>

<!-- 靶场主要内容 -->
<div class="range-container">
    <div class="level-section">
        <div class="tech-container">
            <div class="tech-card">
                <div class="tech-card-header">
                    <h3>
                        <i class="fa fa-code"></i>
                        第三关 · DOM 型 XSS
                    </h3>
                 </div>
                <div class="tech-card-body" id="dom-xss-area">
                    <div class="level-tip">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>通关条件：</strong>触发alert弹窗
                    </div>

                    <div class="operation-guide" style="margin: 20px 0; background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%); border-left: 4px solid #007bff; padding: 15px 20px; border-radius: 6px; display: flex; align-items: flex-start; gap: 12px; box-shadow: 0 2px 8px rgba(0,123,255,0.08);">
                        <i class="fa fa-info-circle" style="color: #007bff; font-size: 20px; margin-top: 2px;"></i>
                        <div>
                            <strong style="color: #0056b3; font-size: 15px; display: block; margin-bottom: 5px;">操作说明：</strong>
                            <span style="color: #495057; font-size: 14px; line-height: 1.5;">请尝试修改当前页面的URL参数 <code style="background: #fff; padding: 2px 6px; border-radius: 4px; color: #d63384; font-family: monospace; border: 1px solid #dee2e6;">?username=HeaSec</code> 来观察下方页面的渲染效果，寻找 DOM XSS 注入点。</span>
                        </div>
                    </div>

                    <div class="dom-demo" style="margin-bottom: 25px;">
                        <div class="welcome-card" style="background: #fff; border-radius: 8px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #e1e4e8; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: #007bff;"></div>
                            <div style="display: flex; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f1f3f5; padding-bottom: 15px;">
                                <div style="width: 40px; height: 40px; background: #e7f3ff; color: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-right: 15px;">
                                    <i class="fa fa-user"></i>
                                </div>
                                <h4 style="margin: 0; color: #343a40; font-size: 18px; font-weight: 600;">欢迎页面</h4>
                            </div>
                            
                            <div id="welcome-content" class="welcome-content-box" style="padding: 20px; background: #f8f9fa; border-radius: 6px; font-size: 16px; color: #495057; word-break: break-all; min-height: 60px; display: flex; align-items: center;">
                                <div style="color: #6c757d; width: 100%;">
                                    <i class="fa fa-spinner fa-spin" style="margin-right: 8px;"></i> 正在加载用户信息...
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top:20px;">
                    </div>

                    <!-- 学习内容区域 -->
                    <div class="learning-content hidden" id="learning-content-dom" style="margin-top:20px;">
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
    // 渲染学习内容
    window.renderLearningContent = function() {
        if (typeof XSSLearningContent === 'undefined') return;
        var contentData = XSSLearningContent.dom;
        var container = document.getElementById('learning-content-dom');
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

    var isLevelCompleted = <?php echo HeaSec_SessionManager::isLevelCompleted(3) ? 'true' : 'false'; ?>;
    if (isLevelCompleted) {
        window.renderLearningContent();
    }

    // DOM XSS漏洞演示代码
    document.addEventListener('DOMContentLoaded', function() {
        var params = new URLSearchParams(window.location.search);
        var username = params.get('username');
        var welcomeContent = document.getElementById('welcome-content');
        
        if (username) {
            // 这里存在DOM XSS漏洞，直接将获取到的参数拼接并插入到DOM中
            // 使用 createContextualFragment 模拟能够执行 <script> 的环境
            welcomeContent.innerHTML = '';
            var htmlString = '<div>欢迎使用，<span style="color: #007bff; font-weight: bold;">' + username + '</span>！</div>';
            var fragment = document.createRange().createContextualFragment(htmlString);
            welcomeContent.appendChild(fragment);
        } else {
            // 没有参数时显示默认提示并提供方便的测试入口
            welcomeContent.innerHTML = '<div style="color: #6c757d; width: 100%;">当前未提供用户名参数，<a href="?username=HeaSec" style="color: #007bff; text-decoration: none; margin-left: 10px;"><i class="fa fa-hand-pointer-o"></i> 快速测试</a></div>';
        }
    });
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
