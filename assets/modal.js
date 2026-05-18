/* ============================================================
   HELPDESK MODAL SYSTEM - modal.js
   Place in: assets/modal.js
   ============================================================ */

const Modal = (() => {

    function _el(tag, cls, html) {
        const e = document.createElement(tag);
        if (cls) e.className = cls;
        if (html) e.innerHTML = html;
        return e;
    }

    function _makeOverlay() {
        const ov = _el('div', 'modal-overlay');
        const box = _el('div', 'modal-box');
        ov.appendChild(box);
        document.body.appendChild(ov);
        ov.addEventListener('click', e => { if (e.target === ov) _close(ov); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') _close(ov); });
        return ov;
    }

    function _close(ov) {
        ov.classList.remove('active');
        setTimeout(() => {
            document.body.style.overflow = '';
            if (ov.parentNode) ov.remove();
        }, 280);
    }

    function _open(ov) {
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(() => ov.classList.add('active'));
    }

    const ICONS = {
        confirm : '&#9888;',
        success : '&#10003;',
        error   : '&#10007;',
        delete  : '&#128465;',
        info    : '&#8505;'
    };

    // 1. CONFIRM MODAL — returns Promise<boolean>
    function confirm({ title = 'Confirm Action', message = 'Are you sure?',
                       confirmText = 'Confirm', cancelText = 'Cancel',
                       type = 'confirm' } = {}) {
        return new Promise(resolve => {
            const ov  = _makeOverlay();
            const box = ov.querySelector('.modal-box');
            box.innerHTML = `
                <div class="modal-icon ${type}">${ICONS[type] || ICONS.confirm}</div>
                <p class="modal-title">${title}</p>
                <p class="modal-message">${message}</p>
                <div class="modal-actions">
                    <button class="modal-btn cancel" id="m-cancel">${cancelText}</button>
                    <button class="modal-btn ${type === 'delete' ? 'danger' : 'confirm'}" id="m-confirm">${confirmText}</button>
                </div>`;
            _open(ov);
            box.querySelector('#m-cancel').onclick  = () => { _close(ov); resolve(false); };
            box.querySelector('#m-confirm').onclick = () => { _close(ov); resolve(true);  };
        });
    }

    // 2. DELETE CONFIRM — returns Promise<boolean>
    function deleteConfirm({ recordName = 'this record', requireTyping = false } = {}) {
        return new Promise(resolve => {
            const ov  = _makeOverlay();
            const box = ov.querySelector('.modal-box');
            const hint = requireTyping
                ? `<p class="modal-message" style="margin-bottom:0.5rem">Type <strong>DELETE</strong> to confirm.</p>
                   <input class="modal-confirm-input" id="m-type-input" placeholder="Type DELETE here" autocomplete="off">`
                : '';
            box.innerHTML = `
                <div class="modal-icon delete">${ICONS.delete}</div>
                <p class="modal-title">Delete Record</p>
                <p class="modal-message">You are about to delete <span class="modal-record-name">"${recordName}"</span>.<br>This action may be irreversible.</p>
                ${hint}
                <div class="modal-actions">
                    <button class="modal-btn cancel" id="m-cancel">Cancel</button>
                    <button class="modal-btn danger" id="m-confirm" ${requireTyping ? 'disabled' : ''}>Delete</button>
                </div>`;
            _open(ov);

            if (requireTyping) {
                const input = box.querySelector('#m-type-input');
                const btn   = box.querySelector('#m-confirm');
                input.addEventListener('input', () => {
                    btn.disabled = input.value.trim().toUpperCase() !== 'DELETE';
                    input.classList.toggle('input-error',
                        input.value.trim() !== '' &&
                        input.value.trim().toUpperCase() !== 'DELETE');
                });
            }

            box.querySelector('#m-cancel').onclick  = () => { _close(ov); resolve(false); };
            box.querySelector('#m-confirm').onclick = () => { _close(ov); resolve(true);  };
        });
    }

    // 3. ALERT / NOTIFY — success, error, info
    function alert({ title, message, type = 'success', btnText = 'OK' } = {}) {
        return new Promise(resolve => {
            const ov  = _makeOverlay();
            const box = ov.querySelector('.modal-box');
            const defaultTitles = { success: 'Success', error: 'Error', info: 'Information' };
            box.innerHTML = `
                <div class="modal-icon ${type}">${ICONS[type] || ICONS.info}</div>
                <p class="modal-title">${title || defaultTitles[type] || 'Notice'}</p>
                <p class="modal-message">${message}</p>
                <button class="modal-btn ok" id="m-ok">${btnText}</button>`;
            _open(ov);
            box.querySelector('#m-ok').onclick = () => { _close(ov); resolve(); };
        });
    }

    // 4. REVIEW MODAL — show form data before submitting, returns Promise<boolean>
    function review({ title = 'Review Before Saving', rows = [],
                      confirmText = 'Confirm & Save',
                      cancelText = 'Go Back & Edit' } = {}) {
        return new Promise(resolve => {
            const ov  = _makeOverlay();
            const box = ov.querySelector('.modal-box');
            const tableRows = rows.map(r =>
                `<tr>
                    <td>${r.label}</td>
                    <td>${r.value || '<span style="color:#9CA3AF">-</span>'}</td>
                </tr>`
            ).join('');
            box.innerHTML = `
                <p class="modal-title" style="text-align:left;margin-bottom:1rem">${title}</p>
                <table class="modal-review-table">${tableRows}</table>
                <div class="modal-actions">
                    <button class="modal-btn cancel" id="m-cancel">${cancelText}</button>
                    <button class="modal-btn confirm" id="m-confirm">${confirmText}</button>
                </div>`;
            _open(ov);
            box.querySelector('#m-cancel').onclick  = () => { _close(ov); resolve(false); };
            box.querySelector('#m-confirm').onclick = () => { _close(ov); resolve(true);  };
        });
    }

    // 5. LOADING STATE — disables button and shows spinner
    function loading(btn, text = 'Processing...') {
        const original = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<span class="btn-spinner"></span>${text}`;
        return function restore() {
            btn.disabled = false;
            btn.innerHTML = original;
        };
    }

    return { confirm, deleteConfirm, alert, review, loading };
})();
