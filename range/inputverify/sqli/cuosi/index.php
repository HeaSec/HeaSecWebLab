<?php
/**
 * HeaSec天积安全团队 - SQL不同语句注入靶场 - 第一关
 * 版本: v1.0.0
 * 功能: UPDATE注入+报错注入 — 用户中心（密码修改+登录）
 * 团队: 天积安全 (HeavenlySecret)
 */

header('X-HeavenlySecret: HeaSec Cuosi Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 页面变量
$pageTitle = 'SQL不同语句注入靶场 - 第一关';
$rangeName = 'SQL不同语句注入';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 数据库配置
$initSqlFile = __DIR__ . '/database/init_database.sql';
$databaseName = 'heasec_sqli';

// 公共组件路径
$commonBasePath = '../../../common/';

// 自定义重置处理：重置时删除所有密码配置文件
if (isset($_GET['action']) && in_array($_GET['action'], ['reset', 'init']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $passFiles = ['secret.php', 'secret2.php', 'secret3.php'];
    foreach ($passFiles as $file) {
        $f = __DIR__ . '/config/' . $file;
        if (file_exists($f)) {
            @unlink($f);
        }
    }
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 会话管理
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('cuosi');

// 星星系统
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入靶场函数
require_once 'includes/functions.php';

// 当前关卡配置
$currentLevel = 1;
$nextPage = 'level2.php';
$nextBtnText = '下一关';

// 确保当前关卡的密码已生成
ensurePasswordExists($currentLevel);

// 检查登录状态（session已由session_manager启动）
$isLoggedIn = isset($_SESSION['cuosi_user_id']);
$currentUsername = $_SESSION['cuosi_username'] ?? '';
$currentRole = $_SESSION['cuosi_role'] ?? '';
$isAdmin = ($currentRole === 'admin');

// 如果已登录且是管理员，获取通关密码
$adminPasscode = '';
if ($isAdmin) {
    $passcode = getPasscode(1);
    $adminPasscode = ($passcode !== false) ? $passcode : '';
}
?>

<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<link rel="stylesheet" href="css/style.css">

<div class="tech-container">
    <!-- 卡片一：用户中心 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user-circle"></i>
                <span>天积社区 — 用户中心</span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 安全防护提示 -->
            <div class="alert-info">
                <i class="fa fa-shield"></i>
                <span>天积社区平台 — 用户中心。管理您的个人信息，修改登录密码</span>
            </div>

            <!-- 任务提示 -->
            <div class="alert-warning">
                <div>
                    <strong>任务目标：</strong>
                    <small>获取管理员权限，查看通关密码。提示：系统在处理密码更新请求时可能存在安全隐患，当你的账户权限足够高……可能看到不一样的东西</small>
                </div>
            </div>

            <!-- 用户信息区 -->
            <?php if ($isLoggedIn): ?>
            <div id="userInfoArea" class="alert-info" style="margin-bottom: 15px;">
                <i class="fa fa-user"></i>
                <span>当前用户：<strong><?php echo htmlspecialchars($currentUsername); ?></strong>（角色：<?php echo $isAdmin ? '管理员' : '普通用户'; ?>）</span>
                <button type="button" id="logoutBtn" class="tech-btn tech-btn-secondary" style="margin-left: auto; padding: 4px 12px; font-size: 13px;">
                    <i class="fa fa-sign-out"></i> 退出
                </button>
            </div>
            <?php endif; ?>

            <!-- 管理员面板（仅admin可见） -->
            <?php if ($isAdmin && $adminPasscode): ?>
            <div id="adminPanel" class="alert-success" style="margin-bottom: 15px;">
                <i class="fa fa-star"></i>
                <span>管理员面板 — 通关密码：<strong style="font-family: monospace; letter-spacing: 1px;"><?php echo htmlspecialchars($adminPasscode); ?></strong></span>
            </div>
            <?php endif; ?>

            <!-- 登录表单（未登录时显示） -->
            <?php if (!$isLoggedIn): ?>
            <div class="submit-section">
                <form id="loginForm" class="query-form">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fa fa-user"></i> 用户名
                        </label>
                        <input type="text" id="username" name="username" class="tech-input" placeholder="请输入用户名" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fa fa-lock"></i> 密码
                        </label>
                        <input type="text" id="password" name="password" class="tech-input" placeholder="请输入密码" autocomplete="off">
                    </div>
                    <div class="form-actions">
                        <button type="submit" id="loginBtn" class="tech-btn tech-btn-primary">
                            <i class="fa fa-sign-in"></i> 登录
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- 密码修改表单（已登录且非管理员时显示） -->
            <?php if ($isLoggedIn && !$isAdmin): ?>
            <div class="submit-section">
                <form id="changePwdForm" class="query-form">
                    <div class="form-group">
                        <label for="oldPassword" class="form-label">
                            <i class="fa fa-lock"></i> 原密码
                        </label>
                        <input type="text" id="oldPassword" name="old_password" class="tech-input" placeholder="请输入原密码" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="newPassword" class="form-label">
                            <i class="fa fa-key"></i> 新密码
                        </label>
                        <input type="text" id="newPassword" name="new_password" class="tech-input" placeholder="请输入新密码" autocomplete="off">
                    </div>
                    <div class="form-actions">
                        <button type="submit" id="changePwdBtn" class="tech-btn tech-btn-primary">
                            <i class="fa fa-save"></i> 修改密码
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            <!-- 操作结果区域 -->
            <div id="userResultArea" style="display: none;"></div>
        </div>
    </div>

    <br>

    <!-- 卡片二：通关验证 -->
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

<script src="js/cuosi.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initCuosi(<?php echo $currentLevel; ?>, <?php echo json_encode($commonBasePath); ?>);
    });
</script>

<?php
require_once $commonBasePath . 'includes/footer.php';
?>
