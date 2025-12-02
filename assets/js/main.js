// assets/js/main.js
// Helpers JS compartilhados para o app: toasts, confirm modal, e um pequeno wrapper fetch.
// Comentários em PT-BR para facilitar manutenção.

(function(){
    // Toast simples: exibe mensagem no canto superior direito
    window.showToast = function(message, type = 'info', duration = 4000) {
        let toast = document.getElementById('siteToast');
        let msg = document.getElementById('siteToastMsg');
        if (!toast || !msg) {
            // criar markup se não existir
            toast = document.createElement('div');
            toast.id = 'siteToast';
            toast.style = 'position:fixed; top:20px; right:20px; z-index:3000; min-width:260px; display:none; padding:12px 16px; border-radius:8px; box-shadow:0 8px 30px rgba(0,0,0,0.2); color:white;';
            msg = document.createElement('div');
            msg.id = 'siteToastMsg';
            msg.style.fontSize = '14px';
            toast.appendChild(msg);
            document.body.appendChild(toast);
        }
        msg.textContent = message;
        toast.style.display = 'block';
        if (type === 'success') toast.style.background = 'linear-gradient(90deg,#2ecc71,#27ae60)';
        else if (type === 'error') toast.style.background = 'linear-gradient(90deg,#e74c3c,#c0392b)';
        else toast.style.background = 'rgba(0,0,0,0.8)';
        clearTimeout(window._siteToastTimer);
        window._siteToastTimer = setTimeout(() => { toast.style.display = 'none'; }, duration);
    };

    // Confirm modal reutilizável: retorna Promise<boolean>
    window.showConfirm = function(message, title = 'Confirmar ação') {
        return new Promise(resolve => {
            let overlay = document.getElementById('confirmOverlay');
            if (!overlay) {
                // criar markup do modal (se ausente)
                overlay = document.createElement('div');
                overlay.id = 'confirmOverlay';
                overlay.style = 'display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;';
                overlay.innerHTML = `
                    <div id="confirmBox" style="background:white; border-radius:10px; padding:20px; max-width:480px; width:90%; box-shadow:0 10px 40px rgba(0,0,0,0.3);">
                        <div style="font-weight:700; font-size:18px; margin-bottom:8px;" id="confirmTitle">${title}</div>
                        <div id="confirmMessage" style="color:#444; margin-bottom:18px;">${message}</div>
                        <div style="display:flex; gap:10px; justify-content:flex-end;">
                            <button id="confirmCancel" style="padding:8px 14px; border-radius:8px; border:none; background:#e0e0e0; cursor:pointer;">Cancelar</button>
                            <button id="confirmOk" style="padding:8px 14px; border-radius:8px; border:none; background:linear-gradient(90deg,#667eea,#764ba2); color:white; cursor:pointer;">OK</button>
                        </div>
                    </div>`;
                document.body.appendChild(overlay);
            }

            const msgEl = overlay.querySelector('#confirmMessage');
            const titleEl = overlay.querySelector('#confirmTitle');
            const ok = overlay.querySelector('#confirmOk');
            const cancel = overlay.querySelector('#confirmCancel');

            titleEl.textContent = title;
            msgEl.textContent = message;
            overlay.style.display = 'flex';

            function cleanup(result) {
                overlay.style.display = 'none';
                ok.removeEventListener('click', onOk);
                cancel.removeEventListener('click', onCancel);
                resolve(result);
            }
            function onOk() { cleanup(true); }
            function onCancel() { cleanup(false); }
            ok.addEventListener('click', onOk);
            cancel.addEventListener('click', onCancel);
        });
    };

    // Pequeno wrapper para fetch que trata erros e retorna JSON com tratamento padrão
    window.apiFetch = async function(path, opts = {}) {
        opts.credentials = opts.credentials || 'same-origin';
        if (opts.body && typeof opts.body === 'object') {
            opts.headers = opts.headers || {};
            if (!opts.headers['Content-Type']) opts.headers['Content-Type'] = 'application/json';
            if (opts.headers['Content-Type'].includes('application/json') && typeof opts.body !== 'string') {
                opts.body = JSON.stringify(opts.body);
            }
        }
        const res = await fetch(path, opts);
        if (!res.ok) {
            const t = await res.text();
            throw new Error('API error: ' + res.status + ' ' + res.statusText + ' - ' + t.slice(0,200));
        }
        const txt = await res.text();
        try {
            return JSON.parse(txt);
        } catch (e) {
            // se o servidor eventualmente enviar JSON + junk, tentamos extrair o JSON inicial
            const idx = txt.indexOf('{');
            if (idx !== -1) {
                try { return JSON.parse(txt.slice(idx)); } catch(e2) { /* fallback */ }
            }
            throw new Error('Resposta inválida da API: ' + e.message);
        }
    };
})();
