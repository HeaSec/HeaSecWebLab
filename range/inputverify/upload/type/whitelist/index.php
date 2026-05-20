<?php
/**
 * HeaSec天积安全团队 - 文件扩展名白名单绕过靶场
 * 版本: v1.0.0
 * 创建日期: 2025-11-25
 * 团队: 天积安全 (HeavenlySecret)
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置响应头
header('X-HeavenlySecret: HeaSec 文件扩展名白名单绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '文件扩展名白名单绕过靶场';
$rangeName = '文件扩展名白名单绕过';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../../common/';

// 设置重置功能相关变量
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_inputverify';
$useDatabase = true;  // 此靶场使用数据库

// 创建上传目录
$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
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
        'message' => '已重置所有上传的文件！'
    ];
    $newAchievement = false;
}

// 文件上传处理逻辑
$uploadResult = null;
$newAchievement = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload_file'])) {
    $uploadResult = handleFileUpload($_FILES['upload_file']);
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

/**
 * 处理文件上传
 */
function handleFileUpload($file)
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => '文件上传失败：' . $file['error']
        ];
    }

    $filename = $file['name'];
    $filesize = $file['size'];
    $tmp_name = $file['tmp_name'];

    // 检查文件大小（限制为5MB）
    if ($filesize > 5 * 1024 * 1024) {
        return [
            'success' => false,
            'message' => '文件大小不能超过5MB'
        ];
    }

    // 获取文件后缀
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    // 允许的白名单扩展名
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

    // 检查绕过方法
    $bypassResult = checkBypassMethods($filename, $extension);

    if ($bypassResult['bypassed']) {
        // 记录绕过成功
        recordBypassAchievement($bypassResult['method']);
        return [
            'success' => true,
            'message' => $bypassResult['message'],
            'bypassed' => true,
            'method' => $bypassResult['method']
        ];
    }

    // 检查是否为PHP文件（直接上传PHP）
    if (in_array($extension, ['php', 'phtml', 'php3', 'php4', 'php5'])) {
        return [
            'success' => false,
            'message' => '疑似上传恶意文件，请遵纪守法'
        ];
    }

    // 检查是否为允许的图片格式
    if (!in_array($extension, $allowedExtensions)) {
        return [
            'success' => false,
            'message' => '只允许上传 JPG、PNG、GIF 格式的图片文件'
        ];
    }

    // 正常上传图片
    global $uploadDir;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $safeFilename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    $uploadPath = $uploadDir . $safeFilename;

    if (move_uploaded_file($tmp_name, $uploadPath)) {
        return [
            'success' => true,
            'message' => '图片上传成功！',
            'bypassed' => false,
            'filename' => $safeFilename
        ];
    } else {
        return [
            'success' => false,
            'message' => '文件保存失败'
        ];
    }
}

/**
 * 检查各种绕过方法
 */
function checkBypassMethods($filename, $extension)
{
    $lowerFilename = strtolower($filename);

    // 1. Apache解析漏洞绕过 (.jpg.php)
    if (preg_match('/\.(jpg|jpeg|png|gif)\.php$/', $lowerFilename)) {
        return [
            'bypassed' => true,
            'method' => 'Apache解析漏洞绕过',
            'message' => '恭喜你，成功通过Apache解析漏洞绕过白名单检测'
        ];
    }

    // 2. Windows尾随点截断特性绕过 (.php.)
    if (preg_match('/\.php\.$/', $lowerFilename)) {
        return [
            'bypassed' => true,
            'method' => 'Windows尾随点截断特性绕过',
            'message' => '恭喜你，成功通过Windows尾随点截断特性上传了文件'
        ];
    }

    // 3. NTFS交换数据流绕过 (.php::DATA)
    if (preg_match('/\.php::data$/', $lowerFilename)) {
        return [
            'bypassed' => true,
            'method' => 'NTFS交换数据流',
            'message' => '恭喜你，成功通过NTFS交换数据流绕过上传了文件'
        ];
    }

    return [
        'bypassed' => false,
        'method' => null
    ];
}

/**
 * 记录绕过成就
 */
