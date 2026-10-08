/**
 * Warehouse Management System - Main JavaScript
 */

(function () {
    'use strict';

    // --- Mobile menu toggle ---
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 768 &&
                !sidebar.contains(e.target) &&
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    // --- Auto-dismiss alerts after 5 seconds ---
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s';
            setTimeout(function () {
                alert.remove();
            }, 300);
        }, 5000);
    });

    // --- Confirm delete actions ---
    document.addEventListener('click', function (e) {
        const target = e.target.closest('[data-confirm]');
        if (target) {
            e.preventDefault();
            const message = target.getAttribute('data-confirm') || 'Are you sure?';
            if (confirm(message)) {
                // If it's a form submit, submit it
                const form = target.closest('form');
                if (form) {
                    form.submit();
                } else {
                    // Create a temporary form for GET links
                    var link = target.closest('a');
                    if (link && link.href) {
                        window.location.href = link.href;
                    }
                }
            }
        }
    });

    // --- Quantity input validation ---
    document.querySelectorAll('input[type="number"]').forEach(function (input) {
        input.addEventListener('input', function () {
            if (this.value < 0) {
                this.value = 0;
            }
        });
    });

    // --- Print button helper ---
    document.querySelectorAll('[data-print]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.print();
        });
    });

    // --- Form auto-save indicator (optional future feature) ---
    let formDirty = false;
    document.addEventListener('change', function (e) {
        if (e.target.closest('form')) {
            formDirty = true;
        }
    }, true);

})();
