<?php
/**
 * HeaSec 三级分类API接口
 *
 * @package HeavenlySecret\API
 * @version HeaSec v1.0.0
 */

require_once __DIR__ . '/../../config/config.php';

class HeaSec_ThirdLevelCategoryManager
{
    private $db;

    public function __construct()
    {
        $this->db = HeaSec_getConnection();
    }

    /**
     * 获取三级分类列表
     */
    public function getThirdLevelCategories()
    {
        try {
            HeaSec_validateRequest();

            // 查询启用的三级分类（统一分类表level=3），支持二级分类过滤
            // 查询启用的三级分类（统一分类表level=3），支持二级分类过滤
            $sql = "SELECT t.id, t.name, t.description, t.code, t.parent_id as subcategory_id, t.sort_order, t.status,
                           t.created_at, t.updated_at,
                           s.name as subcategory_name,
                           c.name as category_name, c.id as category_id
                    FROM all_categories t
                    LEFT JOIN all_categories s ON t.parent_id = s.id
                    LEFT JOIN all_categories c ON s.parent_id = c.id
                    WHERE t.level = 3 AND t.status = 1";

            // 检查是否有二级分类过滤参数
            $subcategory_id = isset($_GET['subcategory_id']) ? (int) $_GET['subcategory_id'] : null;

            $params = [];

            if ($subcategory_id) {
                $sql .= " AND t.parent_id = ?";
                $params[] = $subcategory_id;
            }

            $sql .= " ORDER BY t.parent_id ASC, t.sort_order ASC, t.id ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $thirdCategories = $stmt->fetchAll();

            // 记录操作日志
            HeaSec_log('get_third_level_categories', [
                'count' => count($thirdCategories),
                'subcategory_id' => $subcategory_id,
                'success' => true
            ]);

            HeaSec_returnResponse(true, '获取三级分类成功', $thirdCategories);

        } catch (Exception $e) {
            HeaSec_handleError('获取三级分类失败: ' . $e->getMessage());
        }
    }
}

// 处理请求
$manager = new HeaSec_ThirdLevelCategoryManager();
$manager->getThirdLevelCategories();
?>