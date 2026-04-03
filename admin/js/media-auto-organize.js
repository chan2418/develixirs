/**
 * Media Library Auto-Folder Helper
 * Auto-creates folders based on context when uploading from admin pages
 */

/**
 * Get or create folder for current context
 * @param {string} folderPath - Path like "Products/Product Name" or "Homepage/Banners"
 * @returns {Promise<string>} - Folder ID
 */
async function getOrCreateMediaFolder(folderPath) {
    try {
        const response = await fetch('/admin/handlers/media_get_or_create_folder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `folder_path=${encodeURIComponent(folderPath)}`
        });

        const data = await response.json();

        if (data.success) {
            return data.folder_id;
        } else {
            console.error('Failed to create folder:', data.error);
            return null;
        }
    } catch (error) {
        console.error('Error creating folder:', error);
        return null;
    }
}

/**
 * Auto-organize product images
 * @param {string} productName - Product name
 * @param {FileList} files - Files to upload
 * @returns {Promise<Array>} - Uploaded media IDs
 */
async function uploadProductImages(productName, files) {
    // Create folder: Products/{ProductName}
    const folderId = await getOrCreateMediaFolder(`Products/${productName}`);

    if (!folderId) {
        alert('Failed to create product folder');
        return [];
    }

    // Upload files to folder
    const formData = new FormData();
    Array.from(files).forEach(file => formData.append('files[]', file));
    formData.append('folder_id', folderId);

    const response = await fetch('/admin/handlers/media_upload.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();
    return data.success ? data.uploaded : [];
}

/**
 * Auto-organize banner images
 * @param {FileList} files - Files to upload
 * @param {string} section - Section name (e.g., "Hero", "Promotional")
 * @returns {Promise<Array>} - Uploaded media IDs
 */
async function uploadBannerImages(files, section = 'Banners') {
    // Create folder: Homepage/{Section}
    const folderId = await getOrCreateMediaFolder(`Homepage/${section}`);

    if (!folderId) {
        alert('Failed to create banner folder');
        return [];
    }

    const formData = new FormData();
    Array.from(files).forEach(file => formData.append('files[]', file));
    formData.append('folder_id', folderId);

    const response = await fetch('/admin/handlers/media_upload.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();
    return data.success ? data.uploaded : [];
}

/**
 * Auto-organize category images
 * @param {string} categoryName - Category name
 * @param {FileList} files - Files to upload
 * @returns {Promise<Array>} - Uploaded media IDs
 */
async function uploadCategoryImages(categoryName, files) {
    // Create folder: Categories/{CategoryName}
    const folderId = await getOrCreateMediaFolder(`Categories/${categoryName}`);

    if (!folderId) {
        alert('Failed to create category folder');
        return [];
    }

    const formData = new FormData();
    Array.from(files).forEach(file => formData.append('files[]', file));
    formData.append('folder_id', folderId);

    const response = await fetch('/admin/handlers/media_upload.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();
    return data.success ? data.uploaded : [];
}

/**
 * Auto-organize blog images
 * @param {string} blogTitle - Blog post title
 * @param {FileList} files - Files to upload
 * @returns {Promise<Array>} - Uploaded media IDs
 */
async function uploadBlogImages(blogTitle, files) {
    // Create folder: Blog/{BlogTitle}
    const folderId = await getOrCreateMediaFolder(`Blog/${blogTitle}`);

    if (!folderId) {
        alert('Failed to create blog folder');
        return [];
    }

    const formData = new FormData();
    Array.from(files).forEach(file => formData.append('files[]', file));
    formData.append('folder_id', folderId);

    const response = await fetch('/admin/handlers/media_upload.php', {
        method: 'POST',
        body: formData
    });

    const data = await response.json();
    return data.success ? data.uploaded : [];
}
