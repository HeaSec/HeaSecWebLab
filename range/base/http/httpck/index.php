<?php
/**
 * HeaSec天积安全团队 - HTTP Cookie操作靶场
 * 版本: v1.0.0
 * 创建日期: 2025-11-06
 * 团队: 天积安全 (HeavenlySecret)
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 定义HeaSec常量
define('HEASEC_COOKIE_EXPIRY', 3600); // Cookie过期时间（1小时）
define('HEASEC_SECRET_LENGTH', 20); // 秘密字符串长度

// 设置响应头
header('X-HeavenlySecret: HeaSec HTTP Cookie操作 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');
header('Date: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// 启动输出缓冲
ob_start();

// 设置页面变量
$pageTitle = 'HTTP Cookie 靶场';
$rangeName = 'HTTP Cookie 靶场';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话（基于路径的隔离）
HeaSec_InitRangeSession('httpck');

// 验证会话完整性
HeaSec_ValidateSession();

/**
 * 获取或生成会话中的秘密字符串
 * 使用新的会话管理组件
 */
function getSecret()
{
    return HeaSec_GetSecret(HEASEC_SECRET_LENGTH);
}

/**
 * 自动设置初始Cookie（使用路径隔离）
 */
function setAutoCookies()
{
    // 如果Cookie中没有username和sex字段，则设置它们
    if (!isset($_COOKIE['username']) || !isset($_COOKIE['sex'])) {
        // 使用会话管理组件设置安全的Cookie（自动路径隔离）
        HeaSec_SessionManager::setSecureCookie('username', 'lilei', HEASEC_COOKIE_EXPIRY);
        HeaSec_SessionManager::setSecureCookie('sex', 'male', HEASEC_COOKIE_EXPIRY);

        // 刷新页面以使Cookie生效
        if (!isset($_GET['cookies_set'])) {
            header('Location: ?cookies_set=1');
            exit;
        }
    }
}

/**
 * 验证Cookie条件
 */
function validateCookieConditions()
{
    $targetCookies = [
        'username' => 'hanmeimei',
        'sex' => 'female',
        'age' => '18'
    ];

    $allPassed = true;
    $errorMessage = '';

    foreach ($targetCookies as $cookieName => $expectedValue) {
        if (!isset($_COOKIE[$cookieName])) {
            $allPassed = false;
            $errorMessage = "请告我您的" . getCookieDisplayName($cookieName) . "，才能获取我的秘密";
            break;
        } elseif ($_COOKIE[$cookieName] !== $expectedValue) {
            $allPassed = false;
            $errorMessage = getCookieErrorMessage($cookieName);
            break;
        }
    }

    return ['allPassed' => $allPassed, 'errorMessage' => $errorMessage];
}

/**
 * 获取Cookie显示名称
 */
function getCookieDisplayName($cookieName)
{
    $names = [
        'username' => '姓名',
        'sex' => '性别',
        'age' => '年龄'
    ];
    return isset($names[$cookieName]) ? $names[$cookieName] : $cookieName;
}

/**
 * 获取Cookie错误消息
 */
function getCookieErrorMessage($cookieName)
{
    switch ($cookieName) {
        case 'username':
            return '您不是韩梅梅，无法获取我的秘密';
        case 'sex':
            return '您的性别错误，无法获取我的秘密';
        case 'age':
            return '您的年龄错误，无法获取我的秘密（我只告诉刚刚成年的人）';
        default:
            return $cookieName . '值不正确';
    }
}


// 自动设置初始Cookie
setAutoCookies();

// 获取或生成秘密字符串
$secret = getSecret();

// 验证Cookie条件
$cookieValidation = validateCookieConditions();

// 如果所有条件都满足，在响应头中设置秘密信息
if ($cookieValidation['allPassed']) {
    header('Heasec-Secret-Info: ' . $secret);
}


// 根据验证结果设置显示消息
$displayMessage = '';
$alertType = 'warning';

if ($cookieValidation['allPassed']) {
    $displayMessage = '您发现了一个秘密，但是它在哪里呢？';
    $alertType = 'success';
} else {
    $displayMessage = $cookieValidation['errorMessage'];
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件（用于覆盖和扩展） -->
<link rel="stylesheet" href="css/style.css">

<!-- 星星系统组件资源已由secret_card组件自动引入 -->

<!-- 引入秘密验证卡片组件JavaScript -->
<script src="<?php echo $commonBasePath; ?>components/secret-card/js/secret-card.js?v=<?php echo $version; ?>"></script>

<!-- 引入交互脚本 -->
<script src="js/interactions.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- HTTP Cookie检测区域 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-user-secret"></i>
                请找到我的秘密
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 显示检测消息 -->
            <div class="detection-result">
                <div class="alert alert-<?php echo $alertType; ?>">
                    <div>
                        <i
                            class="fa fa-<?php echo $alertType === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                        <strong><?php echo htmlspecialchars($displayMessage); ?></strong>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- 引入秘密验证卡片公共组件 -->
    <?php
    require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
    // 配置秘密验证卡片组件
    $secretCardConfig = [
        'cardTitle' => '秘密验证',
        'cardIcon' => 'fa fa-key',
        'inputLabel' => '输入你发现的秘密',
        'inputPlaceholder' => '请输入20位的秘密字符串',
        'maxLength' => 20,
        'helpText' => '秘密格式：20位字母和数字组合（例如：AbCd1234EfGh5678IjKl）',
        'successMessage' => '验证成功，恭喜你发现了秘密！',
        'successHint' => 'cookie通常用于存储用户会话信息、身份信息、个人偏好设置等',
        'errorMessage' => '验证失败，这不是我的秘密！',
        'emptyMessage' => '请输入秘密',
        'invalidLengthMessage' => '请输入20位的秘密字符串',
        'invalidFormatMessage' => '秘密格式不正确，请输入20位字母和数字组合',
        'secretValue' => $secret,
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你理解了Cookie请求头的基本作用',
        'rangeCode' => 'httpck'
    ];
    echo renderSecretCard($secretCardConfig);
    ?>

</div>


<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>