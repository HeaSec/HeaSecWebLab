<?php
/**
 * HeaSec 统一状态更新API接口
 * 支持学习状态和学习进度的更新
 *
 * @package HeavenlySecret\API
 * @version HeaSec v1.0.0
 */

// 开启输出缓冲，防止header警告
ob_start();

require_once __DIR__ . '/../../config/config.php';

class HeaSec_StatusManager
{
    private $db;

    public function __construct()
    {
        $this->db = HeaSec_getConnection();
    }

    /**
     * 更新学习状态（简化版本，兼容原有learned_status）
     */
    public function updateLearnedStatus()
    {
        try {
            HeaSec_validateRequest();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                HeaSec_handleError('请求方法不支持', 405);
            }

            // 获取POST数据
            $json_input = file_get_contents('php://input');
            $input = json_decode($json_input, true);

            if (!$input) {
                // 如果不是JSON格式，尝试获取普通POST数据
                $input = $_POST;
            }

            $link_id = isset($input['link_id']) ? (int) $input['link_id'] : 0;
            $learned_status = isset($input['learned_status']) ? (int) $input['learned_status'] : 0;

            // 验证输入
            if ($link_id <= 0) {
                HeaSec_handleError('无效的链接ID', 400);
            }

            if (!in_array($learned_status, [0, 1])) {
                HeaSec_handleError('学习状态值无效', 400);
            }

            // 检查链接是否存在
            $checkStmt = $this->db->prepare("SELECT id FROM links WHERE id = ? AND status = 1");
            $checkStmt->execute([$link_id]);
            if (!$checkStmt->fetch()) {
                HeaSec_handleError('链接不存在或已禁用', 404);
            }

            // 更新学习状态
            $updateStmt = $this->db->prepare("UPDATE links SET learned_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$learned_status, $link_id]);

            // 记录操作日志
            HeaSec_log('update_learned_status', [
                'link_id' => $link_id,
                'learned_status' => $learned_status,
                'success' => true
            ]);

            // 返回成功响应
            HeaSec_returnResponse(true, $learned_status ? '标记为已学习' : '标记为未学习', [
                'link_id' => $link_id,
                'learned_status' => $learned_status
            ]);

        } catch (Exception $e) {
            HeaSec_handleError('更新学习状态失败: ' . $e->getMessage());
        }
    }

    /**
     * 更新学习进度（详细版本）
     */
    public function updateLearningStatus()
    {
        try {
            HeaSec_validateRequest();

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                HeaSec_handleError('请求方法不支持', 405);
            }

            // 获取POST数据
            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data) {
                HeaSec_handleError('无效的请求数据', 400);
            }

            $link_id = isset($data['link_id']) ? (int) $data['link_id'] : 0;
            $learning_status = isset($data['learning_status']) ? $data['learning_status'] : '';

            // 验证参数
            if ($link_id <= 0) {
                HeaSec_handleError('无效的链接ID', 400);
            }

            // 验证学习状态
            $validLearningStatuses = ['待学习', '学习中', '已掌握'];
            if (!in_array($learning_status, $validLearningStatuses)) {
                HeaSec_handleError('无效的学习状态', 400);
            }

            // 检查链接是否存在
            $checkStmt = $this->db->prepare("SELECT id FROM links WHERE id = ? AND status = 1");
            $checkStmt->execute([$link_id]);
            if (!$checkStmt->fetch()) {
                HeaSec_handleError('链接不存在或已禁用', 404);
            }

            // 更新学习状态
            $updateStmt = $this->db->prepare("UPDATE links SET learning_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $updateStmt->execute([$learning_status, $link_id]);

            if ($result) {
                // 获取更新后的状态信息返回
                $selectStmt = $this->db->prepare("SELECT learning_status FROM links WHERE id = ?");
                $selectStmt->execute([$link_id]);
                $updated = $selectStmt->fetch(PDO::FETCH_ASSOC);

                // 记录操作日志
                HeaSec_log('update_learning_status', [
                    'link_id' => $link_id,
                    'learning_status' => $learning_status,
                    'success' => true
                ]);

                HeaSec_returnResponse(true, '学习状态更新成功', [
                    'link_id' => $link_id,
                    'learning_status' => $updated['learning_status']
                ]);
            } else {
                HeaSec_handleError('学习状态更新失败', 500);
            }

        } catch (Exception $e) {
            HeaSec_handleError('系统错误: ' . $e->getMessage());
        }
    }
}

// 处理请求
$manager = new HeaSec_StatusManager();

// 根据action参数执行相应方法
$action = isset($_GET['action']) ? $_GET['action'] : 'updateLearnedStatus';

switch ($action) {
    case 'updateLearnedStatus':
        $manager->updateLearnedStatus();
        break;
    case 'updateLearningStatus':
        $manager->updateLearningStatus();
        break;
    default:
        HeaSec_handleError('未知操作', 400);
}
?>