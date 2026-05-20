<?php
/**
 * HeaSec天积安全团队 - 注册手机管理标签页模板
 * Tab Template: Phone Management
 * 版本: v1.0.0
 * 创建日期: 2026-01-06
 * 团队: 天积安全 (HeavenlySecret)
 */
?>
<!-- 手机号管理工具栏 -->
<div class="heasec-sms-toolbar">
    <div class="toolbar-left">
        <button class="btn btn-primary btn-add-phone" id="btnAddPhone">
            <i class="fa fa-plus"></i>
            添加手机号
        </button>
        <button class="btn btn-success btn-batch-add" id="btnBatchAddPhones">
            <i class="fa fa-list-ul"></i>
            批量添加
        </button>
        <button class="btn btn-danger btn-batch-delete" id="btnBatchDeletePhones" disabled>
            <i class="fa fa-trash"></i>
            批量删除
        </button>
    </div>
    <div class="toolbar-right">
        <button class="btn btn-secondary btn-refresh" id="btnRefreshPhones">
            <i class="fa fa-refresh"></i>
            刷新
        </button>
    </div>
</div>

<!-- 手机号列表表格 -->
<div class="heasec-sms-table-container">
    <table class="heasec-sms-table" id="phonesTable">
        <thead>
            <tr>
                <th >
                    <input type="checkbox" id="selectAllPhones" />
                </th>
                <th >手机号</th>
                <th >状态</th>
                <th >默认</th>
                <th >短信数</th>
                <th>创建时间</th>
                <th >操作</th>
            </tr>
        </thead>
        <tbody id="phonesTableBody">
            <tr class="loading-row">
                <td colspan="7">
                    <div class="loading-indicator">
                        <i class="fa fa-spinner fa-spin"></i>
                        正在加载手机号列表...
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<!-- 添加/编辑手机号模态框 -->
<div class="heasec-sms-modal" id="phoneModal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title" id="phoneModalTitle">添加手机号</h3>
            <button class="modal-close" id="closePhoneModal">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-content">
            <form id="phoneForm">
                <input type="hidden" id="phoneId" name="id" value="" />

                <div class="form-group">
                    <label for="phoneNumber">
                        <i class="fa fa-mobile"></i>
                        手机号
                        <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="phoneNumber"
                           name="phone_number"
                           class="form-control"
                           placeholder="请输入11位手机号"
                           maxlength="11"
                           autocomplete="off" />
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-cancel" id="cancelPhoneModal">
                <i class="fa fa-times"></i>
                取消
            </button>
            <button class="btn btn-primary modal-confirm" id="confirmPhoneModal">
                <i class="fa fa-check"></i>
                确定
            </button>
        </div>
    </div>
</div>

<!-- 批量添加手机号模态框 -->
<div class="heasec-sms-modal" id="batchAddPhonesModal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-container" style="max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">批量添加手机号</h3>
            <button class="modal-close" id="closeBatchAddModal">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <div class="modal-content">
            <form id="batchAddPhonesForm">
                <div class="form-group">
                    <label for="phonesTextarea">
                        <i class="fa fa-mobile"></i>
                        手机号列表
                        <span class="required">*</span>
                    </label>
                    <textarea id="phonesTextarea"
                              name="phones"
                              class="form-control"
                              rows="15"
                              placeholder="请输入手机号，每行一个&#10;例如：&#10;13800138000&#10;13900139000&#10;15800158000"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-cancel" id="cancelBatchAddModal">
                <i class="fa fa-times"></i>
                取消
            </button>
            <button class="btn btn-primary modal-confirm" id="confirmBatchAddModal">
                <i class="fa fa-check"></i>
                开始添加
            </button>
        </div>
    </div>
</div>
