<?php
/**
 * HeaSec天积安全团队 - 密码验证卡片公共组件
 * Secret Card Common Component
 * 版本: v1.0.0
 * 创建日期: 2025-11-21
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 恢复原来的函数式组件结构，保持简洁高效
 */


/**
 * 渲染密码验证卡片组件
 *
 * @param array $config 组件配置参数
 * @return string 组件HTML字符串
 */
function renderSecretCard($config = []) {
    // 获取公共组件基础路径
    $commonBasePath = isset($GLOBALS['commonBasePath']) ? $GLOBALS['commonBasePath'] :
                      (isset($commonBasePath) ? $commonBasePath : '../../../common/');

    // 默认配置
    $defaultConfig = [
        // 基础配置
        'cardTitle' => '秘密验证',
        'cardIcon' => 'fa fa-key',
        'formId' => 'secretForm',
        'inputId' => 'secret',

        // 表单配置
        'inputLabel' => '输入你发现的秘密',
        'inputPlaceholder' => '请输入20位的秘密字符串',
        'maxLength' => 20,
        'helpText' => '秘密格式：20位字母和数字组合（例如：AbCd1234EfGh5678IjKl）',

        // 按钮配置
        'submitText' => '验证秘密',
        'submitIcon' => 'fa fa-sign-in',
        'resetText' => '重置表单',
        'resetIcon' => 'fa fa-refresh',

        // 验证配置
        'secretValue' => null,
        'secretCallback' => null,
        'validationPattern' => '/^[A-Za-z0-9]{20}$/',

        // 消息配置
        'successMessage' => '验证成功，恭喜你发现了秘密！',
        'successHint' => '',
        'errorMessage' => '验证失败，这不是我的秘密！',
        'emptyMessage' => '请输入秘密',
        'invalidLengthMessage' => '请输入20位的秘密字符串',
        'invalidFormatMessage' => '秘密格式不正确，请输入20位字母和数字组合',

        // 恭喜弹窗配置
        'enableCongrats' => true,
        'autoLoadAssets' => true, // 自动引入恭喜消息相关资源
        'congratsTitle' => '恭喜你掌握了一个新技能',
        'congratsMessage' => '你完成了密码验证挑战',
        'congratsButtonText' => '继续学习',
        'rangeCode' => '',
        'showParticles' => true,
        'particleCount' => 8,
        'animationDuration' => 2000,
        'updateStatusApiUrl' => $commonBasePath . 'api/update-learning-status.php',
        'nextRangeApiUrl' => $commonBasePath . 'api/next-range.php'
    ];

    // 合并配置
    $config = array_merge($defaultConfig, $config);

    // 自动引入恭喜消息相关资源（如果需要且未引入）
    if ($config['enableCongrats'] && $config['autoLoadAssets'] && !defined('HEASEC_CONGRATS_ASSETS_LOADED')) {
        echo '<!-- 自动引入的恭喜消息资源 -->' . "\n";
        echo '<link rel="stylesheet" href="' . $commonBasePath . 'components/star-system/css/heasec-congrats-modal.css">' . "\n";
        echo '<script src="' . $commonBasePath . 'components/star-system/js/heasec-congrats-modal.js"></script>' . "\n";

        // 定义常量避免重复加载
        define('HEASEC_CONGRATS_ASSETS_LOADED', true);
    }

    // 获取秘密值并生成MD5哈希值（避免直接暴露秘密）
    $secretValue = null;
    $secretHash = null;
    if ($config['secretValue'] !== null) {
        $secretValue = $config['secretValue'];
        $secretHash = md5($secretValue);
    } elseif ($config['secretCallback'] !== null && is_callable($config['secretCallback'])) {
        $secretValue = call_user_func($config['secretCallback']);
        $secretHash = md5($secretValue);
    }

    // 生成唯一的组件ID
    $componentId = uniqid('heasec_secret_card_');
    $formId = $config['formId'] . '_' . $componentId;
    $inputId = $config['inputId'] . '_' . $componentId;

    // 开始输出缓冲
    ob_start();
    ?>

    <!-- 密码验证卡片组件 -->
    <div class="tech-card" id="<?php echo $componentId; ?>">
        <div class="tech-card-header">
            <h3>
                <i class="<?php echo htmlspecialchars($config['cardIcon']); ?>"></i>
                <?php echo htmlspecialchars($config['cardTitle']); ?>
            </h3>
        </div>
        <div class="tech-card-body">
            <!-- 验证结果显示区域 -->
            <div id="validation-result-<?php echo $componentId; ?>"></div>

            <!-- 秘密输入表单 -->
            <form id="<?php echo $formId; ?>" class="tech-form" action="" method="post">
                <div class="form-group">
                    <label for="<?php echo $inputId; ?>" class="form-label">
                        <i class="fa fa-lock"></i>
                        <?php echo htmlspecialchars($config['inputLabel']); ?>
                    </label>
                    <div class="input-wrapper">
                        <input type="text"
                               id="<?php echo $inputId; ?>"
                               name="secret"
                               class="tech-input"
                               placeholder="<?php echo htmlspecialchars($config['inputPlaceholder']); ?>"
                               maxlength="<?php echo (int)$config['maxLength']; ?>"
                               autocomplete="off"
                               data-validation-pattern="<?php echo htmlspecialchars($config['validationPattern']); ?>">
                        <div class="input-length">
                            <span id="length-indicator-<?php echo $componentId; ?>">0</span>/<?php echo (int)$config['maxLength']; ?>
                        </div>
                    </div>
                    <small class="form-help">
                        <?php echo htmlspecialchars($config['helpText']); ?>
                    </small>
                </div>

                <!-- 操作按钮 -->
                <div class="form-actions">
                    <button type="submit" class="tech-btn tech-btn-primary">
                        <i class="<?php echo htmlspecialchars($config['submitIcon']); ?>"></i>
                        <?php echo htmlspecialchars($config['submitText']); ?>
                    </button>
                    <button type="button" class="tech-btn tech-btn-secondary" onclick="resetSecretCard('<?php echo $componentId; ?>')">
                        <i class="<?php echo htmlspecialchars($config['resetIcon']); ?>"></i>
                        <?php echo htmlspecialchars($config['resetText']); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 组件初始化脚本 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化密码验证卡片
        HeaSecSecretCard.init('<?php echo $componentId; ?>', {
            formId: '<?php echo $formId; ?>',
            inputId: '<?php echo $inputId; ?>',
            maxLength: <?php echo (int)$config['maxLength']; ?>,
            secretHash: <?php echo $secretHash ? "'" . addslashes($secretHash) . "'" : 'null'; ?>,
            validationPattern: <?php echo json_encode($config['validationPattern']); ?>,
            submitText: <?php echo json_encode($config['submitText']); ?>,
            submitIcon: <?php echo json_encode($config['submitIcon']); ?>,

            // 消息配置
            messages: {
                success: <?php echo json_encode($config['successMessage']); ?>,
                successHint: <?php echo json_encode($config['successHint']); ?>,
                error: <?php echo json_encode($config['errorMessage']); ?>,
                empty: <?php echo json_encode($config['emptyMessage']); ?>,
                invalidLength: <?php echo json_encode($config['invalidLengthMessage']); ?>,
                invalidFormat: <?php echo json_encode($config['invalidFormatMessage']); ?>
            },

            // 恭喜弹窗配置
            enableCongrats: <?php echo $config['enableCongrats'] ? 'true' : 'false'; ?>,
            congratsConfig: {
                title: <?php echo json_encode($config['congratsTitle']); ?>,
                message: <?php echo json_encode($config['congratsMessage']); ?>,
                buttonText: <?php echo json_encode($config['congratsButtonText']); ?>,
                enableNextRangeButton: <?php echo !empty($config['rangeCode']) ? 'true' : 'false'; ?>,
                rangeCode: <?php echo json_encode($config['rangeCode']); ?>,
                showParticles: <?php echo $config['showParticles'] ? 'true' : 'false'; ?>,
                particleCount: <?php echo (int)$config['particleCount']; ?>,
                animationDuration: <?php echo (int)$config['animationDuration']; ?>,
                updateStatusApiUrl: <?php echo json_encode($config['updateStatusApiUrl']); ?>,
                nextRangeApiUrl: <?php echo json_encode($config['nextRangeApiUrl']); ?>
            }
        });
    });
    </script>

    <?php
    // 获取缓冲区内容并清理
    $html = ob_get_clean();
    return $html;
}

/**
 * 显示验证结果的辅助函数
 *
 * @param string $componentId 组件ID
 * @param string $message 消息内容
 * @param string $type 消息类型 (success|error)
 */
function showSecretCardResult($componentId, $message, $type = 'success') {
    // 在组件渲染后显示结果
    $script = "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            HeaSecSecretCard.showResult('{$componentId}', " . json_encode($message) . ", '{$type}');
        }, 100);
    });
    </script>";

    return $script;
}
?>