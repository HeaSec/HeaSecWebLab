// 初始化拖拽上传功能
document.addEventListener('DOMContentLoaded', function () {
    initDragUpload();

    // 初始化传统文件选择
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            updateFileList();
        });
    }
});

/**
 * 初始化拖拽上传功能
 */
function initDragUpload() {
    const uploadDropzone = document.getElementById('uploadDropzone');
    const dropzoneInput = document.getElementById('avatarInputDropzone');
    const avatarInput = document.getElementById('avatarInput');

    if (!uploadDropzone || !dropzoneInput) {
        return;
    }

    // 点击拖拽区域触发文件选择
    uploadDropzone.addEventListener('click', function (e) {
        if (e.target !== dropzoneInput) {
            dropzoneInput.click();
        }
    });

    // 拖拽区域文件选择事件
    dropzoneInput.addEventListener('change', function (e) {
        const files = e.target.files;
        if (files.length > 0) {
            updateDropzoneText(files);

            // 同步文件到avatarInput
            syncFileInputs(files);

            // 显示文件列表
            displayFileList(files);
        }
    });

    // 传统文件选择事件
    if (avatarInput) {
        avatarInput.addEventListener('change', function (e) {
            const files = e.target.files;
            if (files.length > 0) {
                // 同步文件到dropzoneInput
                syncFileInputs(files);
                updateDropzoneText(files);
            }
        });
    }

    // 拖拽事件处理
    uploadDropzone.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('drag-over');
    });

    uploadDropzone.addEventListener('dragleave', function (e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');
    });

    uploadDropzone.addEventListener('drop', function (e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            // 同步文件到dropzoneInput
            syncFileInputs(files);

            // 更新拖拽区域显示
            updateDropzoneText(files);

            // 显示文件列表
            displayFileList(files);
        }
    });
}

/**
 * 同步文件到输入框
 */
function syncFileInputs(files) {
    const dropzoneInput = document.getElementById('avatarInputDropzone');
    const avatarInput = document.getElementById('avatarInput');

    // 创建一个新的FileList对象（通过DataTransfer）
    const dataTransfer = new DataTransfer();
    for (let i = 0; i < files.length; i++) {
        dataTransfer.items.add(files[i]);
    }

    if (dropzoneInput) {
        dropzoneInput.files = dataTransfer.files;
    }

    if (avatarInput) {
        avatarInput.files = dataTransfer.files;
    }
}

/**
 * 更新拖拽区域文本
 */
function updateDropzoneText(files) {
    const uploadDropzone = document.getElementById('uploadDropzone');
    if (!uploadDropzone) return;

    const textElement = uploadDropzone.querySelector('p');
    if (textElement) {
        if (files.length === 1) {
            const fileSize = formatFileSize(files[0].size);
            textElement.innerHTML = '<strong>已选择文件:</strong><br>' +
                files[0].name + '<br><small>大小: ' + fileSize + '</small>';
        } else {
            let totalSize = 0;
            for (let i = 0; i < files.length; i++) {
                totalSize += files[i].size;
            }
            textElement.innerHTML = '<strong>已选择 ' + files.length + ' 个文件</strong><br>' +
                '<small>总大小: ' + formatFileSize(totalSize) + '</small>';
        }
    }
}

/**
 * 重置拖拽区域文本
 */
function resetDropzoneText() {
    const uploadDropzone = document.getElementById('uploadDropzone');
    if (!uploadDropzone) return;

    const textElement = uploadDropzone.querySelector('p');
    if (textElement) {
        textElement.textContent = '点击选择文件或拖拽文件到此处（支持多选）';
    }
}

/**
 * 显示文件列表
 */
function displayFileList(files) {
    const fileListContainer = document.getElementById('fileListContainer');
    const fileList = document.getElementById('fileList');

    if (!fileListContainer || !fileList) return;

    fileList.innerHTML = '';

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const li = document.createElement('li');

        const isValid = validateFileTypeByName(file.name);
        const validClass = isValid ? 'file-valid' : 'file-invalid';
        const validIcon = isValid ? 'fa-check-circle' : 'fa-exclamation-circle';

        li.innerHTML = '<span class="file-name">' +
            '<i class="fa ' + validIcon + ' ' + validClass + '"></i> ' +
            file.name +
            '</span>' +
            '<span class="file-size">' + formatFileSize(file.size) + '</span>';

        fileList.appendChild(li);
    }

    fileListContainer.style.display = 'block';
}

/**
 * 更新文件列表
 */
function updateFileList() {
    const avatarInput = document.getElementById('avatarInput');
    const dropzoneInput = document.getElementById('avatarInputDropzone');

    let files = null;
    if (avatarInput && avatarInput.files.length > 0) {
        files = avatarInput.files;
    } else if (dropzoneInput && dropzoneInput.files.length > 0) {
        files = dropzoneInput.files;
    }

    if (files && files.length > 0) {
        syncFileInputs(files);
        updateDropzoneText(files);
        displayFileList(files);
    }
}

/**
 * 格式化文件大小
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';

    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

/**
 * 通过文件名验证文件类型
 */
function validateFileTypeByName(fileName) {
    if (!fileName) return true;

    const lowerFileName = fileName.toLowerCase();
    const allowedExtensions = ['.jpg', '.jpeg', '.png', '.gif'];

    let isValid = false;
    for (let ext of allowedExtensions) {
        if (lowerFileName.endsWith(ext)) {
            isValid = true;
            break;
        }
    }

    return isValid;
}

/**
 * 表单提交时的文件类型验证
 */
function validateFiles() {
    const fileInput = document.getElementById('avatarInput');
    const dropzoneInput = document.getElementById('avatarInputDropzone');

    // 获取文件列表
    let files = null;
    if (fileInput && fileInput.files.length > 0) {
        files = fileInput.files;
    } else if (dropzoneInput && dropzoneInput.files.length > 0) {
        files = dropzoneInput.files;
    }

    if (!files || files.length === 0) {
        return true; // 允许空提交
    }

    // 验证第一个文件必须是图片格式
    const firstFile = files[0];
    if (!validateFileTypeByName(firstFile.name)) {
        alert('检测到非法文件，已拦截！');
        return false;
    }

    return true;
}

/**
 * 确认重置
 */
function confirmReset() {
    return confirm('确认删除全部已上传的文件吗？');
}

// 添加键盘快捷键支持（Ctrl+O 打开文件选择）
document.addEventListener('keydown', function (e) {
    if (e.ctrlKey && e.key === 'o') {
        e.preventDefault();
        const dropzoneInput = document.getElementById('avatarInputDropzone');
        if (dropzoneInput) {
            dropzoneInput.click();
        }
    }
});
