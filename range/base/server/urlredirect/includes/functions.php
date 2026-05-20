<?php
/**
 * HeaSec天积安全团队 - URL任意跳转靶场公共函数
 * 版本: v1.0.0
 * 创建日期: 2026-04-03
 * 团队: 天积安全 (HeavenlySecret)
 */

/**
 * 解析URL获取实际目标host（模拟浏览器的URL解析行为）
 * 浏览器将反斜杠(\)视为路径分隔符，支持协议省略(//开头)格式，
 * 这些行为与PHP原生parse_url不同，需要做兼容处理
 * @param string $url 原始URL
 * @return string|null 解析出的host，失败返回null
 */
function parseUrlHost($url)
{
    $normalizedUrl = $url;

    // 处理协议省略格式（// 或 /// 开头，浏览器会补全为当前页面协议）
    if (preg_match('#^/{2,}#', $normalizedUrl)) {
        $normalizedUrl = 'http://' . ltrim($normalizedUrl, '/');
    }

    // 将反斜杠标准化为正斜杠（浏览器将反斜杠视为路径分隔符，与/等价）
    // 这使得 \0heasec.com 变成 /0heasec.com（路径部分），不再被误认为host
    $normalizedUrl = str_replace('\\', '/', $normalizedUrl);

    // 使用标准parse_url解析标准化后的URL
    $parsed = @parse_url($normalizedUrl);
    if (!empty($parsed['host'])) {
        return $parsed['host'];
    }

    return null;
}

/**
 * 解析绕过方式类型（支持11种分类）
 * 检测顺序从最特殊到最一般，确保精确分类
 * @param string $url 原始URL
 * @param string $host 解析出的实际host
 * @return array ['type' => string, 'desc' => string, 'example' => string, 'scene' => string] 或 null
 */
function analyzeBypassType($url, $host)
{
    // 判断目标域名是否为百度域名
    $isTargetHost = ($host === 'www.baidu.com' || $host === 'baidu.com' || substr($host, -10) === '.baidu.com');

    if (!$isTargetHost) {
        return null;
    }

    // 标准化URL用于解析（与parseUrlHost保持一致的预处理逻辑）
    $normalizedUrl = $url;
    if (preg_match('#^/{2,}#', $normalizedUrl)) {
        $normalizedUrl = 'http://' . ltrim($normalizedUrl, '/');
    }
    $normalizedUrl = str_replace('\\', '/', $normalizedUrl);
    $parsed = @parse_url($normalizedUrl);

    // ====== 特殊格式检测（高优先级）======

    // 类型7：多层@混淆 - URL中出现2个及以上@符号
    // 如 http://user:pass@heasec.com@www.baidu.com，多个@导致解析歧义
    $atCount = substr_count($url, '@');
    if ($atCount >= 2) {
        return [
            'type' => '多层@混淆',
            'desc' => '利用多个@符号造成URL解析歧义，嵌套认证信息',
            'example' => $url,
            'scene' => '仅检查白名单域名字符串存在'
        ];
    }

    // 类型8：协议省略 - URL以//或///开头（无scheme）
    // 浏览器会自动补全当前页面协议，可能绕过scheme校验
    if (preg_match('#^/{2,}#', $url)) {
        return [
            'type' => '协议省略',
            'desc' => '省略URL协议头(//开头)，依赖浏览器自动补全协议',
            'example' => $url,
            'scene' => '仅检查白名单域名字符串存在'
        ];
    }

    // 类型6：反斜杠路径混淆 - 含反斜杠且heasec.com在路径部分
    // 与"反斜杠绕过"的区别：本类型是\作为路径分隔符，而非干扰@解析
    // 如 http://www.baidu.com\0heasec.com → \被视为路径分隔符
    if (strpos($url, '\\') !== false) {
        // 检查标准化后heasec.com是否落在path区域（非user/auth区域）
        $pathPart = isset($parsed['path']) ? $parsed['path'] : '';
        if (strpos($pathPart, 'heasec.com') !== false) {
            return [
                'type' => '反斜杠路径混淆',
                'desc' => '利用反斜杠作为路径分隔符，将白名单域名放入路径中',
                'example' => $url,
                'scene' => '仅检查白名单域名字符串存在'
            ];
        }
    }

    // ====== 标准绕过类型检测 ======

    // 类型5：协议混淆（@符号）- heasec.com在user认证信息中
    // 如 http://heasec.com@www.baidu.com，@前的内容被当作用户名
    if (!empty($parsed['user']) && strpos($parsed['user'], 'heasec.com') !== false) {
        return [
            'type' => '协议混淆',
            'desc' => '利用@符号将白名单域名伪装为认证信息(user)',
            'example' => $url,
            'scene' => '仅检查白名单域名字符串存在'
        ];
    }

    // 类型1：子域名欺骗 - heasec.com在host的子域名部分
    // 如 http://heasec.com.www.baidu.com，白名单域名作为子域名前缀
    if (strpos($host, 'heasec.com') !== false && $host !== 'heasec.com') {
        return [
            'type' => '子域名欺骗',
            'desc' => '将白名单域名作为目标域名的子域名前缀',
            'example' => $url,
            'scene' => '仅检查白名单域名字符串存在'
        ];
    }

    // 类型4：Fragment欺骗 - heasec.com在fragment(锚点)中
    // 如 http://www.baidu.com#heasec.com（注意：#后内容不发往服务器，此为理论类型）
    if (!empty($parsed['fragment']) && strpos($parsed['fragment'], 'heasec.com') !== false) {
        return [
            'type' => 'Fragment欺骗',
            'desc' => '将白名单域名作为URL片段标识符(Fragment)',
            'example' => $url,
            'scene' => '仅检查白名单域名字符串存在'
        ];
    }

    // 类型2：参数欺骗 - heasec.com在query参数中
    // 如 https://www.baidu.com?heasec.com，白名单域名作为参数值
    if (!empty($parsed['query']) && strpos($parsed['query'], 'heasec.com') !== false) {
        return [
            'type' => '参数欺骗',
            'desc' => '将白名单域名作为URL查询参数(query)',
            'example' => $url,
            'scene' => '仅检查白名单域名字符串存在'
        ];
    }

    // 类型3：路径欺骗 - heasec.com在path路径中（正斜杠形式）
    // 如 http://www.baidu.com/heasec.com 或 //www.baidu.com/heasec.com
    if (!empty($parsed['path']) && strpos($parsed['path'], 'heasec.com') !== false) {
        return [
            'type' => '路径欺骗',
            'desc' => '将白名单域名作为URL路径(path)',
            'example' => $url,
            'scene' => '仅检查白名单域名字符串存在'
        ];
    }

    // 其他未分类的绕过方式
    return [
        'type' => '其他',
        'desc' => '未分类的绕过方式',
        'example' => $url,
        'scene' => '仅检查白名单域名字符串存在'
    ];
}

