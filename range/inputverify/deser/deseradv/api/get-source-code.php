<?php
/**
 * HeaSec天积安全团队 - 反序列化实战靶场 - 获取源代码接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-15
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserAdv Range v1.0.0');

$level = isset($_GET['level']) ? intval($_GET['level']) : 0;

if ($level < 1 || $level > 3) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => '无效的关卡编号']);
    exit;
}

/**
 * 获取指定关卡的源码数据
 * @param int $level 关卡编号
 * @return array 源码数据
 */
function getSourceCode($level) {
    switch ($level) {
        case 1:
            return [
                'source' => <<<'PHP'
/**
 * 插件数据验证器类
 * 用于对插件提交的数据进行校验、过滤和转换处理
 *
 * 该验证器支持多种内置的处理策略（如长度校验、格式转换等），
 * 也支持自定义回调函数进行灵活的数据处理。
 *
 * @package HeaSec\PluginSystem
 * @version 1.0.0
 */
class PluginValidator {
    /** @var string 验证器名称 */
    public $validatorName = 'default_validator';

    /** @var mixed 待验证的输入数据 */
    public $inputData = 'sample data';

    /**
     * @var string 回调函数名称
     * 用于对输入数据进行处理的回调函数。
     * 内置支持的回调包括: strlen, strtoupper, strtolower, md5, trim 等
     * 也可设置为自定义回调函数名以扩展处理能力
     */
    public $callbackFunc = 'strlen';

    /**
     * @var array 传递给回调函数的额外参数列表
     * 第一个参数位置固定为 inputData，后续参数从此数组中依次取出
     */
    public $callbackArgs = [];

    /**
     * @var bool 是否启用严格模式
     * 严格模式下仅允许使用预定义的安全回调函数列表中的函数
     */
    public $strictMode = false;

    /** @var array 严格模式下允许的回调函数白名单 */
    public $allowedCallbacks = [
        'strlen', 'strtoupper', 'strtolower', 'trim',
        'ltrim', 'rtrim', 'ucfirst', 'ucwords', 'md5', 'sha1'
    ];

    /**
     * 执行数据验证和处理
     * 根据配置的回调函数对输入数据进行处理并返回结果
     *
     * @return mixed 处理结果
     */
    public function validate() {
        // 如果启用了严格模式，检查回调是否在白名单中
        if ($this->strictMode && !in_array($this->callbackFunc, $this->allowedCallbacks)) {
            return "错误：不允许的回调函数 '{$this->callbackFunc}'";
        }

        // 构建回调参数列表：第一个参数固定为 inputData，后面追加 callbackArgs
        $params = array_merge([$this->inputData], $this->callbackArgs);

        // 调用回调函数进行处理
        $result = call_user_func_array($this->callbackFunc, $params);

        return $result;
    }
}
PHP
            ];

        case 2:
            return [
                'sections' => [
                    [
                        'title' => 'PluginManager — 插件管理器',
                        'code' => <<<'PHP'
/**
 * 插件管理器类
 * 负责管理系统中所有已加载的插件实例
 *
 * @package HeaSec\PluginSystem
 * @version 1.0.0
 */
class PluginManager {
    /** @var string 管理器名称 */
    public $managerName = 'default_manager';

    /**
     * @var array 已注册的插件列表
     * 存储所有需要管理的插件对象
     */
    public $plugins = [];

    /**
     * 析构方法
     * 对象销毁时自动调用，遍历 plugins 数组中的每个插件对象
     */
    public function __destruct() {
        foreach ($this->plugins as $plugin) {
            if (is_object($plugin) && method_exists($plugin, 'initialize')) {
                $plugin->initialize();
            }
        }
    }
}
PHP
                    ],
                    [
                        'title' => 'Logger — 日志记录器',
                        'code' => <<<'PHP'
/**
 * 日志记录器类
 * 负责记录系统运行过程中的各类日志信息
 *
 * @package HeaSec\PluginSystem
 * @version 1.0.0
 */
class Logger {
    /** @var string 日志记录器名称 */
    public $loggerName = 'system_logger';

    /**
     * @var string 日志存储路径
     * 正常情况下用于指定日志文件的保存路径
     */
    public $logFile = '/tmp/system.log';

    /**
     * @var mixed 日志存储介质（private 属性）
     * 正常情况下为文件路径字符串或文件句柄（内部使用）
     */
    private $storage = null;

    /**
     * 初始化日志记录器
     * 将格式化的日志消息写入 storage 指定的存储介质
     */
    public function initialize() {
        $message = date('[Y-m-d H:i:s]') . ' Logger initialized';
        $result = $this->storage . $this->logFile;
        return $result;
    }
}
PHP
                    ],
                    [
                        'title' => 'CacheCleaner — 缓存清理器',
                        'code' => <<<'PHP'
/**
 * 缓存清理器类
 * 负责清理系统缓存数据和临时文件
 *
 * @package HeaSec\PluginSystem
 * @version 1.0.0
 */
class CacheCleaner {
    /** @var string 清理器名称 */
    public $cleanerName = 'cache_cleaner';

    /**
     * @var string 缓存目录路径
     * 正常情况下指定要清理的缓存目录
     */
    public $cacheDir = '/tmp/cache/';

    /**
     * @var mixed 清理策略处理器（protected 属性）
     * 正常情况下为清理策略对象或闭包（子类可访问）
     */
    protected $cleaner = null;

    /**
     * 字符串转换方法
     * 当对象被用于字符串上下文时自动触发
     * 调用 cleaner 属性指向对象的 clean() 方法并返回结果
     */
    public function __toString() {
        if (is_object($this->cleaner) && method_exists($this->cleaner, 'clean')) {
            return $this->cleaner->clean($this->cacheDir);
        }
        return '';
    }
}
PHP
                    ],
                    [
                        'title' => 'FileReader — 文件读取器',
                        'code' => <<<'PHP'
/**
 * 文件读取器类
 * 负责读取和展示文件内容
 *
 * @package HeaSec\PluginSystem
 * @version 1.0.0
 */
class FileReader {
    /** @var string 读取器名称 */
    public $readerName = 'file_reader';

    /**
     * @var string 目标文件路径
     * 正常情况下为要清理的文件或目录路径
     */
    public $filename = '/etc/hosts';

    /**
     * @var bool 是否将结果存入全局变量
     * 开启后将文件内容写入 $GLOBALS['__pop_chain_result']
     */
    public $outputToGlobal = false;

    /**
     * 执行清理/读取操作
     * 读取 filename 属性指定的文件内容并返回
     *
     * @param string $path 传入的路径参数
     * @return string 文件内容或空字符串
     */
    public function clean($path = '') {
        $targetFile = $this->filename;
        $content = @file_get_contents($targetFile);

        if ($this->outputToGlobal && $content !== false) {
            $GLOBALS['__pop_chain_result'] = $content;
        }

        return $content !== false ? $content : '';
    }
}
PHP
                    ]
                ]
            ];

        case 3:
            return [
                'sections' => [
                    [
                        'title' => '数据处理流程',
                        'code' => <<<'PHP'
// 切换工作目录到靶场根目录
chdir(dirname(__DIR__));
require_once dirname(__DIR__) . '/includes/functions.php';

// 接收用户提交的序列化数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$serializedData = isset($data['data']) ? $data['data'] : '';

// 反序列化（严格限制为仅允许PHP内置类白名单）
$obj = unserialize($serializedData, [
    'allowed_classes' => [
        'Exception',
        'Error',
        'TypeError',
        'ParseError',
        'ArrayObject',
        'ArrayIterator',
        'SplFixedArray'
    ]
]);

// 异常对象处理：提取消息内容并进行目标预览
if ($obj instanceof Exception || $obj instanceof Error) {
    $filePath = $obj->getMessage();

    if ($filePath !== null && is_string($filePath) && $filePath !== '') {
        $content = @file_get_contents($filePath);
        // 将内容返回给前端...
    }
}
// ArrayObject 处理路径（备用数据载体）
elseif ($obj instanceof ArrayObject) {
    $filePath = $obj->offsetExists('filePath') ? $obj['filePath'] : null;
    // ...
}
PHP
                    ],
                    [
                        'title' => '允许的内置类说明',
                        'code' => <<<'PHP'
/**
 * 本关卡允许使用的 PHP 内置类列表及其特性：
 *
 * 1. Exception / Error 及其子类（TypeError, ParseError 等）
 *    - PHP 异常/错误处理基类
 *    - 具有关键属性和方法可供分析
 *
 * 2. ArrayObject — 数组包装类
 *    - 将数组封装为对象形式，支持数组式访问 $obj['key']
 *
 * 3. ArrayIterator — 数组迭代器
 *    - 类似 ArrayObject，支持迭代遍历
 *
 * 4. SplFixedArray — 固定长度数组
 *    - 高性能数值索引数组
 *
 * 安全提醒：即使限制了 allowed_classes 为内置类白名单，
 * 内置类的属性值仍由反序列化数据完全控制。
 */
//
// 注意：秘密文件为纯文本格式（非PHP文件），存储在 config/ 目录下
PHP
                    ]
                ]
            ];

        default:
            return ['source' => ''];
    }
}

$result = getSourceCode($level);

echo json_encode(['success' => true] + $result, JSON_UNESCAPED_UNICODE);
