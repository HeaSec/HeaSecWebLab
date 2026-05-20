<?php
/**
 * HeaSec天积安全团队 - 未授权访问靶场 - 第三关单页面应用
 * 版本: v1.0.0
 * 创建日期: 2026-03-05
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 此页面为第三关单页面应用
 */

header('X-HeavenlySecret: HeaSec 未授权访问 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

$pageTitle = '未授权访问靶场 - 第三关';
$rangeName = '未授权访问';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';
$commonBasePath = '../../../../common/';
$initSqlFile = __DIR__ . '/../database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;
$currentLevel = 3;

define('HEASEC_RANGE_ACCESS', true);

require_once $commonBasePath . 'includes/session_manager.php';
require_once $commonBasePath . 'includes/HeaSec_Database.php';
require_once __DIR__ . '/../includes/config-init.php';

HeaSec_InitRangeSession('noauth');

$pdo = HeaSec_Database::getConnection('heasec_logic');
$config = initNoauthLevelConfig($currentLevel, $pdo);

// 检查登录状态
$isLoggedIn = isset($_SESSION['noauth_level3_logged_in']) && $_SESSION['noauth_level3_logged_in'] === true;

require_once $commonBasePath . 'includes/header.php';
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="../css/style.css">
<!-- 引入恭喜弹窗样式 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>components/star-system/css/heasec-congrats-modal.css">

<div class="tech-container">
    <!-- 登录表单 -->
    <div id="loginSection" class="tech-card" <?php echo $isLoggedIn ? 'style="display: none;"' : ''; ?>>
        <div class="tech-card-header">
            <h3><i class="fa fa-wifi"></i> HeaSec Router - 第三关</h3>
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

    <!-- 管理面板 -->
    <div id="adminSection" <?php echo !$isLoggedIn ? 'style="display: none;"' : ''; ?>>
        <div class="tech-card">
            <div class="tech-card-header">
                <h3><i class="fa fa-cog"></i> 路由器状态</h3>
                <div class="header-actions">
                    <button id="logoutBtn" class="tech-btn tech-btn-outline"
                        style="padding: 4px 12px; font-size: 12px; height: 100%;">
                        <i class="fa fa-sign-out"></i> 退出登录
                    </button>
                </div>
            </div>
            <div class="tech-card-body">
                <div id="configLoading" style="text-align: center; padding: 40px;">
                    <i class="fa fa-spinner fa-spin" style="font-size: 32px; color: #16537e;"></i>
                    <p style="color: #888; margin-top: 15px;">加载配置中...</p>
                </div>
                <div id="configDisplay" style="display: none;"></div>
            </div>
        </div>
    </div>

    <br>

    <!-- 通关密码验证卡片 -->
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
                </div>
                <div id="passcodeResultArea" class="result-area"></div>
            </form>
        </div>
    </div>
</div>

<!-- 配置数据（必须在noauth.js之前加载） -->
<script>
    // 将配置存储在全局对象中（apiPath已编码，需分析JS解码）
    window._0xConfig = {
        _0xEnc: '<?php echo base64_encode($config['random_path']); ?>',
        commonBasePath: '<?php echo $commonBasePath; ?>',
        isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>
    };
</script>
<!-- 混淆后的JavaScript代码 -->
<script src="js/noauth.js?v=<?php echo $version; ?>"></script>
<!-- 引入恭喜弹窗脚本 -->
<script
    src="<?php echo $commonBasePath; ?>components/star-system/js/heasec-congrats-modal.js?v=<?php echo $version; ?>"></script>

<?php require_once $commonBasePath . 'includes/footer.php'; ?>