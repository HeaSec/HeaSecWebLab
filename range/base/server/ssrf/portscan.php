<?php
/**
 * HeaSec天积安全团队 - SSRF漏洞靶场端口扫描结果页
 * 版本: v1.0.0
 * 创建日期: 2026-04-06
 * 团队: 天积安全 (HeavenlySecret)
 * 说明: 展示已探测到的开放端口列表和任务进度
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec SSRF漏洞 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'SSRF漏洞靶场 - 端口扫描';
$rangeName = 'SSRF漏洞';
$showVersion = false;
$showResetButton = false;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_base';
$useDatabase = true;

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('ssrf');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入靶场公共函数
require_once __DIR__ . '/includes/functions.php';

// 获取数据库配置信息
$projectRoot = realpath(__DIR__ . '/' . $commonBasePath . '../../');
$configJson = json_decode(file_get_contents($projectRoot . '/config/config.json'), true);
$dbHost = $configJson['database']['host'] ?? 'localhost';
$dbPort = $configJson['database']['port'] ?? 3306;

// 获取数据库连接
try {
    $pdo = HeaSec_Database::getConnection('heasec_base');
    $sessionId = session_id();
    $progress = getOrCreateProgress($pdo, $sessionId);
} catch (Exception $e) {
    $progress = ['current_step' => 3, 'step3_completed' => 0];
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-radar"></i>
                内网端口扫描 - 任务面板
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 任务说明 -->
            <div class="alert alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>端口扫描任务</strong>
                </div>
                <span class="alert-hint">
                    <small>
                        请利用SSRF漏洞探测目标内网服务器开放的端口。<br>
                        目标主机：<strong id="db-host"><?php echo htmlspecialchars($dbHost === 'localhost' ? '127.0.0.1' : $dbHost); ?></strong><br>
                    </small>
                </span>
            </div>

            <!-- 端口探测结果 -->
            <div id="portResults">
                <div class="no-ports">
                    <i class="fa fa-search"></i>
                    <p>尚未探测到任何开放端口...</p>
                </div>
            </div>

            <!-- 第四步提示区域 -->
            <div id="step3Hint" style="display:none;"></div>

            <!-- 返回主页按钮 -->
            <div style="margin-top: 16px;">
                <a href="index.php" class="tech-btn tech-btn-primary">
                    <i class="fa fa-arrow-left"></i> 返回主页
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 引入交互脚本 -->
<script src="js/ssrf.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initPortScan({
            currentStep: <?php echo (int)($progress['current_step'] ?? 3); ?>
        });
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
