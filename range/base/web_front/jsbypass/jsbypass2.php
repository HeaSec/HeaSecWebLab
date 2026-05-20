<?php
/**
 * HeaSec天积安全团队 - JavaScript 绕过靶场（第二关）
 * 版本: v1.0.0
 * 创建日期: 2025-12-24
 * 团队: 天积安全 (HeavenlySecret)
 * 关卡: 复杂计算混淆 + eval Unicode 混淆
 */

// 设置响应头
header('X-HeavenlySecret: HeaSec JavaScript 绕过 Range v1.0.0 - Level 2');
header('Content-Type: text/html; charset=utf-8');

// 设置页面变量
$pageTitle = 'JavaScript 绕过靶场 - 第二关';
$rangeName = 'JavaScript 绕过';
$showVersion = false;
$showResetButton = false;
$version = 'v1.0.0';

// 设置公共组件的基础路径
$commonBasePath = '../../../common/';

// 引入公共头部
require_once $commonBasePath . 'includes/header.php';

// 引入会话管理
require_once $commonBasePath . 'includes/session_manager.php';

// 引入学习状态更新组件
require_once $commonBasePath . 'includes/HeaSec_LearningStatusUpdater.php';

// 初始化靶场会话
HeaSec_InitRangeSession('jsbypass');


// 生成2个10以内随机浮点数（不包括0和10，保留两位小数）
function generateRandomFloat()
{
    $num = mt_rand(1, 999) / 100; // 0.01 到 9.99
    return round($num, 2);
}

// 获取或生成随机浮点数
if (!isset($_SESSION['heasec_level2_a']) || !isset($_SESSION['heasec_level2_b'])) {
    $_SESSION['heasec_level2_a'] = generateRandomFloat();
    $_SESSION['heasec_level2_b'] = generateRandomFloat();
}

$a = $_SESSION['heasec_level2_a'];
$b = $_SESSION['heasec_level2_b'];

// 计算经过复杂计算后的结果
function calculateComplexResult($a, $b)
{
    // 对a进行复杂计算
    $a1 = $a * 2;
    $a2 = $a1 + $a;
    $a3 = $a2 / $a1 + $a;
    $a4 = $a2 - $a3 * $a1 + $a;
    $a5 = $a4 + $a3 / $a2 - $a1 * $a;
    $a6 = $a5 * $a4 - $a3 + $a2 * $a1 + $a;
    $a_final = floor($a6 * $a6);

    // 对b进行复杂计算
    $b1 = $b * 3;
    $b2 = $b1 + $b * 2;
    $b3 = $b2 / $b1 + $b;
    $b4 = $b3 - $b2 * $b1 + $b;
    $b5 = $b4 / $b3 + $b2 - $b1 * $b;
    $b6 = $b5 * $b4 - $b3 + $b2 * $b1 + $b;
    $b_final = floor($b6 * $b6);

    return $a_final + $b_final;
}

$correctPassword = intval(calculateComplexResult($a, $b));

// 处理表单提交
$loginResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $inputPassword = trim($_POST['password']);

    // 验证是否为数字
    if (is_numeric($inputPassword)) {
        if (intval($inputPassword) === $correctPassword) {
            // 密码正确，更新学习状态：从"待学习"更新为"学习中"
            HeaSec_UpdateLearningStatusIfNeeded('jsbypass');

            $loginResult = [
                'type' => 'success',
                'message' => '密码正确，点击按钮进入下一关，注意不要眨眼哦^-^'
            ];
        } else {
            $loginResult = [
                'type' => 'error',
                'message' => '密码错误，请重新输入'
            ];
        }
    } else {
        $loginResult = [
            'type' => 'error',
            'message' => '密码错误，请重新输入'
        ];
    }
}
?>

<!-- 统一样式文件 -->
<link rel="stylesheet" href="<?php echo $commonBasePath; ?>css/heasec_range.css">
<!-- 站点特定样式文件 -->
<link rel="stylesheet" href="css/style.css">

<!-- 交互脚本 -->
<script src="js/common.js?v=<?php echo $version; ?>"></script>

<!-- 靶场主要内容 -->
<div class="tech-container">
    <div class="tech-card">
        <div class="tech-card-header">
            <h3>
                <i class="fa fa-lock"></i>
                第二关 请输入正确通关密码
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 密码输入表单 -->
            <form class="tech-form" id="level2Form" method="post" onsubmit="return check();">
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fa fa-key"></i>
                        密码
                    </label>
                    <input type="text" id="password" name="password" class="tech-input" placeholder="请输入密码"
                        autocomplete="off" aria-label="密码">
                </div>

                <!-- 提交按钮 -->
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="fa fa-unlock"></i>
                        提交
                    </button>
                    <?php if ($loginResult && $loginResult['type'] === 'success'): ?>
                        <a href="jsbypass3.php" class="tech-btn tech-btn-success">
                            <i class="fa fa-arrow-right"></i>
                            下一关
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- 登录结果消息 -->
            <?php if ($loginResult): ?>
                <div class="detection-result" style="margin-top: 20px;">
                    <div class="alert alert-<?php echo $loginResult['type']; ?>">
                        <div>
                            <i
                                class="fa fa-<?php echo $loginResult['type'] === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                            <strong><?php echo htmlspecialchars($loginResult['message']); ?></strong>
                        </div>
                        <?php if ($loginResult['type'] === 'error'): ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<script>
    var a = <?php echo $a; ?>;
    var b = <?php echo $b; ?>;
    var a_b, a_c, a_d, a_e, a_f, a_g;
    a_b = a * 2;
    a_c = a_b + a;
    a_d = a_c / a_b + a;
    a_e = a_c - a_d * a_b + a;
    a_f = a_e + a_d / a_c - a_b * a;
    a_g = a_f * a_e - a_d + a_c * a_b + a;
    a = Math.floor(a_g * a_g);
    var b_b, b_c, b_d, b_e, b_f, b_g;
    b_b = b * 3;
    b_c = b_b + b * 2;
    b_d = b_c / b_b + b;
    b_e = b_d - b_c * b_b + b;
    b_f = b_e / b_d + b_c - b_b * b;
    b_g = b_f * b_e - b_d + b_c * b_b + b;
    b = Math.floor(b_g * b_g);

    eval("\u0066\u0075\u006e\u0063\u0074\u0069\u006f\u006e\u0020\u0063\u0068\u0065\u0063\u006b\u0028\u0029\u007b\u0069\u0066\u0028\u0064\u006f\u0063\u0075\u006d\u0065\u006e\u0074\u002e\u0067\u0065\u0074\u0045\u006c\u0065\u006d\u0065\u006e\u0074\u0042\u0079\u0049\u0064\u0028\u0027\u0070\u0061\u0073\u0073\u0077\u006f\u0072\u0064\u0027\u0029\u002e\u0076\u0061\u006c\u0075\u0065\u003d\u003d\u0061\u002b\u0062\u0029\u007b\u0072\u0065\u0074\u0075\u0072\u006e\u0020\u0074\u0072\u0075\u0065\u003b\u007d\u0065\u006c\u0073\u0065\u007b\u0061\u006c\u0065\u0072\u0074\u0028\u0027\u5bc6\u7801\u9519\u8bef\u0027\u0029\u003b\u0072\u0065\u0074\u0075\u0072\u006e\u0020\u0066\u0061\u006c\u0073\u0065\u003b\u007d\u007d");

</script>
<?php
// 引入公共底部
require_once $commonBasePath . 'includes/footer.php';
?>