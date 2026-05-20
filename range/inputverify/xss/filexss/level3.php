<?php
/**
 * HeaSec天积安全团队 - 文件相关XSS靶场 - 第三关（Data URI XSS）
 * 版本: v1.0.0
 * 创建日期: 2026-03-03
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件相关XSS Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件相关XSS靶场 - 第三关';
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

    // 初始化素材列表会话
    if (!isset($_SESSION['filexss_level3_materials'])) {
        $_SESSION['filexss_level3_materials'] = [];
    }

    $imagePath = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image_file'])) {
        $hasUpload = true;

        // 调用上传API进行验证
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/api/upload.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'file' => new CURLFile($_FILES['image_file']['tmp_name'], $_FILES['image_file']['type'], $_FILES['image_file']['name']),
            'type' => 'image'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($result && $result['success']) {
            $uploadSuccess = true;
            $uploadMessage = '文件上传成功已加入素材库';
            $imagePath = $result['file_path'];

            // 添加到素材库
            array_unshift($_SESSION['filexss_level3_materials'], [
                'id' => uniqid('mat_'),
                'type' => 'local',
                'name' => $_FILES['image_file']['name'],
                'url' => $imagePath,
                'time' => date('Y-m-d H:i:s')
            ]);
        } else {
            $uploadSuccess = false;
            $uploadMessage = $result['message'] ?? '文件上传失败';
        }
    }

    // 处理路径预览请求
    $hasPreview = false;
    $previewPath = '';
    $isBlocked = false;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview_path'])) {
        $hasPreview = true; // 复用为展示添加成功的标志
        $previewPath = trim($_POST['preview_path']);

        // 黑名单过滤：检测并拦截危险关键词（大小写不敏感）
        $blacklist = ['eval', 'onload', 'onerror', 'onclick', 'script', 'alert'];
        $lowerPath = strtolower($previewPath);

        foreach ($blacklist as $keyword) {
            if (strpos($lowerPath, $keyword) !== false) {
                $isBlocked = true;
                break;
            }
        }

        // 字符过滤：拦截尖括号和双引号
        $blockedChars = ['<', '>', '"'];
        foreach ($blockedChars as $char) {
            if (strpos($previewPath, $char) !== false) {
                $isBlocked = true;
                break;
            }
        }

        if (!$isBlocked) {
            // 添加到素材库
            $name = (strlen($previewPath) > 50) ? substr($previewPath, 0, 50) . '...' : $previewPath;
            array_unshift($_SESSION['filexss_level3_materials'], [
                'id' => uniqid('mat_'),
                'type' => 'external',
                'name' => $name,
                'url' => $previewPath,
                'time' => date('Y-m-d H:i:s')
            ]);
        }
    }

    // 处理删除素材请求
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_material'])) {
        $deleteId = $_POST['delete_material'];
        foreach ($_SESSION['filexss_level3_materials'] as $key => $mat) {
            if ($mat['id'] === $deleteId) {
                unset($_SESSION['filexss_level3_materials'][$key]);
                break;
            }
        }
        // 重新索引数组
        $_SESSION['filexss_level3_materials'] = array_values($_SESSION['filexss_level3_materials']);
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
<style>
    /* 素材列表样式 */
    .material-list {
        margin-top: 25px;
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        overflow: hidden;
    }
    .material-list-header {
        background: #f8f9fa;
        padding: 12px 15px;
        font-weight: bold;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .material-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background-color 0.2s;
    }
    .material-item:last-child {
        border-bottom: none;
    }
    .material-item:hover {
        background-color: #fcfcfc;
    }
    .material-info {
        flex: 1;
        overflow: hidden;
    }
    .material-name {
        font-weight: 500;
        color: #333;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .material-meta {
        font-size: 12px;
        color: #888;
        display: flex;
        gap: 15px;
    }
    .material-actions {
        display: flex;
        gap: 10px;
        margin-left: 20px;
    }
    .btn-action {
        padding: 6px 12px;
        font-size: 12px;
        border-radius: 4px;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.2s;
    }
    .btn-preview {
        background-color: #e3f2fd;
        color: #1976d2;
        border-color: #bbdefb;
    }
    .btn-preview:hover {
        background-color: #bbdefb;
    }
    .btn-delete {
        background-color: #ffebee;
        color: #d32f2f;
        border-color: #ffcdd2;
    }
    .btn-delete:hover {
        background-color: #ffcdd2;
    }
    .badge {
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 11px;
        font-weight: normal;
    }
    .badge-local {
        background: #e8f5e9;
        color: #2e7d32;
    }
    .badge-external {
        background: #fff3e0;
        color: #e65100;
    }
    .empty-state {
        padding: 40px 20px;
        text-align: center;
        color: #888;
    }
    .empty-state i {
        font-size: 48px;
        color: #ccc;
        margin-bottom: 15px;
    }
