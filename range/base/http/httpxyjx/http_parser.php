<?php
/**
 * HeaSec天积安全团队 - HTTP请求解析类
 * 版本: v1.0.0
 * 创建日期: 2025-11-03
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 此类包含HTTP请求头智能解析相关功能
 * 从index.php中拆分出来，提高代码可维护性
 */

class HeaSec_HttpParser {

    /**
     * 构造函数
     */
    public function __construct() {
        // 初始化配置
    }

    /**
     * 智能解析Accept-Language请求头
     * @param string $acceptLanguage Accept-Language头内容
     * @return string 解析结果描述
     */
    public function parseAcceptLanguage($acceptLanguage) {
        // 语言代码到中文描述的映射
        $languageMap = array(
            'zh' => '中文',
            'zh-CN' => '中文（简体）',
            'zh-TW' => '中文（繁体）',
            'zh-HK' => '中文（香港）',
            'en' => '英文',
            'en-US' => '英文（美国）',
            'en-GB' => '英文（英国）',
            'ja' => '日文',
            'ja-JP' => '日文',
            'ko' => '韩文',
            'ko-KR' => '韩文',
            'fr' => '法文',
            'fr-FR' => '法文（法国）',
            'de' => '德文',
            'de-DE' => '德文（德国）',
            'es' => '西班牙文',
            'es-ES' => '西班牙文（西班牙）',
            'it' => '意大利文',
            'it-IT' => '意大利文（意大利）',
            'ru' => '俄文',
            'ru-RU' => '俄文（俄罗斯）',
            'pt' => '葡萄牙文',
            'pt-BR' => '葡萄牙文（巴西）',
            'pt-PT' => '葡萄牙文（葡萄牙）',
            'ar' => '阿拉伯文',
            'hi' => '印地文',
            'th' => '泰文',
            'vi' => '越南文',
            'id' => '印尼文',
            'ms' => '马来文',
            'tr' => '土耳其文',
            'pl' => '波兰文',
            'nl' => '荷兰文',
            'sv' => '瑞典文',
            'da' => '丹麦文',
            'no' => '挪威文',
            'fi' => '芬兰文',
            'cs' => '捷克文',
            'sk' => '斯洛伐克文',
            'hu' => '匈牙利文',
            'ro' => '罗马尼亚文',
            'bg' => '保加利亚文',
            'hr' => '克罗地亚文',
            'sr' => '塞尔维亚文',
            'sl' => '斯洛文尼亚文',
            'et' => '爱沙尼亚文',
            'lv' => '拉脱维亚文',
            'lt' => '立陶宛文',
            'uk' => '乌克兰文',
            'el' => '希腊文',
            'he' => '希伯来文',
            'fa' => '波斯文',
            'ur' => '乌尔都文',
            'bn' => '孟加拉文',
            'ta' => '泰米尔文',
            'te' => '泰卢固文',
            'ml' => '马拉雅拉姆文',
            'kn' => '卡纳达文',
            'gu' => '古吉拉特文',
            'pa' => '旁遮普文',
            'mr' => '马拉地文',
            'ne' => '尼泊尔文',
            'si' => '僧伽罗文',
            'my' => '缅甸文',
            'km' => '高棉文',
            'lo' => '老挝文',
            'ka' => '格鲁吉亚文',
            'am' => '阿姆哈拉文',
            'sw' => '斯瓦希里文',
            'zu' => '祖鲁文',
            'af' => '南非荷兰语',
            'is' => '冰岛文',
            'mt' => '马耳他文',
            'cy' => '威尔士文',
            'ga' => '爱尔兰文',
            'gd' => '苏格兰盖尔文',
            'eu' => '巴斯克文',
            'ca' => '加泰罗尼亚文',
            'gl' => '加利西亚文',
            'be' => '白俄罗斯文'
        );

        // 解析Accept-Language头
        $languages = array();
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // 解析语言和权重
            $segments = explode(';q=', $part);
            $lang = trim($segments[0]);
            $quality = isset($segments[1]) ? floatval($segments[1]) : 1.0;

            // 获取语言描述
            $description = isset($languageMap[$lang]) ? $languageMap[$lang] : $lang;

            $languages[] = array(
                'code' => $lang,
                'description' => $description,
                'quality' => $quality
            );
        }

        // 按权重排序
        usort($languages, function($a, $b) {
            if ($b['quality'] == $a['quality']) {
                return 0;
            }
            return ($b['quality'] > $a['quality']) ? 1 : -1;
        });

        // 生成自然语言描述
        if (empty($languages)) {
            return "本次请求头未包含语言偏好信息。";
        }

        $count = count($languages);
        if ($count == 1) {
            $desc = $languages[0]['description'];
            return "本次请求头表示用户使用的语言是" . $desc . "。";
        }

        // 多种语言的情况
        $descriptions = array();
        foreach ($languages as $lang) {
            $descriptions[] = $lang['description'];
        }

        // 去重并保持顺序
        $uniqueDescriptions = array();
        $seen = array();
        foreach ($descriptions as $desc) {
            if (!in_array($desc, $seen)) {
                $uniqueDescriptions[] = $desc;
                $seen[] = $desc;
            }
        }

