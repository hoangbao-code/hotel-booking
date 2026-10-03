/**
 * GRAND OASIS HOTEL - MAIN CLIENT JAVASCRIPT
 * File: assets/js/main.js
 * Vanilla JavaScript - Không phụ thuộc thư viện ngoài
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. TÍNH TOÁN VÀ ĐỒNG BỘ NGÀY CHECK-IN / CHECK-OUT
    const checkInInputs = document.querySelectorAll('input[type="date"][name="check_in"], #check_in');
    const checkOutInputs = document.querySelectorAll('input[type="date"][name="check_out"], #check_out');

    // Lấy ngày hôm nay theo định dạng YYYY-MM-DD
    const today = new Date();
    const todayStr = formatDate(today);

    // Tính ngày mai
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const tomorrowStr = formatDate(tomorrow);

    checkInInputs.forEach(function (checkInInput) {
        if (!checkInInput.hasAttribute('readonly')) {
            // Không cho chọn ngày trong quá khứ
            if (!checkInInput.getAttribute('min')) {
                checkInInput.setAttribute('min', todayStr);
            }

            // Tìm input check-out tương ứng cùng form
            const form = checkInInput.closest('form');
            const checkOutInput = form ? form.querySelector('input[type="date"][name="check_out"], #check_out') : document.getElementById('check_out');

            if (checkOutInput && !checkOutInput.hasAttribute('readonly')) {
                // Khởi tạo min cho check-out
                if (checkInInput.value) {
                    const nextDay = getNextDayStr(checkInInput.value);
                    checkOutInput.setAttribute('min', nextDay);
                } else {
                    checkOutInput.setAttribute('min', tomorrowStr);
                }

                function updateNightsBadge() {
                    if (form && checkInInput.value && checkOutInput.value) {
                        const badge = form.querySelector('.js-nights-badge');
                        if (badge) {
                            const d1 = new Date(checkInInput.value);
                            const d2 = new Date(checkOutInput.value);
                            const diffTime = d2.getTime() - d1.getTime();
                            const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
                            if (diffDays > 0) {
                                badge.textContent = diffDays + ' đêm lưu trú';
                                badge.style.display = 'inline-flex';
                            } else {
                                badge.style.display = 'none';
                            }
                        }
                    }
                }

                // Khởi tạo hiển thị số đêm ban đầu
                updateNightsBadge();

                // Lắng nghe sự kiện thay đổi của check-in
                checkInInput.addEventListener('change', function () {
                    if (this.value) {
                        const minCheckOut = getNextDayStr(this.value);
                        checkOutInput.setAttribute('min', minCheckOut);

                        // Nếu ngày trả phòng nhỏ hơn hoặc bằng ngày nhận phòng -> tự động cập nhật ngày trả phòng sang ngày hôm sau
                        if (!checkOutInput.value || checkOutInput.value <= this.value) {
                            checkOutInput.value = minCheckOut;
                        }
                        updateNightsBadge();
                    }
                });

                // Lắng nghe sự kiện thay đổi của check-out
                checkOutInput.addEventListener('change', function () {
                    if (checkInInput.value && this.value <= checkInInput.value) {
                        alert('Ngày trả phòng phải sau ngày nhận phòng ít nhất 1 đêm.');
                        this.value = getNextDayStr(checkInInput.value);
                    }
                    updateNightsBadge();
                });
            }
        }
    });

    // 2. PHÒNG NGỪA DOUBLE SUBMIT (GỬI LẶP FORM)
    const postForms = document.querySelectorAll('form[method="POST"], form[method="post"]');
    postForms.forEach(function (form) {
        form.addEventListener('submit', function (e) {
            // Kiểm tra xem form có hợp lệ theo HTML5 không
            if (!form.checkValidity()) {
                return;
            }

            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                // Đặt timeout nhẹ để trình duyệt bắt đầu quá trình gửi request trước khi disable nút
                setTimeout(function () {
                    submitBtn.disabled = true;
                    if (submitBtn.tagName.toLowerCase() === 'button') {
                        submitBtn.setAttribute('data-original-text', submitBtn.innerHTML);
                        submitBtn.innerHTML = '<span style="opacity: 0.85;">Đang xử lý...</span>';
                    }
                }, 50);
            }
        });
    });

    // 3. NÚT ĐÓNG THÔNG BÁO FLASH ALERTS
    const flashAlerts = document.querySelectorAll('.flash');
    flashAlerts.forEach(function (alertEl) {
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.innerHTML = '&times;';
        closeBtn.title = 'Đóng thông báo';
        closeBtn.style.marginLeft = 'auto';
        closeBtn.style.background = 'none';
        closeBtn.style.border = 'none';
        closeBtn.style.fontSize = '1.3rem';
        closeBtn.style.fontWeight = 'bold';
        closeBtn.style.lineHeight = '1';
        closeBtn.style.cursor = 'pointer';
        closeBtn.style.color = 'inherit';
        closeBtn.style.opacity = '0.6';
        closeBtn.style.padding = '0 0.25rem';

        closeBtn.addEventListener('mouseenter', function () {
            this.style.opacity = '1';
        });
        closeBtn.addEventListener('mouseleave', function () {
            this.style.opacity = '0.6';
        });

        closeBtn.addEventListener('click', function () {
            alertEl.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
            alertEl.style.opacity = '0';
            alertEl.style.transform = 'translateY(-6px)';
            setTimeout(function () {
                if (alertEl.parentNode) {
                    alertEl.parentNode.removeChild(alertEl);
                }
            }, 200);
        });

        alertEl.appendChild(closeBtn);
    });

    // Hàm tiện ích: Định dạng đối tượng Date thành YYYY-MM-DD
    function formatDate(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Hàm tiện ích: Lấy ngày hôm sau từ chuỗi YYYY-MM-DD
    function getNextDayStr(dateStr) {
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
            d.setDate(d.getDate() + 1);
            return formatDate(d);
        }
        return '';
    }

    // 4. XỬ LÝ MODAL HỘP THOẠI CHI TIẾT PHÒNG
    document.querySelectorAll('[data-open-modal]').forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            const modalId = this.getAttribute('data-open-modal');
            const targetModal = document.getElementById(modalId);
            if (targetModal) {
                targetModal.classList.add('is-active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    document.querySelectorAll('[data-close-modal], .modal-backdrop').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (e.target === this || this.hasAttribute('data-close-modal')) {
                document.querySelectorAll('.modal-backdrop.is-active').forEach(function (m) {
                    m.classList.remove('is-active');
                });
                document.body.style.overflow = '';
            }
        });
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.is-active').forEach(function (m) {
                m.classList.remove('is-active');
            });
            document.body.style.overflow = '';
        }
    });
});
