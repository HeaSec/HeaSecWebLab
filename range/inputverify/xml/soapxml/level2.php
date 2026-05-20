<?php
/**
 * HeaSec天积安全团队 - SOAP与XML安全靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec SOAPXML Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

$pageTitle = 'SOAP与XML安全 - 第二关';
$rangeName = 'SOAP与XML安全';
$showVersion = false;
$showResetButton = true;
$resetUrl = 'api/reset.php';
$version = 'v1.0.0';

$commonBasePath = '../../../common/';

$currentLevel = 2;
$nextPage = 'level3.php';
$nextBtnText = '下一关';
$levelTitle = '第二关：什么是万能的？';

require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_SessionManager::autoInitRangeSession('soapxml');

require_once __DIR__ . '/includes/functions.php';

$secretPath = getSecretFilePath($currentLevel);
generateSecretFile($secretPath);

ensureDataFile($currentLevel);

$isLoggedIn = isset($_SESSION['soapxml_level2_user']);

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
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>天积云服务平台内部鉴权节点。系统中存在管理员账号admin，密码为动态生成。</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>任务目标</strong>
                </div>
                <span><small>登录admin账号获取通关密码。</small></span>
            </div>

            <!-- 表单区域 -->
            <div id="formSection" <?php echo $isLoggedIn ? 'style="display:none;"' : ''; ?>>
                <!-- 登录表单 -->
                <form id="loginFormLevel2" class="tech-form">
                    <div class="form-group">
                        <label for="login-username-l2" class="form-label">
                            <i class="fa fa-user"></i> 用户名
                        </label>
                        <input type="text" id="login-username-l2" name="username" class="tech-input" placeholder="请输入用户名" value="admin" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="login-password-l2" class="form-label">
                            <i class="fa fa-lock"></i> 密码
                        </label>
                        <input type="password" id="login-password-l2" name="password" class="tech-input" placeholder="请输入密码" autocomplete="off">
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="tech-btn tech-btn-primary">
                            <i class="fa fa-sign-in"></i> 登录
                        </button>
                    </div>
                </form>
            </div>

            <!-- 操作结果区域 -->
            <div id="resultArea" style="display: none;"></div>

            <!-- 用户信息区域 -->
            <div id="userInfo" style="<?php echo $isLoggedIn ? 'display:block;' : 'display:none;'; ?>">
                <div class="user-info">
                    <div class="info-row">
                        <span class="info-label"><i class="fa fa-user"></i> 当前用户：</span>
                        <span class="info-value" id="displayUsername"><?php echo $isLoggedIn ? htmlspecialchars($_SESSION['soapxml_level2_user']['username']) : ''; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fa fa-id-badge"></i> 角色：</span>
                        <span class="info-value" id="displayRole"><?php echo $isLoggedIn ? ($_SESSION['soapxml_level2_user']['role'] === 'admin' ? '管理员' : '普通用户') : ''; ?></span>
                    </div>
                </div>

                <!-- 通关密码显示区域 -->
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