</style>

<!-- XSS弹窗检测系统 -->
<script>
    (function () {
        'use strict';

        // 保存原始弹窗函数
        var originalAlert = window.alert;

        var currentLevel = 3;
        var hasPassed = false;

        console.log('[HeaSec FileXSS] 第三关弹窗检测系统已初始化');

        // 自动通关
        function autoCompleteLevel() {
            if (hasPassed) return;
            hasPassed = true;

            console.log('[HeaSec FileXSS] 开始调用通关API...');

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'api/complete_level.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log('[HeaSec FileXSS] 通关API响应:', xhr.responseText);
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            console.log('[HeaSec FileXSS] 通关成功');

                            // 更新星星数量
                            if (window.updateStarCount) {
                                window.updateStarCount(response.star_count);
                            }

                            // 显示返回按钮
                            var formActions = document.querySelector('.form-actions');
                            if (formActions) {
                                formActions.innerHTML = '<a href="index.php" class="tech-btn tech-btn-success"><i class="fa fa-arrow-left"></i> 返回第一关</a>';
                            }

                            // 显示恭喜弹窗
                            setTimeout(function () {
                                console.log('[HeaSec FileXSS] 准备显示恭喜弹窗, HeaSecCongratsModal:', typeof HeaSecCongratsModal);
                                if (typeof HeaSecCongratsModal !== 'undefined' && HeaSecCongratsModal.show) {
                                    console.log('[HeaSec FileXSS] 调用HeaSecCongratsModal.show');
                                    HeaSecCongratsModal.show({
                                        title: '🏆 完美通关！',
                                        message: '恭喜你完成了文件相关XSS靶场的所有关卡！',
                                        buttonText: '继续学习',
                                        enableNextRangeButton: true,
                                        rangeCode: 'filexss',
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
                                } else {
                                    console.error('[HeaSec FileXSS] HeaSecCongratsModal未定义或show方法不存在');
                                    // 备用方案：直接显示成功消息
                                    addPageMessage('恭喜通关！所有关卡已完成！', true);
                                }
                            }, 500);
                        }
                    } catch (e) {
                        console.error('[HeaSec FileXSS] 通关响应解析失败:', e);
                    }
                }
            };
            xhr.send('level=' + currentLevel);
        }

        // 添加提示消息到页面
        function addPageMessage(message, isSuccess) {
            var previewForm = document.getElementById('previewForm');
            if (!previewForm) return;

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
            previewForm.parentNode.insertBefore(msgDiv, previewForm.nextSibling);
        }

        // 检测Data URI XSS攻击
        // 由于<object>/<iframe>标签加载data URI时JavaScript在独立上下文执行，无法直接拦截alert
        // 采用监听load事件 + 延迟检测的方案
        function setupObjectDetection() {
            var testArea = document.getElementById('xss-test-area');
            if (!testArea) return;

            // 检查iframe（用于data:text/html）
            var iframeEl = testArea.querySelector('iframe');
            if (iframeEl) {
                console.log('[HeaSec FileXSS] 检测到iframe');

                // 对于data URI，iframe可能已经加载完成，直接延迟触发检测
                if (!hasPassed) {
                    setTimeout(function() {
                        if (!hasPassed) {
                            var successMsg = '成功实现了Data URI XSS注入攻击！';
                            console.log('[HeaSec FileXSS] ' + successMsg);
                            addPageMessage(successMsg, true);
                            autoCompleteLevel();
                        }
                    }, 800);
                }

                return;
            }

            // 检查object（用于其他类型）
            var objectEl = testArea.querySelector('object');
            if (objectEl) {
                var dataAttr = objectEl.getAttribute('data') || '';

                // 检查是否是data:text/html类型的URI
                if (dataAttr.toLowerCase().indexOf('data:text/html') !== -1) {
                    console.log('[HeaSec FileXSS] 检测到data:text/html URI (object)');

                    // 对于data URI，object可能已经加载完成，直接延迟触发检测
                    if (!hasPassed) {
                        setTimeout(function() {
                            if (!hasPassed) {
                                var successMsg = '成功实现了Data URI XSS注入攻击！';
                                console.log('[HeaSec FileXSS] ' + successMsg);
                                addPageMessage(successMsg, true);
                                autoCompleteLevel();
                            }
                        }, 800);
                    }
                }
            }
        }

        // 重写alert函数（处理同源情况）
        window.alert = function (message) {
            console.log('[HeaSec FileXSS] 拦截到alert:', message);

            // 检查object标签的data属性是否包含data:text/html
            var testArea = document.getElementById('xss-test-area');
            if (testArea) {
                var objectEl = testArea.querySelector('object');
                if (objectEl) {
                    var dataAttr = objectEl.getAttribute('data') || '';
                    if (dataAttr.toLowerCase().indexOf('data:text/html') !== -1) {
                        // 成功实现XSS注入
                        if (!hasPassed) {
                            var successMsg = '成功实现了XSS注入攻击！';
                            console.log('[HeaSec FileXSS] ' + successMsg);
                            addPageMessage(successMsg, true);
                            autoCompleteLevel();
                        }
                    }
                }
            }

            // 调用原始弹窗
            return originalAlert.apply(this, arguments);
        };

        // 监听来自iframe/object的消息（处理跨域情况）
        window.addEventListener('message', function(event) {
            console.log('[HeaSec FileXSS] 收到消息:', event.data);
            // 如果收到XSS成功的消息
            if (event.data && (event.data.type === 'xss-success' || event.data === 'xss-alert')) {
                if (!hasPassed) {
                    var successMsg = '成功实现了XSS注入攻击！';
                    console.log('[HeaSec FileXSS] 通过postMessage检测到XSS');
                    addPageMessage(successMsg, true);
                    autoCompleteLevel();
                }
            }
        });

        // 页面加载完成后设置检测 (同时也在点击预览后调用)
        document.addEventListener('DOMContentLoaded', function() {
            setupObjectDetection();
        });

        // 暴露全局检测函数以便动态点击预览时可以调用
        window.HeaSecDetectObject = setupObjectDetection;

        // 如果页面已经加载完成，立即设置
        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            setTimeout(setupObjectDetection, 100);
        }

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
                        <i class="fa fa-image"></i>
                        第三关
                    </h3>
                    <!-- <p class="level-description">"图片预览功能已上线，我们严格过滤了所有危险关键词。"</p> -->
                </div>
                <div class="tech-card-body">
                    <!-- 关卡提示 -->
                    <div class="level-tip">
                        <i class="fa fa-lightbulb-o"></i>
                        <strong>通关条件：</strong>触发alert弹窗
                    </div>

                    <div style="margin-top: 25px; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed #e0e0e0;">
                        <h4 style="margin: 0 0 10px 0; color: #333; font-size: 18px;"><i class="fa fa-folder-open"></i> 素材管理系统</h4>
                        <div style="color: #666; font-size: 14px;">功能说明：支持本地图片和外部链接两种方式添加素材</div>
                    </div>

                    <!-- 图片上传表单 -->
                    <form method="POST" action="" enctype="multipart/form-data" class="tech-form" id="uploadForm">
                        <div class="form-group">
                            <label class="form-label">本地上传图片</label>
                            <div class="file-upload-wrapper">
                                <input type="file" id="image_file" name="image_file" accept="image/png,image/jpeg,image/gif">
                                <div class="file-upload-info">
                                    <i class="fa fa-info-circle"></i>
                                    支持 PNG、JPG、GIF 格式，最大 2MB
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="tech-btn tech-btn-primary">
                                <i class="fa fa-upload"></i> 上传图片素材
                            </button>
                        </div>
                    </form>

                    <!-- 上传结果区域 -->
                    <?php if ($hasUpload && $uploadSuccess): ?>
                        <div class="search-result" style="margin-top: 20px;">
                            <div class="search-result-title">
                                <i class="fa fa-check-circle"></i>
                                上传成功：
                            </div>
                            <div class="alert alert-success" style="margin-top: 10px;">
                                <div>
                                    <i class="fa fa-file-image-o"></i>
                                    <strong><?php echo htmlspecialchars($uploadMessage); ?></strong>
                                    <br>
                                    <code style="background: #f8f9fa; padding: 5px 10px; border-radius: 4px; margin-top: 5px; display: inline-block;"><?php echo htmlspecialchars($imagePath); ?></code>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- 外部链接表单 -->
                    <form method="POST" action="" class="tech-form" id="previewForm" style="margin-top: 25px;">
                        <div class="form-group">
                            <label class="form-label">外部链接素材</label>
                            <div class="search-input-wrapper">
                                <i class="fa fa-link search-icon"></i>
                                <input
                                    type="text"
                                    id="preview_path"
                                    name="preview_path"
                                    class="search-input"
                                    placeholder="输入外部图片链接"
                                    value="<?php echo isset($_POST['preview_path']) ? htmlspecialchars($_POST['preview_path']) : ''; ?>"
                                    required>
                                <button type="submit" class="search-submit-btn">
                                    <i class="fa fa-plus"></i>
                                    添加素材
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                        </div>
                    </form>

                    <!-- 添加素材结果区域 -->
                    <?php if ($hasPreview): ?>
                        <div class="search-result" style="margin-top: 20px;">
                            <?php if ($isBlocked): ?>
                                <!-- 被拦截时显示错误提示 -->
                                <div class="alert alert-danger">
                                    <div>
                                        <i class="fa fa-exclamation-triangle"></i>
                                        <strong>检测到非法字符，请重新输入</strong>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <div>
                                        <i class="fa fa-check-circle"></i>
                                        <strong>外部链接素材已成功添加</strong>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- 素材列表区域 -->
                    <div class="material-list">
                        <div class="material-list-header">
                            <span><i class="fa fa-folder-open"></i> 当前可用素材</span>
                            <span class="badge badge-local" style="font-size:12px; padding: 4px 8px;"><?php echo count($_SESSION['filexss_level3_materials'] ?? []); ?> 项素材</span>
                        </div>
                        
                        <?php if (empty($_SESSION['filexss_level3_materials'])): ?>
                            <div class="empty-state">
                                <i class="fa fa-inbox"></i>
                                <p>这里空空如也~</p>
                                <p style="font-size: 13px; margin-top: 5px;">请通过上方表单上传本地图片或添加外部链接。</p>
                            </div>
                        <?php else: ?>
                            <div class="material-items-container" style="max-height: 300px; overflow-y: auto;">
                                <?php foreach ($_SESSION['filexss_level3_materials'] as $material): ?>
                                    <div class="material-item">
                                        <div class="material-info">
                                            <div class="material-name" title="<?php echo htmlspecialchars($material['url']); ?>">
                                                <i class="fa <?php echo $material['type'] === 'local' ? 'fa-file-image-o' : 'fa-link'; ?>"></i>
                                                <?php echo htmlspecialchars($material['name']); ?>
                                            </div>
                                            <div class="material-meta">
                                                <span class="badge <?php echo $material['type'] === 'local' ? 'badge-local' : 'badge-external'; ?>">
                                                    <?php echo $material['type'] === 'local' ? '本地上传' : '外部链接'; ?>
                                                </span>
                                                <span><i class="fa fa-clock-o"></i> <?php echo htmlspecialchars($material['time']); ?></span>
                                            </div>
                                        </div>
                                        <div class="material-actions">
                                            <button type="button" class="btn-action btn-preview" onclick="previewMaterial('<?php echo htmlspecialchars($material['url'], ENT_QUOTES); ?>')">
                                                <i class="fa fa-eye"></i> 预览
                                            </button>
                                            <form method="POST" action="" style="display: inline-block; margin: 0;">
                                                <input type="hidden" name="delete_material" value="<?php echo htmlspecialchars($material['id']); ?>">
                                                <button type="submit" class="btn-action btn-delete" onclick="return confirm('确定要删除这个素材吗？')">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- 真正的预览展示区域：隐藏，点击后显示并渲染 -->
                    <div id="material-preview-section" style="display: none; margin-top: 25px;">
                        <div class="search-result-title">
                            <i class="fa fa-image"></i>
                            资源预览：
                        </div>
                        <div id="xss-test-area" style="margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px; min-height: 100px; text-align: center;">
                            <!-- JS动态注入真实内容 -->
                            <p style="color: #888;">正在加载...</p>
                        </div>
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

        var uploadForm = document.getElementById('uploadForm');
        var fileInput = document.getElementById('image_file');
        var previewForm = document.getElementById('previewForm');
        var previewInput = document.getElementById('preview_path');

        // 图片上传表单验证
        if (uploadForm) {
            uploadForm.addEventListener('submit', function (e) {
                var file = fileInput.files[0];

                if (!file) {
                    e.preventDefault();
                    HeaSecModal.showError('上传错误', '请选择图片文件');
                    return false;
                }

                // 验证文件类型
                var allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
                if (allowedTypes.indexOf(file.type) === -1) {
                    e.preventDefault();
                    HeaSecModal.showError('上传错误', '请选择有效的图片文件（PNG、JPG、GIF）');
                    return false;
                }

                // 显示提交中状态
                var submitBtn = uploadForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    var originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 上传中...';

                    setTimeout(function () {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 10000);
                }
            });
        }

        // 路径预览表单验证
        if (previewForm) {
            previewForm.addEventListener('submit', function (e) {
                var path = previewInput.value.trim();

                if (!path) {
                    e.preventDefault();
                    HeaSecModal.showError('添加素材错误', '请输入链接');
                    return false;
                }

                // 前端黑名单验证（与后端一致）
                var blacklist = ['eval', 'onload', 'onerror', 'onclick', 'script', 'alert'];
                var lowerPath = path.toLowerCase();

                for (var i = 0; i < blacklist.length; i++) {
                    if (lowerPath.indexOf(blacklist[i]) !== -1) {
                        e.preventDefault();
                        HeaSecModal.showError('添加素材错误', '检测到非法字符，请重新输入');
                        return false;
                    }
                }

                // 检测尖括号和双引号
                var blockedChars = ['<', '>', '"'];
                for (var j = 0; j < blockedChars.length; j++) {
                    if (path.indexOf(blockedChars[j]) !== -1) {
                        e.preventDefault();
                        HeaSecModal.showError('添加素材错误', '检测到非法字符，请重新输入');
                        return false;
                    }
                }

                // 显示提交中状态
                var submitBtn = previewForm.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    var originalText = submitBtn.innerHTML;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 添加中...';

                    setTimeout(function () {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }, 10000);
                }
            });
        }

        // 按钮调用：预览资源（渲染iframe或object触发XSS）
        window.previewMaterial = function(url) {
            var section = document.getElementById('material-preview-section');
            var area = document.getElementById('xss-test-area');
            if (section && area) {
                section.style.display = 'block';
                
                var isHtmlDataUri = url.toLowerCase().indexOf('data:text/html') === 0;
                if (isHtmlDataUri) {
                    area.innerHTML = '<iframe id="preview-frame" src="' + url + '" style="width: 100%; height: 200px; border: none; min-height: 100px;" sandbox="allow-scripts allow-alerts allow-modals allow-forms allow-same-origin"></iframe>';
                } else {
                    area.innerHTML = '<object id="preview-object" data="' + url + '" type="image/png" style="max-width: 100%; min-height: 100px;"><p>如果无法加载素材，这可能是因为格式不受支持。</p></object>';
                }

                // 重新设置XSS检测绑定
                if (window.HeaSecDetectObject) {
                    setTimeout(window.HeaSecDetectObject, 100);
                }
            }
        };

        // 输入框焦点效果
        if (previewInput) {
            previewInput.addEventListener('focus', function () {
                this.closest('.search-input-wrapper').style.borderColor = '#007bff';
                this.closest('.search-input-wrapper').style.background = 'white';
            });

            previewInput.addEventListener('blur', function () {
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
