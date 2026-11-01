        </main>
    </div>
</div>

<script>
// Global helper for opening/closing admin modals
function openAdminModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeAdminModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('active');
    }
}

// Global helper for copying links with cross-browser fallback
function copyToClipboard(text, btn) {
    if (!text) return;
    
    // Convert relative path to full URL
    if (text.startsWith('/')) {
        text = window.location.origin + text;
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCopyFeedback(btn);
        }).catch(() => {
            fallbackCopy(text, btn);
        });
    } else {
        fallbackCopy(text, btn);
    }
}

function fallbackCopy(text, btn) {
    const tempInput = document.createElement('textarea');
    tempInput.value = text;
    tempInput.style.position = 'fixed';
    tempInput.style.top = '0';
    tempInput.style.left = '0';
    tempInput.style.opacity = '0';
    document.body.appendChild(tempInput);
    tempInput.focus();
    tempInput.select();
    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopyFeedback(btn);
        } else {
            prompt('Copy this link manually: Ctrl+C, Enter', text);
        }
    } catch (err) {
        prompt('Copy this link manually: Ctrl+C, Enter', text);
    }
    document.body.removeChild(tempInput);
}

function showCopyFeedback(btn) {
    if (!btn) return;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '✓ Copied!';
    const origColor = btn.style.color;
    btn.style.color = '#34d399';
    btn.style.borderColor = '#10b981';
    setTimeout(() => {
        btn.innerHTML = origHtml;
        btn.style.color = origColor;
        btn.style.borderColor = '';
    }, 2200);
}

// Close modals on clicking backdrop
document.querySelectorAll('.modal-admin').forEach(m => {
    m.addEventListener('click', (e) => {
        if (e.target === m) {
            m.classList.remove('active');
        }
    });
});
</script>
</body>
</html>

