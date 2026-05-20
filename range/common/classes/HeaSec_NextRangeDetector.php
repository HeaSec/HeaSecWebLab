<?php
/**
 * HeaSec天积安全团队 - 下一靶场检测器
 * 版本: v1.0.0
 * 创建日期: 2025-11-15
 * 团队: 天积安全 (HeavenlySecret)
 *
 * 用于智能检测下一个靶场链接的公共组件
 */



require_once __DIR__ . '/../includes/database.php';

/**
 * HeaSec 下一靶场检测类
 * 用于根据当前靶场信息获取下一个靶场链接
 */
class HeaSec_NextRangeDetector
{

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 初始化数据库连接
    }

    /**
     * 获取下一个靶场信息（数据库查询 + 相对路径构建）
     *
     * @param string $currentRangeCode 当前靶场代码（如：httpal）
     * @return array 下一个靶场信息数组
     */
    public function getNextRange($currentRangeCode)
    {
        try {
            // 获取当前靶场在数据库中的信息
            $currentRangeInfo = $this->getCurrentRangeInfo($currentRangeCode);

            if (!$currentRangeInfo) {
                return $this->getFallbackResult();
            }

            // 查询下一个靶场
            $nextRange = $this->queryNextRange($currentRangeInfo);

            if ($nextRange) {
                // 使用相对路径构建下一个靶场URL
                $nextRangeCode = $nextRange['code'];
                $nextUrl = "../" . $nextRangeCode . "/";

                return array(
                    'success' => true,
                    'type' => 'next',
                    'title' => '下一个靶场',
                    'url' => $nextUrl,
                    'range_code' => $nextRangeCode,
                    'range_info' => $nextRange
                );
            } else {
                // 没有下一个靶场，回到首页 - 自适应检测网站首页URL
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

                // 从当前请求路径中提取网站基础路径
                $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
                $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

                // 从API调用路径中提取网站基础路径
                // 例如：/heasecdev/range/common/api/next-range.php -> /heasecdev/
                $basePath = '';
                if (!empty($scriptName)) {
                    // 处理Windows路径格式
                    $scriptName = str_replace('\\', '/', $scriptName);

                    // 移除 range/common/api/ 部分，获取网站基础路径
                    $pattern = '/^(.*?)(?:\/range\/common\/api\/[^\/]*\.php)$/';
                    if (preg_match($pattern, $scriptName, $matches)) {
                        $basePath = $matches[1];
                    }
                }

                // 如果无法从脚本路径提取，尝试从请求URI提取
                if (empty($basePath) && !empty($requestUri)) {
                    $pattern = '/^(.*?)(?:\/range\/common\/api\/[^\/]*\.php)$/';
                    if (preg_match($pattern, $requestUri, $matches)) {
                        $basePath = $matches[1];
                    }
                }

                // 如果仍然为空，默认使用根路径
                if (empty($basePath)) {
                    $basePath = '';
                }

                // 确保路径格式正确
                $basePath = rtrim($basePath, '/');
                $homeUrl = $protocol . $host . $basePath . '/';

                return array(
                    'success' => true,
                    'type' => 'home',
                    'title' => '回到首页',
                    'url' => $homeUrl,
                    'range_info' => null
                );
            }

        } catch (Exception $e) {
            error_log('[HeaSec] NextRangeDetector error: ' . $e->getMessage());
            return $this->getFallbackResult();
        }
    }


    /**
     * 获取当前靶场信息
     *
     * @param string $rangeCode 靶场代码
     * @return array|false 靶场信息
     */
    private function getCurrentRangeInfo($rangeCode)
    {
        $sql = "SELECT * FROM links WHERE code = ? OR url LIKE ? LIMIT 1";
        $params = array($rangeCode, '%' . $rangeCode . '%');

        $result = heasec_fetch_one($sql, $params, 'heasec_cms');

        return $result;
    }

    /**
     * 查询下一个靶场
     *
     * @param array $currentRangeInfo 当前靶场信息
     * @return array|false 下一个靶场信息
     */
    private function queryNextRange($currentRangeInfo)
    {
        // 获取当前靶场的排序值和分类ID
        $currentSortOrder = $currentRangeInfo['sort_order'];
        $currentCategoryId = $currentRangeInfo['category_id'];

        // 查询同一分类下排序在当前靶场之后的第一个靶场
        $sql = "SELECT * FROM links
                WHERE sort_order > ? AND category_id = ? AND status = 1
                ORDER BY sort_order ASC, id ASC
                LIMIT 1";

        $params = array($currentSortOrder, $currentCategoryId);
        $result = heasec_fetch_one($sql, $params, 'heasec_cms');

        return $result;
    }

    /**
     * 获取备用结果（查询失败时使用）
     *
     * @return array 备用结果
     */
    private function getFallbackResult()
    {
        // 自适应检测网站首页URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        // 从当前请求路径中提取网站基础路径
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';

        // 从API调用路径中提取网站基础路径
        $basePath = '';
        if (!empty($scriptName)) {
            $scriptName = str_replace('\\', '/', $scriptName);
            $pattern = '/^(.*?)(?:\/range\/(?:[^\/]+\/){2}api\/[^\/]*\.php)$/';
            if (preg_match($pattern, $scriptName, $matches)) {
                $basePath = $matches[1];
            }
        }

        // 如果无法从脚本路径提取，尝试从请求URI提取
        if (empty($basePath) && !empty($requestUri)) {
            $pattern = '/^(.*?)(?:\/range\/(?:[^\/]+\/){2}[^\/]*\/?.*)$/';
            if (preg_match($pattern, $requestUri, $matches)) {
                $basePath = $matches[1];
            }
        }

        // 如果仍然为空，默认使用根路径
        if (empty($basePath)) {
            $basePath = '';
        }

        // 确保路径格式正确
        $basePath = rtrim($basePath, '/');
        $homeUrl = $protocol . $host . $basePath . '/';

        return array(
            'success' => false,
            'type' => 'home',
            'title' => '回到首页',
            'url' => $homeUrl,
            'range_info' => null,
            'error' => '无法获取下一个靶场信息'
        );
    }

    /**
     * 根据路径自动检测当前靶场代码
     *
     * @param string $requestPath 请求路径
     * @return string 靶场代码
     */
    public function autoDetectRangeCode($requestPath = '')
    {
        // 如果没有提供路径，使用当前脚本路径
        if (empty($requestPath)) {
            $requestPath = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        }

        // 从路径中提取靶场代码
        // 例如：/heasecdev/range/base/http/httpal/index.php -> httpal
        if (preg_match('/range\/[^\/]+\/[^\/]+\/([^\/]+)\//', $requestPath, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * 验证靶场代码是否有效（数据库查询版本）
     *
     * @param string $rangeCode 靶场代码
     * @return bool 是否有效
     */
    public function validateRangeCode($rangeCode)
    {
        if (empty($rangeCode)) {
            return false;
        }

        $info = $this->getCurrentRangeInfo($rangeCode);
        return !empty($info);
    }

    /**
     * 获取所有HTTP靶场列表（数据库查询版本）
     *
     * @param int $subcategoryId 分类ID（可选）
     * @return array 靶场列表
     */
    public function getAllRanges($categoryId = null)
    {
        $sql = "SELECT * FROM links WHERE status = 1";
        $params = array();

        if ($categoryId) {
            $sql .= " AND category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY sort_order ASC, id ASC";

        return heasec_fetch_all($sql, $params, 'heasec_cms');
    }

    /**
     * 获取靶场进度信息（数据库查询版本）
     *
     * @param string $rangeCode 靶场代码
     * @return array 进度信息
     */
    public function getRangeProgress($rangeCode)
    {
        $currentInfo = $this->getCurrentRangeInfo($rangeCode);

        if (!$currentInfo) {
            return array(
                'current' => 0,
                'total' => 0,
                'percentage' => 0
            );
        }

        // 获取同分类下的所有靶场
        $allRanges = $this->getAllRanges($currentInfo['category_id']);
        $total = count($allRanges);

        // 找到当前靶场在列表中的位置
        $current = 1;
        for ($i = 0; $i < $total; $i++) {
            if ($allRanges[$i]['id'] == $currentInfo['id']) {
                $current = $i + 1;
                break;
            }
        }

        return array(
            'current' => $current,
            'total' => $total,
            'percentage' => $total > 0 ? round(($current / $total) * 100, 2) : 0
        );
    }
}

?>