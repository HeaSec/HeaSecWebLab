<?php
/**
 * SAsec 分类管理API接口
 *
 * @package HeavenlySecret\API
 * @version HeaSec v1.0.0
 */

require_once __DIR__ . '/../../config/config.php';

class HeaSec_CategoryManager
{
    private $db;

    public function __construct()
    {
        $this->db = HeaSec_getConnection();
    }

    /**
     * 获取一级分类列表
     */
    public function getCategories()
    {
        try {
            // 验证请求
            HeaSec_validateRequest();

            // 查询启用的一级分类（统一分类表），按排序字段排序
            $stmt = $this->db->prepare(
                "SELECT id, name, description, code, sort_order, status, created_at, updated_at
                 FROM all_categories
                 WHERE status = 1 AND level = 1
                 ORDER BY sort_order ASC, id ASC"
            );
            $stmt->execute();
            $categories = $stmt->fetchAll();

            // 记录操作日志
            HeaSec_log('get_categories', [
                'count' => count($categories),
                'success' => true
            ]);

            // 返回成功响应
            HeaSec_returnResponse(true, '获取分类成功', $categories);

        } catch (Exception $e) {
            HeaSec_handleError('获取分类失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取团队信息
     */
    public function getTeamInfo()
    {
        try {
            HeaSec_validateRequest();

            $stmt = $this->db->prepare(
                "SELECT team_name, team_en_name, team_abbr, team_slogan, version, build, security_level
                 FROM heasec_team_info
                 WHERE status = 1
                 LIMIT 1"
            );
            $stmt->execute();
            $teamInfo = $stmt->fetch();

            HeaSec_log('get_team_info', [
                'team_abbr' => $teamInfo ? $teamInfo['team_abbr'] : 'unknown'
            ]);

            HeaSec_returnResponse(true, '获取团队信息成功', $teamInfo);

        } catch (Exception $e) {
            HeaSec_handleError('获取团队信息失败: ' . $e->getMessage());
        }
    }
}

// 处理请求
$manager = new HeaSec_CategoryManager();

// 根据action参数执行相应方法
$action = isset($_GET['action']) ? $_GET['action'] : 'getCategories';

switch ($action) {
    case 'getCategories':
        $manager->getCategories();
        break;
    case 'teamInfo':
        $manager->getTeamInfo();
        break;
    default:
        HeaSec_handleError('未知操作', 400);
}
?>