function recordBypassAchievement($method)
{
    global $newAchievement;

    try {
        global $commonBasePath;
        require_once $commonBasePath . 'includes/database.php';
        $db = heasec_db('heasec_inputverify');

        // 检查是否是新成就
        $stmt = $db->prepare("SELECT id FROM heasec_whitelist_records WHERE achievement = ?");
        $stmt->execute([$method]);
        $isNew = !$stmt->fetch();

        // 插入或更新记录
        $sql = "INSERT INTO heasec_whitelist_records (achievement, success_count, last_success_at)
                VALUES (?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                success_count = success_count + 1,
                last_success_at = NOW()";
        $stmt = $db->prepare($sql);
        $stmt->execute([$method]);

        $newAchievement = $isNew;

    } catch (Exception $e) {
        error_log('[HeaSec] Database error: ' . $e->getMessage());
    }
}

// 引入公共头部
require_once __DIR__ . '/../../../../common/includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="../../../../common/css/heasec_range.css">

<!-- 引入自定义样式 -->
<style>
    /* ===== JS绕过靶场统一样式 ===== */
    .upload-section {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 12px;
        padding: 30px;
        margin: 20px 0;
        box-shadow: 0 8px 32px rgba(0, 123, 255, 0.1);
        backdrop-filter: blur(10px);
    }

    .file-input-wrapper {
        position: relative;
        display: inline-block;
        cursor: pointer;
        background: linear-gradient(45deg, #007BFF, #667eea);
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        transition: all 0.3s ease;
        border: none;
        font-size: 16px;
    }

    .file-input-wrapper:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.3);
    }

    .file-input {
        position: absolute;
        left: -9999px;
    }

    .upload-button {
        background: linear-gradient(45deg, #28a745, #20c997);
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        transition: all 0.3s ease;
        margin-left: 10px;
    }

    .upload-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
    }

    /* 拖拽上传区域样式 */
    .upload-dropzone {
        border: 2px dashed var(--heasec-primary-color, #007BFF);
        border-radius: 8px;
        padding: 40px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        background-color: rgba(0, 123, 255, 0.05);
        position: relative;
        margin: 15px 0;
    }

    .upload-dropzone:hover {
        border-color: var(--heasec-primary-color, #007BFF);
        background-color: rgba(0, 123, 255, 0.1);
        transform: scale(1.01);
    }

    .upload-dropzone.drag-over {
        border-color: var(--heasec-success-color, #28a745);
        background-color: rgba(40, 167, 69, 0.1);
        transform: scale(1.02);
    }

    .upload-dropzone.drag-hint {
        color: var(--heasec-success-color, #28a745);
        font-weight: 500;
        margin-top: 10px;
        display: none;
    }

    .upload-dropzone.drag-over .drag-hint {
        display: block;
    }

    .upload-dropzone.drag-over p {
        display: none;
    }

    #upload_file_dropzone {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
    }

    .reset-button {
        background: linear-gradient(45deg, #dc3545, #e74c3c);
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .reset-button:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
    }

    .files-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .files-table th,
    .files-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }

    .files-table th {
        background: linear-gradient(45deg, #007BFF, #667eea);
        color: white;
        font-weight: bold;
    }

    .files-table tr:hover {
        background-color: rgba(0, 123, 255, 0.05);
    }

    .file-link {
        color: #007BFF;
        text-decoration: none;
    }

    .file-link:hover {
        text-decoration: underline;
    }

    .file-size {
        color: #6c757d;
        font-size: 14px;
    }

    .form-hint {
        margin-top: 8px;
        color: #666;
        font-style: italic;
    }
</style>

<!-- 引入星星系统组件资源 -->
<?php
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);
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
                        <input type="file" name="upload_file" class="file-input" id="upload_file" accept="*/*"
                            onchange="validateFileType();" required>
                        <i class="fa fa-folder-open"></i> 选择文件
                    </label>
                    <button type="submit" class="upload-button">
                        <i class="fa fa-cloud-upload"></i> 上传文件
                    </button>
                </div>

                <div class="form-hint">
                    <small>📌 提示：尝试使用不同的方法绕过白名单检测</small>
                </div>
            </form>

            <!-- 上传结果显示 -->
            <?php if ($uploadResult): ?>
                <div class="upload-result" style="margin-top: 20px;">
                    <h4>上传结果：</h4>
                    <div class="alert alert-<?php
                    echo $uploadResult['bypassed'] ? 'success' :
                        ($uploadResult['success'] ? 'info' : 'error');
                    ?>">
                        <div>
                            <i class="fa fa-<?php
                            echo $uploadResult['bypassed'] ? 'check-circle' :
                                ($uploadResult['success'] ? 'info-circle' : 'times-circle');
                            ?>"></i>
                            <strong><?php echo htmlspecialchars($uploadResult['message']); ?></strong>
                        </div>

                        <?php if ($uploadResult['success'] && $uploadResult['bypassed']): ?>
                            <div class="bypass-details" style="margin-top: 10px;">
                                <small>
                                    <strong>绕过方法：</strong> <?php echo htmlspecialchars($uploadResult['method']); ?><br>
                                    <?php if (isset($uploadResult['filename'])): ?>
                                        <strong>文件名：</strong> <?php echo htmlspecialchars($uploadResult['filename']); ?>
                                    <?php endif; ?>
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

        </div>
    </div>

    <!-- 成就系统区域 - 使用公共组件 -->
    <?php
    // 引入成就卡片公共组件
    require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';

    // 获取成就数量和记录
    $starCount = 0;
    $records = [];
    try {
        require_once $commonBasePath . 'includes/database.php';
        $db = heasec_db('heasec_inputverify');
        $stmt = $db->query("SELECT COUNT(*) as count FROM heasec_whitelist_records");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $starCount = intval($result['count']);

        // 获取记录列表
        $stmt = $db->query("SELECT achievement, success_count, last_success_at FROM heasec_whitelist_records ORDER BY last_success_at DESC");
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 转换记录格式
        $formattedRecords = [];
        foreach ($records as $record) {
            $formattedRecords[] = [
                'name' => $record['achievement'],
                'count' => $record['success_count'],
                'time' => $record['last_success_at']
            ];
        }
    } catch (Exception $e) {
        error_log('[HeaSec] Database error: ' . $e->getMessage());
        $starCount = 0;
        $formattedRecords = [];
    }

    // 渲染成就卡片公共组件
    echo renderAchievementCard([
        'achievedCount' => $starCount,
        'customRecords' => $formattedRecords,
        'recordsTitle' => '成功使用过的绕过方式',
        'rangeCode' => 'whitelist',

        // 恭喜功能配置（自定义消息标题和内容）
        'congratsConfig' => [
            'messages' => [
                'partial' => '你可以继续努力，获得更多的方法！',
                'complete' => '你已经掌握了所有的技能，继续保持！'
            ]
        ]
    ], $commonBasePath);
    ?>
</div>

<?php if ($newAchievement && $uploadResult && $uploadResult['bypassed']): ?>
    <!-- 新成就时的自动触发脚本 -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 获取当前星星数量
            var currentStars = <?php echo $starCount; ?>;

            // 触发恭喜消息
            setTimeout(function () {
                if (typeof showCongratsModal === 'function') {
                    showCongratsModal(currentStars);
                }
            }, 500);
        });
    </script>
<?php endif; ?>

<!-- JavaScript拖拽上传功能 -->
<script>
    // 初始化拖拽上传功能
    document.addEventListener('DOMContentLoaded', function () {
        initDragUpload();

        // 初始化传统文件选择
        const uploadFile = document.getElementById('upload_file');
        if (uploadFile) {
            uploadFile.addEventListener('change', function () {
                const fileName = this.files[0] ? this.files[0].name : '';
                if (fileName) {
                    console.log('已选择文件:', fileName);
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
     * 同步文件到两个输入框
     */
    function syncFileInputs(file) {
        const dropzoneInput = document.getElementById('upload_file_dropzone');
        const uploadFile = document.getElementById('upload_file');

        // 创建一个新的FileList对象（通过DataTransfer）
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);

        if (dropzoneInput) {
            dropzoneInput.files = dataTransfer.files;
        }
        if (uploadFile) {
            uploadFile.files = dataTransfer.files;
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

        // 白名单绕过靶场不做前端验证，允许所有文件类型
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

<?php
// 引入公共底部
require_once __DIR__ . '/../../../../common/includes/footer.php';
?>