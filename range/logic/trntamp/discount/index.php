<?php
/**
 * HeaSec天积安全团队 - 优惠滥用靶场 - 第一关
 * 版本: v1.0.0
 * 创建日期: 2026-03-21
 * 团队: 天积安全 (HeavenlySecret)
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 优惠滥用 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '优惠滥用靶场 - 第一关';
$rangeName = '优惠滥用';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;
$resetUrl = 'api/reset.php';  // 使用靶场自定义的重置接口

// 当前关卡配置
$currentLevel = 1;
$levelTitle = '第一关：小孩子才做选择';
$taskHint = '购买1个天积元宝即可通关';
$nextPage = 'level2.php';
$nextBtnText = '下一关';

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话
HeaSec_InitRangeSession('discount');

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
$couponData = null;
$passcode = null;

$sessionUserId = isset($_SESSION['discount_user_id_level' . $currentLevel]) ? $_SESSION['discount_user_id_level' . $currentLevel] : null;
if ($sessionUserId) {
    $user = getUserById($sessionUserId, $currentLevel, $pdo);
    if ($user) {
        $isLoggedIn = true;
        $userData = [
            'username' => $user['username'],
            'balance' => $user['balance']
        ];
        $products = getProducts($currentLevel, $pdo);
        $productData = !empty($products) ? $products[0] : null;
        $couponData = getCoupons($currentLevel, $pdo);

        // 初始化通关密码（首次登录时生成）
        initUserPasscode($user['id'], $currentLevel, $pdo);

        // 检查是否满足通关条件，满足则展示通关密码
        if (checkLevel1PassCondition($user['id'], $currentLevel, $pdo)) {
            $passcode = getUserPasscode($user['id'], $currentLevel, $pdo);
        }
    }
}

// 如果未登录，也获取商品和优惠券数据用于展示
if (!$isLoggedIn) {
    $products = getProducts($currentLevel, $pdo);
    $productData = !empty($products) ? $products[0] : null;
    $couponData = getCoupons($currentLevel, $pdo);
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
            <button type="button" class="header-logout-btn" id="logoutBtn" style="<?php echo $isLoggedIn ? 'display:inline-flex;' : 'display:none;'; ?>">
                <i class="fa fa-sign-out"></i> 退出登录
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
                </div>

                <div id="passcodeArea" class="alert-success" style="display: none;">
                    <i class="fa fa-check-circle"></i>
                    <span>通关密码：<strong id="displayPasscode"></strong></span>
                </div>

                <!-- 商品展示区域 -->
                <div class="products-section">
                    <h4><i class="fa fa-shopping-bag"></i> 商品列表</h4>
                    <div id="productsList" class="products-list"></div>
                </div>

                <!-- 购物车区域 -->
                <div class="cart-section">
                    <h4><i class="fa fa-shopping-cart"></i> 购物车</h4>
                    <div id="cartEmpty" class="cart-empty">
                        <i class="fa fa-shopping-basket"></i> 购物车为空，请添加商品
                    </div>
                    <div id="cartItems" class="cart-items"></div>
                    <div id="cartSummary" class="cart-summary" style="display: none;">
                        <div class="cart-summary-row">
                            <span class="label">合计：</span>
                            <span class="value balance" id="cartTotal">¥0.00</span>
                        </div>
                        <div class="form-actions" style="margin-top: 15px;">
                            <button type="button" class="tech-btn tech-btn-success" id="submitOrderBtn">
                                <i class="fa fa-check"></i> 提交订单
                            </button>
                        </div>
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
<script src="js/discount.js?v=<?php echo $version; ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initDiscount(<?php echo $currentLevel; ?>, '<?php echo $commonBasePath; ?>');
        <?php if ($isLoggedIn && $userData): ?>
        displayUserInfoFromServer({
            username: '<?php echo addslashes($userData['username']); ?>',
            balance: <?php echo $userData['balance']; ?>,
            products: <?php echo json_encode($products); ?>,
            coupon: <?php echo json_encode($couponData); ?>,
            passcode: <?php echo $passcode ? json_encode($passcode) : 'null'; ?>
        });
        <?php endif; ?>
    });
</script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>
