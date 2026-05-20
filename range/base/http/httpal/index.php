<?php
/**
 * HeaSec天积安全团队 - HTTP请求头Accept-Language靶场
 * 版本: v1.0.0
 * 创建日期: 2025-11-04
 * 团队: 天积安全 (HeavenlySecret)
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置响应头
header('X-HeavenlySecret: HeaSec HTTP Accept-Language Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');
header('Date: ' . gmdate('D, d M Y H:i:s') . ' GMT');


// 启动输出缓冲
ob_start();

// 设置页面变量
$pageTitle = 'HTTP Accept-Language 靶场';
$rangeName = 'HTTP Accept-Language 靶场';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.0';

// 设置公共组件的基础路径（从靶场目录到range/common/的相对路径）
$commonBasePath = '../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';

// 初始化靶场会话（基于路径的隔离）
HeaSec_InitRangeSession('httpal');

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
 * 检测是否为英文用户
 */
function isEnglishUser($acceptLanguage)
{
    if (empty($acceptLanguage)) {
        return true; // 如果没有Accept-Language头，默认认为是英文用户
    }

    // 英文语言标签列表
    $englishLanguageCodes = [
        'en',
        'en-us',
        'en-gb',
        'en-au',
        'en-ca',
        'en-nz',
        'en-ie',
        'en-za',
        'en-in',
        'en-sg',
        'en-hk',
        'en-my',
        'en-ph'
    ];

    // 分割语言列表
    $languageParts = explode(',', $acceptLanguage);

    foreach ($languageParts as $part) {
        $part = trim($part);
        if (empty($part))
            continue;

        // 分离语言代码和质量因子
        $segments = explode(';', $part);
        $language = strtolower(trim($segments[0]));

        if (empty($language))
            continue;

        // 如果不是英文语言标签，则为非英文用户
        if (!in_array($language, $englishLanguageCodes)) {
            return false;
        }
    }

    return true;
}

// 获取或生成秘密字符串
$secret = getSecret();

// 获取Accept-Language请求头
$acceptLanguage = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';

// 检测是否为英文用户
$isEnglishUser = isEnglishUser($acceptLanguage);

// 如果是英文用户，在响应头中设置秘密信息
if ($isEnglishUser) {
    header('Heasec-Secret-Info: ' . $secret);
}

// 处理表单提交
$message = '';
$messageType = ''; // success, error
$userSecret = '';
$showCongrats = false;

// 根据用户语言类型设置显示消息
$displayMessage = '';
$hintMessage = '';
if ($isEnglishUser) {
    $displayMessage = '您发现了一个秘密，但是它在哪里呢？';
} else {
    $displayMessage = '您所在的国家或地区无法知道这个秘密';
    $hintMessage = '拥有这个秘密的人特别爱喝下午茶';
}

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入密码验证卡片组件
require_once $commonBasePath . 'components/secret-card/includes/HeaSec_SecretCard.php';
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">

<!-- 星星系统组件资源已由secret_card组件自动引入 -->

<!-- 引入密码验证卡片组件脚本 -->
<script src="<?php echo $commonBasePath; ?>components/secret-card/js/secret-card.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <!-- HTTP请求头检测区域 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-globe"></i>
                请找到我的秘密
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 子区域1：显示当前 Accept-Language -->
            <h4>当前 Accept-Language：</h4>
            <div class="tech-info-panel no-animation">
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-value">
                            <code>
                                <?php
                                if (!empty($acceptLanguage)) {
                                    echo htmlspecialchars($acceptLanguage);
                                } else {
                                    echo '未检测到 Accept-Language 请求头';
                                }
                                ?>
                            </code>
                        </span>
                    </div>
                </div>
            </div>

            <!-- 子区域2：验证结果 -->
            <div class="detection-result">
                <?php if ($isEnglishUser): ?>
                    <div class="alert alert-success">
                        <div>
                            <i class="fa fa-check-circle"></i>
                            <strong><?php echo htmlspecialchars($displayMessage); ?></strong>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <div>
                            <i class="fa fa-exclamation-triangle"></i>
                            <strong><?php echo htmlspecialchars($displayMessage); ?></strong>
                        </div>
                        <?php if (!empty($hintMessage)): ?>
                            <p class="alert-hint">
                                <small><?php echo htmlspecialchars($hintMessage); ?></small>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
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
        'successHint' => 'Accept-Language 用于告知服务器用户使用的语言，有些网站可以面向不同用户展示不同的语言就是通过这个字段实现的。',
        'errorMessage' => '验证失败，这不是我的秘密！',
        'emptyMessage' => '请输入秘密',
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你理解了Accept-Language请求头的作用',
        'rangeCode' => 'httpal'
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