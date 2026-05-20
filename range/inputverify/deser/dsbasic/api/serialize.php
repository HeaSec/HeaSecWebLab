<?php
/**
 * HeaSec天积安全团队 - 序列化API
 * 版本: v1.0.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 安全的序列化API，使用模板化参数映射方式，不使用eval()
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec Serialize API v1.0.0');

// 安全常量检查
if (!defined('HEASEC_RANGE_ACCESS')) {
    define('HEASEC_RANGE_ACCESS', true);
}

// 预定义的安全演示类
class HeaSecUser {
    public $name = 'guest';
    public $role = 'user';
    public $email = 'guest@test.com';
}

class HeaSecConfig {
    public $key = 'default';
    public $value = '';
    public $description = '';
}

class HeaSecLogger {
    public $logFile = 'app.log';
    public $logData = '';
}

// 允许的类名白名单（已废弃，现支持自由类名，保留用于POP链功能）
// $allowedClasses 已移除，序列化对象时接受任意合法类名

// 类型白名单
$allowedTypes = ['string', 'integer', 'float', 'boolean', 'null', 'assoc_array', 'index_array', 'object'];

try {
    // 仅接受POST请求
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('仅接受POST请求');
    }

    // 读取JSON输入
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['type'])) {
        throw new Exception('无效的请求参数');
    }

    $type = $input['type'];
    if (!in_array($type, $allowedTypes)) {
        throw new Exception('不支持的类型');
    }

    $result = null;
    $formattedInput = '';

    switch ($type) {
        case 'string':
            $value = isset($input['value']) ? (string)$input['value'] : '';
            if (mb_strlen($value) > 1000) {
                throw new Exception('字符串长度不能超过1000字符');
            }
            $result = $value;
            $formattedInput = '"' . htmlspecialchars($value) . '"';
            break;

        case 'integer':
            $value = isset($input['value']) ? $input['value'] : 0;
            if (!is_numeric($value)) {
                throw new Exception('无效的整数值');
            }
            $result = (int)$value;
            $formattedInput = (string)$result;
            break;

        case 'float':
            $value = isset($input['value']) ? $input['value'] : 0.0;
            if (!is_numeric($value)) {
                throw new Exception('无效的浮点数值');
            }
            $result = (float)$value;
            $formattedInput = (string)$result;
            break;

        case 'boolean':
            $value = isset($input['value']) ? $input['value'] : false;
            $result = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            $formattedInput = $result ? 'true' : 'false';
            break;

        case 'null':
            $result = null;
            $formattedInput = 'null';
            break;

        case 'assoc_array':
            $data = isset($input['data']) ? $input['data'] : [];
            if (!is_array($data)) {
                throw new Exception('无效的数组数据');
            }
            if (count($data) > 20) {
                throw new Exception('数组元素不能超过20个');
            }
            $result = [];
            foreach ($data as $k => $v) {
                if (mb_strlen((string)$k) > 50 || mb_strlen((string)$v) > 500) {
                    throw new Exception('键名或值过长');
                }
                $result[htmlspecialchars((string)$k)] = htmlspecialchars((string)$v);
            }
            $formattedInput = json_encode($result, JSON_UNESCAPED_UNICODE);
            break;

        case 'index_array':
            $data = isset($input['data']) ? $input['data'] : [];
            if (!is_array($data)) {
                throw new Exception('无效的数组数据');
            }
            if (count($data) > 20) {
                throw new Exception('数组元素不能超过20个');
            }
            $result = [];
            foreach ($data as $v) {
                $result[] = htmlspecialchars((string)$v);
            }
            $formattedInput = json_encode($result, JSON_UNESCAPED_UNICODE);
            break;

        case 'object':
            $class = isset($input['class']) ? trim($input['class']) : '';
            if ($class === '') {
                throw new Exception('请输入类名');
            }
            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
                throw new Exception('类名格式不合法');
            }
            if (mb_strlen($class) > 128) {
                throw new Exception('类名过长（最大128字符）');
            }

            $properties = isset($input['properties']) ? $input['properties'] : [];
            if (!is_array($properties)) {
                throw new Exception('属性数据格式错误');
            }
            if (count($properties) > 50) {
                throw new Exception('属性数量不能超过50个');
            }

            // 构建序列化字符串
            $result = buildObjectSerialized($class, $properties);
            $formattedInput = $class . ' {' . formatPropertiesForDisplay($properties) . '}';
            break;
    }

    // 对象类型的结果已经是手动构建的序列化字符串，其他类型仍需serialize()
    $serializedResult = ($type === 'object') ? $result : serialize($result);

    echo json_encode([
        'success' => true,
        'result' => $serializedResult,
        'input_type' => $type,
        'formatted_input' => $formattedInput
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 手动构建对象的序列化字符串
 * 支持任意类名和属性（含 public/protected/private 访问修饰符），不依赖实际存在的PHP类
 *
 * @param string $className 类名
 * @param array $properties 属性数组 [['name'=>'prop','value'=>'val','visibility'=>'public'], ...]
 *                         兼容旧格式关联数组 ['propName' => 'propValue']
 * @return string 序列化后的字符串（O格式）
 */
