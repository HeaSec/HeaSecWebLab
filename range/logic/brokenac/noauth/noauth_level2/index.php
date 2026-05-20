<?php
/**
 * HeaSec天积安全团队 - 未授权访问靶场 - 第二关登录页面
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec 未授权访问 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

$pageTitle = '未授权访问靶场 - 第二关';
$rangeName = '未授权访问';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';
$commonBasePath = '../../../../common/';
$initSqlFile = __DIR__ . '/../database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;
$currentLevel = 2;
$nextPage = '../noauth_level3/index.php';
$nextBtnText = '第三关';

define('HEASEC_RANGE_ACCESS', true);

require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/config-init.php';

HeaSec_InitRangeSession('noauth');

$pdo = HeaSec_Database::getConnection('heasec_logic');
$config = initNoauthLevelConfig($currentLevel, $pdo);

// 处理退出登录
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['noauth_level2_logged_in']);
    unset($_SESSION['noauth_level2_user']);
    header('Location: index.php');
    exit;
}

// 检查登录状态
$isLoggedIn = isset($_SESSION['noauth_level2_logged_in']) && $_SESSION['noauth_level2_logged_in'] === true;

if ($isLoggedIn) {
    header('Location: ' . htmlspecialchars($config['random_path']) . '/');
    exit;
}

require_once $commonBasePath . 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="../css/style.css">

<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3><i class="fa fa-wifi"></i> HeaSec Router - 第二关</h3>
        </div>
        <div class="tech-card-body">
            <div class="alert alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>系统提示</strong>
                </div>
                <span class="alert-hint">
                    <small>本系统仅限管理员访问</small>
                </span>
            </div>

            <form id="loginForm" class="tech-form">
                <div class="form-group">
                    <label class="form-label" for="account">
                        <i class="fa fa-user"></i> 账号
                    </label>
                    <input type="text" id="account" name="account" class="tech-input" placeholder="请输入账号"
                        autocomplete="off">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fa fa-key"></i> 密码
                    </label>
                    <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码"
                        autocomplete="off">
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i> 登录
                    </button>
                </div>
                <div id="loginResultArea" class="result-area"></div>
            </form>
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

<script src="../js/noauth.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initNoauth(2, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php require_once $commonBasePath . 'includes/footer.php'; ?>