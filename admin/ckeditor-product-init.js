// CKEditor Product Integration Script
// To be included in add_product.php and edit_product.php

document.addEventListener('DOMContentLoaded', function () {
    const ckEditorFontCssUrl = 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Cormorant+Garamond:wght@300;400;500;600;700&family=Lato:wght@300;400;700;900&family=Open+Sans:wght@300;400;600;700;800&family=Source+Sans+3:wght@300;400;500;600;700&family=Libre+Baskerville:wght@400;700&family=EB+Garamond:wght@400;500;600;700&family=Montserrat:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Cormorant:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700;800&family=Raleway:wght@300;400;500;600;700;800&display=swap';
    const ckEditorExtraFontNames =
        'Cormorant Garamond/Cormorant Garamond, serif;' +
        'Cormorant/Cormorant, serif;' +
        'EB Garamond/EB Garamond, serif;' +
        'Libre Baskerville/Libre Baskerville, serif;' +
        'Source Sans/Source Sans 3, Source Sans Pro, sans-serif';

    // CKEditor configuration with 55+ fonts
    const ckEditorConfig = {
        height: 300,
        removePlugins: 'easyimage,cloudservices',
        extraPlugins: 'uploadimage',
        allowedContent: true, // Allow all HTML tags/attributes
        extraAllowedContent: 'video[*]{*};source[*]{*};',
        filebrowserUploadUrl: '/admin/upload_blog_image.php',
        uploadUrl: '/admin/upload_blog_image.php',
        font_names: 'Arial/Arial, Helvetica, sans-serif;' +
            'Arial Black/Arial Black, Gadget, sans-serif;' +
            'Baskerville/Baskerville, Times New Roman, serif;' +
            'Book Antiqua/Book Antiqua, Palatino, serif;' +
            'Brush Script MT/Brush Script MT, cursive;' +
            'Calibri/Calibri, Candara, Segoe, sans-serif;' +
            'Cambria/Cambria, Georgia, serif;' +
            'Candara/Candara, Calibri, Segoe, sans-serif;' +
            'Century Gothic/Century Gothic, CenturyGothic, sans-serif;' +
            'Comic Sans MS/Comic Sans MS, cursive;' +
            'Consolas/Consolas, monaco, monospace;' +
            'Copperplate/Copperplate, Copperplate Gothic Light, fantasy;' +
            'Courier/Courier, monospace;' +
            'Courier New/Courier New, Courier, monospace;' +
            'Didot/Didot, Didot LT STD, Hoefler Text, serif;' +
            'Franklin Gothic Medium/Franklin Gothic Medium, sans-serif;' +
            'Futura/Futura, Trebuchet MS, sans-serif;' +
            'Garamond/Garamond, Baskerville, serif;' +
            'Geneva/Geneva, Tahoma, sans-serif;' +
            'Georgia/Georgia, Times, serif;' +
            'Gill Sans/Gill Sans, Gill Sans MT, Calibri, sans-serif;' +
            'Goudy Old Style/Goudy Old Style, Garamond, serif;' +
            'Helvetica/Helvetica, Arial, sans-serif;' +
            'Helvetica Neue/Helvetica Neue, Helvetica, Arial, sans-serif;' +
            'Hoefler Text/Hoefler Text, Baskerville Old Face, serif;' +
            'Impact/Impact, Charcoal, sans-serif;' +
            'Inter/Inter, sans-serif;' +
            'Lato/Lato, sans-serif;' +
            'Lucida Bright/Lucida Bright, Georgia, serif;' +
            'Lucida Console/Lucida Console, Monaco, monospace;' +
            'Lucida Grande/Lucida Grande, Lucida Sans Unicode, sans-serif;' +
            'Lucida Sans/Lucida Sans, Lucida Sans Unicode, sans-serif;' +
            'Merriweather/Merriweather, serif;' +
            'Monaco/Monaco, Consolas, monospace;' +
            'Montserrat/Montserrat, sans-serif;' +
            'MS Serif/MS Serif, New York, serif;' +
            'Nunito/Nunito, sans-serif;' +
            'Open Sans/Open Sans, sans-serif;' +
            'Optima/Optima, Segoe, sans-serif;' +
            'Oswald/Oswald, sans-serif;' +
            'Palatino/Palatino, Palatino Linotype, serif;' +
            'Perpetua/Perpetua, Baskerville, serif;' +
            'Playfair Display/Playfair Display, serif;' +
            'Poppins/Poppins, sans-serif;' +
            'PT Sans/PT Sans, sans-serif;' +
            'Quicksand/Quicksand, sans-serif;' +
            'Raleway/Raleway, sans-serif;' +
            'Roboto/Roboto, sans-serif;' +
            'Rockwell/Rockwell, Courier Bold, serif;' +
            'Segoe UI/Segoe UI, Frutiger, sans-serif;' +
            'Source Sans Pro/Source Sans Pro, sans-serif;' +
            'Tahoma/Tahoma, Geneva, sans-serif;' +
            'Times/Times, Times New Roman, serif;' +
            'Times New Roman/Times New Roman, Times, serif;' +
            'Trebuchet MS/Trebuchet MS, Helvetica, sans-serif;' +
            'Ubuntu/Ubuntu, sans-serif;' +
            'Verdana/Verdana, Geneva, sans-serif;' +
            ckEditorExtraFontNames
    };

    // Initialize all product editors
    const editors = [
        { id: 'editor-short-desc', textarea: 'hidden-short-desc', height: 150 },
        { id: 'editor-description', textarea: 'hidden-description', height: 300 },
        { id: 'editor-ingredients', textarea: 'hidden-ingredients', height: 250 },
        { id: 'editor-how-to-use', textarea: 'hidden-how-to-use', height: 250 }
    ];

    editors.forEach(function (editorInfo) {
        const config = Object.assign({}, ckEditorConfig, { height: editorInfo.height });

        config.on = {
            change: function () {
                document.getElementById(editorInfo.textarea).value = this.getData();
            },
            instanceReady: function () {
                this.document.appendStyleSheet(ckEditorFontCssUrl);
                const existingContent = document.getElementById(editorInfo.textarea).value;
                if (existingContent) {
                    this.setData(existingContent);
                }
                const notificationArea = document.querySelector('.cke_notifications_area');
                if (notificationArea) notificationArea.style.display = 'none';
            }
        };

        CKEDITOR.replace(editorInfo.id, config);
    });

    // Suppress console warnings
    const originalWarn = console.warn;
    console.warn = function (msg) {
        if (typeof msg === 'string' && (msg.includes('CKEditor') || msg.includes('ckeditor'))) {
            return;
        }
        originalWarn.apply(console, arguments);
    };

    // Toggle editor functionality
    function setupToggle(toggleBtnId, editorWrapperId) {
        const btn = document.getElementById(toggleBtnId);
        const wrapper = document.getElementById(editorWrapperId);

        if (btn && wrapper) {
            btn.addEventListener('click', function () {
                if (wrapper.style.display === 'none') {
                    wrapper.style.display = 'block';
                    btn.textContent = 'Hide Editor';
                } else {
                    wrapper.style.display = 'none';
                    btn.textContent = 'Show Editor';
                }
            });
        }
    }

    // Setup media modal
    function setupMediaButton(mediaBtnId, targetType, targetContainerId) {
        const btn = document.getElementById(mediaBtnId);

        if (btn) {
            btn.addEventListener('click', function () {
                const modal = document.createElement('div');
                modal.id = 'mediaLibraryModal';
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
                modal.innerHTML = `
          <div class="bg-white rounded-lg shadow-xl w-11/12 h-5/6 max-w-6xl overflow-hidden relative">
            <button onclick="closeMediaModal()" class="absolute top-4 right-4 z-50 bg-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg hover:bg-gray-100 text-gray-600 hover:text-gray-800" style="font-size: 24px;">
              &times;
            </button>
            <iframe src="/admin/media.php?select=1" class="w-full h-full border-0"></iframe>
          </div>
        `;
                document.body.appendChild(modal);

                // Set current target
                window.currentMediaTarget = {
                    type: targetType,
                    id: targetContainerId
                };
            });
        }
    }

    // Setup all toggles and media buttons
    setupToggle('toggleEditorShortDesc', 'editorWrapperShortDesc');
    setupToggle('toggleEditorDescription', 'editorWrapperDescription');
    setupToggle('toggleEditorIngredients', 'editorWrapperIngredients');
    setupToggle('toggleEditorHowToUse', 'editorWrapperHowToUse');

    setupMediaButton('addMediaShortDesc', 'editor', 'editor-short-desc');
    setupMediaButton('addMediaDescription', 'editor', 'editor-description');
    setupMediaButton('addMediaIngredients', 'editor', 'editor-ingredients');
    setupMediaButton('addMediaHowToUse', 'editor', 'editor-how-to-use');

    // Setup Product Image Media Buttons (New)
    setupMediaButton('addLegacyMediaBtn', 'legacy', 'legacy_media_container');
    setupMediaButton('addGalleryMediaBtn', 'gallery', 'gallery_media_container');

    // Global modal close function
    window.closeMediaModal = function () {
        const modal = document.getElementById('mediaLibraryModal');
        if (modal) modal.remove();
    };

    // Remove helper
    window.removeMediaItem = function (btn) {
        btn.closest('.media-item-wrapper').remove();
    };

    // Global image insertion function
    window.insertImagesToEditor = function (imagePaths) {
        console.log('insertImagesToEditor called with:', imagePaths);

        if (!imagePaths || imagePaths.length === 0) {
            alert('No images selected');
            return;
        }

        const target = window.currentMediaTarget || {};

        // CASE 1: CKEditor Insertion
        if (target.type === 'editor') {
            const editorId = target.id;
            let editor = CKEDITOR.instances[editorId];

            // Fallback if specific editor not found
            if (!editor) {
                console.warn(`Editor instance '${editorId}' not found. Searching for available instances...`);
                // Note: For products we have multiple editors, so blindly picking the first one might be wrong.
                // However, usually only one modal is open for one specific button.
                // Better to just warn for now, or maybe check if there's only one active?
                // For product page, we have short-desc, description, etc.
                // If we can't find the specific ID, we likely can't safely fallback to "any" without risking putting text in the wrong box.
                // But we can try to re-query by ID just in case timing issue.
            }

            if (!editor) {
                console.error('No active editor found for ID:', editorId);
                alert("Editor instance not found. Please refresh the page.");
                return;
            }

            try {
                editor.focus();

                // Delay insert to allow focus to settle
                setTimeout(() => {
                    const imgHtml = imagePaths.map(url => {
                        const ext = url.split('.').pop().toLowerCase();
                        const isVideo = ['mp4', 'webm', 'ogg', 'mov'].includes(ext);

                        if (isVideo) {
                            return `
                              <div style="display: inline-block; width: 80%; max-width: 100%; resize: both; overflow: hidden; border: 1px dashed #ccc; vertical-align: top; margin: 10px 0;">
                                <video src="${url}" controls style="width: 100%; height: auto; display: block;"></video>
                              </div>
                              <p><br/></p>
                            `;
                        } else {
                            return `<img src="${url}" alt="Image" style="max-width: 100%; height: auto; margin: 10px 0;" /><p><br/></p>`;
                        }
                    }).join('');

                    try {
                        if (editor.mode === 'wysiwyg') {
                            // Fix: Ensure selection exists before inserting (avoids getParents null error)
                            var selection = editor.getSelection();
                            if (!selection || !selection.getStartElement()) {
                                var range = editor.createRange();
                                range.moveToPosition(editor.editable(), CKEDITOR.POSITION_BEFORE_END);
                                editor.getSelection().selectRanges([range]);
                            }

                            editor.insertHtml(imgHtml);
                            editor.fire('change');
                            console.log('Inserted images into', editorId);
                        } else {
                            alert("Please switch editor to Visual mode to insert images.");
                        }
                    } catch (err) {
                        console.error('Insertion error:', err);
                        alert('Failed to insert image: ' + err.message);
                    }

                    setTimeout(closeMediaModal, 100);
                }, 50);

            } catch (error) {
                console.error('Error preparing editor:', error);
                closeMediaModal();
            }
            return;
        }

        // CASE 2: Legacy Images or Gallery Media
        if (target.type === 'legacy' || target.type === 'gallery') {
            const container = document.getElementById(target.id);
            if (!container) {
                console.error('Target container not found:', target.id);
                return;
            }

            // Determine input name based on type
            // legacy -> images_from_media[]
            // gallery -> product_media_from_media[]
            const inputName = target.type === 'legacy' ? 'images_from_media[]' : 'product_media_from_media[]';

            imagePaths.forEach(imageUrl => {
                const wrapper = document.createElement('div');
                wrapper.className = 'media-item-wrapper relative group w-24 h-24 border rounded overflow-hidden';

                // For gallery, we might want to distinguish video types in future, but media library returns paths. 
                // Assuming images for now as media library returns mixed but usually images.

                wrapper.innerHTML = `
                    <img src="${imageUrl}" class="w-full h-full object-cover">
                    <input type="hidden" name="${inputName}" value="${imageUrl}">
                    <button type="button" onclick="removeMediaItem(this)" class="absolute top-0 right-0 bg-red-500 text-white p-1 rounded-bl opacity-0 group-hover:opacity-100 transition text-xs">
                        &times;
                    </button>
                `;
                container.appendChild(wrapper);
            });

            closeMediaModal();
            return;
        }
    };
});