/**
 * 处理URL跳转（漏洞逻辑）
 * 漏洞点：仅使用strpos检查URL中是否包含heasec.com字符串
 * @param int $userId 用户ID
 * @param string $url 跳转URL
 * @param PDO $pdo 数据库连接
 * @param string $fallbackUrl 白名单检查失败时的回退URL
 * @return void
 */
function handleUrlRedirect($userId, $url, $pdo, $fallbackUrl)
{
    if (!empty($url)) {
        // 漏洞点：仅检查字符串包含
        if (strpos($url, 'heasec.com') !== false) {
            // 通过白名单检查，记录请求到数据库并解析
            recordRequest($userId, $url, $pdo);
            // 防止CRLF注入：过滤换行符
            $safeUrl = str_replace(array("\r", "\n", "%0d", "%0a"), '', $url);
            header('Location: ' . $safeUrl);
            exit;
        }
        // 未通过白名单检查，拒绝（不记录）
    }
    // 无url或未通过白名单检查，跳转到fallback
    header('Location: ' . $fallbackUrl);
    exit;
}

/**
 * 记录URL请求到数据库（仅记录通过白名单检查的URL）
 * 调用前提：strpos($url, 'heasec.com') !== false 已经通过
 * @param int $userId 用户ID
 * @param string $url 原始URL（已通过白名单检查）
 * @param PDO $pdo 数据库连接
 * @return void
 */
function recordRequest($userId, $url, $pdo)
{
    // 使用parseUrlHost解析URL（支持反斜杠等特殊情况）
    $host = parseUrlHost($url);

    // 判断是否为目标域名（百度域名）
    $isTargetHost = ($host === 'www.baidu.com' || $host === 'baidu.com' || substr($host, -10) === '.baidu.com');

    // 解析绕过类型（仅对目标域名为百度的URL进行）
    $bypassType = null;
    $bypassDesc = null;
    if ($isTargetHost) {
        $bypassInfo = analyzeBypassType($url, $host);
        if ($bypassInfo) {
            $bypassType = $bypassInfo['type'];
            $bypassDesc = $bypassInfo['desc'];

            // 记录到成就表（按user_id + bypass_type唯一，重复则递增success_count）
            $stmt = $pdo->prepare("
                INSERT INTO heasec_urlredirect_achievements (user_id, bypass_type, bypass_desc, bypass_example, applicable_scene, success_count)
                VALUES (:user_id, :bypass_type, :bypass_desc, :bypass_example, :applicable_scene, 1)
                ON DUPLICATE KEY UPDATE
                success_count = success_count + 1,
                bypass_example = VALUES(bypass_example),
                applicable_scene = VALUES(applicable_scene)
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':bypass_type' => $bypassType,
                ':bypass_desc' => $bypassDesc,
                ':bypass_example' => $bypassInfo['example'],
                ':applicable_scene' => $bypassInfo['scene']
            ]);
        }
    }

    // 插入请求记录（所有通过白名单的URL都记录）
    $stmt = $pdo->prepare("
        INSERT INTO heasec_urlredirect_requests (user_id, raw_url, parsed_host, is_valid, bypass_type, bypass_desc)
        VALUES (:user_id, :raw_url, :parsed_host, :is_valid, :bypass_type, :bypass_desc)
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':raw_url' => $url,
        ':parsed_host' => $host ?: null,
        ':is_valid' => $isTargetHost ? 1 : 0,
        ':bypass_type' => $bypassType,
        ':bypass_desc' => $bypassDesc
    ]);
}
