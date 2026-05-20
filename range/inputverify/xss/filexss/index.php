<?php
/**
 * HeaSec天积安全团队 - 文件相关XSS靶场 - 第一关（SVG文件XSS）
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件相关XSS Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件相关XSS靶场 - 第一关';
$rangeName = '文件相关XSS';
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

    // 处理文件上传
    $hasUpload = false;
    $uploadSuccess = false;
    $uploadMessage = '';
    $svgContent = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['svg_file'])) {
        $hasUpload = true;

        // 调用上传API进行验证
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/api/upload.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'file' => new CURLFile($_FILES['svg_file']['tmp_name'], $_FILES['svg_file']['type'], $_FILES['svg_file']['name']),
            'type' => 'svg'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($result && $result['success']) {
            $uploadSuccess = true;
            $uploadMessage = $result['message'] ?? '文件上传成功';
            $svgContent = $result['content'] ?? '';
        } else {
            $uploadSuccess = false;
            $uploadMessage = $result['message'] ?? '文件上传失败';
        }
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

        var currentLevel = 1;
        var hasPassed = false;

        console.log('[HeaSec FileXSS] 第一关弹窗检测系统已初始化');

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
                            console.log('[HeaSec FileXSS] 通关成功');

                            // 更新星星数量
                            if (window.updateStarCount) {
                                window.updateStarCount(response.star_count);
                            }

                            // 显示下一关按钮
                            var formActions = document.querySelector('.form-actions');
                            if (formActions) {
                                formActions.innerHTML = '<a href="level2.php" class="tech-btn tech-btn-success"><i class="fa fa-arrow-right"></i> 下一关</a>';
                            }
                        }
                    } catch (e) {
                        console.log('[HeaSec FileXSS] 通关响应解析失败:', e);
                    }
                }
            };
            xhr.send('level=' + currentLevel);
        }

        // 添加提示消息到页面
        function addPageMessage(message, isSuccess) {
            var uploadForm = document.getElementById('uploadForm');
            if (!uploadForm) return;

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
            uploadForm.parentNode.insertBefore(msgDiv, uploadForm.nextSibling);
        }

        // 重写alert函数
        window.alert = function (message) {
            console.log('[HeaSec FileXSS] 拦截到alert:', message);

            var isConsole = false;
            try {
                throw new Error('stack trace');
            } catch (e) {
                var stack = e.stack || '';
                if (stack.indexOf('index.php') === -1 &&
                    (stack.indexOf('<anonymous>') !== -1 ||
                     stack.indexOf('VM') !== -1 ||
                     stack.indexOf('debugger') !== -1)) {
                    isConsole = true;
                }
            }

            if (isConsole) {
                var warningMsg = '检测到可能通过控制台作弊，请通过上传包含漏洞的SVG文件通关。';
                console.warn('[HeaSec FileXSS] ' + warningMsg);
                addPageMessage(warningMsg, false);
                return originalAlert.apply(this, arguments);
            }

            var successMsg = '成功实现了XSS注入攻击！';
            console.log('[HeaSec FileXSS] ' + successMsg);

            if (!hasPassed) {
                addPageMessage(successMsg, true);
                autoCompleteLevel();
            }

            // 调用原始弹窗
            return originalAlert.apply(this, arguments);
        };

        console.log('[HeaSec FileXSS] 弹窗拦截函数已设置');
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
                        <i class="fa fa-file-image-o"></i>
                        第一关 · 图形审核员
                    </h3>
                    <p class="level-description">"SVG矢量图形上传功能已上线，系统会检查文件安全性。"</p>
                </div>
                <div class="tech-card-body">
                    <!-- 关卡提示 -->
                    <div class="level-tip">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>通关条件：</strong>触发alert弹窗
                    </div>

                    <!-- 文件上传表单 -->
                    <form method="POST" action="" enctype="multipart/form-data" class="tech-form" id="uploadForm">
                        <div class="form-group">
                            <label class="form-label">选择SVG文件</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="svg_file" name="svg_file" accept=".svg" required>
                                <div class="file-upload-info">
                                    <i class="fa fa-info-circle"></i>
                                    仅支持 .svg 文件，最大 1MB
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="tech-btn tech-btn-primary">
                                <i class="fa fa-upload"></i> 上传并预览
                            </button>
                        </div>
                    </form>

                    <!-- 上传结果区域 -->
                    <?php if ($hasUpload): ?>
                        <div class="search-result">
                            <div class="search-result-title">
                                <i class="fa fa-file-image-o"></i>
                                上传结果：
                            </div>
                            <?php if ($uploadSuccess): ?>
                                <!-- 上传成功提示 -->
                                <div class="alert alert-success" style="margin-top: 15px;">
                                    <div>
                                        <i class="fa fa-check-circle"></i>
                                        <strong><?php echo htmlspecialchars($uploadMessage); ?></strong>
                                    </div>
                                </div>
                                <!-- SVG渲染区域 -->
                                <div id="xss-test-area" style="margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                                    <?php echo $svgContent; ?>
                                </div>
                            <?php else: ?>
                                <!-- 上传失败提示 -->
                                <div class="alert alert-danger" style="margin-top: 15px;">
                                    <div>
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <strong><?php echo htmlspecialchars($uploadMessage); ?></strong>
                                    </div>
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

        var uploadForm = document.getElementById('uploadForm');
        var fileInput = document.getElementById('svg_file');

        if (!uploadForm) return;

        // 表单提交验证
        uploadForm.addEventListener('submit', function (e) {
            var file = fileInput.files[0];

            if (!file) {
                e.preventDefault();
                HeaSecModal.showError('上传错误', '请选择SVG文件');
                return false;
            }

            // 验证文件类型
            var fileName = file.name.toLowerCase();
            if (!fileName.endsWith('.svg')) {
                e.preventDefault();
                HeaSecModal.showError('上传错误', '请选择有效的SVG文件');
                return false;
            }

            var submitBtn = uploadForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                var originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 上传中...';

                // 10秒后恢复按钮状态
                setTimeout(function () {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }, 10000);
            }
        });
    })();
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
