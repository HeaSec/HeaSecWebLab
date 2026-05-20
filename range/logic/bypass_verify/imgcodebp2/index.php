<?php
/**
 * HeaSec天积安全团队 - 图片验证码绕过2靶场
 * 版本: v1.0.0
 * 创建日期: 2026-01-20
 * 团队: 天积安全 (HeavenlySecret)
 */

// 定义访问常量
define('HEASEC_RANGE_ACCESS', true);

// 设置响应头
header('X-HeavenlySecret: HeaSec 图片验证码绕过2 Range v1.0.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '图片验证码绕过2靶场';
$rangeName = '图片验证码绕过2';
$showVersion = false;
$showResetButton = true;
$version = 'v1.0.1';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 引入会话管理组件
require_once $commonBasePath . 'includes/session_manager.php';
HeaSec_InitRangeSession('imgcodebp2');

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;  // 此靶场使用数据库


// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/database.php';

// 引入靶场公共配置
require_once 'includes/config.php';

// 引入假验证码生成器（本靶场本地副本）
require_once 'includes/FakeCaptchaGenerator.php';

// 初始化验证消息变量
$verifyMessage = '';
$verifyMessageType = ''; // 'success' 或 'error'

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取POST数据
    $data = $_POST;

    // 识别绕过类型
    $bypassType = null;

    // 1. 检查验证码字段是否存在（绕过方式2：字段不存在）
    if (!isset($data['captcha'])) {
        $bypassType = 'missing';
    }
    // 2. 检查验证码是否为空（绕过方式1：空值）
    elseif (trim($data['captcha']) === '') {
        $bypassType = 'empty';
    }
    // 3. 检查验证码是否为通配符*（绕过方式3：通配符）
    elseif (trim($data['captcha']) === '*') {
        $bypassType = 'wildcard';
    }

    if ($bypassType !== null) {
        // 发现绕过方式，验证通过
        // 记录到数据库
        try {
            $db = heasec_db('heasec_logic');
            $sql = "INSERT INTO heasec_imgcodebp2_records (bypass_type, success_count, last_success_at)
                    VALUES (?, 1, NOW())
                    ON DUPLICATE KEY UPDATE
                    success_count = success_count + 1,
                    last_success_at = NOW()";
            $stmt = $db->prepare($sql);
            $stmt->execute([$bypassType]);
        } catch (Exception $e) {
            error_log('[HeaSec] RecordBypass error: ' . $e->getMessage());
        }

        $verifyMessage = '验证通过（发现绕过：' . getImgCodeBP2BypassTypeName($bypassType) . '）';
        $verifyMessageType = 'success';
    } else {
        // 正常验证流程
        $captcha = trim($data['captcha']);
        $generator = new FakeCaptchaGenerator();
        if ($generator->verify('imgcodebp2_captcha', $captcha, false)) {
            $verifyMessage = '验证通过（正常输入）';
            $verifyMessageType = 'success';
        } else {
            $verifyMessage = '验证码错误';
            $verifyMessageType = 'error';
            // 验证失败，重新生成验证码
            $generator->generate('imgcodebp2_captcha');
        }
    }
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css?v=<?php echo $version; ?>">

<!-- 引入星星系统组件资源（CSS样式） -->
<?php
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['js' => false]);
?>

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- 验证码提交表单卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-lock"></i>
                验证码提交测试
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 提示信息 -->
            <div class="alert alert-warning">
                <div>
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>提示</strong>
                </div>
                <span class="alert-hint">
                    <small>验证码图片似乎出了点问题，看不清楚内容，试试能不能绕过验证呢？</small>
                </span>
            </div>

            <!-- 验证码表单 -->
            <form id="loginForm" class="tech-form" method="post">
                <!-- 用户名（只读） -->
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fa fa-user"></i>
                        用户名
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="username" class="tech-input" value="admin" readonly>
                    </div>
                </div>

                <!-- 密码（只读） -->
                <div class="form-group">
                    <label class="form-label" for="password">
                        <i class="fa fa-lock"></i>
                        密码
                    </label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" class="tech-input" value="123456" readonly>
                    </div>
                </div>

                <!-- 验证码 -->
                <div class="form-group">
                    <label class="form-label" for="captcha">
                        <i class="fa fa-shield"></i>
                        验证码
                    </label>
                    <div class="heasec-captcha-group">
                        <div class="heasec-captcha-input-wrapper">
                            <input type="text" id="captcha" name="captcha" class="tech-input" maxlength="4"
                                autocomplete="off" placeholder="请输入验证码">
                        </div>
                        <div class="heasec-captcha-image-wrapper">
                            <img id="captchaImage" class="heasec-captcha-image" src="" alt="验证码">
                        </div>
                    </div>
                </div>

                <!-- 提交按钮 -->
                <div class="form-actions">
                    <button type="submit" id="submitBtn" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in-alt"></i>
                        提交验证
                    </button>
                </div>
            </form>

            <!-- 验证结果显示区域 -->
            <?php if (!empty($verifyMessage)): ?>
                <div class="heasec-verify-<?php echo $verifyMessageType; ?>">
                    <i class="fa fa-<?php echo $verifyMessageType === 'success' ? 'check-circle' : 'times-circle'; ?>"></i>
                    <?php echo htmlspecialchars($verifyMessage); ?>
                </div>
            <?php endif; ?>

            <div id="heasec-verifyResult"></div>
        </div>
    </div>

    <!-- 成就系统卡片 - 使用公共组件 -->
    <?php
    // 引入成就卡片公共组件
    require_once $commonBasePath . 'components/achievement-card/includes/HeaSec_AchievementCard.php';

    // 获取成就数量和记录
    $starCount = 0;
    $records = [];
    try {
        $db = heasec_db('heasec_logic');
        $stmt = $db->query("SELECT COUNT(*) as count FROM heasec_imgcodebp2_records");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $starCount = intval($result['count']);

        // 获取记录列表
        $stmt = $db->query("SELECT bypass_type, success_count FROM heasec_imgcodebp2_records ORDER BY bypass_type");
        $dbRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 转换记录格式为成就系统需要的格式（使用公共配置）
        foreach ($dbRecords as $record) {
            $typeName = getImgCodeBP2BypassTypeName($record['bypass_type']);
            $records[] = [
                'name' => $typeName,
                'count' => $record['success_count']
            ];
        }
    } catch (Exception $e) {
        error_log('[HeaSec] Database error: ' . $e->getMessage());
        $starCount = 0;
        $records = [];
    }

    // 渲染成就卡片公共组件
    echo renderAchievementCard([
        'achievedCount' => $starCount,
        'customRecords' => $records,
        'recordsTitle' => '已解锁的绕过方式',
        'rangeCode' => 'imgcodebp2',

        // 恭喜功能配置（自定义消息标题和内容）
        'congratsConfig' => [
            'messages' => [
                'partial' => '你已经掌握了 %d/3 种参数篡改绕过方式！继续探索，发现更多漏洞！',
                'complete' => '太棒了！你已经掌握了3种参数篡改绕过方式，成为了真正的安全大师！'
            ]
        ]
    ], $commonBasePath);
    ?>
</div>

<!-- 引入前端脚本 -->
<script src="js/imgcodebp2.js?v=<?php echo $version; ?>"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>