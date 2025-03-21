document.addEventListener('DOMContentLoaded', function() {
    const updateProfileBtn = document.getElementById('update-profile');
    const nicknameInput = document.getElementById('nickname');
    const websiteInput = document.getElementById('website');
    const descriptionInput = document.getElementById('description');

    let currentMessage = null;
    let messageTimeout = null;

    function showMessage(message, type = 'success') {
        if (currentMessage) {
            currentMessage.remove();
            clearTimeout(messageTimeout);
        }

        const messageDiv = document.createElement('div');
        messageDiv.className = `alert alert-${type}`;
        messageDiv.textContent = message;
        
        document.body.appendChild(messageDiv);
        currentMessage = messageDiv;

        messageTimeout = setTimeout(() => {
            messageDiv.style.opacity = '0';
            messageDiv.style.transform = 'translateX(100%)';
            setTimeout(() => messageDiv.remove(), 300);
            currentMessage = null;
        }, 3000);
    }

    function validateInputs() {
        if (!nicknameInput.value.trim()) {
            showMessage('昵称不能为空', 'error');
            return false;
        }

        if (websiteInput.value.trim() && !websiteInput.value.match(/^https?:\/\/.+/)) {
            showMessage('请输入有效的网站地址', 'error');
            return false;
        }

        return true;
    }

    updateProfileBtn.addEventListener('click', function(e) {
        e.preventDefault();

        if (!validateInputs()) {
            return;
        }

        const data = {
            action: 'update_user_profile',
            nickname: nicknameInput.value.trim(),
            website: websiteInput.value.trim(),
            description: descriptionInput.value.trim(),
            nonce: wpApiSettings.nonce
        };

        updateProfileBtn.disabled = true;
        updateProfileBtn.innerHTML = '<span class="spinner"></span>更新中...';

        fetch(wpApiSettings.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('资料更新成功');
            } else {
                showMessage(data.data.message || '更新失败，请稍后重试', 'error');
            }
        })
        .catch(error => {
            showMessage('更新失败，请稍后重试', 'error');
            console.error('Error:', error);
        })
        .finally(() => {
            updateProfileBtn.disabled = false;
            updateProfileBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                保存更改
            `;
        });
    });
});