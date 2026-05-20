<?php
/**
 * HeaSec天积安全团队 - 异常数据处理靶场 - 第一关
 * 版本: v1.0.0
 * 创建日期: 2026-03-18
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 异常数据 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '异常数据处理靶场 - 第一关';
$rangeName = '异常数据';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 当前关卡配置
$currentLevel = 1;
$levelTitle = '第一关：四舍五入';
$taskHint = '目标：银行卡余额达到15元（如果提现小于1分钱会怎么样呢？）';
$nextPage = 'level2.php';
$nextBtnText = '下一关';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话（基于路径的隔离）
HeaSec_InitRangeSession('anomdata');

// 验证会话完整性
HeaSec_ValidateSession();

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入星星系统组件（恭喜弹窗）
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['congrats' => true]);

// 引入数据库组件
require_once $commonBasePath . 'includes/HeaSec_Database.php';

// 引入公共函数
require_once 'includes/functions.php';

// 获取数据库连接
$pdo = HeaSec_Database::getConnection('heasec_logic');

// 获取通关密码（如果存在）
$passcode = getPasscode($currentLevel);

// 检查是否已登录
$isLoggedIn = false;
$userData = null;

$sessionUserId = isset($_SESSION['anomdata_user_id_level' . $currentLevel]) ? $_SESSION['anomdata_user_id_level' . $currentLevel] : null;
if ($sessionUserId) {
    $user = getUserById($sessionUserId, $currentLevel, $pdo);
    if ($user) {
        $isLoggedIn = true;
        $userData = [
            'username' => $user['username'],
            'alipayBalance' => floatval($user['alipay_balance']),
            'bankBalance' => floatval($user['bank_balance'])
        ];
        // 检查银行卡余额是否达到15元
        if ($user['bank_balance'] >= 15) {
            $existingPasscode = getPasscode($currentLevel);
            if (!$existingPasscode) {
                $existingPasscode = generatePasscode($currentLevel);
            }
            $passcode = $existingPasscode;
        }
    }
}
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
                <button type="button" class="tech-btn tech-btn-secondary header-logout-btn" id="logoutBtn" style="display: none;">
                    <i class="fa fa-sign-out"></i> 退出登录
                </button>
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
                    <small><?php echo htmlspecialchars($taskHint); ?></small>
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
                    <div class="balance-section">
                        <div class="balance-item">
                            <span class="balance-label"><i class="fa fa-credit-card"></i> 支付宝余额</span>
                            <span class="balance-value" id="displayAlipayBalance">¥0.000</span>
                        </div>
                        <div class="balance-item">
                            <span class="balance-label"><i class="fa fa-university"></i> 银行卡余额</span>
                            <span class="balance-value highlight" id="displayBankBalance">¥0.00</span>
                        </div>
                    </div>
                </div>

                <!-- 提现区域 -->
                <div class="function-section">
                    <h4><i class="fa fa-money"></i> 从支付宝提现到银行卡</h4>
                    <div class="withdraw-form">
                        <div class="form-group">
                            <label class="form-label">提现金额（单位：元）</label>
                            <div class="input-with-btn">
                                <input type="number" id="withdrawAmount" class="tech-input" placeholder="请输入提现金额" step="0.001" min="0.001" max="100">
                                <button type="button" class="tech-btn tech-btn-success" id="withdrawBtn">
                                    <i class="fa fa-arrow-right"></i> 提现
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 交易记录区域 -->
                <div class="transactions-section">
                    <h4>
                        <span><i class="fa fa-list-alt"></i> 交易记录</span>
                        <span id="refreshTransactionsBtn" class="refresh-btn"><i class="fa fa-refresh"></i> 刷新</span>
                    </h4>
                    <div id="transactionsList" class="transactions-list">
                        <div class="transactions-empty"><i class="fa fa-inbox"></i> 暂无交易记录</div>
                    </div>
                </div>

                <!-- 通关密码区域 -->
                <div id="passcodeArea" class="passcode-section" style="display: none;">
                    <i class="fa fa-trophy"></i>
                    <div class="passcode-content">
                        <span class="passcode-label">恭喜！通关密码：</span>
                        <span class="passcode-value" id="displayPasscode"></span>
                    </div>
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
<script src="js/anomdata.js?v=<?php echo $version; ?>"></script>
<script>
    // 初始化第一关
    document.addEventListener('DOMContentLoaded', function() {
        initAnomdata(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
        <?php if ($isLoggedIn && $userData): ?>
        // 已登录，初始化用户信息显示
        displayUserInfoFromServer({
            username: '<?php echo addslashes($userData['username']); ?>',
            alipayBalance: <?php echo $userData['alipayBalance']; ?>,
            bankBalance: <?php echo $userData['bankBalance']; ?>,
            passcode: <?php echo $passcode ? json_encode($passcode) : 'null'; ?>
        });
        <?php endif; ?>
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
