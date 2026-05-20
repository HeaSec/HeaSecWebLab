<?php
/**
 * HeaSec天积安全团队 - 三方支付漏洞靶场 - 第二关
 * 版本: v1.0.0
 * 创建日期: 2026-03-19
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 三方支付漏洞 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '三方支付漏洞靶场 - 第二关';
$rangeName = '三方支付漏洞';
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
$levelTitle = '第二关：我付过钱了';
$taskHint = '目标：购买1个天积元宝（你听说过callback吗？）';
$nextPage = 'level3.php';
$nextBtnText = '下一关';
$payPage = 'pay2.php';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('3rdpay');

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

// 检查是否已登录
$isLoggedIn = false;
$userData = null;
$productData = null;
$passcode = null;
$hasPaidOrder = false;

$sessionUserId = isset($_SESSION['3rdpay_user_id_level' . $currentLevel]) ? $_SESSION['3rdpay_user_id_level' . $currentLevel] : null;
if ($sessionUserId) {
    $user = getUserById($sessionUserId, $currentLevel, $pdo);
    if ($user) {
        $isLoggedIn = true;
        $userData = [
            'username' => $user['username']
        ];
        $products = getProducts($currentLevel, $pdo);
        $productData = !empty($products) ? $products[0] : null;

        // 初始化用户通关密码（首次访问时生成）
        initUserPasscode($user['id'], $currentLevel, $pdo);

        // 检查是否满足通关条件（有已支付订单）
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM heasec_3rdpay_orders WHERE user_id = ? AND level = ? AND status = 'paid'");
        $stmt->execute([$user['id'], $currentLevel]);
        $hasPaidOrder = $stmt->fetchColumn() > 0;

        // 如果满足条件，显示通关密码
        if ($hasPaidOrder) {
            $passcode = getUserPasscode($user['id'], $currentLevel, $pdo);
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
    <!-- 电商平台卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-shopping-cart"></i>
                <span id="mainCardTitle"><?php echo $isLoggedIn ? '天积商城' : $levelTitle; ?></span>
            </h3>
            <button type="button" class="header-logout-btn" id="logoutBtn" style="display: none;">
                <i class="fa fa-sign-out"></i> 退出
            </button>
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
            <form id="loginForm" class="tech-form" <?php echo $isLoggedIn ? 'style="display:none;"' : ''; ?>>
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
            <div id="userInfoArea" <?php echo !$isLoggedIn ? 'style="display: none;"' : ''; ?>>
                <div class="user-info">
                    <div class="info-row">
                        <span class="info-label"><i class="fa fa-user"></i> 账号：</span>
                        <span class="info-value" id="displayUsername"><?php echo $isLoggedIn ? htmlspecialchars($userData['username']) : ''; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><i class="fa fa-shopping-bag"></i> 购买状态：</span>
                        <span class="info-value purchase-status" id="displayPurchaseStatus">
                            <?php if ($hasPaidOrder): ?>
                                <span class="status-purchased"><i class="fa fa-check-circle"></i> 已购买</span>
                            <?php else: ?>
                                <span class="status-not-purchased"><i class="fa fa-clock-o"></i> 未购买</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- 商品展示区域 -->
                <div class="product-section">
                    <h4><i class="fa fa-shopping-bag"></i> 商品列表</h4>
                    <div id="productArea" class="product-display">
                        <?php if ($productData): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <i class="fa fa-diamond"></i>
                            </div>
                            <div class="product-info">
                                <h5 class="product-name"><?php echo htmlspecialchars($productData['name']); ?></h5>
                                <p class="product-price">¥<?php echo number_format($productData['price'], 2); ?></p>
                            </div>
                            <div class="product-action">
                                <button type="button" class="tech-btn tech-btn-primary" id="buyBtn">
                                    <i class="fa fa-shopping-cart"></i> 购买
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 订单列表区域 -->
                <div class="orders-section">
                    <h4>
                        <span><i class="fa fa-list-alt"></i> 我的订单</span>
                        <span id="refreshOrdersBtn" class="refresh-btn"><i class="fa fa-refresh"></i> 刷新</span>
                    </h4>
                    <div id="ordersList" class="orders-list">
                        <div class="orders-empty"><i class="fa fa-inbox"></i> 暂无订单记录</div>
                    </div>
                </div>

                <!-- 通关密码区域 -->
                <div id="passcodeArea" class="passcode-section" <?php echo !$passcode ? 'style="display: none;"' : ''; ?>>
                    <div class="passcode-display">
                        <i class="fa fa-trophy"></i>
                        <span class="passcode-label">通关密码：</span>
                        <span class="passcode-value" id="displayPasscode"><?php echo $passcode ? htmlspecialchars($passcode) : ''; ?></span>
                    </div>
                </div>


            </div>

            <!-- 测试账号提示 -->
            <div class="test-account-hint">
                <i class="fa fa-info-circle"></i> 电商账号：heasec / 123456，天积宝账号：heasecpay / 支付密码666888
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
<script src="js/3rdpay.js?v=<?php echo $version; ?>"></script>
<script>
    // 天积宝支付配置（回调接口配置）
    var HeaSecPayConfig = {
        callbackUrl: 'api/level2/callback.php',
        secretKey: 'HeaSec_Pay_Secret_2026'
    };

    // 生成签名函数
    function generateCallbackSign(orderId, status, amount, timestamp) {
        return sha256(orderId + status + amount + timestamp + HeaSecPayConfig.secretKey);
    }

    // SHA256哈希函数（简化实现）
    function sha256(str) {
        // 使用Web Crypto API
        var buffer = new TextEncoder('utf-8').encode(str);
        return crypto.subtle.digest('SHA-256', buffer).then(function(hash) {
            var hexCodes = [];
            var view = new DataView(hash);
            for (var i = 0; i < view.byteLength; i += 4) {
                var value = view.getUint32(i);
                var stringValue = value.toString(16);
                var padding = '00000000';
                var paddedValue = (padding + stringValue).slice(-padding.length);
                hexCodes.push(paddedValue);
            }
            return hexCodes.join('');
        });
    }

    // 初始化第二关
    document.addEventListener('DOMContentLoaded', function() {
        init3rdpay(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>', '<?php echo $payPage; ?>');
        <?php if ($isLoggedIn && $userData && $productData): ?>
        displayUserInfoFromServer({
            username: '<?php echo addslashes($userData['username']); ?>',
            product: <?php echo json_encode($productData); ?>,
            passcode: <?php echo $passcode ? json_encode($passcode) : 'null'; ?>,
            hasPaidOrder: <?php echo $hasPaidOrder ? 'true' : 'false'; ?>
        });
        <?php endif; ?>
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
