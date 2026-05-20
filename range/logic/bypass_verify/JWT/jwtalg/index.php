<?php
/**
 * HeaSec天积安全团队 - JWT签名算法绕过靶场 - 第一关
 * 版本: v1.0.0
 * 创建日期: 2026-03-02
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JWT签名算法绕过 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'JWT签名算法绕过靶场 - 第一关';
$rangeName = 'JWT签名算法绕过';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 当前关卡
$currentLevel = 1;
$levelTitle = '第一关';
$nextPage = 'level2.php';
$nextBtnText = '下一关';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

/**
 * 生成随机密码
 * @param int $length
 * @return string
 */
function generateRandomPassword($length = 10)
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

/**
 * 生成通关密码
 * @return string
 */
function generatePasscode()
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $passcode = '';
    for ($i = 0; $i < 20; $i++) {
        $passcode .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $passcode;
}

/**
 * 检查并创建用户账号
 * @param int $level
 * @param PDO $pdo
 */
function ensureUsersExist($level, $pdo)
{
    // 检查test账号是否存在
    $stmt = $pdo->prepare("SELECT * FROM heasec_jwtalg_users WHERE level = ? AND username = 'test'");
    $stmt->execute([$level]);
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$testUser) {
        // 创建test账号
        $stmt = $pdo->prepare("INSERT INTO heasec_jwtalg_users (level, username, password, role) VALUES (?, 'test', '123456', 'user')");
        $stmt->execute([$level]);
    }

    // 检查admin账号是否存在
    $stmt = $pdo->prepare("SELECT * FROM heasec_jwtalg_users WHERE level = ? AND username = 'admin'");
    $stmt->execute([$level]);
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adminUser) {
        // 创建admin账号
        $password = generateRandomPassword(10);
        $passcode = generatePasscode();
        $stmt = $pdo->prepare("INSERT INTO heasec_jwtalg_users (level, username, password, role, passcode) VALUES (?, 'admin', ?, 'admin', ?)");
        $stmt->execute([$level, $password, $passcode]);
    }
}

// 获取数据库连接并确保用户存在
try {
    $pdo = HeaSec_Database::getConnection('heasec_logic');
    ensureUsersExist($currentLevel, $pdo);
} catch (Exception $e) {
    error_log('[HeaSec] Database error: ' . $e->getMessage());
}
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
                <i class="fa fa-shield"></i>
                <?php echo htmlspecialchars($levelTitle); ?>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 提示信息 -->
            <div class="alert alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>任务提示</strong>
                </div>
                <span class="alert-hint">
                    <small>尝试通过伪造JWT Token获取管理员权限</small>
                </span>
            </div>

            <!-- 开发人员提示 -->
            <div class="alert-dev-tip">
                <i class="fa fa-user-secret"></i>
                <span>开发人员ps：反正我做了算法签名，密钥是随机生成的，很安全！</span>
            </div>

            <!-- 登录表单 -->
            <form id="loginForm" class="tech-form">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fa fa-user"></i> 账号
                    </label>
                    <input type="text" id="username" name="username" class="tech-input" placeholder="请输入账号" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fa fa-lock"></i> 密码
                    </label>
                    <input type="password" id="password" name="password" class="tech-input" placeholder="请输入密码" autocomplete="off">
                </div>
                <div id="loginErrorArea" class="alert-error" style="display: none; margin-bottom: 15px;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <span id="loginErrorMsg"></span>
                </div>
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i> 登录
                    </button>
                </div>
            </form>

            <!-- 用户信息区域（登录后显示） -->
            <div id="userInfoArea" style="display: none;">
                <div class="user-info">
                    <div class="info-row">
                        <span class="info-label"><i class="fa fa-user"></i> 账号：</span>
                        <span class="info-value" id="displayUsername"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fa fa-id-badge"></i> 角色：</span>
                        <span class="info-value" id="displayRole"></span>
                    </div>
                    <div id="passcodeArea" class="info-row highlight" style="display: none;">
                        <span class="info-label"><i class="fa fa-trophy"></i> 通关密码：</span>
                        <span class="info-value passcode" id="displayPasscode"></span>
                    </div>
                </div>
                <div id="userHintArea" class="alert-info" style="display: none;">
                    <i class="fa fa-info-circle"></i>
                    <span>你当前是以普通用户身份登录，尝试获取管理员权限</span>
                </div>
                <div class="form-actions">
                    <button type="button" class="tech-btn tech-btn-danger" id="logoutBtn">
                        <i class="fa fa-sign-out"></i> 退出登录
                    </button>
                </div>
            </div>

            <!-- 测试账号提示 -->
            <div class="test-account-hint" style="margin-top: 20px; text-align: center; color: #6c757d; font-size: 13px;">
                <i class="fa fa-info-circle"></i> 测试账号：test / 123456
            </div>
        </div>
    </div>

    <br>

    <!-- 通关密码验证卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-trophy"></i> 通关验证
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

<!-- 引入交互脚本 -->
<script src="js/jwtalg.js?v=<?php echo $version; ?>"></script>
<script>
    // 初始化第一关
    document.addEventListener('DOMContentLoaded', function() {
        initJwtAlg(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
