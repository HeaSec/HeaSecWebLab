<?php
/**
 * HeaSec天积安全团队 - 文件上传黑名单绕过靶场
 * File Upload Blacklist Bypass Range
 * 版本: v1.0.0
 * 创建日期: 2025-12-08
 * 团队: 天积安全 (HeavenlySecret)
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件上传黑名单绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件上传黑名单绕过靶场';
$rangeName = '文件上传黑名单绕过';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../../common/';

// 设置重置功能相关变量
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_inputverify';
$useDatabase = true;  // 此靶场使用数据库

// 引入核心检测类
require_once __DIR__ . '/includes/HeaSec_UploadBypassDetector.php';

// 引入上传结果闪存组件（PRG模式，防止刷新/重置时POST表单重复提交）
require_once __DIR__ . '/includes/HeaSec_UploadResultFlash.php';

// 创建上传目录
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 处理文件上传
$uploadResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_file'])) {
    $detector = new HeaSec_UploadBypassDetector();
    $uploadResult = $detector->processUpload($_FILES['upload_file']);

    // 如果上传成功且未被阻止，保存文件
    if ($uploadResult && $uploadResult['success'] && !$uploadResult['should_block']) {
        $uploadedFile = $_FILES['upload_file'];
        $fileName = basename($uploadedFile['name']);

        // 安全的文件名处理
        $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
        $fileName = trim($fileName, '._-');

        if (!empty($fileName) && strlen($fileName) <= 255) {
            $targetPath = $uploadDir . $fileName;

            // 文件大小限制（10MB）
            $maxSize = 10 * 1024 * 1024;
            if ($uploadedFile['size'] <= $maxSize) {
                // 移动上传的文件
                if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
                    $uploadResult['message'] = '文件上传成功，但保存失败！';
                }
            }
        }
    }

    // PRG模式：上传结果存入session闪存，并302重定向到GET请求
    // 目的：让最终页面以GET方式加载，避免重置时location.reload()重复提交POST表单
    HeaSec_UploadResultFlash::storeAndRedirect($uploadResult);
}

// 处理重置请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
    // 删除上传目录中的所有文件
    $files = glob($uploadDir . '*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    $uploadResult = [
        'success' => true,
        'message' => '已重置所有上传的文件！',
        'should_block' => false,
        'achievement' => false
    ];
}

// PRG模式：GET请求时从session闪存读取上传结果（一次性消费）
// 仅当存在闪存时覆盖，避免影响其他场景（如表单重置POST）的$uploadResult
$flashResult = HeaSec_UploadResultFlash::readOnce();
if ($flashResult !== null) {
    $uploadResult = $flashResult;
}

// 获取已上传的文件列表
$uploadedFiles = [];
if (file_exists($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $uploadedFiles[] = [
                'name' => $file,
                'path' => 'uploads/' . $file,
                'size' => filesize($uploadDir . $file)
            ];
        }
    }
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="../../../../common/css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css?v=<?php echo $version; ?>">

<!-- 引入星星系统组件资源 -->
<?php
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['js' => false]);
?>

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- 文件上传区域 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-upload"></i>
                请上传您的图片文件(JPG/PNG格式)
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 文件上传表单 -->
            <form method="POST" enctype="multipart/form-data" id="uploadForm" class="upload-form"
                onsubmit="return validateFileType();">
                <!-- 拖拽上传区域 -->
                <div class="upload-dropzone" id="uploadDropzone">
                    <i class="fa fa-cloud-upload fa-3x"
                        style="color: var(--heasec-primary-color, #007BFF); margin-bottom: 15px;"></i>
                    <p style="margin: 0; color: #666;">点击选择文件或拖拽文件到此处</p>
                    <input type="file" name="upload_file" id="upload_file_dropzone" accept="*/*">
                </div>

                <!-- 传统文件选择区域 -->
                <div class="form-group" style="margin-bottom: 20px; margin-top: 20px;">
                    <label class="file-input-wrapper">
                        <input type="file" class="file-input" id="upload_file" accept="*/*">
                        <i class="fa fa-folder-open"></i> 选择文件
                    </label>
                    <button type="submit" class="upload-button">
                        <i class="fa fa-cloud-upload"></i> 上传文件
                    </button>
                </div>

                <div class="form-hint">
                    <small>📌 提示：尝试使用不同的方法绕过黑名单检测</small>
                </div>
            </form>

            <!-- 上传结果显示 -->
            <?php if ($uploadResult): ?>
                <div class="upload-result" style="margin-top: 20px;">
                    <h4>上传结果：</h4>
                    <div class="alert alert-<?php
                    echo $uploadResult['should_block'] ? 'error' :
                        ($uploadResult['achievement'] ? 'success' : 'info');
                    ?>">
                        <div>
                            <i class="fa fa-<?php
                            echo $uploadResult['should_block'] ? 'times-circle' :
                                ($uploadResult['achievement'] ? 'check-circle' : 'info-circle');
                            ?>"></i>
                            <strong><?php echo htmlspecialchars($uploadResult['message']); ?></strong>
                        </div>

                        <?php if (!$uploadResult['should_block'] && $uploadResult['bypass_type'] > 0): ?>
                            <div class="bypass-details" style="margin-top: 10px;">
                                <small>
                                    <strong>绕过方法：</strong> <?php echo htmlspecialchars($uploadResult['bypass_name']); ?><br>
                                    <strong>文件名：</strong> <?php echo htmlspecialchars($uploadResult['filename']); ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 已上传文件列表 - 已隐藏 -->
            <?php /*
<?php if (!empty($uploadedFiles)): ?>
 <div style="margin-top: 30px;">
     <h4>已上传的文件：</h4>
     <table class="files-table">
         <thead>
             <tr>
                 <th>文件名</th>
                 <th>文件大小</th>
                 <th>预览</th>
             </tr>
         </thead>
         <tbody>
             <?php foreach ($uploadedFiles as $file): ?>
                 <tr>
                     <td><?php echo htmlspecialchars($file['name']); ?></td>
                     <td class="file-size"><?php echo number_format($file['size'] / 1024, 2); ?> KB</td>
                     <td>
                         <a href="<?php echo htmlspecialchars($file['path']); ?>" target="_blank" class="file-link">
                             <i class="fa fa-eye"></i> 预览
                         </a>
                     </td>
                 </tr>
             <?php endforeach; ?>
         </tbody>
     </table>

     <form method="POST" style="margin-top: 20px;" onsubmit="return confirmReset();">
         <input type="hidden" name="action" value="reset">
         <button type="submit" class="reset-button">
             <i class="fa fa-trash"></i> 重置文件列表
         </button>
     </form>
 </div>
<?php endif; ?>
*/ ?>

            <!-- 绕过方法提示 -->

        </div>
    </div>

    <!-- 成就系统区域 - 使用公共组件 -->
    <?php
    // 引入成就卡片公共组件
    require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';

    // 获取成就统计
    $detector = new HeaSec_UploadBypassDetector();
    $achievementStats = $detector->getAchievementStats();

    // 获取成就数量和记录
    $starCount = $achievementStats['starCount'];
    $formattedRecords = $achievementStats['records'];

    // 渲染成就卡片公共组件
    echo renderAchievementCard([
        'achievedCount' => $starCount,
        'customRecords' => $formattedRecords,
        'recordsTitle' => '成功使用过的绕过方式',
        'rangeCode' => 'blacklist',

        // 恭喜功能配置（自定义消息标题和内容）
        'congratsConfig' => [
            'messages' => [
                'partial' => '你可以继续努力，获得更多的方法！',
                'complete' => '你已经掌握了所有的技能，继续保持！',
            ]
        ]
    ], $commonBasePath);
    ?>
