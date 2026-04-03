// Banner Media Integration Script
// Handles the "Select from Library" modal for banner pages

// Make setup function global so it can be called from anywhere
window.setupBannerMediaButton = function (mediaBtnId, targetContainerId) {
    const btn = document.getElementById(mediaBtnId);
    if (btn) {
        // Use a flag to prevent double-binding if called multiple times
        if (btn.dataset.mediaBound) return;
        btn.dataset.mediaBound = "true";

        btn.addEventListener('click', function () {
            const modal = document.createElement('div');
            modal.id = 'mediaLibraryModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
            modal.innerHTML = `
      <div class="bg-white rounded-lg shadow-xl w-11/12 h-5/6 max-w-6xl overflow-hidden relative" style="position:fixed; top:5%; left:5%; width:90%; height:90%; background:white; z-index:10000; box-shadow:0 0 20px rgba(0,0,0,0.5);">
        <button onclick="closeMediaModal()" class="absolute top-4 right-4 z-50 bg-white rounded-full w-10 h-10 flex items-center justify-center shadow-lg hover:bg-gray-100 text-gray-600 hover:text-gray-800" style="position:absolute; top:10px; right:10px; z-index:10001; font-size: 24px; border:none; cursor:pointer;">
          &times;
        </button>
        <iframe src="/admin/media.php?select=1" style="width:100%; height:100%; border:0;"></iframe>
      </div>
    `;
            document.body.appendChild(modal);

            // Set current target
            window.currentMediaTarget = {
                type: 'banner_gallery',
                id: targetContainerId
            };
        });
    }
};

document.addEventListener('DOMContentLoaded', function () {
    // Global modal close function
    window.closeMediaModal = function () {
        const modal = document.getElementById('mediaLibraryModal');
        if (modal) modal.remove();
    };

    // Remove helper for the preview items
    window.removeMediaItem = function (btn) {
        btn.closest('.media-item-wrapper').remove();
    };

    // Global image insertion function called by media.php iframe
    window.insertImagesToEditor = function (imagePaths) {
        console.log('insertImagesToEditor called with:', imagePaths);

        if (!imagePaths || imagePaths.length === 0) {
            alert('No images selected');
            return;
        }

        const target = window.currentMediaTarget || {};

        if (target.type === 'banner_gallery') {
            const container = document.getElementById(target.id);
            if (!container) {
                console.error('Target container not found:', target.id);
                return;
            }

            const inputName = 'banners_from_media[]';

            imagePaths.forEach(imageUrl => {
                const wrapper = document.createElement('div');
                wrapper.className = 'media-item-wrapper relative group border rounded overflow-hidden';
                wrapper.style.width = '120px';
                wrapper.style.height = '120px';
                wrapper.style.display = 'inline-block';
                wrapper.style.marginRight = '10px';
                wrapper.style.marginBottom = '10px';
                wrapper.style.position = 'relative';

                wrapper.innerHTML = `
                    <img src="${imageUrl}" style="width:100%; height:100%; object-fit:cover;">
                    <input type="hidden" name="${inputName}" value="${imageUrl}">
                    <button type="button" onclick="removeMediaItem(this)" style="position:absolute; top:0; right:0; background:red; color:white; border:none; cursor:pointer; padding:0 5px;">
                        &times;
                    </button>
                `;
                container.appendChild(wrapper);
            });

            closeMediaModal();
            return;
        }
    };

    // Initialize buttons
    setupBannerMediaButton('addBannerMediaBtn', 'banner_media_container');
    setupBannerMediaButton('addSidebarMediaBtn', 'sidebar_media_container');
    setupBannerMediaButton('addCenterMediaBtn', 'center_media_container');
    setupBannerMediaButton('addOfferMediaBtn', 'offer_media_container');

    // New: Before Blogs
    setupBannerMediaButton('addBlogsMediaBtn', 'blogs_media_container');

});
