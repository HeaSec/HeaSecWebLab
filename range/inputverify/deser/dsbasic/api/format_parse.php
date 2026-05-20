<?php
/**
 * HeaSec天积安全团队 - 序列化格式解析API
 * 版本: v2.0.0
 * 更新日期: 2026-04-12
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 解析序列化字符串，返回层级化树形结构数据
 */

header('Content-Type: application/json; charset=utf-8');
header('X-HeavenlySecret: HeaSec Format Parse API v2.0.0');

/** 最大递归深度限制 */
define('MAX_PARSE_DEPTH', 20);

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

    $tree = parseToTree($data);

    echo json_encode([
        'success' => true,
        'tree' => $tree
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 入口函数：将序列化字符串解析为树形结构
 */
function parseToTree($data) {
    $pos = 0;
    return parseValue($data, $pos, 0);
}

/**
 * 解析任意值节点（根据首字符分发到具体类型解析函数）
 * @param string $data 原始字符串
 * @param int &$pos 当前位置引用
 * @param int $depth 当前嵌套深度
 * @return array 节点数组
 */
function parseValue(&$data, &$pos, $depth) {
    if ($pos >= strlen($data)) {
        return ['type' => 'error', 'value' => '', 'segments' => []];
    }

    $char = $data[$pos];

    switch ($char) {
        case 's': return parseStringValue($data, $pos);
        case 'i': return parseIntegerValue($data, $pos);
        case 'd': return parseFloatValue($data, $pos);
        case 'b': return parseBooleanValue($data, $pos);
        case 'N': return parseNullValue($data, $pos);
        case 'a': return parseArrayValue($data, $pos, $depth);
        case 'O': return parseObjectValue($data, $pos, $depth);
        default:
            $seg = [
                'value' => $char,
                'description' => '未知字符',
                'color' => '#95a5a6',
                'css_class' => 'ser-separator'
            ];
            $pos++;
            return ['type' => 'unknown', 'value' => $char, 'segments' => [$seg]];
    }
}

/**
 * 解析容器类型（数组或对象）内部的键值对列表
 * 循环读取 key-value 对，直到遇到 } 或到达最大深度
 *
 * @param string $data 原始字符串引用
 * @param int &$pos 当前位置引用
 * @param int $depth 当前嵌套深度
 * @param string|null $parentType 父容器类型 'object' | 'array' | null（用于附加可见性信息）
 * @return array 键值对子节点数组
 */
function parseKeyValuePairs(&$data, &$pos, $depth, $parentType = null) {
    $children = [];

    while ($pos < strlen($data) && isset($data[$pos]) && $data[$pos] !== '}') {
        if ($depth >= MAX_PARSE_DEPTH) {
            break;
        }
        $keyNode = parseValue($data, $pos, $depth + 1);
        $valueNode = parseValue($data, $pos, $depth + 1);

        $kvp = [
            'type' => 'key_value_pair',
            'key' => $keyNode,
            'value' => $valueNode
        ];

        // 如果是对象的属性键，附加可见性信息（供前端展示使用）
        if ($parentType === 'object' && isset($keyNode['visibility'])) {
            $kvp['property_visibility'] = $keyNode['visibility'];
        }

        $children[] = $kvp;
    }

    return $children;
}

// ==================== 原子类型解析函数 ====================

/**
 * 解析字符串类型 s:length:"content";
 * 正则匹配 s:(\d+):" 后，位置已到内容起始处
 */
function parseStringValue(&$data, &$pos) {
    $startPos = $pos;
    $segments = [];

    if (preg_match('/^s:(\d+):"/', substr($data, $pos), $m)) {
        $strLen = (int)$m[1];
        $matchLen = strlen($m[0]); // 如 s:5:" 长度为 5

        // 类型标识 s
        $segments[] = [
            'value' => 's', 'description' => '字符串类型标识',
            'color' => '#3498db', 'css_class' => 'ser-identifier'
        ];

        // 冒号分隔符
        $segments[] = [
            'value' => ':', 'description' => '分隔符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        // 长度数字
        $segments[] = [
            'value' => $strLen, 'description' => '字符串长度为' . $strLen,
            'color' => '#e67e22', 'css_class' => 'ser-number'
        ];

        // :"
        $segments[] = [
            'value' => ':"', 'description' => '字符串值开始标记',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        // 推进位置到字符串内容开始处（跳过 s:length:"）
        $pos += $matchLen;

        // 读取字符串内容
        $strValue = substr($data, $pos, $strLen);
        $segments[] = [
            'value' => $strValue, 'description' => '字符串内容',
            'color' => '#27ae60', 'css_class' => 'ser-value'
        ];
        $pos += $strLen;

        // 结束标记 ";
        $segments[] = [
            'value' => '";', 'description' => '字符串结束标记',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];
        $pos += 2;

        $raw = substr($data, $startPos, $pos - $startPos);

        return [
            'type' => 'string',
            'label' => 'String',
            'raw' => $raw,
            'value' => $strValue,
            'length' => $strLen,
            'visibility' => detectKeyVisibility($strValue),
            'segments' => $segments
        ];
    }

    // 匹配失败，按未知字符处理
    $segments[] = [
        'value' => 's', 'description' => '未知字符',
        'color' => '#95a5a6', 'css_class' => 'ser-separator'
    ];
    $pos++;
    return ['type' => 'unknown', 'value' => 's', 'segments' => $segments];
}

/**
 * 解析整数类型 i:value;
 */
function parseIntegerValue(&$data, &$pos) {
    $startPos = $pos;
    $segments = [];

    if (preg_match('/^i:(-?\d+);/', substr($data, $pos), $m)) {
        $intValue = $m[1];

        $segments[] = [
            'value' => 'i', 'description' => '整数类型标识',
            'color' => '#3498db', 'css_class' => 'ser-identifier'
        ];
        $segments[] = [
            'value' => ':', 'description' => '分隔符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];
        $segments[] = [
            'value' => $intValue, 'description' => '整数值为' . $intValue,
            'color' => '#e67e22', 'css_class' => 'ser-number'
        ];
        $segments[] = [
            'value' => ';', 'description' => '元素结束符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        $pos += strlen($m[0]);
        $raw = substr($data, $startPos, $pos - $startPos);

        return [
            'type' => 'integer',
            'label' => 'Integer',
            'raw' => $raw,
            'value' => (int)$intValue,
            'segments' => $segments
        ];
    }

    $pos++;
    return ['type' => 'unknown', 'value' => 'i', 'segments' => []];
}

/**
 * 解析浮点数类型 d:value;
 */
function parseFloatValue(&$data, &$pos) {
    $startPos = $pos;
    $segments = [];

    if (preg_match('/^d:(-?\d+\.?\d*(?:[Ee][+-]?\d+)?);/', substr($data, $pos), $m)) {
        $floatVal = $m[1];

        $segments[] = [
            'value' => 'd', 'description' => '浮点数类型标识',
            'color' => '#3498db', 'css_class' => 'ser-identifier'
        ];
        $segments[] = [
            'value' => ':', 'description' => '分隔符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];
        $segments[] = [
            'value' => $floatVal, 'description' => '浮点数值为' . $floatVal,
            'color' => '#e67e22', 'css_class' => 'ser-number'
        ];
        $segments[] = [
            'value' => ';', 'description' => '元素结束符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        $pos += strlen($m[0]);
        $raw = substr($data, $startPos, $pos - $startPos);

        return [
            'type' => 'float',
            'label' => 'Float',
            'raw' => $raw,
            'value' => (float)$floatVal,
            'segments' => $segments
        ];
    }

    $pos++;
    return ['type' => 'unknown', 'value' => 'd', 'segments' => []];
}

/**
 * 解析布尔类型 b:0/1;
 */
function parseBooleanValue(&$data, &$pos) {
    $startPos = $pos;
    $segments = [];

    if (preg_match('/^b:([01]);/', substr($data, $pos), $m)) {
        $boolVal = $m[1] === '1' ? 'true' : 'false';
        $rawBool = $m[1];

        $segments[] = [
            'value' => 'b', 'description' => '布尔类型标识',
            'color' => '#3498db', 'css_class' => 'ser-identifier'
        ];
        $segments[] = [
            'value' => ':', 'description' => '分隔符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];
        $segments[] = [
            'value' => $rawBool, 'description' => '布尔值为' . $boolVal,
            'color' => '#27ae60', 'css_class' => 'ser-value'
        ];
        $segments[] = [
            'value' => ';', 'description' => '元素结束符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        $pos += strlen($m[0]);
        $raw = substr($data, $startPos, $pos - $startPos);

        return [
            'type' => 'boolean',
            'label' => 'Boolean',
            'raw' => $raw,
            'value' => $boolVal,
            'segments' => $segments
        ];
    }

    $pos++;
    return ['type' => 'unknown', 'value' => 'b', 'segments' => []];
}

/**
 * 解析 NULL 类型 N;
 */
function parseNullValue(&$data, &$pos) {
    $segments = [];

    if (isset($data[$pos + 1]) && $data[$pos + 1] === ';') {
        $segments[] = [
            'value' => 'N', 'description' => 'NULL类型标识',
            'color' => '#e74c3c', 'css_class' => 'ser-null'
        ];
        $segments[] = [
            'value' => ';', 'description' => '元素结束符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];
        $pos += 2;

        return [
            'type' => 'null',
            'label' => 'NULL',
            'raw' => 'N;',
            'segments' => $segments
        ];
    }

    $segments[] = [
        'value' => 'N', 'description' => '未知字符',
        'color' => '#95a5a6', 'css_class' => 'ser-separator'
    ];
    $pos++;
    return ['type' => 'unknown', 'value' => 'N', 'segments' => $segments];
}

/**
 * 从解析出的字符串值中检测是否为特殊属性键（含 null byte 前缀）
 * 用于对象属性的 key 解析时判断可见性
 *
 * @param string $strValue 解析出的字符串内容
 * @return string 'public' | 'protected' | 'private'
 */
function detectKeyVisibility($strValue) {
    if (isset($strValue[0]) && $strValue[0] === "\0") {
        if (isset($strValue[1]) && $strValue[1] === '*') {
            return 'protected';
        }
        return 'private';
    }
    return 'public';
}

// ==================== 容器类型解析函数 ====================

/**
 * 解析数组类型 a:count:{key_value_pairs}
 */
function parseArrayValue(&$data, &$pos, $depth) {
    $startPos = $pos;
    $segments = [];

    if (preg_match('/^a:(\d+):\{/', substr($data, $pos), $m)) {
        $elementCount = (int)$m[1];
        $matchLen = strlen($m[0]); // 如 a:2:{ 长度

        // 数组标识
        $segments[] = [
            'value' => 'a', 'description' => '数组类型标识',
            'color' => '#3498db', 'css_class' => 'ser-identifier'
        ];
        $segments[] = [
            'value' => ':', 'description' => '分隔符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        // 元素个数
        $segments[] = [
            'value' => $elementCount, 'description' => '数组元素个数为' . $elementCount,
            'color' => '#e67e22', 'css_class' => 'ser-number'
        ];
        $segments[] = [
            'value' => ':', 'description' => '分隔符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        // 内容开始 {
        $segments[] = [
            'value' => '{', 'description' => '数组内容开始',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        // 推进位置到 { 之后（即第一个键值对开始处）
        $pos += $matchLen;

        // 解析键值对子节点
        $children = parseKeyValuePairs($data, $pos, $depth + 1, 'array');

        // 结束标记 }
        $footerSegment = null;
        if ($pos < strlen($data) && isset($data[$pos]) && $data[$pos] === '}') {
            $footerSegment = [
                'value' => '}', 'description' => '数组结束标记',
                'color' => '#95a5a6', 'css_class' => 'ser-separator'
            ];
            $pos++;
        }

        $raw = substr($data, $startPos, $pos - $startPos);

        return [
            'type' => 'array',
            'label' => 'Array',
            'raw' => $raw,
            'element_count' => $elementCount,
            'header_segments' => $segments,
            'children' => $children,
            'footer_segment' => $footerSegment
        ];
    }

    $segments[] = [
        'value' => 'a', 'description' => '未知字符',
        'color' => '#95a5a6', 'css_class' => 'ser-separator'
    ];
    $pos++;
    return ['type' => 'unknown', 'value' => 'a', 'segments' => $segments];
}

/**
 * 解析对象类型 O:len:"ClassName":propCount:{properties}
 */
function parseObjectValue(&$data, &$pos, $depth) {
    $startPos = $pos;
    $segments = [];

    if (preg_match('/^O:(\d+):"([^"]+)":(\d+):\{/', substr($data, $pos), $m)) {
        $classNameLen = (int)$m[1];
        $className = $m[2];
        $propCount = (int)$m[3];
        $matchLen = strlen($m[0]); // 完整匹配长度

        // 对象标识 O
        $segments[] = [
            'value' => 'O', 'description' => '对象类型标识',
            'color' => '#3498db', 'css_class' => 'ser-identifier'
        ];
        $segments[] = [
            'value' => ':', 'description' => '分隔符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        // 类名长度
        $segments[] = [
            'value' => $classNameLen, 'description' => '类名长度为' . $classNameLen,
            'color' => '#e67e22', 'css_class' => 'ser-number'
        ];
        $segments[] = [
            'value' => ':', 'description' => '分隔符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        // 类名（带引号）
        $segments[] = [
            'value' => $className, 'description' => '类名：' . $className,
            'color' => '#9b59b6', 'css_class' => 'ser-class'
        ];
        $segments[] = [
            'value' => '":', 'description' => '分隔符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        // 属性个数
        $segments[] = [
            'value' => $propCount, 'description' => '属性个数为' . $propCount,
            'color' => '#e67e22', 'css_class' => 'ser-number'
        ];
        $segments[] = [
            'value' => ':', 'description' => '分隔符',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        // 属性开始 {
        $segments[] = [
            'value' => '{', 'description' => '对象属性开始',
            'color' => '#95a5a6', 'css_class' => 'ser-separator'
        ];

        // 推进位置到 { 之后（即第一个属性开始处）
        $pos += $matchLen;

        // 解析属性键值对子节点
        $children = parseKeyValuePairs($data, $pos, $depth + 1, 'object');

        // 结束标记 }
        $footerSegment = null;
        if ($pos < strlen($data) && isset($data[$pos]) && $data[$pos] === '}') {
            $footerSegment = [
                'value' => '}', 'description' => '对象结束标记',
                'color' => '#95a5a6', 'css_class' => 'ser-separator'
            ];
            $pos++;
        }

        $raw = substr($data, $startPos, $pos - $startPos);

        return [
            'type' => 'object',
            'label' => 'Object',
            'raw' => $raw,
            'class_name' => $className,
            'property_count' => $propCount,
            'header_segments' => $segments,
            'children' => $children,
            'footer_segment' => $footerSegment
        ];
    }

    $segments[] = [
        'value' => 'O', 'description' => '未知字符',
        'color' => '#95a5a6', 'css_class' => 'ser-separator'
    ];
    $pos++;
    return ['type' => 'unknown', 'value' => 'O', 'segments' => $segments];
}
