<?php
/**
 * HeaSec天积安全团队 - 反序列化API
 * 版本: v1.1.0
 * 创建日期: 2026-04-11
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 安全的反序列化API，使用allowed_classes白名单限制
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec Unserialize API v1.0.0');

// 预定义的安全演示类（与serialize.php一致）
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

// 类名映射
$classNameMap = [
    'HeaSecUser' => 'User',
    'HeaSecConfig' => 'Config',
    'HeaSecLogger' => 'Logger'
];

// 允许反序列化的类列表
$allowedClasses = ['HeaSecUser', 'HeaSecConfig', 'HeaSecLogger'];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('仅接受POST请求');
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['data'])) {
        throw new Exception('无效的请求参数');
    }

    $data = $input['data'];
    if (!is_string($data) || strlen($data) > 5000) {
        throw new Exception('序列化字符串无效或过长');
    }

    // 允许反序列化任意类名（学习靶场环境，已做安全隔离）
    $result = @unserialize($data, ['allowed_classes' => false]);

    if ($result === false && $data !== 'b:0;') {
        throw new Exception('反序列化失败：无效的序列化格式');
    }

    // 分析反序列化结果的类型
    $response = [
        'success' => true,
        'result' => analyzeValue($result, $classNameMap)
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 分析反序列化后的值，返回结构化信息
 * @param mixed $value 反序列化结果
 * @param array $classNameMap 类名映射表
 * @return array 结构化的值信息
 */
function analyzeValue($value, $classNameMap) {
    $type = gettype($value);
    $result = ['type' => $type];

    switch ($type) {
        case 'object':
            $className = get_class($value);

            // 处理 __PHP_Incomplete_Class（类不存在的情况）
            if ($className === '__PHP_Incomplete_Class') {
                $incompleteProps = (array)$value;
                // __PHP_Incomplete_Class 的类名存储在 __PHP_Incomplete_Class_Name 键中
                $originalClassName = isset($incompleteProps['__PHP_Incomplete_Class_Name'])
                    ? $incompleteProps['__PHP_Incomplete_Class_Name']
                    : 'Unknown';

                $result['class'] = $originalClassName;
                $result['is_incomplete'] = true;
                $result['properties'] = [];

                // 提取不完整类的属性（排除内部键）
                foreach ($incompleteProps as $key => $val) {
                    if ($key === '__PHP_Incomplete_Class_Name') continue;
                    // 从原始键名分析可见性并提取纯净属性名
                    $visibility = detectVisibilityFromKey($key);
                    $cleanKey = stripNullBytePrefix($key);
                    $result['properties'][$cleanKey] = [
                        'visibility' => $visibility,
                        'value' => $val
                    ];
                }

                $result['formatted'] = formatIncompleteObject($originalClassName, $result['properties']);
            } else {
                $displayName = isset($classNameMap[$className]) ? $classNameMap[$className] : $className;
                $result['class'] = $displayName;
                $result['is_incomplete'] = false;
                $result['properties'] = [];
                $reflection = new ReflectionClass($value);
                $props = $reflection->getProperties();
                foreach ($props as $prop) {
                    $prop->setAccessible(true);
                    $propName = $prop->getName();
                    $visibility = 'public';
                    if ($prop->isPrivate()) {
                        $visibility = 'private';
                    } elseif ($prop->isProtected()) {
                        $visibility = 'protected';
                    }
                    $result['properties'][$propName] = [
                        'visibility' => $visibility,
                        'value' => $prop->getValue($value)
                    ];
                }
                $result['formatted'] = formatObject($value, $displayName);
            }
            break;

        case 'array':
            $result['value'] = formatArray($value);
            $formatted = "Array\n(\n";
            foreach ($value as $k => $v) {
                $formatted .= "    [" . (is_int($k) ? $k : '"' . $k . '"') . "] => " . formatSimpleValue($v) . "\n";
            }
            $formatted .= ")";
            $result['formatted'] = $formatted;
            break;

        case 'string':
            $result['value'] = $value;
            $result['formatted'] = '"' . $value . '"';
            break;

        case 'integer':
        case 'double':
            $result['value'] = $value;
            $result['formatted'] = (string)$value;
            break;

        case 'boolean':
            $result['value'] = $value;
            $result['formatted'] = $value ? 'true' : 'false';
            break;

        case 'NULL':
            $result['value'] = null;
            $result['formatted'] = 'NULL';
            break;
    }

    return $result;
}

/**
 * 从 __PHP_Incomplete_Class 的原始属性键名中检测可见性
 *
 * PHP 反序列化后，不完整类的属性键名规则：
 *   - public:     "propertyName"          无前缀（首字符非 \0）
 *   - protected:  "\0*\0propertyName"     以 \0* 开头
 *   - private:    "\0ClassName\0propertyName" 以 \0ClassName\0 开头
 *
 * @param string $rawKey 原始键名（可能含 null byte）
 * @return string 'public' | 'protected' | 'private'
 */
function detectVisibilityFromKey($rawKey) {
    // 检查是否以 \0 开头（null byte）
    if (isset($rawKey[0]) && $rawKey[0] === "\0") {
        // 第二个字符是 * 则为 protected
        if (isset($rawKey[1]) && $rawKey[1] === '*') {
            return 'protected';
        }
        // 否则为 private (\0ClassName\0name 格式)
        return 'private';
    }
    return 'public';
}

