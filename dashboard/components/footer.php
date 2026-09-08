            </div>
        </main>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-6 right-6 translate-y-24 opacity-0 transition-all duration-300 z-[60] flex items-center gap-3 bg-[#0f0f0f] border border-gray-800 shadow-2xl rounded-xl p-4 min-w-[300px]">
        <div id="toast-icon"></div>
        <p id="toast-msg" class="text-sm font-bold text-white tracking-wide"></p>
    </div>

    <script src="assets/js/main.js?v=<?= time() ?>"></script>
    <?= isset($extra_js) ? $extra_js :  '' ?>

    <!-- Global SweetAlert2 Interceptor for all standard confirms -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Intercept Form Submissions
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.hasAttribute('onsubmit') && form.getAttribute('onsubmit').includes('confirm(')) {
                e.preventDefault();
                const msgMatch = form.getAttribute('onsubmit').match(/confirm\(['"](.*?)['"]\)/);
                const msg = msgMatch ? msgMatch[1] : 'คุณยืนยันที่จะดำเนินการต่อหรือไม่?';
                const actionUrl = form.getAttribute('action') || '';
                    const isDelete = msg.includes('ลบ') || actionUrl.includes('delete');
                    const themeColor = isDelete ? '#ff003c' : '#00d0ff';
                    const iconClass = isDelete ? 'ph-trash' : 'ph-question';
                    
                    Swal.fire({
                        html: `
                            <div class="flex justify-center mb-6 mt-4">
                                <div class="w-24 h-24 rounded-full flex items-center justify-center" style="background: rgba(${isDelete ? '255,0,60' : '0,208,255'}, 0.1); box-shadow: 0 0 30px rgba(${isDelete ? '255,0,60' : '0,208,255'}, 0.5);">
                                    <i class="ph-bold ${iconClass} text-5xl" style="color: ${themeColor}"></i>
                                </div>
                            </div>
                            <h2 class="text-3xl font-black text-white mb-6 tracking-wide">${isDelete ? 'ยืนยันการลบ?' : 'ยืนยันการทำรายการ?'}</h2>
                            <div class="bg-[#151515] border border-gray-800 rounded-2xl p-5 mb-8 text-center shadow-inner">
                                <p class="text-gray-300 text-lg font-bold">${msg}</p>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: `<i class="ph-bold ph-check-circle text-xl mr-2"></i> ยืนยัน`,
                        cancelButtonText: `ยกเลิก`,
                        buttonsStyling: false,
                        showCloseButton: false,
                        customClass: {
                            popup: '!bg-[#0a0a0a] !border !border-gray-800 !rounded-[2rem] shadow-2xl !p-6',
                            htmlContainer: '!m-0',
                            actions: '!w-full !mt-0 !grid !grid-cols-2 !gap-4',
                            confirmButton: `!w-full !m-0 !py-4 !rounded-xl !text-lg !font-bold !text-white !transition-all !border-0`,
                            cancelButton: `!w-full !m-0 !py-4 !rounded-xl !text-lg !font-bold !text-gray-300 !bg-[#151515] !border !border-gray-800 hover:!bg-[#222] !transition-all`
                        },
                        didOpen: () => {
                            const confirmBtn = Swal.getConfirmButton();
                            confirmBtn.style.background = themeColor;
                            confirmBtn.style.boxShadow = \`0 0 15px \${themeColor}66\`;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.removeAttribute('onsubmit'); // Remove to prevent infinite loop
                            form.submit();
                        }
                    });
                }
            });

            // Intercept inline onclick confirm()
            document.addEventListener('click', function(e) {
                let target = e.target.closest('[onclick]');
                if (target && target.tagName !== 'FORM') {
                    const onclickStr = target.getAttribute('onclick');
                    if (onclickStr && onclickStr.includes('if(confirm(')) {
                        e.preventDefault();
                        e.stopPropagation();
                        const msgMatch = onclickStr.match(/confirm\(['"](.*?)['"]\)/);
                        const msg = msgMatch ? msgMatch[1] : 'ยืนยันการทำรายการ?';
                        const isDelete = msg.includes('ลบ');
                        const themeColor = isDelete ? '#ff003c' : '#00d0ff';
                        const iconClass = isDelete ? 'ph-trash' : 'ph-question';
                        
                        Swal.fire({
                            html: `
                                <div class="flex justify-center mb-6 mt-4">
                                    <div class="w-24 h-24 rounded-full flex items-center justify-center" style="background: rgba(${isDelete ? '255,0,60' : '0,208,255'}, 0.1); box-shadow: 0 0 30px rgba(${isDelete ? '255,0,60' : '0,208,255'}, 0.5);">
                                        <i class="ph-bold ${iconClass} text-5xl" style="color: ${themeColor}"></i>
                                    </div>
                                </div>
                                <h2 class="text-3xl font-black text-white mb-6 tracking-wide">${isDelete ? 'ยืนยันการลบ?' : 'ยืนยันการทำรายการ?'}</h2>
                                <div class="bg-[#151515] border border-gray-800 rounded-2xl p-5 mb-8 text-center shadow-inner">
                                    <p class="text-gray-300 text-lg font-bold">${msg}</p>
                                </div>
                            `,
                            showCancelButton: true,
                            confirmButtonText: `<i class="ph-bold ph-check-circle text-xl mr-2"></i> ยืนยัน`,
                            cancelButtonText: `ยกเลิก`,
                            buttonsStyling: false,
                            showCloseButton: false,
                            customClass: {
                                popup: '!bg-[#0a0a0a] !border !border-gray-800 !rounded-[2rem] shadow-2xl !p-6',
                                htmlContainer: '!m-0',
                                actions: '!w-full !mt-0 !grid !grid-cols-2 !gap-4',
                                confirmButton: `!w-full !m-0 !py-4 !rounded-xl !text-lg !font-bold !text-white !transition-all !border-0`,
                                cancelButton: `!w-full !m-0 !py-4 !rounded-xl !text-lg !font-bold !text-gray-300 !bg-[#151515] !border !border-gray-800 hover:!bg-[#222] !transition-all`
                            },
                            didOpen: () => {
                                const confirmBtn = Swal.getConfirmButton();
                                confirmBtn.style.background = themeColor;
                                confirmBtn.style.boxShadow = \`0 0 15px \${themeColor}66\`;
                            }
                        }).then((result) => {
                        if (result.isConfirmed) {
                            // Execute the code inside the true block of if(confirm(...)) { ... }
                            // Extract the code inside the curly braces
                            const codeMatch = onclickStr.match(/if\s*\(confirm\([^)]+\)\)\s*\{([^}]+)\}/);
                            if (codeMatch && codeMatch[1]) {
                                // Execute it using Function constructor in global scope
                                new Function(codeMatch[1]).call(target);
                            }
                        }
                    });
                }
            }
        }, true); // Use capture phase to intercept before inline execution
    });
    </script>
</body>
</html>
