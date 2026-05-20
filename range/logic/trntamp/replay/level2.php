<?php
/**
 * HeaSec天积安全团队 - 重放攻击靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2026-03-12
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 重放攻击 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '重放攻击靶场 - 第二关';
$rangeName = '重放攻击';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 当前关卡配置
$currentLevel = 2;
$levelTitle = '第二关：今夕是何夕';
$signinHint = '每天只能签到一次，随机获得1-50元红包';
$nextPage = 'level3.php';
$nextBtnText = '下一关';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('replay');

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入公共函数
require_once 'includes/functions.php';

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_logic');

// 确保用户存在
function ensureUserExistsLevel2($level, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM heasec_replay_users WHERE level = ? AND username = 'heasec'");
    $stmt->execute([$level]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO heasec_replay_users (level, username, password, balance) VALUES (?, 'heasec', '123456', 0.00)");
        $stmt->execute([$level]);
    }
}

ensureUserExistsLevel2($currentLevel, $pdo);

// 获取通关密码（如果存在）
$passcode = getPasscode($currentLevel);
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- 用户登录/信息卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user"></i>
                <span id="mainCardTitle"><?php echo $isLoggedIn ? '天积商城' : $levelTitle; ?></span>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 任务提示 -->
            <div class="alert alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>任务提示</strong>
                </div>
                <span class="alert-hint">
                    <small>通过签到领取红包，余额达到500元即可获得通关密码</small>
                </span>
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
                        <span class="info-label"><i class="fa fa-money"></i> 余额：</span>
                        <span class="info-value balance" id="displayBalance">0.00</span>
                    </div>
                    <div id="passcodeArea" class="info-row highlight" style="display: none;">
                        <span class="info-label"><i class="fa fa-trophy"></i> 通关密码：</span>
                        <span class="info-value passcode" id="displayPasscode"></span>
                    </div>
                </div>
                <div id="passcodeHint" class="alert-info" style="display: flex;">
                    <i class="fa fa-info-circle"></i>
                    <span>余额达到500元后显示通关密码</span>
                </div>

                <!-- 签到区域 -->
                <div class="signin-section">
                    <h4><i class="fa fa-gift"></i> 签到领红包</h4>
                    <p style="color: #6c757d; font-size: 14px; margin-bottom: 15px;"><?php echo htmlspecialchars($signinHint); ?></p>
                    <div class="signin-info">
                        <button type="button" class="tech-btn tech-btn-success" id="signinBtn">
                            <i class="fa fa-gift"></i> 签到领红包
                        </button>
                        <span id="signinStatus" class="signin-status">
                            <i class="fa fa-info-circle"></i> 点击按钮签到
                        </span>
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 20px;">
                    <button type="button" class="tech-btn tech-btn-danger" id="logoutBtn">
                        <i class="fa fa-sign-out"></i> 退出登录
                    </button>
                </div>
            </div>

            <!-- 测试账号提示 -->
            <div class="test-account-hint">
                <i class="fa fa-info-circle"></i> 测试账号：heasec / 123456
            </div>
        </div>
    </div>

    <br>

    <!-- 通关验证卡片 -->
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
<script src="js/replay.js?v=<?php echo $version; ?>"></script>
<script>
    // 初始化第二关
    document.addEventListener('DOMContentLoaded', function() {
        initReplay(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
