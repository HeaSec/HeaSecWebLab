<?php
/**
 * HeaSec天积安全团队 - SOAP与XML安全靶场 - 第一关
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec SOAPXML Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

$pageTitle = 'SOAP与XML安全 - 第一关';
$rangeName = 'SOAP与XML安全';
$showVersion = false;
$showResetButton = true;
$resetUrl = 'api/reset.php';
$version = 'v1.0.0';

$commonBasePath = '../../../common/';

$currentLevel = 1;
$nextPage = 'level2.php';
$nextBtnText = '下一关';
$levelTitle = '第一关：管理员的role是admin';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_SessionManager::autoInitRangeSession('soapxml');

require_once __DIR__ . '/includes/functions.php';

$secretPath = getSecretFilePath($currentLevel);
generateSecretFile($secretPath);

ensureDataFile($currentLevel);

$isLoggedIn = isset($_SESSION['soapxml_level1_user']);

require_once $commonBasePath . 'includes/header.php';

require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">

<div class="tech-container">
    <!-- 卡片一 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-shield"></i>
                <span id="mainCardTitle"><?php echo $isLoggedIn ? '用户信息' : $levelTitle; ?></span>
            </h3>
            <button type="button" class="header-logout-btn" id="logoutBtn" style="<?php echo $isLoggedIn ? 'display:inline-flex;' : 'display:none;'; ?>">
                <i class="fa fa-sign-out"></i> 退出登录
            </button>
        </div>
        <div class="tech-card-body">
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>欢迎使用天积云服务平台，请注册账号或登录以使用服务。</span>
            </div>

            <div class="alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>任务目标</strong>
                </div>
                <span><small>以管理员身份登录系统获取通关密码。</small></span>
            </div>

            <div id="formSection" <?php echo $isLoggedIn ? 'style="display:none;"' : ''; ?>>
                <div class="tab-switch">
                    <button class="active" data-tab="register">注册</button>
                    <button data-tab="login">登录</button>
                </div>

                <form id="registerForm" class="tech-form">
                    <div class="form-group">
                        <label for="reg-username" class="form-label">
                            <i class="fa fa-user"></i> 用户名
                        </label>
                        <input type="text" id="reg-username" name="username" class="tech-input" placeholder="请输入用户名" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="reg-password" class="form-label">
                            <i class="fa fa-lock"></i> 密码
                        </label>
                        <input type="password" id="reg-password" name="password" class="tech-input" placeholder="请输入密码" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="reg-confirm-password" class="form-label">
                            <i class="fa fa-lock"></i> 确认密码
                        </label>
                        <input type="password" id="reg-confirm-password" name="confirm_password" class="tech-input" placeholder="请再次输入密码" autocomplete="off">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="tech-btn tech-btn-primary">
                            <i class="fa fa-user-plus"></i> 注册
                        </button>
                    </div>
                </form>

                <form id="loginFormLevel1" class="tech-form" style="display: none;">
                    <div class="form-group">
                        <label for="login-username-l1" class="form-label">
                            <i class="fa fa-user"></i> 用户名
                        </label>
                        <input type="text" id="login-username-l1" name="username" class="tech-input" placeholder="请输入用户名" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="login-password-l1" class="form-label">
                            <i class="fa fa-lock"></i> 密码
                        </label>
                        <input type="password" id="login-password-l1" name="password" class="tech-input" placeholder="请输入密码" autocomplete="off">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="tech-btn tech-btn-primary">
                            <i class="fa fa-sign-in"></i> 登录
                        </button>
                    </div>
                </form>
            </div>

            <div id="resultArea" style="display: none;"></div>

            <div id="userInfo" style="<?php echo $isLoggedIn ? 'display:block;' : 'display:none;'; ?>">
                <div class="user-info">
                    <div class="info-row">
                        <span class="info-label"><i class="fa fa-user"></i> 当前用户：</span>
                        <span class="info-value" id="displayUsername"><?php echo $isLoggedIn ? htmlspecialchars($_SESSION['soapxml_level1_user']['username']) : ''; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fa fa-id-badge"></i> 角色：</span>
                        <span class="info-value" id="displayRole"><?php echo $isLoggedIn ? ($_SESSION['soapxml_level1_user']['role'] === 'admin' ? '管理员' : '普通用户') : ''; ?></span>
                    </div>
                </div>

                <div id="passcodeDisplay" class="passcode-display" style="display: none;">
                    <i class="fa fa-key"></i>
                    <span class="passcode-label">通关密码：</span>
                    <span class="passcode-value" id="displayPasscode"></span>
                </div>
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
                    <a href="<?php echo htmlspecialchars($nextPage); ?>" id="nextLevelBtn" class="tech-btn tech-btn-success" style="display: none;">
                        <i class="fa fa-arrow-right"></i> <?php echo htmlspecialchars($nextBtnText); ?>
                    </a>
                </div>
                <div id="verifyResultArea" class="detection-result" style="display: none;"></div>
            </form>
        </div>
    </div>
</div>

<script src="js/soapxml.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initSoapXmlRange(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
