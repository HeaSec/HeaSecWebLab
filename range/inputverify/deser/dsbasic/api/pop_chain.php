<?php
/**
 * HeaSec天积安全团队 - POP链练习API
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 *
 * POP链构造练习后端，文件写入限制在靶场临时目录内
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec POP Chain API v1.0.0');

// 沙箱目录
$sandboxDir = __DIR__ . '/../uploads';
$uploadsDir = $sandboxDir;

// 确保沙箱目录存在
if (!is_dir($sandboxDir)) {
    @mkdir($sandboxDir, 0755, true);
}

// POP链练习类定义
class FileLogger {
    public $writer;

    public function __destruct() {
        if ($this->writer) {
            $this->writer->write();
        }
    }
}

class HtmlRenderer {
    public $template;
    public $engine;

    public function write() {
        if ($this->engine) {
            $this->engine->render($this->template);
        }
    }
}

class TemplateExecutor {
    public $cacheDir;

    public function render($content) {
        global $sandboxDir;
        // 将内容写入沙箱目录
        $targetPath = $sandboxDir . '/cache.php';
        file_put_contents($targetPath, $content);
    }
}

// 允许的类
$allowedClasses = ['FileLogger', 'HtmlRenderer', 'TemplateExecutor'];

try {
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // 重置环境
    if ($action === 'reset') {
        $files = glob($sandboxDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        echo json_encode(['success' => true, 'message' => '环境已重置'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 检查文件状态
    if ($action === 'status') {
        $shellFile = $sandboxDir . '/shell.php';
        $exists = file_exists($shellFile);
        $content = '';
        if ($exists) {
            $content = file_get_contents($shellFile);
        }
        echo json_encode([
            'success' => true,
            'shell_exists' => $exists,
            'shell_content' => $content
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 执行POP链
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('仅接受POST请求');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['payload'])) {
        throw new Exception('无效的请求参数');
    }

    $payload = $input['payload'];
    if (!is_string($payload) || strlen($payload) > 10000) {
        throw new Exception('Payload无效或过长');
    }

    // 反序列化，限制允许的类
    $callChain = [];
    $result = @unserialize($payload, ['allowed_classes' => $allowedClasses]);

    if ($result === false) {
        throw new Exception('反序列化失败：无效的序列化格式');
    }

    // 检查POP链是否正确构造
    if (!($result instanceof FileLogger)) {
        throw new Exception('POP链构造不正确：根对象必须是FileLogger');
    }

    // 检查writer属性
    if (!isset($result->writer) || !($result->writer instanceof HtmlRenderer)) {
        throw new Exception('POP链构造不正确：FileLogger的$writer属性必须是HtmlRenderer实例');
    }

    // 检查engine属性
    if (!isset($result->writer->engine) || !($result->writer->engine instanceof TemplateExecutor)) {
        throw new Exception('POP链构造不正确：HtmlRenderer的$engine属性必须是TemplateExecutor实例');
    }

    // 检查template属性
    if (empty($result->writer->template)) {
        throw new Exception('POP链构造不正确：HtmlRenderer的$template属性不能为空');
    }

    // 检查cacheDir属性
    if (empty($result->writer->engine->cacheDir)) {
        throw new Exception('POP链构造不正确：TemplateExecutor的$cacheDir属性不能为空');
    }

    // 安全过滤：检查写入内容是否包含可执行PHP代码
    $content = $result->writer->template;
    $dangerousPatterns = ['<?php', '<?=', '<script language="php"', '<%'];
    $isSafe = true;
    foreach ($dangerousPatterns as $pattern) {
        if (stripos($content, $pattern) !== false) {
            $isSafe = false;
            break;
        }
    }

    // 记录调用链
    $callChain = [
        ['class' => 'FileLogger', 'method' => '__destruct', 'step' => 1,
         'detail' => '对象销毁时调用 $this->writer->write()'],
        ['class' => 'HtmlRenderer', 'method' => 'write', 'step' => 2,
         'detail' => '调用 $this->engine->render($this->template)'],
        ['class' => 'TemplateExecutor', 'method' => 'render', 'step' => 3,
         'detail' => '执行 file_put_contents($this->cacheDir . \'/cache.php\', $content)']
    ];

    // 写入文件（内容安全处理）
    $safeContent = str_replace(['<?php', '<?=', '<?'], ['&lt;?php', '&lt;?=', '&lt;?'], $content);
    $shellPath = $sandboxDir . '/shell.php';
    file_put_contents($shellPath, $safeContent);

    echo json_encode([
        'success' => true,
        'message' => 'POP链执行成功',
        'call_chain' => $callChain,
        'result' => '文件写入成功：./uploads/shell.php',
        'content_warning' => !$isSafe ? '检测到PHP代码标记，已自动转义为纯文本' : ''
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'hint' => '请检查对象的属性是否正确设置'
    ], JSON_UNESCAPED_UNICODE);
}
