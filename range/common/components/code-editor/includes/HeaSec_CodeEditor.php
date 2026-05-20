<?php
/**
 * HeaSec天积安全团队 - 在线代码编辑器公共组件
 * Code Editor Common Component
 * 版本: v1.2.8
 * 创建日期: 2025-11-30
 * 更新日期: 2025-12-23
 * 团队: 天积安全 (HeavenlySecret)
 * 修复：点击放大镜按钮时预览窗口高度异常问题
 */



/**
 * 渲染在线代码编辑器组件
 *
 * @param array $config 组件配置参数
 * @return string 组件HTML字符串
 */
function renderCodeEditor($config = []) {
    // 获取公共组件基础路径
    $commonBasePath = isset($GLOBALS['commonBasePath']) ? $GLOBALS['commonBasePath'] :
                      (isset($commonBasePath) ? $commonBasePath : '../../../common/');

    // 默认配置
    $defaultConfig = [
        // 基础配置
        'cardTitle' => '在线代码编辑器',
        'cardIcon' => 'fa fa-code',
        'editorId' => 'codeEditor',
        'previewId' => 'codePreview',
        
        // 编辑器配置
        'height' => '400px',
        'fontSize' => '14px',
        'theme' => 'light',
        'syntaxHighlighting' => true,
        
        // 自动高度配置
        'autoHeight' => true,
        'minHeight' => 200,
        'maxHeight' => 800,
        
        // 语言标签配置
        'languages' => ['html', 'css', 'javascript'],
        'defaultLanguage' => 'html',
        
        // 代码默认内容
        'defaultCode' => [
            'html' => '<!DOCTYPE html>\n<html>\n<head>\n    <title>示例页面</title>\n</head>\n<body>\n    <h1>Hello, World!</h1>\n    <p>这是一个示例页面</p>\n</body>\n</html>',
            'css' => 'body {\n    font-family: Arial, sans-serif;\n    background-color: #f0f0f0;\n    margin: 20px;\n}\n\nh1 {\n    color: #007bff;\n    text-align: center;\n}',
            'javascript' => 'console.log("Hello, World!");\n\n// 点击事件处理\ndocument.addEventListener("click", function() {\n    alert("页面被点击了！");\n});'
        ],
        
        // 按钮配置
        'runButtonText' => '运行代码',
        'runButtonIcon' => 'fa fa-play',
        'clearButtonText' => '清空代码',
        'clearButtonIcon' => 'fa fa-trash',
        'resetButtonText' => '重置代码',
        'resetButtonIcon' => 'fa fa-refresh',
        
        // 布局配置
        'layout' => 'horizontal', // horizontal 或 vertical (移动端自适应)
        'splitRatio' => '50:50', // 左右比例
        
        // 自动引入资源
        'autoLoadAssets' => true
    ];

    // 合并配置
    $config = array_merge($defaultConfig, $config);

    // 自动引入样式和脚本
    if ($config['autoLoadAssets'] && !defined('HEASEC_CODE_EDITOR_ASSETS_LOADED')) {
        echo '<!-- 在线代码编辑器样式 -->' . "\n";
        
        $cssPath = $commonBasePath . 'components/code-editor/css/heasec-code-editor.css';
        $jsPath = $commonBasePath . 'components/code-editor/js/heasec-code-editor.js';
        
        echo '<link rel="stylesheet" href="' . $cssPath . '?v=v1.2.8">' . "\n";
        echo '<script src="' . $jsPath . '?v=v1.2.4"></script>' . "\n";
        
        // 定义常量避免重复加载
        define('HEASEC_CODE_EDITOR_ASSETS_LOADED', true);
    }

    // 生成唯一的组件ID
    $componentId = uniqid('heasec_code_editor_');
    $editorId = $config['editorId'] . '_' . $componentId;
    $previewId = $config['previewId'] . '_' . $componentId;

    // 开始输出缓冲
    ob_start();

    ?>

    <!-- 在线代码编辑器组件 -->

    <div class="heasec-code-editor" id="<?php echo $componentId; ?>" data-theme="<?php echo htmlspecialchars($config['theme']); ?>" data-auto-height="<?php echo $config['autoHeight'] ? 'true' : 'false'; ?>">
        <div class="heasec-code-editor-header">
            <h3>
                <i class="<?php echo htmlspecialchars($config['cardIcon']); ?>"></i>
                <?php echo htmlspecialchars($config['cardTitle']); ?>
            </h3>
        </div>
        
        <div class="heasec-code-editor-body">
            <!-- 语言标签已移除 - 支持HTML/CSS/JavaScript混合语法高亮 -->
            
            <!-- 主要内容区域 -->
            <div class="heasec-code-editor-main">
                <!-- 左侧代码编辑区域 -->
                <div class="heasec-code-editor-pane heasec-editor-pane">
                    <div class="heasec-editor-header">
                        <span class="heasec-editor-title">
                            <i class="fa fa-code"></i>
                            原始代码
                        </span>
                        <div class="heasec-editor-header-buttons">
                            <button class="heasec-editor-actions" onclick="HeaSecCodeEditor.toggleFullscreen('<?php echo $componentId; ?>')" title="放大/缩小">
                                <i class="fa fa-search-plus"></i>
                            </button>
                            <button class="heasec-editor-actions" onclick="HeaSecCodeEditor.runCode('<?php echo $componentId; ?>')" title="运行代码">
                                <i class="fa fa-play"></i>
                            </button>
                        </div>
                    </div>
                    <div class="heasec-code-editor-container">
                        <!-- 代码编辑区域 -->
                        <div class="heasec-code-editor-wrapper">
                            <!-- 行号容器 -->
                            <div class="heasec-line-numbers" id="<?php echo $editorId; ?>_linenumbers">
                                <div class="heasec-line-number">1</div>
                            </div>

                            <!-- 文本编辑区域 -->
                            <textarea
                                id="<?php echo $editorId; ?>"
                                class="heasec-code-textarea"
                                spellcheck="false"
                                autocomplete="off"
                                autocorrect="off"
                                autocapitalize="off"
                                data-language="<?php echo htmlspecialchars($config['defaultLanguage']); ?>"
                                placeholder="在这里输入你的代码..."><?php
                                    // 支持字符串格式的默认代码（混合HTML/CSS/JS）
                                    if (isset($config['defaultCode']) && is_string($config['defaultCode'])) {
                                        echo htmlspecialchars($config['defaultCode']);
                                    } elseif (isset($config['defaultCode'][$config['defaultLanguage']])) {
                                        echo htmlspecialchars($config['defaultCode'][$config['defaultLanguage']]);
                                    } else {
                                        echo '';
                                    }
                                ?></textarea>
                            <?php if ($config['syntaxHighlighting']): ?>
                            <pre class="heasec-code-highlight" id="<?php echo $editorId; ?>_highlight" aria-hidden="true"><code></code></pre>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- 分割线 -->
                <div class="heasec-code-editor-divider"></div>
                
                <!-- 右侧预览区域 -->
                <div class="heasec-code-editor-pane heasec-preview-pane">
                    <div class="heasec-preview-header">
                        <span class="heasec-preview-title">
                            <i class="fa fa-eye"></i>
                            预览效果
                        </span>
                        <button class="heasec-preview-refresh" onclick="HeaSecCodeEditor.refreshPreview('<?php echo $componentId; ?>')" title="刷新预览">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>
                    <div class="heasec-preview-content">
                        <iframe id="<?php echo $previewId; ?>" class="heasec-preview-frame" frameborder="0"></iframe>
                    </div>
                </div>
            </div>
            
            <!-- 操作按钮 -->
            <div class="heasec-code-editor-actions">
                <button type="button" class="heasec-btn heasec-btn-primary" onclick="HeaSecCodeEditor.runCode('<?php echo $componentId; ?>')">
                    <i class="<?php echo htmlspecialchars($config['runButtonIcon']); ?>"></i>
                    <?php echo htmlspecialchars($config['runButtonText']); ?>
                </button>
                <button type="button" class="heasec-btn heasec-btn-secondary" onclick="HeaSecCodeEditor.clearCode('<?php echo $componentId; ?>')">
                    <i class="<?php echo htmlspecialchars($config['clearButtonIcon']); ?>"></i>
                    <?php echo htmlspecialchars($config['clearButtonText']); ?>
                </button>
                <button type="button" class="heasec-btn heasec-btn-secondary" onclick="HeaSecCodeEditor.resetCode('<?php echo $componentId; ?>')">
                    <i class="<?php echo htmlspecialchars($config['resetButtonIcon']); ?>"></i>
                    <?php echo htmlspecialchars($config['resetButtonText']); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- 组件初始化脚本 -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // 初始化代码编辑器
        HeaSecCodeEditor.init('<?php echo $componentId; ?>', {
            editorId: '<?php echo $editorId; ?>',
            previewId: '<?php echo $previewId; ?>',
            height: '<?php echo htmlspecialchars($config['height']); ?>',
            fontSize: '<?php echo htmlspecialchars($config['fontSize']); ?>',
            theme: '<?php echo htmlspecialchars($config['theme']); ?>',
            syntaxHighlighting: <?php echo $config['syntaxHighlighting'] ? 'true' : 'false'; ?>,
            autoHeight: <?php echo $config['autoHeight'] ? 'true' : 'false'; ?>,
            minHeight: <?php echo (int)$config['minHeight']; ?>,
            maxHeight: <?php echo (int)$config['maxHeight']; ?>,
            languages: <?php echo json_encode($config['languages']); ?>,
            defaultLanguage: '<?php echo htmlspecialchars($config['defaultLanguage']); ?>',
            defaultCode: <?php echo json_encode($config['defaultCode']); ?>,
            layout: '<?php echo htmlspecialchars($config['layout']); ?>',
            splitRatio: '<?php echo htmlspecialchars($config['splitRatio']); ?>'
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
function showCodeEditorResult($componentId, $message, $type = 'success') {
    // 在组件渲染后显示结果
    $script = "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            HeaSecCodeEditor.showResult('{$componentId}', " . json_encode($message) . ", '{$type}');
        }, 100);
    });
    </script>";

    return $script;
}

