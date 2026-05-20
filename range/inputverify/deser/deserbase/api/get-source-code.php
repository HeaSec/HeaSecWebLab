<?php
/**
 * HeaSec天积安全团队 - 反序列化练习靶场 - 获取源代码接口
 * 版本: v1.0.0
 * 创建日期: 2026-04-14
 * 团队: 天积安全 (HeavenlySecret)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec DeserBase Range v1.0.0');

$level = isset($_GET['level']) ? intval($_GET['level']) : 0;

if ($level < 1 || $level > 3) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['success' => false, 'message' => '无效的关卡编号']);
    exit;
}

/**
 * 获取指定关卡的源码数据
 * @param int $level 关卡编号
 * @return array 源码数据（单段返回 ['source'=>...]，多段返回 ['sections'=>[...]]）
 */
function getSourceCode($level) {
    switch ($level) {
        case 1:
            return [
                'source' => <<<'PHP'
/**
 * 用户信息类
 * 用于存储和展示用户基本信息
 */
class UserInfo {
    /** @var string 用户姓名 */
    public $name = 'guest';

    /** @var string 用户角色 */
    public $role = 'user';

    /**
     * 获取用户信息摘要
     * @return string 格式化的用户信息
     */
    public function getInfo() {
        return "姓名：{$this->name}，角色：{$this->role}";
    }
}
PHP
            ];

        case 2:
            return [
                'source' => <<<'PHP'
// 切换工作目录到靶场根目录，确保文件路径相对路径正确解析
chdir(dirname(__DIR__));

/**
 * 文件读取器类
 * 用于读取指定文件的内容并以字符串形式返回
 */
class FileReader {
    /** @var string 要读取的文件路径 */
    public $filename = 'logs/info.log';

    /**
     * 将对象转换为字符串时自动调用
     * 读取 filename 指定文件的内容并返回
     * @return string 文件内容或错误信息
     */
    public function __toString() {
        if (file_exists($this->filename)) {
            return file_get_contents($this->filename);
        }
        return "文件不存在：{$this->filename}";
    }
}
PHP
            ];

        case 3:
            return [
                'sections' => [
                    [
                        'title' => '用户资料类',
                        'code' => <<<'PHP'
/**
 * 用户资料类
 * 用于存储和更新用户个人信息
 */
class UserProfile {
    /** @var string 用户昵称 */
    public $nickname = '新用户';

    /** @var string 头像文件名 */
    public $avatar = 'avatar01.png';

    /** @var string 个性签名 */
    public $signature = '这个人很懒，什么都不想说啊';

    /** @var bool 是否为VIP用户 */
    public $isVIP = false;
}
PHP
                    ],
                    [
                        'title' => '数据处理流程',
                        'code' => <<<'PHP'
// 接收用户提交的序列化数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$serializedData = isset($data['data']) ? $data['data'] : '';

// 数据安全过滤：移除包含敏感关键字的内容
$filteredData = str_replace('danger', '', $serializedData);

// 对过滤后的数据进行反序列化处理
$obj = unserialize($filteredData);
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
