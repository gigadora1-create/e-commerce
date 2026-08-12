document.addEventListener('DOMContentLoaded', function () {
    const dragDropArea = document.getElementById('drag-drop-area');
    const fileUpload = document.getElementById('file-upload');
    const fileInfo = document.getElementById('file-info');
    const fileName = document.getElementById('file-name');

    if (dragDropArea && fileUpload && fileInfo && fileName) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
            dragDropArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach((eventName) => {
            dragDropArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dragDropArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            dragDropArea.classList.add('border-primary');
        }

        function unhighlight() {
            dragDropArea.classList.remove('border-primary');
        }

        dragDropArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileUpload.files = files;
            updateFileInfo();
        }

        fileUpload.addEventListener('change', updateFileInfo);

        function updateFileInfo() {
            if (fileUpload.files && fileUpload.files[0]) {
                fileName.textContent = fileUpload.files[0].name;
                fileInfo.style.display = 'block';
            } else {
                fileInfo.style.display = 'none';
            }
        }
    }

    const clearSearchButton = document.getElementById('clearSearchButton');
    const searchForm = document.getElementById('searchForm');
    const searchModal = document.getElementById('searchModal');

    if (clearSearchButton && searchForm) {
        clearSearchButton.addEventListener('click', function () {
            searchForm.reset();
        });
    }

    if (searchModal && window.jQuery) {
        window.jQuery(searchModal).on('show.bs.modal', function () {
            const dialog = this.querySelector('.modal-dialog');
            if (!dialog) {
                return;
            }

            dialog.style.transform = 'scale(0.8)';
            dialog.style.opacity = '0';

            setTimeout(() => {
                dialog.style.transform = 'scale(1)';
                dialog.style.opacity = '1';
                dialog.style.transition = 'all 0.3s ease';
            }, 200);
        });
    }
});