/**
 * 渲染懒加载代码编辑器占位符
 * 只在区域展开时才渲染完整的编辑器
 *
 * @param array $config 组件配置参数
 * @return string 组件HTML字符串
 */
function renderCodeEditorLazy($config = []) {
    // 获取公共组件基础路径
    $commonBasePath = isset($GLOBALS['commonBasePath']) ? $GLOBALS['commonBasePath'] :
                      (isset($commonBasePath) ? $commonBasePath : '../../../common/');

    // 默认配置
    $defaultConfig = [
        'cardTitle' => '在线代码编辑器',
        'cardIcon' => 'fa fa-code',
        'height' => '400px',
        'fontSize' => '14px',
        'theme' => 'light',
        'syntaxHighlighting' => true,
        'autoHeight' => true,
        'minHeight' => 200,
        'maxHeight' => 800,
        'languages' => ['html', 'css', 'javascript'],
        'defaultLanguage' => 'html',
        'defaultCode' => [
            'html' => '',
            'css' => '',
            'javascript' => ''
        ],
        'runButtonText' => '运行代码',
        'runButtonIcon' => 'fa fa-play',
        'clearButtonText' => '清空代码',
        'clearButtonIcon' => 'fa fa-trash',
        'resetButtonText' => '重置代码',
        'resetButtonIcon' => 'fa fa-refresh',
        'layout' => 'horizontal',
        'splitRatio' => '50:50'
    ];

    // 合并配置
    $config = array_merge($defaultConfig, $config);

    // 自动引入样式和脚本（如果还没引入）
    if (!defined('HEASEC_CODE_EDITOR_ASSETS_LOADED')) {
        echo '<!-- 在线代码编辑器样式 -->' . "\n";

        $cssPath = $commonBasePath . 'components/code-editor/css/heasec-code-editor.css';
        $jsPath = $commonBasePath . 'components/code-editor/js/heasec-code-editor.js';

        echo '<link rel="stylesheet" href="' . $cssPath . '?v=v1.2.8">' . "\n";
        echo '<script src="' . $jsPath . '?v=v1.2.4"></script>' . "\n";

        define('HEASEC_CODE_EDITOR_ASSETS_LOADED', true);
    }

    // 生成唯一的组件ID
    $componentId = uniqid('heasec_code_editor_');

    // 将配置转换为JSON并存储在data属性中
    $configJson = json_encode($config);

    // 开始输出缓冲
    ob_start();

    ?>
    <!-- 懒加载代码编辑器占位符 -->
    <div class="heasec-code-editor-placeholder" id="<?php echo $componentId; ?>_placeholder"
         data-config="<?php echo htmlspecialchars($configJson); ?>"
         data-loaded="false">
        <div class="heasec-code-editor-header">
            <h3>
                <i class="<?php echo htmlspecialchars($config['cardIcon']); ?>"></i>
                <?php echo htmlspecialchars($config['cardTitle']); ?>
            </h3>
        </div>
        <div class="heasec-code-editor-loading">
            <i class="fa fa-spinner fa-spin"></i>
            <span>点击展开后加载编辑器...</span>
        </div>
    </div>

    <?php
    // 获取缓冲区内容并清理
    $html = ob_get_clean();
    return $html;
}
?>