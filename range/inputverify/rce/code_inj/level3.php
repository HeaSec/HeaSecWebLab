<?php
/**
 * HeaSec天积安全团队 - 代码注入靶场 - 第三关
 * 版本: v1.0.0
 * 创建日期: 2026-04-22
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec CodeInj Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '代码注入 - 第三关';
$rangeName = '代码注入';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 当前关卡配置
$currentLevel = 3;

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入星星系统组件（恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入公共函数
require_once 'includes/functions.php';

// 初始化靶场会话（日志功能依赖session）
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('code_inj');

// 确保当前关卡的secret.php存在
$secretPath = getSecretFilePath($currentLevel);
generateSecretFile($secretPath);

// 确保 logs 目录存在
$logDir = __DIR__ . '/logs/';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// ====== 页面初始化时自动记录当前请求到日志 ======
$logFileName = isset($_SESSION['code_inj_log_file']) ? $_SESSION['code_inj_log_file'] : null;

if (!empty($logFileName)) {
    $logFile = $logDir . $logFileName;
    $requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    $clientIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    $requestTime = date('Y-m-d H:i:s');
    $logEntry = "[{$requestTime}] {$requestMethod} {$clientIp} {$requestUrl}\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 日志分析器卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-file-text-o"></i>
                <span>天积日志分析器</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>当前系统的日志分析器会在每次页面加载时自动记录HTTP请求信息到日志文件中，用于安全审计和流量分析。您可以自定义日志文件的名称</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>利用日志功能获取通关密码，秘密文件位于 config/level3 目录下</small>
                </div>
            </div>

            <!-- 日志配置 -->
            <div class="form-group">
                <label for="logFilename" class="form-label">
                    <i class="fa fa-cog"></i> 日志文件名
                </label>
                <input type="text" id="logFilename" class="tech-input" placeholder="请输入日志文件名（默认 .log）" autocomplete="off">
            </div>
            <div class="form-actions">
                <button type="button" id="setLogConfigBtn" class="tech-btn tech-btn-primary">
                    <i class="fa fa-cog"></i> 设置日志文件
                </button>
            </div>

            <hr class="section-divider">

            <!-- 日志内容查看区域 -->
            <h4><i class="fa fa-eye"></i> 日志内容</h4>
            <div class="form-actions" style="margin-top: 10px;">
                <button type="button" id="refreshLogBtn" class="tech-btn tech-btn-info">
                    <i class="fa fa-refresh"></i> 刷新日志内容
                </button>
            </div>
            <div id="logViewArea" class="log-view-area" style="margin-top: 15px;">
                日志内容将在此显示...
            </div>
        </div>
    </div>

    <br>

    <!-- 通关验证卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-trophy"></i>
                <span>通关验证</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <form id="verifyForm" class="tech-form">
                <div class="form-group">
                    <label for="passcode" class="form-label">
                        <i class="fa fa-key"></i> 通关密码
                    </label>
                    <input type="text" id="passcode" name="passcode" class="tech-input" placeholder="请输入通关密码" autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i> 提交
                    </button>
                </div>
                <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/code_inj.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initCodeInj(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
