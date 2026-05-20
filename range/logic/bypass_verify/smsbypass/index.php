<?php
/**
 * HeaSec天积安全团队 - 短信验证码绕过靶场（验证码接收方篡改）
 * SMS Verification Code Bypass - Recipient Tampering
 * 版本: v1.1.0
 * 创建日期: 2026-01-21
 * 团队: 天积安全 (HeavenlySecret)
 *
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec 短信验证码绕过 Range v1.1.0');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = '短信验证码绕过靶场';
$rangeName = '短信验证码绕过';
$showVersion = false;
$showResetButton = true;
$showSmsSimulator = true;  // 显示短信模拟器按钮
$version = 'v1.1.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 设置重置功能相关变量
$initSqlFile = 'database/init_database.sql';
$databaseName = 'heasec_logic';
$useDatabase = true;

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入数据库组件
require_once $commonBasePath . 'includes/database.php';

// 引入靶场配置
require_once 'includes/config.php';

// 初始化消息变量
$loginMessage = '';
$loginMessageType = '';  // success, error, warning, info
$loginSuccess = false;

// 处理POST登录请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $code = isset($_POST['code']) ? trim($_POST['code']) : '';

    // 验证用户名
    if ($username !== 'admin') {
        $loginMessage = '用户名不存在';
        $loginMessageType = 'error';
    }
    // 验证码为空
    elseif (empty($code)) {
        $loginMessage = '请输入验证码';
        $loginMessageType = 'warning';
    }
    // 验证码格式检查
    elseif (!preg_match('/^\d{6}$/', $code)) {
        $loginMessage = '验证码格式错误，请输入6位数字';
        $loginMessageType = 'warning';
    } else {
        // 从数据库获取有效验证码
        $codeRecord = getValidVerificationCode();

        // 验证码错误（包括未获取验证码的情况）
        if ($codeRecord === null || $code !== $codeRecord['code']) {
            $loginMessage = '验证码错误，请重新输入';
            $loginMessageType = 'error';
        }
        // 验证通过
        else {
            $loginSuccess = true;

            // 获取发送的手机号列表和请求参数
            $sentPhones = $codeRecord['sent_phones'];
            $requestParams = $codeRecord['request_params'];

            // 检查目标手机号是否在发送列表中
            if (in_array(TARGET_PHONE, $sentPhones)) {
                // 识别篡改方式
                $bypassType = identifyBypassType($requestParams);

                if ($bypassType !== null) {
                    // 检查成就是否已存在（用于显示不同消息）
                    $isNewAchievement = !isAchievementExists($bypassType);

                    // 每次都记录成就（内部使用ON DUPLICATE KEY UPDATE自动处理计数）
                    recordAchievement($bypassType);

                    if ($isNewAchievement) {
                        $loginMessage = '登录成功！发现篡改方式：' . getBypassTypeName($bypassType);
                        $loginMessageType = 'success';
                    } else {
                        $loginMessage = '登录成功！该篡改方式已记录（' . getBypassTypeName($bypassType) . '）';
                        $loginMessageType = 'info';
                    }
                } else {
                    // 目标手机号在列表中，但未能识别篡改方式
                    $loginMessage = '登录成功，没有识别到篡改方式';
                    $loginMessageType = 'success';
                }
            } else {
                // 目标手机号不在发送列表中
                if (in_array(ORIGINAL_PHONE, $sentPhones) && count($sentPhones) === 1) {
                    // 只发送到了原手机号（正常流程）
                    $loginMessage = '登录成功，但这是正常的登录流程，不构成篡改攻击。提示：试试把验证码发送到13866668888';
                    $loginMessageType = 'info';
                } else {
                    // 发送到了其他手机号，但不是目标手机号
                    $loginMessage = '登录成功，但未检测到有效的篡改方式。提示：请确保验证码发送到了手机号13866668888';
                    $loginMessageType = 'warning';
                }
            }

            // 将验证码设为失效（一次性使用）
            invalidateVerificationCode($codeRecord['id']);
        }
    }
}
?>

<!-- 引入统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 引入站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css?v=<?php echo $version; ?>">

<!-- 引入星星系统组件资源 -->
<?php
require_once $commonBasePath . 'components/star-system/includes/HeaSec_StarSystem.php';
echo HeaSec_StarSystem::renderAssets($commonBasePath, ['js' => false]);
?>

<!-- 靶场主要内容 -->
<div class="range-container">
    <!-- 短信验证码登录表单卡片 -->
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-mobile"></i>
                短信验证码登录
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
                    <small>请使用短信验证码登录admin账号，试试能不能把验证码发送到你控制的手机号<code>13866668888</code>上并登录admin账号</small>
                </span>
            </div>

            <!-- 验证结果显示区域 -->
            <?php if (!empty($loginMessage)): ?>
                <div class="heasec-verify-<?php echo $loginMessageType; ?>">
                    <i class="fa fa-<?php
                    switch ($loginMessageType) {
                        case 'success':
                            echo 'check-circle';
                            break;
                        case 'error':
                            echo 'times-circle';
                            break;
                        case 'warning':
                            echo 'exclamation-triangle';
                            break;
                        case 'info':
                            echo 'info-circle';
                            break;
                    }
                    ?>"></i>
                    <?php echo htmlspecialchars($loginMessage); ?>
                </div>
            <?php endif; ?>

            <!-- 消息容器（用于AJAX消息显示） -->
            <div id="messageContainer"></div>

            <!-- 登录表单 -->
            <form id="loginForm" class="tech-form" method="post" action="">
                <input type="hidden" name="action" value="login">
                <!-- 隐藏字段：存储admin用户名和手机号，用于前端发送验证码请求 -->
                <input type="hidden" id="adminUsername" name="adminUsername" value="admin">
                <input type="hidden" id="adminPhone" name="adminPhone"
                    value="<?php echo defined('ORIGINAL_PHONE') ? ORIGINAL_PHONE : '11066668888'; ?>">

                <!-- 用户名（只读） -->
                <div class="form-group">
                    <label class="form-label" for="username">
                        <i class="fa fa-user"></i>
                        用户名
                    </label>
                    <div class="input-wrapper">
                        <input type="text" id="username" name="username" class="tech-input" value="admin" readonly>
                    </div>
                </div>

                <!-- 短信验证码 -->
                <div class="form-group">
                    <label class="form-label" for="code">
                        <i class="fa fa-shield"></i>
                        短信验证码
                    </label>
                    <div class="heasec-sms-code-group">
                        <div class="heasec-sms-code-input-wrapper">
                            <input type="text" id="code" name="code" class="tech-input" maxlength="6" autocomplete="off"
                                placeholder="请输入6位验证码">
                        </div>
                        <div class="heasec-sms-code-btn-wrapper">
                            <button type="button" id="sendCodeBtn" class="tech-btn tech-btn-secondary">
                                <i class="fa fa-paper-plane"></i>
                                获取验证码
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 提交按钮 -->
                <div class="form-actions">
                    <button type="submit" id="loginBtn" class="tech-btn tech-btn-primary">
                        <i class="fa fa-sign-in"></i>
                        登录
                    </button>
                </div>
            </form>
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
        $stmt = $db->query("SELECT COUNT(*) as count FROM heasec_smsbypass_records");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $starCount = intval($result['count']);

        // 获取记录列表
        $stmt = $db->query("SELECT bypass_type, success_count, last_success_at FROM heasec_smsbypass_records ORDER BY last_success_at DESC");
        $dbRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 转换记录格式为成就系统需要的格式
        foreach ($dbRecords as $record) {
            $typeName = getBypassTypeName($record['bypass_type']);
            $records[] = [
                'name' => $typeName,
                'count' => $record['success_count'],
                'time' => $record['last_success_at']
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
        'recordsTitle' => '已解锁的篡改方式',
        'rangeCode' => 'smsbypass',

        // 恭喜功能配置（自定义消息标题和内容）
        'congratsConfig' => [
            'messages' => [
                'partial' => '你已经掌握了 %d/3 种验证码接收方篡改方式！继续探索，发现更多漏洞！',
                'complete' => '太棒了！你已经掌握了3种验证码接收方篡改方式，成为了真正的安全大师！'
            ]
        ]
    ], $commonBasePath);
    ?>
</div>

<!-- 引入前端脚本 -->
<script src="js/smsbypass.js?v=<?php echo $version; ?>"></script>

<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>