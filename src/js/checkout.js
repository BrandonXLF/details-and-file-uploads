jQuery($ => {
    const uploadInputs = [...document.getElementsByClassName('input-cffu-file-upload')];

    for (const uploadInput of uploadInputs) {
        const hiddenInput = document.createElement('input');
        hiddenInput.name = uploadInput.dataset.name;
        hiddenInput.type = 'hidden';

        uploadInput.dataset.processed = '';
        uploadInput.after(hiddenInput);

        uploadInput.addEventListener('change', () => {
            hiddenInput.value = uploadInput.files.length || '';
            uploadInput.dataset.processed = '';
        });
    }

    $('form.woocommerce-checkout').on('checkout_place_order', () => {
        let allProcessed = uploadInputs.every(uploadInput => uploadInput.dataset.processed);

        if (allProcessed)
            return true;

        (async () => {
            for (const uploadInput of uploadInputs) {
                const data = new FormData();
                
                data.append('action', 'cffu_file_upload');
                data.append('name', uploadInput.dataset.name);
                data.append('nonce', cffu_checkout_params.file_upload_nonce);

                for (const file of uploadInput.files) {
                    data.append('files[]', file);
                }

                try {
                    await $.post({
                        url: cffu_checkout_params.file_upload_endpoint,
                        data,
                        contentType: false,
                        processData: false
                    }).promise();
                } catch (e) {
                    $('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();

                    const errorNotice = $('<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">')
                        .append(
                            $('<div class="woocommerce-error">').append(e.responseText)
                        )
                        .prependTo($('form.checkout'));

                    $.scroll_to_notices(errorNotice);
                        
                    $(document.body).trigger('checkout_error', [e.responseText]);

                    uploadInput.dataset.processed = '';
                    throw new Error('Error uploading a file.');
                }

                uploadInput.dataset.processed = 'true';
            }

            $('form.woocommerce-checkout').submit();
        })();

        return false;
    });
});