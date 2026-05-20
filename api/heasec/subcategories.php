<?php
/**
 * SAsec 二级分类管理API接口
 *
 * @package HeavenlySecret\API
 * @version HeaSec v1.0.0
 */

require_once __DIR__ . '/../../config/config.php';

class HeaSec_SubcategoryManager
{
    private $db;

    public function __construct()
    {
        $this->db = HeaSec_getConnection();
    }

    /**
     * 获取二级分类列表
     */
    public function getSubcategories()
    {
        try {
            HeaSec_validateRequest();

            // 检查是否有分类过滤参数（parent_id即原category_id）
            $category_id = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;

            $sql = "SELECT sc.id, sc.name, sc.description, sc.code, sc.parent_id as category_id, sc.sort_order,
                           sc.status, sc.created_at, sc.updated_at,
                           p.name as category_name
                    FROM all_categories sc
                    LEFT JOIN all_categories p ON sc.parent_id = p.id
                    WHERE sc.status = 1 AND sc.level = 2";

            $params = [];

            if ($category_id) {
                $sql .= " AND sc.parent_id = ?";
                $params[] = $category_id;
            }

            $sql .= " ORDER BY sc.sort_order ASC, sc.id ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $subcategories = $stmt->fetchAll();

            // 记录操作日志
            HeaSec_log('get_subcategories', [
                'count' => count($subcategories),
                'category_id' => $category_id,
                'success' => true
            ]);

            HeaSec_returnResponse(true, '获取二级分类成功', $subcategories);

        } catch (Exception $e) {
            HeaSec_handleError('获取二级分类失败: ' . $e->getMessage());
        }
    }
}

// 处理请求
$manager = new HeaSec_SubcategoryManager();
$manager->getSubcategories();
?>