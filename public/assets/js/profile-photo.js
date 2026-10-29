(() => {
    const form = document.getElementById('photo-upload-form');

    if (!form) return;

    const source = document.getElementById('photo-source');
    const output = document.getElementById('photo-output');
    const message = document.getElementById('photo-message');

    const dialog = document.getElementById('photo-crop-dialog');
    const image = document.getElementById('crop-image');
    const cropMessage = document.getElementById('crop-message');

    const saveButton = document.getElementById('crop-save');
    const cancelButton = document.getElementById('crop-cancel');
    const zoomIn = document.getElementById('crop-zoom-in');
    const zoomOut = document.getElementById('crop-zoom-out');
    const resetButton = document.getElementById('crop-reset');

    let cropper = null;
    let objectUrl = null;
    let busy = false;

    // Pengiriman hanya dilakukan setelah hasil crop siap.
    form.addEventListener('submit', (event) => {
        event.preventDefault();
    });

    function cleanup() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }

        image.onload = null;
        image.onerror = null;
        image.removeAttribute('src');

        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }

        source.value = '';
        source.disabled = false;
        output.value = '';
        saveButton.disabled = true;
    }

    function setBusy(value) {
        busy = value;

        saveButton.disabled = value;
        cancelButton.disabled = value;
        zoomIn.disabled = value;
        zoomOut.disabled = value;
        resetButton.disabled = value;

        saveButton.textContent = value
            ? 'Menyimpan...'
            : 'Simpan foto';

        if (cropper) {
            value ? cropper.disable() : cropper.enable();
        }
    }

    source.addEventListener('change', () => {
        const file = source.files[0];

        message.textContent = '';

        if (!file) return;

        if (typeof window.Cropper !== 'function') {
            message.textContent = 'Library crop belum termuat. Periksa file cropper.min.js.';
            source.value = '';
            return;
        }

        if (!['image/jpeg', 'image/png'].includes(file.type)) {
            message.textContent = 'Pilih foto JPG atau PNG.';
            source.value = '';
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            message.textContent = 'Ukuran foto maksimal 2 MB.';
            source.value = '';
            return;
        }

        cleanup();
        source.disabled = true;
        cropMessage.textContent = '';
        objectUrl = URL.createObjectURL(file);

        image.onload = () => {
            // Lepaskan handler sebelum Cropper memproses gambar.
            image.onload = null;

            if (
                image.naturalWidth > 2048
                || image.naturalHeight > 2048
            ) {
                message.textContent = 'Dimensi foto maksimal 2048 × 2048 piksel.';
                cleanup();
                return;
            }

            dialog.showModal();

            try {
                cropper = new Cropper(image, {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    autoCropArea: 0.85,
                    background: false,
                    responsive: true,
                    preview: '.crop-preview',
                    toggleDragModeOnDblclick: false,

                    ready() {
                        saveButton.disabled = false;
                    },
                });
            } catch (error) {
                message.textContent = 'Area crop gagal dibuka. Coba foto lain.';
                dialog.close();
            }
        };

        image.onerror = () => {
            message.textContent = 'Foto tidak dapat dibaca. Coba file lain.';
            cleanup();
        };

        image.src = objectUrl;
    });

    zoomIn.addEventListener('click', () => {
        if (cropper && !busy) cropper.zoom(0.1);
    });

    zoomOut.addEventListener('click', () => {
        if (cropper && !busy) cropper.zoom(-0.1);
    });

    resetButton.addEventListener('click', () => {
        if (cropper && !busy) cropper.reset();
    });

    cancelButton.addEventListener('click', () => {
        if (!busy) dialog.close();
    });

    // Tombol Escape boleh menutup dialog selama tidak sedang menyimpan.
    dialog.addEventListener('cancel', (event) => {
        if (busy) event.preventDefault();
    });

    dialog.addEventListener('close', cleanup);

    saveButton.addEventListener('click', async () => {
        if (!cropper || busy) return;

        cropMessage.textContent = '';

        try {
            const canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                fillColor: '#ffffff',
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            if (!canvas) {
                throw new Error('Hasil crop kosong.');
            }

            setBusy(true);

            const blob = await new Promise((resolve, reject) => {
                canvas.toBlob((result) => {
                    if (result) {
                        resolve(result);
                    } else {
                        reject(new Error('Gagal membuat file foto.'));
                    }
                }, 'image/jpeg', 0.9);
            });

            const croppedFile = new File(
                [blob],
                'profile.jpg',
                { type: 'image/jpeg' }
            );

            const transfer = new DataTransfer();
            transfer.items.add(croppedFile);
            output.files = transfer.files;

            // Kirim form biasa, termasuk token CSRF.
            HTMLFormElement.prototype.submit.call(form);
        } catch (error) {
            setBusy(false);
            cropMessage.textContent = 'Foto gagal diproses. Coba atur ulang atau pilih foto lain.';
        }
    });
})();