/**
 * 移除属性键名中的 null byte 前缀，提取纯净的属性名
 *
 * @param string $rawKey 原始键名
 * @return string 纯净属性名
 */
function stripNullBytePrefix($rawKey) {
    if (isset($rawKey[0]) && $rawKey[0] === "\0") {
        // 找到第二个 \0 之后的内容即为真实属性名
        $secondNullPos = strpos($rawKey, "\0", 1);
        if ($secondNullPos !== false) {
            return substr($rawKey, $secondNullPos + 1);
        }
        // 兜底：用正则清理
        return preg_replace('/^\x00[^\x00]*\x00/', '', $rawKey);
    }
    return $rawKey;
}

/**
 * 格式化对象为可读字符串
 * @param object $obj 对象
 * @param string $className 类名
 * @return string 格式化后的字符串
 */
function formatObject($obj, $className) {
    $output = $className . " Object\n(\n";
    $reflection = new ReflectionClass($obj);
    $props = $reflection->getProperties();
    foreach ($props as $prop) {
        $prop->setAccessible(true);
        $propName = $prop->getName();
        $visibility = '* ';
        if ($prop->isPrivate()) {
            $className = get_class($obj);
            $visibility = $className . ':';
        } elseif ($prop->isProtected()) {
            $visibility = '* ';
        } else {
            $visibility = '';
        }
        $output .= "    ['" . $prop->getName() . "'] => " . formatSimpleValue($prop->getValue($obj)) . "\n";
    }
    $output .= ")";
    return $output;
}

/**
 * 格式化不完整类对象为可读字符串（类不存在时的反序列化结果）
 * @param string $className 原始类名
 * @param array $properties 属性数组
 * @return string 格式化后的字符串
 */
function formatIncompleteObject($className, $properties) {
    // 从属性数组构建行（模拟不完整类的结构）
    $lines = [$className . ' Object', '('];
    foreach ($properties as $propName => $propData) {
        $val = $propData['value'];
        if (is_object($val) && get_class($val) === '__PHP_Incomplete_Class') {
            $subLines = buildIncompleteObjectLines($val);
            $firstLine = array_shift($subLines);
            $lines[] = "['" . $propName . "'] => " . $firstLine;
            foreach ($subLines as $subLine) {
                $lines[] = '    ' . $subLine;
            }
        } else {
            $lines[] = "['" . $propName . "'] => " . formatSimpleValue($val);
        }
    }
    $lines[] = ')';
    return implode("\n", $lines);
}

/**
 * 格式化数组为可读字符串
 * @param array $arr 数组
 * @return string 格式化后的字符串
 */
function formatArray($arr) {
    $result = [];
    foreach ($arr as $k => $v) {
        $result[$k] = is_array($v) ? formatArray($v) : $v;
    }
    return $result;
}

/**
 * 格式化简单值为字符串（单行值，不含换行）
 * @param mixed $value 值
 * @return string 格式化后的字符串
 */
function formatSimpleValue($value) {
    if (is_string($value)) {
        return '"' . $value . '"';
    } elseif (is_bool($value)) {
        return $value ? 'true' : 'false';
    } elseif (is_null($value)) {
        return 'NULL';
    } elseif (is_array($value)) {
        return 'Array';
    } elseif (is_object($value)) {
        $cls = get_class($value);
        // 对象类型返回类名标记，实际展开由调用方处理
        return $cls . ' Object';
    }
    return (string)$value;
}

/**
 * 递归格式化不完整类对象为结构化行数组（不含前导缩进，由调用方统一加缩进）
 *
 * 返回的行数组结构：
 *   [0] => "ClassName Object"     类名行
 *   [1] => "("                   开括号行
 *   [2..n-2] => 属性行           每行格式: "['key'] => value" 或 嵌套子行
 *   [n-1] => ")"                  闭括号行
 *
 * @param object $incompleteObj __PHP_Incomplete_Class 实例
 * @return array 字符串行数组（每行不含前导空格）
 */
function buildIncompleteObjectLines($incompleteObj) {
    $props = (array)$incompleteObj;
    $originalClassName = isset($props['__PHP_Incomplete_Class_Name'])
        ? $props['__PHP_Incomplete_Class_Name']
        : 'Unknown';

    $lines = [$originalClassName . ' Object', '('];

    foreach ($props as $key => $val) {
        if ($key === '__PHP_Incomplete_Class_Name') continue;
        // 使用新的检测函数提取纯净属性名
        $cleanKey = stripNullBytePrefix($key);

        if (is_object($val) && get_class($val) === '__PHP_Incomplete_Class') {
            // 嵌套对象：递归获取子行，整体缩进一级
            $subLines = buildIncompleteObjectLines($val);
            $firstLine = array_shift($subLines);
            $lines[] = "['" . $cleanKey . "'] => " . $firstLine;
            foreach ($subLines as $subLine) {
                $lines[] = '    ' . $subLine; // 子内容缩进一级
            }
        } else {
            $lines[] = "['" . $cleanKey . "'] => " . formatSimpleValue($val);
        }
    }

    $lines[] = ')';
    return $lines;
}