function buildObjectSerialized($className, $properties) {
    // 兼容旧格式：如果 properties 是关联数组（字符串键），自动转换为新格式
    if (!empty($properties)) {
        $firstKey = array_key_first($properties);
        if (is_string($firstKey)) {
            $legacyProps = [];
            foreach ($properties as $name => $value) {
                $legacyProps[] = ['name' => $name, 'value' => $value, 'visibility' => 'public'];
            }
            $properties = $legacyProps;
        }
    }

    $classLen = mb_strlen($className);
    $propCount = count($properties);

    // 构建对象头部: O:classLen:"className":propCount:{
    $serialized = 'O:' . $classLen . ':"' . $className . '":' . $propCount . ':{';

    // 逐个序列化每个属性（根据访问修饰符生成不同的键格式）
    foreach ($properties as $prop) {
        $propName = isset($prop['name']) ? $prop['name'] : '';
        $propValue = isset($prop['value']) ? $prop['value'] : '';
        $visibility = isset($prop['visibility']) ? $prop['visibility'] : 'public';

        // 根据访问修饰符生成不同的属性键格式
        $serialized .= serializePropertyKey($propName, $visibility, $className);

        // 属性值根据实际类型序列化
        $serialized .= serializeValueForProperty($propValue);
    }

    $serialized .= '}';
    return $serialized;
}

/**
 * 根据访问修饰符生成属性名的序列化键格式
 *
 * PHP 序列化中不同访问修饰符的属性键格式：
 *   - public:     s:len:"name"              直接使用属性名
 *   - protected:  s:(len+2):"\0*\0name"      前缀 \0*\0（长度含 \0 和 *）
 *   - private:    s:(len+clsLen+1):"\0cls\0name" 前缀 \0类名\0（长度含两个 \0 和类名）
 *
 * @param string $propName      属性名
 * @param string $visibility    可见性: 'public' | 'protected' | 'private'
 * @param string $className     类名（private 属性需要）
 * @return string 序列化的属性键片段，如 s:4:"name"; 或 s:10:"\0*\0email";
 */
function serializePropertyKey($propName, $visibility, $className) {
    switch ($visibility) {
        case 'protected':
            // protected: \0*\0属性名  (strlen 计算：\0=1 + *=1 + \0=1 + 属性名字节数)
            $rawKey = "\0*\0" . $propName;
            break;
        case 'private':
            // private: \0类名\0属性名  (strlen 计算：\0=1 + 类名字节 + \0=1 + 属性名字节)
            $rawKey = "\0" . $className . "\0" . $propName;
            break;
        case 'public':
        default:
            // public: 直接使用属性名
            $rawKey = $propName;
            break;
    }

    // PHP 序列化中的长度是字节数；含 \0 的字符串必须用 strlen 而非 mb_strlen
    $keyLen = ($visibility !== 'public') ? strlen($rawKey) : mb_strlen($rawKey);

    return 's:' . $keyLen . ':"' . $rawKey . '";';
}

/**
 * 将单个属性值序列化为PHP序列化格式片段
 * 自动检测值的类型并生成对应的序列化片段
 *
 * @param mixed $value 属性值
 * @return string 序列化片段（不含分号外的其他包装）
 */
function serializeValueForProperty($value) {
    if ($value === null || $value === '') {
        return 'N;';
    }

    // 尝试判断是否为整数
    if (is_numeric($value) && strpos($value, '.') === false && strpos($value, 'e') === false && strpos($value, 'E') === false) {
        $intVal = (int)$value;
        if ((string)$intVal === (string)$value) {
            return 'i:' . $intVal . ';';
        }
    }

    // 尝试判断是否为浮点数
    if (is_numeric($value)) {
        return 'd:' . (float)$value . ';';
    }

    // 布尔值特殊处理
    if ($value === 'true' || $value === '1') {
        return 'b:1;';
    }
    if ($value === 'false' || $value === '0') {
        return 'b:0;';
    }

    // 默认作为字符串处理
    $strVal = (string)$value;
    $strLen = mb_strlen($strVal);
    return 's:' . $strLen . ':"' . $strVal . '";';
}

/**
 * 格式化属性列表用于显示（支持新格式数组和旧格式关联数组）
 *
 * @param array $properties 属性数组
 * @return string 格式化的显示文本
 */
function formatPropertiesForDisplay($properties) {
    $parts = [];
    // 兼容新旧两种格式
    if (!empty($properties)) {
        $firstKey = array_key_first($properties);
        if (is_int($firstKey)) {
            // 新格式：索引数组
            foreach ($properties as $prop) {
                $vis = isset($prop['visibility']) ? $prop['visibility'] : 'public';
                $visPrefix = ($vis === 'public') ? '' : (($vis === 'protected') ? '*:' : $vis . ':');
                $name = isset($prop['name']) ? $prop['name'] : '';
                $value = isset($prop['value']) ? $prop['value'] : '';
                $parts[] = $visPrefix . htmlspecialchars($name) . ': "' . htmlspecialchars((string)$value) . '"';
            }
        } else {
            // 旧格式：关联数组
            foreach ($properties as $prop => $val) {
                $parts[] = htmlspecialchars($prop) . ': "' . htmlspecialchars((string)$val) . '"';
            }
        }
    }
    return implode(', ', $parts);
}
