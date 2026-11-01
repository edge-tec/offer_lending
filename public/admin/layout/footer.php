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

