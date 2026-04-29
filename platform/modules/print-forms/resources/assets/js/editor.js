function printPreview() {
    const iframe = document.getElementById('preview-frame');
    if (iframe && iframe.contentWindow) {
        iframe.contentWindow.print();
    }
}