        $uniqueCount = count($uniqueDescriptions);
        if ($uniqueCount == 1) {
            return "本次请求头表示用户使用的语言是" . $uniqueDescriptions[0] . "。";
        } elseif ($uniqueCount == 2) {
            return "本次请求头表示用户使用的主要语言是" . $uniqueDescriptions[0] . "，" . $uniqueDescriptions[1] . "次之。";
        } else {
            $primary = array_shift($uniqueDescriptions);
            $secondary = array_shift($uniqueDescriptions);
            $remaining = implode('、', $uniqueDescriptions);
            return "本次请求头表示用户使用的主要语言是" . $primary . "，" . $secondary . "次之，还有" . $remaining . "。";
        }
    }

    /**
     * 智能解析User-Agent请求头
     * @param string $userAgent User-Agent头内容
     * @return string 解析结果描述
     */
    public function parseUserAgent($userAgent) {
        if (empty($userAgent)) {
            $analysis = "本次请求头未包含User-Agent信息。";

            // 智能分析不存在的原因
            $reasons = array();
            $reasons[] = "可能是极简化的HTTP客户端（如curl、telnet等）";
            $reasons[] = "可能是自定义的网络爬虫或脚本工具";
            $reasons[] = "可能是某些安全工具或扫描器";
            $reasons[] = "客户端故意隐藏用户代理信息";

            // 分析请求方法
            $method = $_SERVER['REQUEST_METHOD'];
            if ($method === 'GET' || $method === 'POST') {
                $reasons[] = "可能是程序化的API调用";
            }

            $analysis .= implode("，", array_slice($reasons, 0, 3)) . "等原因。";

            return $analysis;
        }

        $result = array(
            'browser' => '',
            'browser_version' => '',
            'os' => '',
            'os_version' => '',
            'architecture' => '',
            'device_type' => '',
            'device' => ''
        );

        // 浏览器识别规则
        $browserPatterns = array(
            'Edge' => array(
                'pattern' => '/Edge\/(\d+\.\d+\.\d+\.\d+)/',
                'name' => 'Edge'
            ),
            'Edg' => array(
                'pattern' => '/Edg\/(\d+\.\d+\.\d+\.\d+)/',
                'name' => 'Edge'
            ),
            'Chrome' => array(
                'pattern' => '/Chrome\/(\d+\.\d+\.\d+\.\d+)/',
                'name' => 'Chrome',
                'exclude' => 'Edg' // 排除Edge，因为Edge也包含Chrome字符串
            ),
            'Firefox' => array(
                'pattern' => '/Firefox\/(\d+\.\d+)/',
                'name' => 'Firefox'
            ),
            'Safari' => array(
                'pattern' => '/Safari\/(\d+\.\d+\.\d+)/',
                'name' => 'Safari',
                'exclude' => 'Chrome' // 排除Chrome，因为Chrome也包含Safari字符串
            ),
            'Opera' => array(
                'pattern' => '/Opera|OPR\/(\d+\.\d+\.\d+\.\d+)/',
                'name' => 'Opera'
            ),
            'Internet Explorer' => array(
                'pattern' => '/MSIE\s(\d+\.\d+);|Trident.*rv:(\d+\.\d+)/',
                'name' => 'Internet Explorer'
            ),
            'Googlebot' => array(
                'pattern' => '/Googlebot\/(\d+\.\d+)/',
                'name' => 'Googlebot'
            ),
            'Baidu' => array(
                'pattern' => '/Baiduspider\/(\d+\.\d+)/',
                'name' => 'Baidu蜘蛛'
            )
        );

        // 操作系统识别规则
        $osPatterns = array(
            'Windows' => array(
                'pattern' => '/Windows\sNT\s([\d.]+)/',
                'name' => 'Windows',
                'version_map' => array(
                    '10.0' => 'Windows 10',
                    '6.3' => 'Windows 8.1',
                    '6.2' => 'Windows 8',
                    '6.1' => 'Windows 7',
                    '6.0' => 'Windows Vista',
                    '5.1' => 'Windows XP'
                )
            ),
            'macOS' => array(
                'pattern' => '/Mac\sOS\sX\s([0-9_]+)/',
                'name' => 'macOS',
                'format_version' => function($version) {
                    return str_replace('_', '.', $version);
                }
            ),
            'Linux' => array(
                'pattern' => '/Linux/i',
                'name' => 'Linux'
            ),
            'Ubuntu' => array(
                'pattern' => '/Ubuntu\/([\d.]+)/',
                'name' => 'Ubuntu'
            ),
            'Android' => array(
                'pattern' => '/Android\s([\d.]+)/',
                'name' => 'Android'
            ),
            'iOS' => array(
                'pattern' => '/iPhone\sOS\s([\d_]+)/',
                'name' => 'iOS',
                'format_version' => function($version) {
                    return str_replace('_', '.', $version);
                }
            ),
            'iPadOS' => array(
                'pattern' => '/iPad.*OS\s([\d_]+)/',
                'name' => 'iPadOS',
                'format_version' => function($version) {
                    return str_replace('_', '.', $version);
                }
            )
        );

        // 架构识别
        $archPatterns = array(
            'Win64' => array(
                'pattern' => '/WOW64|Win64|x64|amd64/i',
                'name' => '64位'
            ),
            'Win32' => array(
                'pattern' => '/Windows|Win32/i',
                'name' => '32位'
            ),
            'ARM64' => array(
                'pattern' => '/arm64|aarch64/i',
                'name' => 'ARM64'
            ),
            'ARM' => array(
                'pattern' => '/arm|aarch/i',
                'name' => 'ARM'
            ),
            'x86_64' => array(
                'pattern' => '/x86_64|amd64/i',
                'name' => '64位'
            )
        );

        // 设备类型识别
        $devicePatterns = array(
            'Mobile' => array(
                'pattern' => '/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i',
                'name' => '移动设备'
            ),
            'Tablet' => array(
                'pattern' => '/iPad|Tablet/i',
                'name' => '平板电脑'
            ),
            'Desktop' => array(
                'pattern' => '/Windows|Macintosh|Linux|x11/i',
                'name' => '桌面电脑'
            ),
            'Bot' => array(
                'pattern' => '/bot|crawler|spider|scraper/i',
                'name' => '网络爬虫'
            )
        );

        // 解析浏览器
        foreach ($browserPatterns as $key => $browser) {
            if (isset($browser['exclude']) && strpos($userAgent, $browser['exclude']) !== false) {
                continue;
            }

            if (preg_match($browser['pattern'], $userAgent, $matches)) {
                $result['browser'] = $browser['name'];
                if (isset($matches[1])) {
                    $result['browser_version'] = $matches[1];
                }
                break;
            }
        }

        // 解析操作系统
        foreach ($osPatterns as $key => $os) {
            if (preg_match($os['pattern'], $userAgent, $matches)) {
                $result['os'] = $os['name'];
                if (isset($matches[1])) {
                    $version = $matches[1];
                    if (isset($os['version_map'][$version])) {
                        $result['os_version'] = $os['version_map'][$version];
                    } elseif (isset($os['format_version'])) {
                        $result['os_version'] = $os['format_version']($version);
                    } else {
                        $result['os_version'] = $version;
                    }
                }
                break;
            }
        }

        // 解析架构
        foreach ($archPatterns as $key => $arch) {
            if (preg_match($arch['pattern'], $userAgent)) {
                $result['architecture'] = $arch['name'];
                break;
            }
        }

        // 解析设备类型
        foreach ($devicePatterns as $key => $device) {
            if (preg_match($device['pattern'], $userAgent)) {
                $result['device_type'] = $device['name'];
                break;
            }
        }

        // 生成自然语言描述
        $description = "本次请求头表示客户端";

        // 设备类型
        if (!empty($result['device_type'])) {
            $description .= "使用的是" . $result['device_type'];
        }

        // 操作系统信息 - 避免重复
        $osInfo = array();
        if (!empty($result['os'])) {
            // 检查os_version是否已经包含os名称
            if (!empty($result['os_version']) && strpos($result['os_version'], $result['os']) !== false) {
                $osInfo[] = $result['os_version']; // 如果版本信息已包含OS名称，直接使用版本信息
            } else {
                $osInfo[] = $result['os'];
                if (!empty($result['os_version'])) {
                    $osInfo[] = $result['os_version'];
                }
            }
        }
        if (!empty($result['architecture'])) {
            $osInfo[] = $result['architecture'];
        }

        if (!empty($osInfo)) {
            $description .= "，" . implode(' ', $osInfo);
        }

        // 浏览器
        if (!empty($result['browser'])) {
            $browserInfo = $result['browser'];
            if (!empty($result['browser_version'])) {
                $browserInfo .= " " . $result['browser_version'];
            }
            if (!empty($osInfo)) {
                $description .= "，浏览器为" . $browserInfo;
            } else {
                $description .= "使用的浏览器是" . $browserInfo;
            }
        }

        $description .= "。";

        // 特殊情况处理
        if (strpos($userAgent, 'Googlebot') !== false) {
            $description = "本次请求头表示客户端是Google搜索引擎的网络爬虫。";
        } elseif (strpos($userAgent, 'Baiduspider') !== false) {
            $description = "本次请求头表示客户端是百度搜索引擎的网络爬虫。";
        } elseif (empty($result['browser']) && empty($result['os'])) {
            $description = "本次请求头表示客户端信息未知或被隐藏。";
        }

        return $description;
    }

    /**
     * 智能解析Accept-Charset请求头
     * @param string $acceptCharset Accept-Charset头内容
     * @return string 解析结果描述
     */
    public function parseAcceptCharset($acceptCharset) {
        if (empty($acceptCharset)) {
            return "本次请求头未包含字符编码偏好信息，服务器将使用默认编码。";
        }

        $charsets = array();
        $parts = explode(',', $acceptCharset);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // 解析编码和权重
            $segments = explode(';q=', $part);
            $charset = trim($segments[0]);
            $quality = isset($segments[1]) ? floatval($segments[1]) : 1.0;

            // 字符编码到中文描述的映射
            $charsetMap = array(
                'UTF-8' => 'UTF-8编码',
                'ISO-8859-1' => 'ISO-8859-1编码（拉丁字母）',
                'GBK' => 'GBK编码（汉字内码扩展规范）',
                'GB2312' => 'GB2312编码（简体中文）',
                'Big5' => 'Big5编码（繁体中文）',
                'Shift_JIS' => 'Shift_JIS编码（日文）',
                'EUC-JP' => 'EUC-JP编码（日文）',
                'EUC-KR' => 'EUC-KR编码（韩文）',
                'Windows-1251' => 'Windows-1251编码（西里尔字母）',
                'Windows-1252' => 'Windows-1252编码（西欧字母）'
            );

            $description = isset($charsetMap[$charset]) ? $charsetMap[$charset] : $charset;
            $charsets[] = array('name' => $charset, 'description' => $description, 'quality' => $quality);
        }

        // 按权重排序
        usort($charsets, function($a, $b) {
            if ($b['quality'] == $a['quality']) {
                return 0;
            }
            return ($b['quality'] > $a['quality']) ? 1 : -1;
        });

        $count = count($charsets);
        if ($count == 1) {
            return "本次请求头表示客户端仅接受" . $charsets[0]['description'] . "的响应内容。";
        } elseif ($count == 2) {
            return "本次请求头表示客户端优先接受" . $charsets[0]['description'] . "，其次接受" . $charsets[1]['description'] . "。";
        } else {
            $primary = array_shift($charsets);
            $secondary = array_shift($charsets);
            $others = array();
            foreach ($charsets as $charset) {
                $others[] = $charset['description'];
            }
            return "本次请求头表示客户端优先接受" . $primary['description'] . "，" . $secondary['description'] . "次之，还有" . implode('、', $others) . "。";
        }
    }

    /**
     * 智能解析Cache-Control请求头
     * @param string $cacheControl Cache-Control头内容
     * @return string 解析结果描述
     */
    public function parseCacheControl($cacheControl) {
        if (empty($cacheControl)) {
            return "本次请求头未包含缓存控制指令。";
        }

        $directives = array();
        $parts = explode(',', $cacheControl);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            // 分离指令和参数
            if (strpos($part, '=') !== false) {
                list($directive, $value) = explode('=', $part, 2);
                $directives[trim($directive)] = trim($value, '"');
            } else {
                $directives[$part] = true;
            }
        }

        $description = "本次请求头表示客户端的缓存控制要求：";

        if (isset($directives['no-cache'])) {
            $description .= "不使用缓存（no-cache），";
        }
        if (isset($directives['no-store'])) {
            $description .= "不存储缓存内容（no-store），";
        }
        if (isset($directives['max-age'])) {
            $description .= "允许缓存内容最多" . $directives['max-age'] . "秒（max-age），";
        }
        if (isset($directives['max-stale'])) {
            $description .= "允许使用过期最多" . $directives['max-stale'] . "秒的缓存（max-stale），";
        }
        if (isset($directives['min-fresh'])) {
            $description .= "要求缓存至少保持" . $directives['min-fresh'] . "秒新鲜度（min-fresh），";
        }
        if (isset($directives['must-revalidate'])) {
            $description .= "必须重新验证缓存有效性（must-revalidate），";
        }
        if (isset($directives['public'])) {
            $description .= "缓存可被共享存储（public），";
        }
        if (isset($directives['private'])) {
            $description .= "缓存仅限私人使用（private），";
        }

        // 移除最后的逗号
        $description = rtrim($description, '，');

        if ($description === "本次请求头表示客户端的缓存控制要求：") {
            $description .= "包含其他缓存指令：" . $cacheControl;
        }

        return $description . "。";
    }

    /**
     * 智能解析Cookie请求头
     * @param string $cookie Cookie头内容
     * @return string 解析结果描述
     */
    public function parseCookie($cookie) {
        if (empty($cookie)) {
            $analysis = "本次请求头未包含Cookie信息。";

            // 智能分析不存在的原因
            $reasons = array();
            $reasons[] = "表示这是用户的首次访问";
            $reasons[] = "用户之前清除了浏览器Cookie";
            $reasons[] = "用户开启了隐私模式或无痕浏览";
            $reasons[] = "Cookie已过期被浏览器自动删除";
            $reasons[] = "浏览器设置了阻止Cookie的策略";

            // 分析当前站点状态
            if (session_status() === PHP_SESSION_NONE) {
                $reasons[] = "服务器未启用会话功能";
            }

            // 分析用户代理
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
            if (strpos($userAgent, 'bot') !== false || strpos($userAgent, 'crawler') !== false) {
                $reasons[] = "可能是爬虫或机器人访问，通常不会携带Cookie";
            }

            $analysis .= implode("，", array_slice($reasons, 0, 4)) . "等原因。";

            return $analysis;
        }

        $cookies = array();
        $pairs = explode(';', $cookie);

        foreach ($pairs as $pair) {
            $pair = trim($pair);
            if (empty($pair)) continue;

            if (strpos($pair, '=') !== false) {
                list($name, $value) = explode('=', $pair, 2);
                $cookies[trim($name)] = trim($value);
            }
        }

        $count = count($cookies);
        if ($count == 0) {
            return "本次请求头包含Cookie信息但格式无法解析。";
        }

        $description = "本次请求头表示客户端携带了" . $count . "个Cookie：";
        $cookieList = array();

        foreach ($cookies as $name => $value) {
            $cookieList[] = $name . "=" . $value;
        }

        $description .= implode("，", $cookieList) . "，";

        // 分析Cookie用途
        if (isset($cookies['PHPSESSID']) || isset($cookies['session_id'])) {
            $description .= "包含会话标识，服务器可用于会话跟踪；";
        }
        if (isset($cookies['username']) || isset($cookies['user_id']) || isset($cookies['token'])) {
            $description .= "包含用户身份信息，服务器可用于用户认证；";
        }
        if (isset($cookies['remember_me']) || isset($cookies['auto_login'])) {
            $description .= "包含自动登录信息，服务器可用于保持登录状态；";
        }
        if (isset($cookies['preferences']) || isset($cookies['settings'])) {
            $description .= "包含用户偏好设置，服务器可用于个性化服务；";
        }
        if (isset($cookies['visit_count']) || isset($cookies['last_visit'])) {
            $description .= "包含访问统计信息，服务器可用于用户行为分析；";
        }

        // 移除最后的分号
        $description = rtrim($description, '；');

        return $description . "。";
    }

    /**
     * 智能解析Content-Type请求头
     * @param string $contentType Content-Type头内容
     * @param string $requestMethod 请求方法
     * @return string 解析结果描述
     */
    public function parseContentType($contentType, $requestMethod = null) {
        if (empty($contentType)) {
            $analysis = "本次请求头未包含内容类型信息。";

            // 智能分析不存在的原因
            $method = $requestMethod ?: $_SERVER['REQUEST_METHOD'];

            if ($method === 'GET' || $method === 'HEAD') {
                $analysis .= "由于请求方法为" . $method . "，通常不包含请求体，所以不需要Content-Type头。";
            } elseif ($method === 'POST' || $method === 'PUT') {
                $analysis .= "对于" . $method . "请求，缺少Content-Type头可能表示：1) 请求体为空；2) 服务器将使用默认的application/x-www-form-urlencoded格式解析；3) 可能导致服务器无法正确解析请求体数据。";
            } else {
                $analysis .= "对于" . $method . "请求，通常不需要指定内容类型。";
            }

            return $analysis;
        }

        $description = "本次请求头表示请求体的媒体类型为：";

        // 解析主要类型
        if (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            $description .= "URL编码的表单数据，适用于POST方法提交的表单数据";
        } elseif (strpos($contentType, 'multipart/form-data') !== false) {
            $description .= "多部分表单数据";
            if (preg_match('/boundary=([^;]+)/', $contentType, $matches)) {
                $description .= "，使用分隔符：" . trim($matches[1], '"');
            }
            $description .= "，适用于上传文件等场景";
        } elseif (strpos($contentType, 'application/json') !== false) {
            $description .= "JSON格式数据，常用于API调用";
        } elseif (strpos($contentType, 'application/xml') !== false) {
            $description .= "XML格式数据";
        } elseif (strpos($contentType, 'text/plain') !== false) {
            $description .= "纯文本数据";
        } elseif (strpos($contentType, 'text/html') !== false) {
            $description .= "HTML文档数据";
        } elseif (strpos($contentType, 'application/octet-stream') !== false) {
            $description .= "二进制流数据";
        } else {
            $description .= $contentType;
        }

        // 解析字符编码
        if (preg_match('/charset=([^;]+)/', $contentType, $matches)) {
            $description .= "，使用" . trim($matches[1], '"') . "字符编码";
        }

        return $description . "。";
    }

    /**
     * 智能解析Content-Length请求头
     * @param string $contentLength Content-Length头内容
     * @param string $requestMethod 请求方法
     * @return string 解析结果描述
     */
    public function parseContentLength($contentLength, $requestMethod = null) {
        if (empty($contentLength) || !is_numeric($contentLength)) {
            $analysis = "本次请求头未包含内容长度信息。";

            // 智能分析不存在的原因
            $method = $requestMethod ?: $_SERVER['REQUEST_METHOD'];

            if ($method === 'GET' || $method === 'HEAD') {
                $analysis .= "由于请求方法为" . $method . "，通常不包含请求体，所以不需要Content-Length头。";
            } elseif ($method === 'POST' || $method === 'PUT') {
                $analysis .= "对于" . $method . "请求，缺少Content-Length头可能表示：1) 请求体为空；2) 使用了Transfer-Encoding: chunked编码；3) 服务器可能无法正确处理请求体。";
            } else {
                $analysis .= "对于" . $method . "请求，通常不需要指定内容长度。";
            }

            return $analysis;
        }

        $length = intval($contentLength);
        $description = "本次请求头表示请求体的字节长度为" . $length . "个字节";

        // 添加大小描述
        if ($length < 1024) {
            $description .= "（较小文件）";
        } elseif ($length < 1024 * 1024) {
            $kb = round($length / 1024, 2);
            $description .= "，约" . $kb . "KB";
        } else {
            $mb = round($length / (1024 * 1024), 2);
            $description .= "，约" . $mb . "MB";
        }

        return $description . "。";
    }

    /**
     * 智能解析Host请求头
     * @param string $host Host头内容
     * @return string 解析结果描述
     */
    public function parseHost($host) {
        if (empty($host)) {
            return "本次请求头未包含主机信息。";
        }

        $description = "本次请求头表示请求的目标主机";

        // 解析主机名和端口
        if (strpos($host, ':') !== false) {
            list($hostname, $port) = explode(':', $host, 2);
            $description .= "是" . $hostname . "，端口号为" . $port;

            // 添加常见端口说明
            $portDescriptions = array(
                '80' => '（HTTP默认端口）',
                '443' => '（HTTPS默认端口）',
                '8080' => '（常用代理端口）',
                '3000' => '（常用开发端口）',
                '8000' => '（常用开发端口）',
                '8888' => '（常用开发端口）'
            );

            if (isset($portDescriptions[$port])) {
                $description .= $portDescriptions[$port];
            }
        } else {
            $description .= "是" . $host;
            $description .= "（使用默认端口）";
        }

        return $description . "。";
    }

    /**
     * 智能解析Date请求头
     * @param string $date Date头内容
     * @return string 解析结果描述
     */
    public function parseDate($date) {
        if (empty($date)) {
            return "本次请求头未包含日期时间信息。";
        }

        // 尝试解析各种日期格式
        $formats = array(
            'D, d M Y H:i:s T',  // RFC 822
            'D, d-M-y H:i:s T',   // RFC 850
            'D, d-M-Y H:i:s T',   // RFC 1036
            'D M d H:i:s Y T',    // ANSI C
            'Y-m-d\TH:i:s\Z',     // ISO 8601
            'Y-m-d H:i:s'         // 标准日期格式
        );

        $timestamp = null;
        foreach ($formats as $format) {
            $timestamp = strtotime($date);
            if ($timestamp !== false) break;
        }

        if ($timestamp === false) {
            return "本次请求头表示请求发送的日期时间为：" . $date . "（格式无法解析）。";
        }

        $description = "本次请求头表示请求发送的日期和时间为：" . date('Y年m月d日星期H', $timestamp) . "时" . date('i分', $timestamp);

        // 添加时区信息
        if (preg_match('/GMT|UTC/i', $date)) {
            $description .= "（格林威治标准时间）";
        }

        return $description . "。";
    }

    /**
     * 智能解析Referer请求头
     * @param string $referer Referer头内容
     * @return string 解析结果描述
     */
    public function parseReferer($referer) {
        if (empty($referer)) {
            $analysis = "本次请求未包含Referer字段，";

            // 智能分析不存在的原因
            $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
            $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            $currentScheme = $isHttps ? 'https' : 'http';

            // 分析可能的原因
            $reasons = array();
            $reasons[] = "表示用户可能是直接在浏览器地址栏输入URL访问当前页面";
            $reasons[] = "从书签收藏夹访问";
            $reasons[] = "点击邮件中的链接";
            $reasons[] = "从隐私模式或无痕浏览模式访问";

            // HTTPS到HTTP的跳转
            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                $refererScheme = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_SCHEME);
                if ($refererScheme === 'https' && !$isHttps) {
                    $reasons[] = "从HTTPS页面跳转到HTTP页面时，浏览器出于安全考虑不会发送Referer头";
                }
            }

            // 移动端应用
            if (strpos($userAgent, 'mobile') !== false || strpos($userAgent, 'android') !== false || strpos($userAgent, 'iphone') !== false) {
                $reasons[] = "从移动端应用内嵌浏览器访问";
            }

            // 某些安全策略
            if (strpos($userAgent, 'firefox') !== false) {
                $reasons[] = "Firefox浏览器的隐私保护模式或跟踪保护功能阻止了Referer发送";
            }

            $analysis .= implode("，", array_slice($reasons, 0, 3)) . "等原因。";

            return $analysis;
        }

        $description = "本次请求头表示用户是从";

        // 解析URL
        $urlParts = parse_url($referer);

        if (isset($urlParts['scheme'])) {
            $description .= $urlParts['scheme'] . "://";
        }

        if (isset($urlParts['host'])) {
            $description .= $urlParts['host'];
        }

        if (isset($urlParts['port']) && $urlParts['port'] != 80 && $urlParts['port'] != 443) {
            $description .= ":" . $urlParts['port'];
        }

        if (isset($urlParts['path'])) {
            $description .= $urlParts['path'];
        }

        if (isset($urlParts['query'])) {
            $description .= "?" . $urlParts['query'];
        }

        $description .= "页面点击链接来到当前页面的。";

        return $description;
    }

    /**
     * 智能解析Connection请求头
     * @param string $connection Connection头内容
     * @return string 解析结果描述
     */
    public function parseConnection($connection) {
        if (empty($connection)) {
            $analysis = "本次请求头未包含Connection字段，";

            // 智能分析连接类型
            $httpVersion = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';

            if (strpos($httpVersion, 'HTTP/1.1') !== false) {
                $analysis .= "在HTTP/1.1协议中，默认使用持久连接（keep-alive），除非明确指定关闭。";
            } elseif (strpos($httpVersion, 'HTTP/1.0') !== false) {
                $analysis .= "在HTTP/1.0协议中，默认每个请求都需要建立新的TCP连接。";
            } else {
                $analysis .= "将使用协议默认的连接方式。";
            }

            // 分析可能的影响
            $analysis .= "这意味着：";
            $analysis .= "1) 如果是HTTP/1.1，连接可能会保持开放状态以供后续请求使用；";
            $analysis .= "2) 如果是HTTP/1.0，连接可能在请求完成后立即关闭；";
            $analysis .= "3) 服务器将根据协议版本和配置来决定连接管理策略。";

            return $analysis;
        }

        $connection = strtolower(trim($connection));

        switch ($connection) {
            case 'keep-alive':
                return "本次请求头表示客户端希望保持持久连接，这样可以减少建立新连接的开销，提高性能。适用于需要多次请求的场景。";
            case 'close':
                return "本次请求头表示客户端希望在请求完成后关闭连接，适用于单次请求或需要释放服务器资源的场景。";
            case 'upgrade':
                return "本次请求头表示希望升级协议（如WebSocket），需要特殊的协议切换处理。";
            default:
                return "本次请求头指定的连接类型为：" . htmlspecialchars($connection) . "，表示客户端希望使用特定的连接管理方式。";
        }
    }

    /**
     * 智能解析X-Forwarded-For请求头
     * @param string $xForwardedFor X-Forwarded-For头内容
     * @return string 解析结果描述
     */
    public function parseXForwardedFor($xForwardedFor) {
        if (empty($xForwardedFor)) {
            return "本次请求头未包含X-Forwarded-For信息，表示请求未经过代理服务器转发。";
        }

        $ips = array();
        $parts = explode(',', $xForwardedFor);

        foreach ($parts as $part) {
            $ip = trim($part);
            if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
                $ips[] = $ip;
            }
        }

        if (empty($ips)) {
            return "本次请求头包含X-Forwarded-For信息但格式无法解析：" . $xForwardedFor . "。";
        }

        $description = "本次请求头表示客户端的真实IP地址为" . $ips[0];

        if (count($ips) > 1) {
            $description .= "，经过代理服务器";
            for ($i = 1; $i < count($ips); $i++) {
                if ($i > 1) $description .= "、";
                $description .= $ips[$i];
            }
            $description .= "转发";
        }

        $description .= "（经过代理时）。";

        return $description;
    }

    // ========== 智能解析通用方法 ==========

    /**
     * 通用请求头/响应头智能分析
     * @param string $headerName 头部字段名
     * @param string $type 类型：request/response
     * @return string 分析结果
     */
    public function getHeaderIntelligentAnalysis($headerName, $type) {
        $analysis = "";

        if ($type === 'request') {
            switch ($headerName) {
                case 'Authorization':
                    $analysis = "此字段用于身份验证。缺失表示请求未包含认证信息，通常适用于公开访问的资源。";
                    break;
                case 'If-Modified-Since':
                    $analysis = "此字段用于条件请求。缺失表示客户端未使用缓存验证，将获取资源的完整版本。";
                    break;
                case 'If-None-Match':
                    $analysis = "此字段用于ETag验证。缺失表示客户端未进行实体标签验证，服务器将返回完整资源。";
                    break;
                case 'Range':
                    $analysis = "此字段用于部分内容请求。缺失表示客户端请求完整资源，而不是分块下载。";
                    break;
                case 'Upgrade-Insecure-Requests':
                    $analysis = "此字段用于HTTPS升级。缺失表示客户端未明确请求将HTTP升级为HTTPS。";
                    break;
                case 'DNT':
                    $analysis = "此字段用于用户隐私偏好。缺失表示用户未设置禁止跟踪偏好。";
                    break;
                case 'Save-Data':
                    $analysis = "此字段用于数据节省模式。缺失表示客户端未启用数据节省功能。";
                    break;
                default:
                    $analysis = "根据HTTP协议标准，此字段的有无通常不影响基本请求处理，但可能影响某些高级功能或服务器优化。";
            }
        } elseif ($type === 'response') {
            switch ($headerName) {
                case 'ETag':
                    $analysis = "此字段用于资源版本标识。缺失表示服务器未提供实体标签，客户端无法使用条件请求优化缓存。";
                    break;
                case 'Last-Modified':
                    $analysis = "此字段用于资源最后修改时间。缺失表示服务器未提供修改时间信息，影响客户端缓存策略。";
                    break;
                case 'Expires':
                    $analysis = "此字段用于缓存过期时间。缺失表示服务器未指定过期时间，客户端需要使用其他缓存控制策略。";
                    break;
                case 'Vary':
                    $analysis = "此字段用于内容协商。缺失表示响应内容不基于请求头变化，缓存机制相对简单。";
                    break;
                case 'Content-Encoding':
                    $analysis = "此字段用于内容编码。缺失表示响应体未经过压缩处理，客户端可直接使用原始内容。";
                    break;
                case 'Content-Disposition':
                    $analysis = "此字段用于内容处理方式。缺失表示响应内容将直接显示，不会触发下载。";
                    break;
                case 'Access-Control-Allow-Origin':
                    $analysis = "此字段用于跨域控制。缺失表示服务器未设置跨域策略，可能影响跨域请求。";
                    break;
                default:
                    $analysis = "根据HTTP协议标准，此字段的有无通常不影响基本响应处理，但可能影响客户端的高级功能或性能优化。";
            }
        }

        return $analysis;
    }

    /**
     * HTTP版本智能解析
     * @param string $version HTTP版本
     * @return string 解析结果
     */
    public function parseHttpVersion($version) {
        $versionMap = array(
            'HTTP/1.0' => array(
                'description' => 'HTTP/1.0',
                'features' => array('每次请求都需要建立新的TCP连接，请求完成后连接即关闭'),
                'advantages' => array('实现简单，兼容性好'),
                'disadvantages' => array('性能较差，每个请求都需要连接建立和关闭的开销')
            ),
            'HTTP/1.1' => array(
                'description' => 'HTTP/1.1',
                'features' => array('支持持久连接（keep-alive），可以在一个TCP连接上发送多个请求和响应'),
                'advantages' => array('性能更好，支持管道化，缓存机制更完善'),
                'disadvantages' => array('协议相对复杂，存在队头阻塞问题')
            ),
            'HTTP/2.0' => array(
                'description' => 'HTTP/2',
                'features' => array('基于二进制协议，支持多路复用（multiple requests over a single connection）'),
                'advantages' => array('性能大幅提升，支持服务器推送，头部压缩'),
                'disadvantages' => array('实现复杂，需要TLS支持')
            )
        );

        $versionKey = strtoupper($version);
        if (!isset($versionMap[$versionKey])) {
            return "本次响应中使用的HTTP版本为" . htmlspecialchars($version) . "。";
        }

        $info = $versionMap[$versionKey];
        $description = "本次响应中使用的HTTP版本为" . $info['description'] . "，该版本的主要特点是支持" . implode('，', $info['features']) . "。";

        if (!empty($info['advantages'])) {
            $description .= "优点：" . implode('，', $info['advantages']) . "。";
        }
        if (!empty($info['disadvantages'])) {
            $description .= "缺点：" . implode('，', $info['disadvantages']) . "。";
        }

        return $description;
    }

    /**
     * 请求体智能解析
     * @param string $body 请求体内容
     * @param string $contentType 内容类型
     * @return string 解析结果
     */
    public function parseRequestBody($body, $contentType = '') {
        if (empty($body)) {
            return "本次请求未包含请求体。";
        }

        if (empty($contentType)) {
            $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        }

        $result = "";
        $body = trim($body);

        // JSON格式解析
        if (strpos($contentType, 'application/json') !== false) {
            $data = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $paramCount = count($data);
                $result .= "当前请求体为JSON格式，包含了用户提交的表单数据。";

                if ($paramCount > 0) {
                    $result .= "传递了{$paramCount}个参数，参数详情如下：";
                    $hasChinese = false;

                    foreach ($data as $key => $value) {
                        $result .= "<br><strong>" . htmlspecialchars($key) . "</strong> = " . htmlspecialchars($value) . "（" . gettype($value) . "）";

                        // 检查参数值是否包含中文字符
                        if (is_string($value) && preg_match('/[\x{4e00}-\x{9fff}]/u', $value)) {
                            $hasChinese = true;
                            $result .= "（中文参数）";
                        }
                    }

                    if ($hasChinese) {
                        $result .= "<br><em>注意：JSON中的中文参数直接以UTF-8格式传输，不需要URL编码。</em>";
                    }

                    // 特殊字段分析
                    if (isset($data['username']) || isset($data['user']) || isset($data['email'])) {
                        $result .= "<br><br>请求体中包含用户身份相关信息。";
                    }
                    if (isset($data['password']) || isset($data['token']) || isset($data['secret'])) {
                        $result .= " 请求体中包含敏感信息，应通过HTTPS传输。";
                    }
                    if (isset($data['action']) || isset($data['method']) || isset($data['operation'])) {
                        $result .= " 请求体中指定了要执行的操作类型。";
                    }
                } else {
                    $result .= "但没有包含任何参数。";
                }
            } else {
                $result .= "当前请求体声明为JSON格式，但内容无法解析或格式不正确。";
            }
        }
        // URL编码格式解析
        elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
            parse_str($body, $data);
            if (!empty($data) && is_array($data)) {
                $paramCount = count($data);
                $result .= "当前请求体为URL编码格式，包含了用户提交的表单数据。";

                if ($paramCount > 0) {
                    $result .= "传递了{$paramCount}个参数，参数详情如下：";
                    $hasChinese = false;

                    foreach ($data as $key => $value) {
                        $result .= "<br><strong>" . htmlspecialchars($key) . "</strong> = " . htmlspecialchars($value);

                        // 检查参数值是否包含中文字符
                        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $value)) {
                            $hasChinese = true;
                            $result .= "（中文参数已进行URL编码）";
                        }
                    }

                    if ($hasChinese) {
                        $result .= "<br><em>注意：中文参数在HTTP传输中会自动进行URL编码。</em>";
                    }

                    // 特殊字段分析
                    if (isset($data['username']) || isset($data['user']) || isset($data['email'])) {
                        $result .= "<br><br>请求体中包含用户身份相关信息。";
                    }
                    if (isset($data['password']) || isset($data['token']) || isset($data['secret'])) {
                        $result .= " 请求体中包含敏感信息，应通过HTTPS传输。";
                    }
                    if (isset($data['action']) || isset($data['method']) || isset($data['operation'])) {
                        $result .= " 请求体中指定了要执行的操作类型。";
                    }
                } else {
                    $result .= "但没有包含任何参数。";
                }
            } else {
                $result .= "当前请求体为URL编码格式，但解析失败或没有包含有效参数。";
            }
        }
        // 多部分表单数据解析
        elseif (strpos($contentType, 'multipart/form-data') !== false) {
            $result .= "当前请求体为多部分表单数据格式，";

            // 分析boundary
            if (preg_match('/boundary=([^\s;]+)/', $contentType, $matches)) {
                $boundary = $matches[1];
                $result .= "使用boundary：{$boundary}分隔各个部分。";
            }

            // 简单分析文件上传
            if (preg_match('/Content-Disposition:\s*form-data;\s*name="([^"]+)";\s*filename="([^"]+)"/', $body, $matches)) {
                $fieldName = $matches[1];
                $fileName = $matches[2];
                $result .= " 检测到文件上传字段：{$fieldName}，上传的文件名为{$fileName}。";

                // 分析文件类型
                $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                $imageTypes = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp');
                $docTypes = array('pdf', 'doc', 'docx', 'txt', 'rtf');
                $videoTypes = array('mp4', 'avi', 'mov', 'wmv', 'flv');
                $audioTypes = array('mp3', 'wav', 'flac', 'aac');

                if (in_array(strtolower($fileExt), $imageTypes)) {
                    $result .= " 这是一个图片文件。";
                } elseif (in_array(strtolower($fileExt), $docTypes)) {
                    $result .= " 这是一个文档文件。";
                } elseif (in_array(strtolower($fileExt), $videoTypes)) {
                    $result .= " 这是一个视频文件。";
                } elseif (in_array(strtolower($fileExt), $audioTypes)) {
                    $result .= " 这是一个音频文件。";
                } else {
                    $result .= " 这是一个{$fileExt}类型的文件。";
                }
            } else {
                $result .= " 包含表单字段数据，但没有检测到文件上传。";
            }
        }
        // XML格式解析
        elseif (strpos($contentType, 'application/xml') !== false || strpos($contentType, 'text/xml') !== false) {
            $result .= "当前请求体为XML格式，包含了结构化的数据交换格式。";

            // 简单XML分析
            if (preg_match('/<(\w+)[^>]*>/', $body, $matches)) {
                $rootElement = $matches[1];
                $result .= " 根元素为{$rootElement}。";
            }

            if (preg_match_all('/<(\w+)[^>]*>/', $body, $matches)) {
                $elementCount = count(array_unique($matches[1]));
                $result .= " 包含{$elementCount}种不同的元素类型。";
            }
        }
        // 纯文本格式解析
        elseif (strpos($contentType, 'text/plain') !== false) {
            $result .= "当前请求体为纯文本格式，包含了未格式化的文本信息。";
            $textLength = strlen($body);
            $result .= " 文本长度为{$textLength}个字符。";

            // 简单文本分析
            if (preg_match('/[\u4e00-\u9fff]/', $body)) {
                $result .= " 文本包含中文字符。";
            }
            if (preg_match('/\d{4}-\d{2}-\d{2}/', $body)) {
                $result .= " 文本可能包含日期信息。";
            }
            if (filter_var($body, FILTER_VALIDATE_EMAIL)) {
                $result .= " 文本可能是一个邮箱地址。";
            }
        }
        // 二进制数据解析
        elseif (strpos($contentType, 'application/octet-stream') !== false) {
            $result .= "当前请求体为二进制流格式，包含了原始的二进制数据。";
            $dataSize = strlen($body);
            $result .= " 数据大小为{$dataSize}字节。";

            if ($dataSize < 1024) {
                $result .= " 数据较小。";
            } elseif ($dataSize < 1024 * 1024) {
                $result .= " 数据中等大小。";
            } else {
                $result .= " 数据较大，传输可能需要较长时间。";
            }
        }
        // 其他格式
        else {
            $result .= "当前请求体为";
            if (!empty($contentType)) {
                $result .= $contentType . "格式，";
            } else {
                $result .= "未知格式，";
            }
            $result .= "包含了自定义格式的数据。";

            $dataSize = strlen($body);
            $result .= " 数据大小为{$dataSize}字节。";
        }

        return $result;
    }

    /**
     * 响应体智能解析
     * @param string $body 响应体内容
     * @param string $contentType 内容类型
     * @return string 解析结果
     */
    public function parseResponseBody($body, $contentType = '') {
        if (empty($body)) {
            return "本次响应未包含响应体。";
        }

        if (empty($contentType)) {
            // 尝试从响应头获取Content-Type
            $headers = headers_list();
            foreach ($headers as $header) {
                if (strpos(strtolower($header), 'content-type:') === 0) {
                    $contentType = substr($header, 13);
                    $contentType = trim($contentType);
                    break;
                }
            }
        }

        // 默认HTML响应体
        if (empty($contentType) || strpos($contentType, 'text/html') !== false) {
            return "当前响应体为HTML格式，包含了服务器返回的网页内容。";
        }

        // JSON响应体
        if (strpos($contentType, 'application/json') !== false) {
            return "当前响应体为JSON格式，包含了服务器返回的API数据。";
        }

        // 纯文本响应体
        if (strpos($contentType, 'text/plain') !== false) {
            return "当前响应体为纯文本格式，包含了服务器返回的简单文本信息。";
        }

        // 二进制数据响应体
        if (strpos($contentType, 'application/octet-stream') !== false) {
            return "当前响应体为二进制数据，包含了服务器返回的图片文件。";
        }

        return "当前响应体为{$contentType}格式，包含了服务器返回的数据内容。";
    }

    /**
     * 请求路径智能解析
     * @param string $uri 请求URI
     * @return string 解析结果
     */
    public function parseRequestPathSmart($uri) {
        $uri = trim($uri, '/');

        // 分离路径和参数
        if (strpos($uri, '?') !== false) {
            list($path, $queryString) = explode('?', $uri, 2);
        } else {
            $path = $uri;
            $queryString = '';
        }

        // 解析资源路径部分
        $pathInfo = '';
        if (empty($path)) {
            $pathInfo = "本次请求的资源路径为根目录下的资源；";
        } else {
            // 检查是否包含具体文件
            $pathParts = explode('/', $path);
            $lastPart = end($pathParts);

            if (strpos($lastPart, '.') !== false) {
                // 包含具体文件
                $pathInfo = "本次请求的资源路径为" . htmlspecialchars($path) . "文件；";
            } else {
                // 目录路径
                $pathInfo = "本次请求的资源路径为" . htmlspecialchars($path) . "目录下的资源，没有指定具体的资源文件，因此服务器会返回该目录下的默认资源，如index.php文件；";
            }
        }

        // 解析参数部分
        $paramInfo = '';
        if (!empty($queryString)) {
            parse_str($queryString, $params);
            if (!empty($params)) {
                $paramCount = count($params);
                $paramNames = array_keys($params);
                $paramValues = array_values($params);

                $paramInfo .= "?" . htmlspecialchars($queryString) . "表示传递了" . $paramCount . "个参数，";
                $paramInfo .= "分别是" . implode("和", array_map('htmlspecialchars', $paramNames));
                $paramInfo .= "，对应的值分别是" . implode("和", array_map('htmlspecialchars', $paramValues)) . "。";
            }
        } else {
            $paramInfo = "本次请求路径中没有传递任何参数。";
        }

        // 在资源路径和参数说明之间添加换行
        return $pathInfo . "<br>" . $paramInfo;
    }

    // ========== HTTP响应头解析方法 ==========

    /**
     * 解析HTTP状态码
     * @param int $statusCode HTTP状态码
     * @return string 解析结果描述
     */
    public function parseStatusCode($statusCode) {
        if (!is_numeric($statusCode) || $statusCode < 100 || $statusCode > 599) {
            return "无效的HTTP状态码：" . $statusCode;
        }

        $statusCodes = array(
            100 => '继续',
            101 => '切换协议',
            102 => '处理中',
            200 => '成功',
            201 => '已创建',
            202 => '已接受',
            203 => '非权威信息',
            204 => '无内容',
            205 => '重置内容',
            206 => '部分内容',
            300 => '多种选择',
            301 => '永久移动',
            302 => '临时移动',
            303 => '查看其他',
            304 => '未修改',
            305 => '使用代理',
            307 => '临时重定向',
            308 => '永久重定向',
            400 => '错误请求',
            401 => '未授权',
            402 => '付款要求',
            403 => '禁止访问',
            404 => '未找到',
            405 => '方法不允许',
            406 => '不可接受',
            407 => '需要代理授权',
            408 => '请求超时',
            409 => '冲突',
            410 => '已删除',
            411 => '需要长度',
            412 => '先决条件失败',
            413 => '请求体过大',
            414 => 'URI过长',
            415 => '不支持的媒体类型',
            416 => '范围无效',
            417 => '期望失败',
            418 => '我是一个茶壶',
            422 => '无法处理的实体',
            423 => '已锁定',
            424 => '失败依赖',
            426 => '需要升级',
            429 => '请求过多',
            431 => '请求头字段过大',
            451 => '法律原因不可用',
            500 => '内部服务器错误',
            501 => '未实现',
            502 => '错误网关',
            503 => '服务不可用',
            504 => '网关超时',
            505 => 'HTTP版本不支持',
            506 => '变体协商',
            507 => '存储空间不足',
            508 => '检测到循环',
            510 => '未扩展',
            511 => '需要网络认证'
        );

        $statusRange = floor($statusCode / 100);
        $rangeDesc = '';
        switch ($statusRange) {
            case 1:
                $rangeDesc = '信息性响应';
                break;
            case 2:
                $rangeDesc = '成功响应';
                break;
            case 3:
                $rangeDesc = '重定向响应';
                break;
            case 4:
                $rangeDesc = '客户端错误';
                break;
            case 5:
                $rangeDesc = '服务器错误';
                break;
        }

        $description = isset($statusCodes[$statusCode]) 
            ? $statusCodes[$statusCode] 
            : '未知状态码';

        return "HTTP状态码 " . $statusCode . " 表示：" . $description . "（" . $rangeDesc . "）";
    }

    /**
     * 解析HTTP状态描述
     * @param string $statusPhrase 状态描述
     * @return string 解析结果描述
     */
    public function parseStatusPhrase($statusPhrase) {
        if (empty($statusPhrase)) {
            return "未提供HTTP状态描述信息";
        }

        return "HTTP状态描述：" . $statusPhrase . "，这是对HTTP状态码的详细说明文字";
    }

    /**
     * 解析Content-Type响应头
     * @param string $contentType Content-Type头内容
     * @return string 解析结果描述
     */
    public function parseResponseContentType($contentType) {
        if (empty($contentType)) {
            return "响应头未包含Content-Type信息，客户端可能无法正确解析响应内容";
        }

        $description = "响应内容类型：";

        // 解析主要类型
        if (strpos($contentType, 'text/html') !== false) {
            $description .= "HTML网页文档";
        } elseif (strpos($contentType, 'text/plain') !== false) {
            $description .= "纯文本内容";
        } elseif (strpos($contentType, 'application/json') !== false) {
            $description .= "JSON格式数据";
        } elseif (strpos($contentType, 'application/xml') !== false) {
            $description .= "XML格式数据";
        } elseif (strpos($contentType, 'application/javascript') !== false) {
            $description .= "JavaScript代码";
        } elseif (strpos($contentType, 'text/css') !== false) {
            $description .= "CSS样式表";
        } elseif (strpos($contentType, 'application/pdf') !== false) {
            $description .= "PDF文档";
        } elseif (strpos($contentType, 'image/') !== false) {
            if (preg_match('/image\/([^;]+)/', $contentType, $matches)) {
                $imageType = strtoupper($matches[1]);
                $description .= $imageType . "图像文件";
            } else {
                $description .= "图像文件";
            }
        } elseif (strpos($contentType, 'video/') !== false) {
            if (preg_match('/video\/([^;]+)/', $contentType, $matches)) {
                $videoType = strtoupper($matches[1]);
                $description .= $videoType . "视频文件";
            } else {
                $description .= "视频文件";
            }
        } elseif (strpos($contentType, 'audio/') !== false) {
            if (preg_match('/audio\/([^;]+)/', $contentType, $matches)) {
                $audioType = strtoupper($matches[1]);
                $description .= $audioType . "音频文件";
            } else {
                $description .= "音频文件";
            }
        } elseif (strpos($contentType, 'application/octet-stream') !== false) {
            $description .= "二进制流数据（未知类型）";
        } else {
            $description .= $contentType;
        }

        // 解析字符编码
        if (preg_match('/charset=([^;]+)/', $contentType, $matches)) {
            $description .= "，使用" . trim($matches[1], '"') . "字符编码";
        }

        return $description;
    }

    /**
     * 解析Content-Length响应头
     * @param string $contentLength Content-Length头内容
     * @param int|null $actualContentLength 实际内容长度（可选）
     * @return string 解析结果描述
     */
    public function parseResponseContentLength($contentLength, $actualContentLength = null) {
        // 如果有实际内容长度参数，优先使用实际内容长度进行解析
        if ($actualContentLength !== null && is_numeric($actualContentLength)) {
            $length = intval($actualContentLength);
            $description = "响应体长度为" . $length . "字节";

            // 添加大小描述
            if ($length < 1024) {
                $description .= "（较小文件）";
            } elseif ($length < 1024 * 1024) {
                $kb = round($length / 1024, 2);
                $description .= "，约" . $kb . "KB";
            } elseif ($length < 1024 * 1024 * 1024) {
                $mb = round($length / (1024 * 1024), 2);
                $description .= "，约" . $mb . "MB";
            } else {
                $gb = round($length / (1024 * 1024 * 1024), 2);
                $description .= "，约" . $gb . "GB";
            }

            // 如果响应头中也有Content-Length，进行比较
            if (!empty($contentLength) && is_numeric($contentLength)) {
                $headerLength = intval($contentLength);
                if ($headerLength != $length) {
                    $description .= "【注意：响应头声明长度为" . $headerLength . "字节，与实际计算的" . $length . "字节不匹配，可能存在传输问题或动态内容变化】";
                } else {
                    $description .= "【响应头声明的长度与实际计算长度一致】";
                }
            } else {
                $description .= "";
            }

            return $description;
        }

        // 如果没有实际内容长度参数，尝试解析响应头中的Content-Length
        if (empty($contentLength) || !is_numeric($contentLength)) {
            return "响应头未包含Content-Length信息，响应体长度未知";
        }

        $length = intval($contentLength);
        $description = "响应体长度为" . $length . "字节";

        // 添加大小描述
        if ($length < 1024) {
            $description .= "（较小文件）";
        } elseif ($length < 1024 * 1024) {
            $kb = round($length / 1024, 2);
            $description .= "，约" . $kb . "KB";
        } elseif ($length < 1024 * 1024 * 1024) {
            $mb = round($length / (1024 * 1024), 2);
            $description .= "，约" . $mb . "MB";
        } else {
            $gb = round($length / (1024 * 1024 * 1024), 2);
            $description .= "，约" . $gb . "GB";
        }

        return $description;
    }

    /**
     * 解析Set-Cookie响应头
     * @param string $setCookie Set-Cookie头内容
     * @return string 解析结果描述
     */
    public function parseSetCookie($setCookie) {
        if (empty($setCookie)) {
            return "响应头未包含Set-Cookie信息，不会设置新的Cookie";
        }

        $description = "设置Cookie：";
        $parts = explode(';', $setCookie);
        $cookieName = '';
        $hasExpires = false;
        $hasSecure = false;
        $hasHttpOnly = false;
        $hasSameSite = false;

        foreach ($parts as $i => $part) {
            $part = trim($part);
            if (empty($part)) continue;

            if ($i === 0) {
                // 第一个部分是name=value
                if (strpos($part, '=') !== false) {
                    list($name, $value) = explode('=', $part, 2);
                    $cookieName = $name;
                    $description .= $name . "=" . $value;
                } else {
                    $description .= $part;
                }
            } else {
                // 解析属性
                $lowerPart = strtolower($part);
                if (strpos($lowerPart, 'expires=') === 0) {
                    $hasExpires = true;
                    $expires = substr($part, 8);
                    $description .= "，过期时间：" . $expires;
                } elseif (strpos($lowerPart, 'max-age=') === 0) {
                    $maxAge = substr($part, 8);
                    $description .= "，有效期限：" . $maxAge . "秒";
                } elseif (strpos($lowerPart, 'domain=') === 0) {
                    $domain = substr($part, 7);
                    $description .= "，域名：" . $domain;
                } elseif (strpos($lowerPart, 'path=') === 0) {
                    $path = substr($part, 5);
                    $description .= "，路径：" . $path;
                } elseif ($lowerPart === 'secure') {
                    $hasSecure = true;
                    $description .= "，仅HTTPS传输";
                } elseif ($lowerPart === 'httponly') {
                    $hasHttpOnly = true;
                    $description .= "，仅HTTP访问";
                } elseif (strpos($lowerPart, 'samesite=') === 0) {
                    $hasSameSite = true;
                    $sameSite = strtoupper(substr($part, 9));
                    $description .= "，SameSite：" . $sameSite;
                }
            }
        }

        return $description;
    }

    /**
     * 解析Location响应头
     * @param string $location Location头内容
     * @return string 解析结果描述
     */
    public function parseLocation($location) {
        if (empty($location)) {
            return "响应头未包含Location信息，不进行重定向";
        }

        $description = "重定向到：" . $location;

        // 解析URL类型
        if (strpos($location, 'http://') === 0 || strpos($location, 'https://') === 0) {
            $description .= "（绝对URL重定向）";
        } elseif (strpos($location, '/') === 0) {
            $description .= "（相对于当前域名的路径重定向）";
        } else {
            $description .= "（相对路径重定向）";
        }

        return $description;
    }

    /**
     * 解析Server响应头
     * @param string $server Server头内容
     * @return string 解析结果描述
     */
    public function parseServer($server) {
        if (empty($server)) {
            return "响应头未包含Server信息，服务器信息被隐藏";
        }

        $description = "Web服务器：" . $server;

        // 识别常见的服务器软件
        if (stripos($server, 'apache') !== false) {
            $description .= "（Apache服务器）";
        } elseif (stripos($server, 'nginx') !== false) {
            $description .= "（Nginx服务器）";
        } elseif (stripos($server, 'iis') !== false) {
            $description .= "（IIS服务器）";
        } elseif (stripos($server, 'tomcat') !== false) {
            $description .= "（Tomcat服务器）";
        } elseif (stripos($server, 'node') !== false) {
            $description .= "（Node.js应用）";
        } elseif (stripos($server, 'lighttpd') !== false) {
            $description .= "（Lighttpd服务器）";
        }

        return $description;
    }

    /**
     * 解析X-Powered-By响应头
     * @param string $xPoweredBy X-Powered-By头内容
     * @return string 解析结果描述
     */
    public function parseXPoweredBy($xPoweredBy) {
        if (empty($xPoweredBy)) {
            return "响应头未包含X-Powered-By信息，技术栈信息被隐藏";
        }

        $description = "技术栈：" . $xPoweredBy;

        // 识别常见的后端技术
        if (stripos($xPoweredBy, 'php') !== false) {
            $description .= "（PHP语言开发）";
        } elseif (stripos($xPoweredBy, 'asp.net') !== false) {
            $description .= "（ASP.NET框架）";
        } elseif (stripos($xPoweredBy, 'express') !== false) {
            $description .= "（Express.js框架）";
        } elseif (stripos($xPoweredBy, 'django') !== false) {
            $description .= "（Django框架）";
        } elseif (stripos($xPoweredBy, 'rails') !== false) {
            $description .= "（Ruby on Rails框架）";
        } elseif (stripos($xPoweredBy, 'node.js') !== false) {
            $description .= "（Node.js运行环境）";
        }

        return $description;
    }

    /**
     * 解析Date响应头
     * @param string $date Date头内容
     * @return string 解析结果描述
     */
    public function parseResponseDate($date) {
        if (empty($date)) {
            return "响应头未包含Date信息，无法确定响应生成时间";
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return "响应生成时间：" . $date . "（格式无法解析）";
        }

        return "响应生成时间：" . date('Y年m月d日 H:i:s', $timestamp);
    }

    /**
     * 解析Allow响应头
     * @param string $allow Allow头内容
     * @return string 解析结果描述
     */
    public function parseAllow($allow) {
        if (empty($allow)) {
            return "响应头未包含Allow信息，未明确支持的HTTP方法";
        }

        $methods = array_map('trim', explode(',', $allow));
        $description = "支持的HTTP方法：" . implode('、', $methods);

        // 检查常见方法
        $commonMethods = array('GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH');
        $supportedMethods = array_intersect($methods, $commonMethods);
        
        if (count($supportedMethods) === 1 && $supportedMethods[0] === 'GET') {
            $description .= "（只读资源）";
        } elseif (count($supportedMethods) === 2 && in_array('GET', $supportedMethods) && in_array('POST', $supportedMethods)) {
            $description .= "（可读写资源）";
        } elseif (in_array('DELETE', $supportedMethods)) {
            $description .= "（支持删除操作）";
        }

        return $description;
    }

    /**
     * 解析Last-Modified响应头
     * @param string $lastModified Last-Modified头内容
     * @return string 解析结果描述
     */
    public function parseLastModified($lastModified) {
        if (empty($lastModified)) {
            return "响应头未包含Last-Modified信息，无法确定资源最后修改时间";
        }

        $timestamp = strtotime($lastModified);
        if ($timestamp === false) {
            return "资源最后修改时间：" . $lastModified . "（格式无法解析）";
        }

        return "资源最后修改时间：" . date('Y年m月d日 H:i:s', $timestamp) . "，可用于缓存验证";
    }

    /**
     * 解析Refresh响应头
     * @param string $refresh Refresh头内容
     * @return string 解析结果描述
     */
    public function parseRefresh($refresh) {
        if (empty($refresh)) {
            return "响应头未包含Refresh信息，页面不会自动刷新或跳转";
        }

        $description = "刷新设置：" . $refresh . "；";

        // 解析Refresh头的格式
        if (strpos($refresh, ';') !== false) {
            // 格式: seconds; url=xxx
            $parts = explode(';', $refresh, 2);
            $seconds = trim($parts[0]);
            $urlPart = trim($parts[1]);

            if (is_numeric($seconds)) {
                $description .= "浏览器将在" . $seconds . "秒后";
            }

            if (stripos($urlPart, 'url=') === 0) {
                $url = substr($urlPart, 4);
                $description .= "跳转到" . $url;
            }
        } else {
            // 格式: 仅秒数，刷新当前页面
            if (is_numeric($refresh)) {
                if ($refresh == 0) {
                    $description .= "浏览器将立即刷新当前页面";
                } else {
                    $description .= "浏览器将在" . $refresh . "秒后刷新当前页面";
                }
            }
        }

        $description .= "。Refresh响应头是一种非标准的HTTP响应头，但被广泛支持，常用于页面定时刷新、操作成功后的跳转提示等场景。";

        return $description;
    }

    /**
     * 解析ETag响应头
     * @param string $etag ETag头内容
     * @return string 解析结果描述
     */
    public function parseETag($etag) {
        if (empty($etag)) {
            return "响应头未包含ETag信息，无实体标签用于缓存验证";
        }

        $description = "实体标签：" . $etag;

        // 分析ETag类型
        if (strpos($etag, '"') !== false) {
            if (strpos($etag, 'W/"') === 0) {
                $description .= "（弱验证ETag，用于缓存验证）";
            } else {
                $description .= "（强验证ETag，用于精确匹配）";
            }
        }

        $description .= "，可用于If-None-Match请求头的缓存验证";

        return $description;
    }

    /**
     * 解析Expires响应头
     * @param string $expires Expires头内容
     * @return string 解析结果描述
     */
    public function parseExpires($expires) {
        if (empty($expires)) {
            return "响应头未包含Expires信息，无过期时间设置";
        }

        if ($expires === '0' || strtolower($expires) === 'now') {
            return "响应内容已立即过期，需要重新获取";
        }

        $timestamp = strtotime($expires);
        if ($timestamp === false) {
            return "响应过期时间：" . $expires . "（格式无法解析）";
        }

        $now = time();
        $remaining = $timestamp - $now;

        $description = "响应过期时间：" . date('Y年m月d日 H:i:s', $timestamp);
        
        if ($remaining <= 0) {
            $description .= "（已过期）";
        } elseif ($remaining < 60) {
            $description .= "（" . $remaining . "秒后过期）";
        } elseif ($remaining < 3600) {
            $minutes = floor($remaining / 60);
            $description .= "（" . $minutes . "分钟后过期）";
        } elseif ($remaining < 86400) {
            $hours = floor($remaining / 3600);
            $description .= "（" . $hours . "小时后过期）";
        } else {
            $days = floor($remaining / 86400);
            $description .= "（" . $days . "天后过期）";
        }

        return $description;
    }

    /**
     * 解析Cache-Control响应头
     * @param string $cacheControl Cache-Control头内容
     * @return string 解析结果描述
     */
    public function parseResponseCacheControl($cacheControl) {
        if (empty($cacheControl)) {
            return "响应头未包含Cache-Control信息，将使用默认缓存策略";
        }

        $directives = array();
        $parts = explode(',', $cacheControl);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            if (strpos($part, '=') !== false) {
                list($directive, $value) = explode('=', $part, 2);
                $directives[trim($directive)] = trim($value, '"');
            } else {
                $directives[$part] = true;
            }
        }

        $description = "缓存控制策略：";
        $cacheStrategies = array();

        if (isset($directives['public'])) {
            $cacheStrategies[] = "可被缓存（public）";
        }
        if (isset($directives['private'])) {
            $cacheStrategies[] = "仅私人缓存（private）";
        }
        if (isset($directives['no-cache'])) {
            $cacheStrategies[] = "使用前必须验证（no-cache）";
        }
        if (isset($directives['no-store'])) {
            $cacheStrategies[] = "禁止缓存（no-store）";
        }
        if (isset($directives['must-revalidate'])) {
            $cacheStrategies[] = "过期后必须重新验证（must-revalidate）";
        }
        if (isset($directives['proxy-revalidate'])) {
            $cacheStrategies[] = "代理缓存过期后必须重新验证（proxy-revalidate）";
        }
        if (isset($directives['max-age'])) {
            $cacheStrategies[] = "最多缓存" . $directives['max-age'] . "秒（max-age）";
        }
        if (isset($directives['s-maxage'])) {
            $cacheStrategies[] = "代理缓存最多" . $directives['s-maxage'] . "秒（s-maxage）";
        }
        if (isset($directives['immutable'])) {
            $cacheStrategies[] = "内容永不改变（immutable）";
        }

        $description .= implode('，', $cacheStrategies);

        return $description;
    }

    /**
     * 解析Content-Encoding响应头
     * @param string $contentEncoding Content-Encoding头内容
     * @return string 解析结果描述
     */
    public function parseContentEncoding($contentEncoding) {
        if (empty($contentEncoding)) {
            return "响应头未包含Content-Encoding信息，响应体未压缩";
        }

        $encodings = array_map('trim', explode(',', $contentEncoding));
        $description = "内容编码：";
        $encodingNames = array();

        foreach ($encodings as $encoding) {
            switch (strtolower($encoding)) {
                case 'gzip':
                    $encodingNames[] = "GZIP压缩";
                    break;
                case 'deflate':
                    $encodingNames[] = "Deflate压缩";
                    break;
                case 'br':
                    $encodingNames[] = "Brotli压缩";
                    break;
                case 'compress':
                    $encodingNames[] = "Unix压缩";
                    break;
                case 'identity':
                    $encodingNames[] = "无压缩";
                    break;
                default:
                    $encodingNames[] = $encoding;
                    break;
            }
        }

        $description .= implode('→', $encodingNames);

        if (count($encodingNames) > 1) {
            $description .= "（多级压缩）";
        }

        return $description;
    }

    /**
     * 解析Content-Disposition响应头
     * @param string $contentDisposition Content-Disposition头内容
     * @return string 解析结果描述
     */
    public function parseContentDisposition($contentDisposition) {
        if (empty($contentDisposition)) {
            return "响应头未包含Content-Disposition信息，浏览器将按默认方式处理响应内容";
        }

        $description = "内容处理方式：";

        if (stripos($contentDisposition, 'attachment') !== false) {
            $description .= "作为附件下载";
            if (preg_match('/filename="?([^";]+)"?/', $contentDisposition, $matches)) {
                $description .= "，文件名：" . $matches[1];
            }
        } elseif (stripos($contentDisposition, 'inline') !== false) {
            $description .= "内联显示";
            if (preg_match('/filename="?([^";]+)"?/', $contentDisposition, $matches)) {
                $description .= "，建议文件名：" . $matches[1];
            }
        } elseif (stripos($contentDisposition, 'form-data') !== false) {
            $description .= "表单数据";
            if (preg_match('/name="?([^";]+)"?/', $contentDisposition, $matches)) {
                $description .= "，字段名：" . $matches[1];
            }
        } else {
            $description .= $contentDisposition;
        }

        return $description;
    }

    /**
     * 解析Access-Control-Allow-Origin响应头
     * @param string $allowOrigin Access-Control-Allow-Origin头内容
     * @return string 解析结果描述
     */
    public function parseAccessControlAllowOrigin($allowOrigin) {
        if (empty($allowOrigin)) {
            return "响应头未包含Access-Control-Allow-Origin信息，不允许跨域访问";
        }

        $description = "跨域访问控制：";

        if ($allowOrigin === '*') {
            $description .= "允许任何域名跨域访问（*）";
        } elseif (strpos($allowOrigin, 'http://') === 0 || strpos($allowOrigin, 'https://') === 0) {
            $description .= "仅允许" . $allowOrigin . "域名跨域访问";
        } elseif (strtolower($allowOrigin) === 'null') {
            $description .= "不允许跨域访问（null）";
        } else {
            $description .= $allowOrigin;
        }

        return $description;
    }

    /**
     * 解析Vary响应头
     * @param string $vary Vary头内容
     * @return string 解析结果描述
     */
    public function parseVary($vary) {
        if (empty($vary)) {
            return "响应头未包含Vary信息，缓存不区分请求头";
        }

        $fields = array_map('trim', explode(',', $vary));
        $description = "缓存区分依据：";
        $fieldDescriptions = array();

        foreach ($fields as $field) {
            switch (strtolower($field)) {
                case 'accept-encoding':
                    $fieldDescriptions[] = "内容编码";
                    break;
                case 'accept-language':
                    $fieldDescriptions[] = "语言偏好";
                    break;
                case 'user-agent':
                    $fieldDescriptions[] = "用户代理";
                    break;
                case 'cookie':
                    $fieldDescriptions[] = "Cookie信息";
                    break;
                case 'authorization':
                    $fieldDescriptions[] = "授权信息";
                    break;
                case 'host':
                    $fieldDescriptions[] = "主机名";
                    break;
                case 'referer':
                    $fieldDescriptions[] = "来源页面";
                    break;
                default:
                    $fieldDescriptions[] = $field;
                    break;
            }
        }

        $description .= implode('、', $fieldDescriptions);
        $description .= "，表示缓存将根据这些请求头的不同值创建不同的缓存版本";

        return $description;
    }

  
    /**
     * 获取HTTP请求信息的综合方法
     * @return array 包含请求行、请求头、请求体等信息的数组
     */
    public function getHttpRequestInfo() {
        $requestInfo = array();

        // 获取请求行信息
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';

        $requestInfo['request_line'] = $method . ' ' . $uri . ' ' . $protocol;
        $requestInfo['method'] = $method;
        $requestInfo['uri'] = $uri;
        $requestInfo['protocol'] = $protocol;

        // 获取请求头信息
        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $headerName))));
                $headers[$headerName] = $value;
            }
        }

        // 添加一些特殊的请求头
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
        }
        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
        }

        // 添加Date请求头（当前请求时间）
        $headers['Date'] = gmdate('D, d M Y H:i:s') . ' GMT';

        // 获取请求体信息
        $requestBody = file_get_contents('php://input');
        $requestInfo['body'] = $requestBody;
        $requestInfo['body_raw'] = $requestBody;

        // 添加Content-Length（如果请求体不为空且未设置）
        if (!isset($headers['Content-Length']) && !empty($requestBody)) {
            $headers['Content-Length'] = strlen($requestBody);
        }

        $requestInfo['headers'] = $headers;

        // 对于POST请求，优先使用原始URL编码的请求体
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($requestBody)) {
            // POST请求显示原始URL编码状态
            $requestInfo['body_display'] = $requestBody;
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            // GET请求显示查询字符串（如果有）
            $queryString = $_SERVER['QUERY_STRING'];
            if (!empty($queryString)) {
                // 将查询字符串格式化为请求体样式
                $requestInfo['body_display'] = str_replace('&', "\n", $queryString);
            } else {
                $requestInfo['body_display'] = '';
            }
        } else {
            // 其他请求方法或空请求体
            $requestInfo['body_display'] = $requestBody;
        }

        // 保留格式化版本用于详细解析区域
        if (!empty($requestBody) && strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false) {
            parse_str($requestBody, $parsedBody);
            if (!empty($parsedBody)) {
                $formattedBody = '';
                foreach ($parsedBody as $key => $value) {
                    $formattedBody .= $key . '=' . $value . "\n";
                }
                $requestInfo['body_formatted'] = trim($formattedBody);
            }
        } else {
            $requestInfo['body_formatted'] = $requestInfo['body_display'];
        }

        return $requestInfo;
    }

    /**
     * 获取HTTP响应信息的综合方法
     * @return array 包含状态行、响应头等信息的数组
     */
    public function getHttpResponseInfo() {
        $responseInfo = array();

        // 获取响应头信息
        $headers = headers_list();
        $responseInfo['headers'] = $headers;

        // 构建状态行
        $responseInfo['status_line'] = 'HTTP/1.1 200 OK';
        $responseInfo['status_code'] = '200';
        $responseInfo['status_phrase'] = 'OK';
        $responseInfo['protocol'] = 'HTTP/1.1';

        return $responseInfo;
    }
}

?>