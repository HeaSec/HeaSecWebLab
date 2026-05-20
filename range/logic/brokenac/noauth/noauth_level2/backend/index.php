<?php
/**
 * HeaSec天积安全团队 - 未授权访问靶场 - 第二关管理页面 (backend)
 */

header('X-HeavenlySecret: HeaSec 未授权访问 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

$currentDirName = basename(__DIR__);
$currentLevel = 2;
$commonBasePath = '../../../../../common/';
define('HEASEC_RANGE_ACCESS', true);

require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../../includes/config-init.php';
require_once __DIR__ . '/../../includes/access-control.php';

HeaSec_InitRangeSession('noauth');
$pdo = HeaSec_Database::getConnection('heasec_logic');
$config = checkNoauthAccess($currentLevel, $currentDirName, $pdo);

if (!$config) {
    showNoauth404($commonBasePath);
}

$routerData = generateRouterData();

$pageTitle = 'HeaSec Router - 管理面板';
$rangeName = '未授权访问';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';
$initSqlFile = __DIR__ . '/../../database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;
$nextPage = '../../noauth_level3/index.php';
$nextBtnText = '第三关';

require_once $commonBasePath . 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="../../css/style.css">

<div id="jsRedirectNotice" class="js-redirect-notice" style="display: none;">
    <i class="fa fa-exclamation-triangle"></i>
    <p>未登录，正在跳转到登录页面...</p>
</div>

<script>
    (function () {
        var loggedIn = <?php echo isset($_SESSION['noauth_level2_logged_in']) && $_SESSION['noauth_level2_logged_in'] === true ? 'true' : 'false'; ?>;
        if (!loggedIn) {
            document.getElementById('jsRedirectNotice').style.display = 'block';
            setTimeout(function () {
                window.location.href = '../index.php';
            }, 2000);
        }
    })();
</script>

<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3><i class="fa fa-wifi"></i> HeaSec Router - 管理面板 (第二关)</h3>
            <div class="header-actions">
                <a href="../index.php?action=logout" class="tech-btn tech-btn-outline"
                    style="padding: 4px 12px; font-size: 12px; height: 100%;">
                    <i class="fa fa-sign-out"></i> 退出登录
                </a>
            </div>
        </div>
        <div class="tech-card-body">
            <div class="status-indicator" style="margin-bottom: 20px;">
                <span class="dot connected"></span>
                <span style="color: #28a745;">系统运行正常</span>
            </div>

            <div class="status-grid">
                <div class="status-item">
                    <div class="status-label">设备名称</div>
                    <div class="status-value"><?php echo htmlspecialchars($routerData['device_name']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">固件版本</div>
                    <div class="status-value"><?php echo htmlspecialchars($routerData['firmware_version']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">MAC地址</div>
                    <div class="status-value"><?php echo htmlspecialchars($routerData['mac_address']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">运行时间</div>
                    <div class="status-value"><?php echo htmlspecialchars($routerData['uptime']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">在线设备数</div>
                    <div class="status-value"><?php echo htmlspecialchars($routerData['online_devices']); ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">WAN状态</div>
                    <div class="status-value status-connected">
                        <?php echo htmlspecialchars($routerData['wan_status']); ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">LAN状态</div>
                    <div class="status-value status-connected">
                        <?php echo htmlspecialchars($routerData['lan_status']); ?>
                    </div>
                </div>
            </div>

            <div class="config-section">
                <h4><i class="fa fa-key"></i> 系统配置</h4>
                <div class="config-item highlight">
                    <span class="config-label"><i class="fa fa-lock"></i> 通关密码</span>
                    <span class="config-value"><?php echo htmlspecialchars($config['passcode']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <br>

    <div class="tech-card">
        <div class="tech-card-header">
            <h3><i class="fa fa-trophy"></i> 通关验证</h3>
        </div>
        <div class="tech-card-body">
            <form id="passcodeForm" class="tech-form">
                <div class="form-group">
                    <label class="form-label" for="passcode">
                        <i class="fa fa-key"></i> 通关密码
                    </label>
                    <input type="text" id="passcode" name="passcode" class="tech-input" placeholder="请输入通关密码"
                        autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-check"></i> 提交
                    </button>
                    <a href="<?php echo htmlspecialchars($nextPage); ?>" id="nextLevelBtn"
                        class="tech-btn tech-btn-success" style="display: none;">
                        <i class="fa fa-arrow-right"></i> <?php echo htmlspecialchars($nextBtnText); ?>
                    </a>
                </div>
                <div id="passcodeResultArea" class="result-area"></div>
            </form>
        </div>
    </div>
</div>

<script src="../../js/noauth.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initNoauth(2, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php require_once $commonBasePath . 'includes/footer.php'; ?>