</div>

<!-- JavaScript拖拽上传功能 -->
<script>
    // 初始化拖拽上传功能
    document.addEventListener('DOMContentLoaded', function () {
        initDragUpload();

        // 初始化传统文件选择
        const uploadFile = document.getElementById('upload_file');
        if (uploadFile) {
            uploadFile.addEventListener('change', function () {
                const files = this.files;
                if (files.length > 0) {
                    // 同步文件到拖拽输入框（参考 upload_base 模式）
                    syncFileInputs(files[0]);
                }
            });
        }
    });

    /**
     * 初始化拖拽上传功能
     */
    function initDragUpload() {
        const uploadDropzone = document.getElementById('uploadDropzone');
        const dropzoneInput = document.getElementById('upload_file_dropzone');
        const uploadFile = document.getElementById('upload_file');

        if (!uploadDropzone || !dropzoneInput) {
            return;
        }

        // 点击拖拽区域触发文件选择
        uploadDropzone.addEventListener('click', function (e) {
            if (e.target !== dropzoneInput) {
                dropzoneInput.click();
            }
        });

        // 拖拽区域文件选择事件
        dropzoneInput.addEventListener('change', function (e) {
            const files = e.target.files;
            if (files.length > 0) {
                updateDropzoneText(files[0]);
                syncFileInputs(files[0]);
            }
        });

        // 拖拽事件处理
        uploadDropzone.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('drag-over');
        });

        uploadDropzone.addEventListener('dragleave', function (e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');
        });

        uploadDropzone.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('drag-over');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const file = files[0];

                // 同步文件到两个输入框
                syncFileInputs(file);

                // 更新拖拽区域显示
                updateDropzoneText(file);
            }
        });
    }

    /**
     * 同步文件到拖拽输入框
     * 注意：只同步到dropzoneInput，避免重复上传
     */
    function syncFileInputs(file) {
        const dropzoneInput = document.getElementById('upload_file_dropzone');

        // 创建一个新的FileList对象（通过DataTransfer）
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);

        if (dropzoneInput) {
            dropzoneInput.files = dataTransfer.files;
        }
    }

    /**
     * 更新拖拽区域文本
     */
    function updateDropzoneText(file) {
        const uploadDropzone = document.getElementById('uploadDropzone');
        if (!uploadDropzone) return;

        const textElement = uploadDropzone.querySelector('p');
        const fileSize = formatFileSize(file.size);

        if (textElement) {
            textElement.innerHTML = '<strong>已选择文件:</strong><br>' +
                file.name + '<br><small>大小: ' + fileSize + '</small>';
        }
    }

    /**
     * 格式化文件大小
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';

        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * 表单提交时的文件类型验证
     */
    function validateFileType() {
        const uploadFile = document.getElementById('upload_file');
        const dropzoneInput = document.getElementById('upload_file_dropzone');

        // 检查两个输入框是否有文件
        let file = null;
        if (uploadFile && uploadFile.files.length > 0) {
            file = uploadFile.files[0];
        } else if (dropzoneInput && dropzoneInput.files.length > 0) {
            file = dropzoneInput.files[0];
        }

        // 黑名单绕过靶场不做前端验证，允许所有文件类型
        // 这里只确保有文件被选择
        if (!file) {
            alert('请选择要上传的文件！');
            return false;
        }

        return true;
    }

    // 添加键盘快捷键支持（Ctrl+O 打开文件选择）
    document.addEventListener('keydown', function (e) {
        if (e.ctrlKey && e.key === 'o') {
            e.preventDefault();
            const dropzoneInput = document.getElementById('upload_file_dropzone');
            if (dropzoneInput) {
                dropzoneInput.click();
            }
        }
    });

    function confirmReset() {
        return confirm('确认删除全部已上传的文件吗？');
    }
</script>

<!-- 引入公共底部 -->
<?php
require_once $commonBasePath . 'includes/footer.php';
?>