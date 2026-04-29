window.PoliriumPrint = {
    printUrl: function(url) {
        // Hiển thị loading indicator
        this.showLoading();

        // Fetch HTML từ server
        fetch(url)
            .then(response => response.text())
            .then(html => {
                // Tạo iframe ẩn
                const iframe = this.createHiddenIframe();

                // Xử lý khi iframe đã load xong
                iframe.onload = function() {
                    // Đợi một chút để HTML render xong
                    setTimeout(() => {
                        try {
                            // Tắt loading trước khi in (dialog in sẽ block UI)
                            PoliriumPrint.hideLoading();

                            // Gọi print trên iframe
                            iframe.contentWindow.print();

                            // Tự động xóa iframe sau 5 giây
                            setTimeout(() => {
                                if (iframe.parentNode) {
                                    document.body.removeChild(iframe);
                                }
                            }, 5000);
                        } catch (error) {
                            console.error('Print error:', error);
                            document.body.removeChild(iframe);
                            PoliriumPrint.hideLoading();
                            alert('Không thể in. Vui lòng thử lại.');
                        }
                    }, 500);
                };

                // Xử lý lỗi khi load iframe
                iframe.onerror = function() {
                    console.error('Failed to load print iframe');
                    document.body.removeChild(iframe);
                    PoliriumPrint.hideLoading();
                    alert('Không thể tải hóa đơn để in. Vui lòng thử lại.');
                };

                // Write HTML vào iframe
                iframe.srcdoc = html;
                document.body.appendChild(iframe);
            })
            .catch(error => {
                console.error('Fetch error:', error);
                this.hideLoading();
                alert('Không thể tải hóa đơn để in. Vui lòng thử lại.');
            });
    },

    createHiddenIframe: function() {
        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = 'none';
        return iframe;
    },

    showLoading: function() {
        // Tạo loading overlay nếu chưa có
        let loading = document.getElementById('polirium-print-loading');
        if (!loading) {
            loading = document.createElement('div');
            loading.id = 'polirium-print-loading';
            loading.innerHTML = '<div class="spinner-border spinner-border-lg text-primary" role="status"><span class="visually-hidden">Đang chuẩn bị in...</span></div>';
            loading.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.3);display:flex;align-items:center;justify-content:center;z-index:9999;';
            document.body.appendChild(loading);
        }
        loading.style.display = 'flex';
    },

    hideLoading: function() {
        const loading = document.getElementById('polirium-print-loading');
        if (loading) {
            loading.style.display = 'none';
        }
    }
};
