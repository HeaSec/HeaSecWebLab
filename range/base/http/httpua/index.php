<?php
/**
 * HeaSec天积安全团队 - HTTP User-Agent靶场
 * 版本: v1.0.0
 * 创建日期: 2025-11-05
 * 团队: 天积安全 (HeavenlySecret)
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置响应头
header('X-HeavenlySecret: HeaSec HTTP User-Agent Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');
header('Date: ' . gmdate('D, d M Y H:i:s') . ' GMT');

// 启动输出缓冲
ob_start();

// 设置页面变量
$pageTitle = 'HTTP User-Agent靶场';
$rangeName = 'HTTP User-Agent靶场';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';


// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话（基于路径的隔离）
HeaSec_InitRangeSession('httpua');

// 验证会话完整性
HeaSec_ValidateSession();

/**
 * 获取或生成会话中的秘密字符串
 * 使用新的会话管理组件
 */
function getSecret()
{
    return HeaSec_GetSecret(20);
}

/**
 * 检测是否为鸿蒙系统
 */
function isHarmonyOS($userAgent)
{
    if (empty($userAgent) || !is_string($userAgent)) {
        return false;
    }

    // 处理超长字符串（防止DoS攻击）
    if (strlen($userAgent) > 2048) {
        return false;
    }

    $userAgent = trim($userAgent);

    // 鸿蒙系统User-Agent特征模式
    $harmonyOSPatterns = [
        '/HarmonyOS/i',           // 标准HarmonyOS
        '/OpenHarmony/i',         // 开源版本
        '/Hongmeng/i',            // 中文拼音
        '/HMOS/i',                // 简写形式
        '/HUAWEI.*HarmonyOS/i',   // 华为设备明确标识
    ];

    foreach ($harmonyOSPatterns as $pattern) {
        if (preg_match($pattern, $userAgent)) {
            // 进一步验证：确保不是误报
            if ($pattern === '/HarmonyOS/i') {
                // 额外验证：HarmonyOS应该出现在合理位置
                if (
                    preg_match('/\((?:.*;)?[^)]*HarmonyOS[^)]*\)/i', $userAgent) ||
                    preg_match('/HarmonyOS\s*[\d\.]*\/?[\d\.]*/i', $userAgent)
                ) {
                    return true;
                }
            } else {
                return true;
            }
        }
    }

    return false;
}

/**
 * 检测是否为macOS上的原生Safari浏览器
 */
function isMacOSSafari($userAgent)
{
    if (empty($userAgent) || !is_string($userAgent)) {
        return false;
    }

    if (strlen($userAgent) > 2048) {
        return false;
    }

    $userAgent = trim($userAgent);

    // 排除iOS设备（防止iPad/iPhone伪装成macOS）
    $hasIOS = preg_match('/iPhone|iPad|iPod|iOS|Mobile.*Safari/i', $userAgent);
    if ($hasIOS) {
        return false;
    }

    // 必须包含macOS标识
    if (!preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
        return false;
    }

    // 排除其他浏览器（Chrome伪装等）
    $hasOtherBrowser = preg_match('/Chrome|Edge|Firefox|OPR|Opera|Chromium|MSIE/i', $userAgent);
    if ($hasOtherBrowser) {
        return false;
    }

    // 必须包含Safari标识
    if (!preg_match('/Safari/i', $userAgent)) {
        return false;
    }

    // 版本号验证（确保是真实Safari）
    if (preg_match('/Version\/(\d+\.\d+)/i', $userAgent, $matches)) {
        $version = floatval($matches[1]);
        // Safari 4.0+ (2010年发布) 作为最低版本要求
        if ($version < 4.0) {
            return false;
        }
    } else {
        // 没有版本号的Safari不合法
        return false;
    }

    return true;
}

// 获取或生成秘密字符串
$secret = getSecret();

// 获取User-Agent请求头
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

// 检测用户环境
$isHarmonyOS = isHarmonyOS($userAgent);
$isMacOSSafari = isMacOSSafari($userAgent);

// 确定是否有权限获取秘密
$hasSecretAccess = $isHarmonyOS && $isMacOSSafari;

// 如果有权限，在响应头中设置秘密信息
if ($hasSecretAccess) {
    header('Heasec-Secret-Info: ' . $secret);
}

// 处理表单提交
$message = '';
$messageType = ''; // success, error
$userSecretForDisplay = '';
$showCongrats = false;


// 根据检测结果设置显示消息
$displayMessage = '';
$hintMessage = '';
$alertType = 'warning';

if ($isHarmonyOS && $isMacOSSafari) {
    $displayMessage = '您发现了一个秘密，但是它在哪里呢？';
    $alertType = 'success';
} elseif ($isHarmonyOS) {
    $displayMessage = '您不是在苹果电脑上使用Safari浏览器，无法获取我的秘密';
    $alertType = 'warning';
} else {
    $displayMessage = '您不是使用鸿蒙系统，无法获取我的秘密';
    $alertType = 'warning';
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入密码验证卡片组件
require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件（用于覆盖和扩展） -->
<link rel="stylesheet" href="css/style.css">

<!-- 星星系统组件资源已由secret_card组件自动引入 -->

<!-- 引入密码验证卡片组件脚本 -->
<script src="<?php echo $commonBasePath; ?>components/secret-card/js/secret-card.js?v=<?php echo $version; ?>"></script>

<!-- 引入交互脚本 -->
<script src="js/interactions.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- HTTP请求头检测区域 -->
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
                    <?php if (!empty($hintMessage)): ?>
                        <p class="alert-hint">
                            <small><?php echo htmlspecialchars($hintMessage); ?></small>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- 秘密验证区域 -->
    <?php
    echo renderSecretCard([
        'cardTitle' => '秘密验证',
        'cardIcon' => 'fa fa-key',
        'secretValue' => $secret,
        'successMessage' => '验证成功，恭喜你发现了秘密！',
        'successHint' => 'User-Agent通常用于告知服务器客户端使用的浏览器和操作系统信息等，服务器可以根据User-Agent来返回不同的页面内容，如面向移动设备提供与面向桌面设备不同的页面',
        'errorMessage' => '验证失败，这不是我的秘密！',
        'emptyMessage' => '请输入秘密',
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你理解了User-Agent请求头的基本作用',
        'rangeCode' => 'httpua'
    ]);

    // 如果页面加载时有消息需要显示（比如通过POST提交后）
    if (!empty($message)):
        echo showSecretCardResult('heasec_secret_card_' . uniqid(), $message, $messageType);
    endif;
    ?>
</div>


<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>