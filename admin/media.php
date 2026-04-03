<?php
// admin/media.php
require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Check if in selection mode (opened from blog editor)
$selectionMode = isset($_GET['select']) && $_GET['select'] == '1';

$page_title = 'Media';
if (!$selectionMode) {
    include __DIR__ . '/layout/header.php';
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

<style>
/* Main Container */
.media-container {
    background: #f8fafc;
    min-height: 100vh;
    padding: 1.5rem;
}

/* Breadcrumb */
.breadcrumb {
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 1.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.breadcrumb a {
    color: #3b82f6;
    text-decoration: none;
}

/* Toolbar */
.media-toolbar {
    background: white;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 0.75rem;
    align-items: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.toolbar-left {
    display: flex;
    gap: 0.5rem;
    flex: 1;
}

.toolbar-right {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

/* Buttons */
.btn-primary {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 0.625rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: background 0.2s;
    position: relative;
}
.btn-primary:hover {
    background: #2563eb;
}

.btn-icon {
    background: #3b82f6;
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-icon:hover {
    background: #2563eb;
}

.btn-dropdown {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 0.625rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    position: relative;
}

.btn-secondary {
    background: white;
    color: #475569;
    border: 1px solid #e2e8f0;
    padding: 0.625rem 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* Dropdown Menus */
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-menu {
    position: absolute;
    top: calc(100% + 0.5rem);
    left: 0;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    min-width: 200px;
    z-index: 100;
    display: none;
    padding: 0.5rem 0;
}

.dropdown-menu.show {
    display: block;
}

.dropdown-item {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    color: #475569;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: background 0.15s;
}

.dropdown-item:hover {
    background: #f8fafc;
}

.dropdown-item i {
    width: 16px;
    color: #94a3b8;
}

/* Search */
.search-box {
    position: relative;
    min-width: 300px;
}
.search-box input {
    width: 100%;
    padding: 0.625rem 1rem 0.625rem 2.5rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}
.search-box i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
}

/* View Controls */
.view-controls {
    display: flex;
    gap: 0.25rem;
    background: #f1f5f9;
    padding: 0.25rem;
    border-radius: 0.375rem;
}
.view-btn {
    background: transparent;
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 0.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #64748b;
}
.view-btn.active {
    background: white;
    color: #3b82f6;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

/* Content Area */
.media-content {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.media-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.media-title {
    font-size: 0.875rem;
    color: #3b82f6;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* Grid */
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 1.5rem;
}

.media-card {
    position: relative;
    cursor: pointer;
    transition: transform 0.2s;
}
.media-card:hover {
    transform: translateY(-2px);
}
.media-card.selected .media-thumbnail {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}

.media-checkbox {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    width: 20px;
    height: 20px;
    cursor: pointer;
    z-index: 10;
    accent-color: #3b82f6;
}

.media-thumbnail {
    width: 100%;
    aspect-ratio: 1;
    border: 2px solid #e2e8f0;
    border-radius: 0.5rem;
    overflow: hidden;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
}
.media-card:hover .media-thumbnail {
    border-color: #3b82f6;
}

.media-thumbnail img,
.media-thumbnail video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.media-card-name {
    text-align: center;
    font-size: 0.75rem;
    color: #475569;
    margin-top: 0.5rem;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding: 0 0.25rem;
}

/* Upload Zone */
.upload-zone {
    border: 2px dashed #cbd5e1;
    background: #f8fafc;
    border-radius: 0.5rem;
    padding: 3rem;
    text-align: center;
    margin-bottom: 1.5rem;
    cursor: pointer;
    transition: all 0.2s;
}
.upload-zone:hover {
    border-color: #3b82f6;
    background: #eff6ff;
}

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 998;
    display: none;
    align-items: center;
    justify-content: center;
}
.modal-overlay.show {
    display: flex;
}

.modal {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    min-width: 400px;
    max-width: 90%;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.modal-header h3 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1e293b;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #94a3b8;
    cursor: pointer;
}

.form-group {
    margin-bottom: 1rem;
}
.form-group label {
    display: block;
    font-size: 0.875rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.5rem;
}
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.625rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.modal-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

/* Inspector Panel */
.inspector-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
    display: none;
}
.inspector-overlay.visible {
    display: block;
}

.inspector-panel {
    position: fixed;
    top: 0;
    right: -450px;
    width: 450px;
    height: 100vh;
    background: white;
    box-shadow: -4px 0 12px rgba(0,0,0,0.15);
    transition: right 0.3s;
    overflow-y: auto;
    z-index: 1000;
}
.inspector-panel.open {
    right: 0;
}

.inspector-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.inspector-body {
    padding: 1.5rem;
}

.inspector-preview {
    margin-bottom: 1.5rem;
    border-radius: 0.5rem;
    overflow: hidden;
    background: #f8fafc;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
}
.empty-state i {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

/* Bulk Actions Bar */
.bulk-actions-bar {
    position: fixed;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    background: white;
    border-radius: 0.5rem;
    padding: 1rem 1.5rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    display: none;
    align-items: center;
    gap: 1rem;
    z-index: 100;
}
.bulk-actions-bar.show {
    display: flex;
}
.bulk-count {
    font-size: 0.875rem;
    color: #475569;
    font-weight: 600;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Context Menu */
.context-menu {
    position: fixed;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    min-width: 200px;
    z-index: 2000;
    display: none;
    padding: 0.5rem 0;
    max-height: 300px;
    overflow-y: auto;
}
.context-menu.show {
    display: block;
}
.context-menu-item {
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    color: #475569;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: background 0.15s;
}
.context-menu-item:hover {
    background: #f8fafc;
}
.context-menu-item i {
    width: 16px;
    color: #94a3b8;
}
.context-menu-separator {
    border-top: 1px solid #e2e8f0;
    margin: 0.5rem 0;
}
</style>

<div class="media-container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="/admin/dashboard.php">DASHBOARD</a> / <span>MEDIA</span>
    </div>

    <!-- Toolbar -->
    <div class="media-toolbar">
        <div class="toolbar-left">
            <?php if ($selectionMode): ?>
            <!-- Import Button (shown in selection mode) -->
            <button class="btn-primary" id="btnImportSelected" onclick="importSelectedImages()" style="background: #10b981;">
                <i class="fas fa-check"></i>
                Import Selected
            </button>
            <span id="selectedCountDisplay" style="font-size: 0.875rem; color: #64748b; padding: 0 0.5rem;">0 selected</span>
            <?php endif; ?>
            
            <!-- Back Button (shown when inside folder) -->
            <button class="btn-icon" id="btnBack" onclick="goToFolder(-1)" title="Back to Parent" style="display: none;">
                <i class="fas fa-arrow-left"></i>
            </button>

            <!-- Upload Dropdown -->
            <div class="dropdown">
                <button class="btn-primary" id="btnUpload">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Upload
                    <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                </button>
                <div class="dropdown-menu" id="uploadMenu">
                    <div class="dropdown-item" onclick="openFileUpload()">
                        <i class="fas fa-desktop"></i>
                        Upload from local
                    </div>
                    <div class="dropdown-item" onclick="openUrlUpload()">
                        <i class="fas fa-link"></i>
                        Upload from URL
                    </div>
                </div>
            </div>

            <!-- Create Folder -->
            <button class="btn-icon" onclick="openCreateFolder()" title="Create Folder">
                <i class="fas fa-folder-plus"></i>
            </button>
            
            <!-- Sync All Images -->
            <button class="btn-icon" onclick="syncAllMedia()" title="Auto-Organize All Images" style="background: #10b981; color: white;">
                <i class="fas fa-sync"></i>
            </button>

            <!-- Refresh -->
            <button class="btn-icon" id="btnRefresh" title="Refresh">
                <i class="fas fa-sync-alt"></i>
            </button>

            <!-- Filter Dropdown -->
            <div class="dropdown">
                <button class="btn-dropdown" id="btnFilter">
                    <i class="fas fa-filter"></i>
                    <span id="filterLabel">Everything</span>
                    <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                </button>
                <div class="dropdown-menu" id="filterMenu">
                    <div class="dropdown-item" onclick="setFilter('all', 'Everything')">
                        <i class="fas fa-globe"></i>
                        Everything
                    </div>
                    <div class="dropdown-item" onclick="setFilter('image', 'Image')">
                        <i class="fas fa-image"></i>
                        Image
                    </div>
                    <div class="dropdown-item" onclick="setFilter('video', 'Video')">
                        <i class="fas fa-video"></i>
                        Video
                    </div>
                    <div class="dropdown-item" onclick="setFilter('document', 'Document')">
                        <i class="fas fa-file-alt"></i>
                        Document
                    </div>
                </div>
            </div>

            <!-- View Dropdown -->
            <div class="dropdown">
                <button class="btn-dropdown" id="btnView">
                    <i class="fas fa-eye"></i>
                    <i class="fas fa-globe" style="font-size: 0.75rem;"></i>
                    <span id="viewLabel">All media</span>
                    <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                </button>
                <div class="dropdown-menu" id="viewMenu">
                    <div class="dropdown-item" onclick="setView('all', 'All media')">
                        <i class="fas fa-images"></i>
                        All media
                    </div>
                    <div class="dropdown-item" onclick="setView('trash', 'Trash')">
                        <i class="fas fa-trash"></i>
                        Trash
                    </div>
                    <div class="dropdown-item" onclick="setView('recent', 'Recent')">
                        <i class="fas fa-clock"></i>
                        Recent
                    </div>
                    <div class="dropdown-item" onclick="setView('favorites', 'Favorites')">
                        <i class="fas fa-star"></i>
                        Favorites
                    </div>
                </div>
            </div>
        </div>

        <div class="toolbar-right">
            <!-- Search -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="mediaSearch" placeholder="Search in current folder">
            </div>

            <!-- Sort Dropdown -->
            <div class="dropdown">
                <button class="btn-secondary" id="btnSort">
                    <i class="fas fa-sort"></i>
                    Sort
                    <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                </button>
                <div class="dropdown-menu" id="sortMenu">
                    <div class="dropdown-item" onclick="setSort('name_asc')">
                        <i class="fas fa-sort-alpha-down"></i>
                        File name - ASC
                    </div>
                    <div class="dropdown-item" onclick="setSort('name_desc')">
                        <i class="fas fa-sort-alpha-up"></i>
                        File name - DESC
                    </div>
                    <div class="dropdown-item" onclick="setSort('date_asc')">
                        <i class="fas fa-sort-amount-up"></i>
                        Uploaded date - ASC
                    </div>
                    <div class="dropdown-item" onclick="setSort('date_desc')">
                        <i class="fas fa-sort-amount-down"></i>
                        Uploaded date - DESC
                    </div>
                    <div class="dropdown-item" onclick="setSort('size_asc')">
                        <i class="fas fa-sort-numeric-up"></i>
                        Size - ASC
                    </div>
                    <div class="dropdown-item" onclick="setSort('size_desc')">
                        <i class="fas fa-sort-numeric-down"></i>
                        Size - DESC
                    </div>
                </div>
            </div>


            <!-- Actions Dropdown -->
            <div class="dropdown">
                <button class="btn-secondary" id="btnActions">
                    <i class="fas fa-hand-pointer"></i>
                    Actions
                    <i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>
                </button>
                <div class="dropdown-menu" id="actionsMenu" style="min-width: 220px;">
                    <div class="dropdown-item" onclick="previewMedia()">
                        <i class="fas fa-eye"></i>
                        Preview
                    </div>
                    <div class="dropdown-item" onclick="openCropModal()">
                        <i class="fas fa-crop"></i>
                        Crop
                    </div>
                    <div class="dropdown-item" onclick="renameMedia()">
                        <i class="fas fa-edit"></i>
                        Rename
                    </div>
                    <div class="dropdown-item" onclick="makeCopy()">
                        <i class="fas fa-copy"></i>
                        Make a copy
                    </div>
                    <div class="dropdown-item" onclick="editAltText()">
                        <i class="fas fa-font"></i>
                        ALT text
                    </div>
                    <div class="dropdown-item" onclick="copyLink()">
                        <i class="fas fa-link"></i>
                        Copy link
                    </div>
                    <div class="dropdown-item" onclick="copyIndirectLink()">
                        <i class="fas fa-link"></i>
                        Copy indirect link
                    </div>
                    <div class="dropdown-item" onclick="shareMedia()">
                        <i class="fas fa-share-alt"></i>
                        Share
                    </div>
                    <div class="dropdown-item" onclick="toggleFavorite()">
                        <i class="fas fa-star"></i>
                        Add to favorite
                    </div>
                    <div class="dropdown-item" onclick="downloadMedia()">
                        <i class="fas fa-download"></i>
                        Download
                    </div>
                    <div class="dropdown-item" onclick="moveToTrash()" style="border-top: 1px solid #e2e8f0; margin-top: 0.5rem; padding-top: 0.75rem;">
                        <i class="fas fa-trash"></i>
                        Move to trash
                    </div>
                </div>
            </div>


            <!-- View Controls -->
            <div class="view-controls">
                <button class="view-btn active" title="Grid View">
                    <i class="fas fa-th"></i>
                </button>
                <button class="view-btn" title="List View">
                    <i class="fas fa-list"></i>
                </button>
                <button class="view-btn" title="Slideshow">
                    <i class="fas fa-play"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Upload Zone (hidden by default) -->
    <div id="uploadZone" class="upload-zone" style="display: none;">
        <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #94a3b8; margin-bottom: 1rem;"></i>
        <p style="font-size: 1.125rem; font-weight: 600; color: #475569;">Drop files here to upload</p>
        <p style="font-size: 0.875rem; color: #64748b;">or click to browse</p>
        <input type="file" id="fileInput" multiple accept="image/*,video/*,.pdf" style="display: none;">
    </div>

    <!-- Content -->
    <div class="media-content">
        <div class="media-header">
            <div class="media-title">
                <i class="fas fa-images"></i>
                <span id="contentTitle">All media</span>
            </div>
        </div>

        <div id="mediaGrid" class="media-grid">
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-images"></i>
                <p>No media files found</p>
                <button class="btn-primary" onclick="openFileUpload()">
                    <i class="fas fa-upload"></i>
                    Upload Files
                </button>
            </div>
        </div>
    </div>
    
    <!-- Bulk Actions Bar -->
    <div class="bulk-actions-bar" id="bulkActionsBar">
        <span class="bulk-count"><span id="selectedCount">0</span> selected</span>
        <button class="btn-secondary" onclick="clearSelection()">
            <i class="fas fa-times"></i> Clear
        </button>
        <button class="btn-primary" id="btnBulkRestore" onclick="bulkRestore()" style="background: #10b981; display: none;">
            <i class="fas fa-undo"></i> Restore
        </button>
        <button class="btn-primary" id="btnBulkDelete" onclick="bulkDelete()" style="background: #ef4444;">
            <i class="fas fa-trash"></i> Delete
        </button>
    </div>
</div>

<!-- Create Folder Modal -->
<div class="modal-overlay" id="folderModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Create folder</h3>
            <button class="modal-close" onclick="closeFolderModal()">&times;</button>
        </div>
        <form onsubmit="createFolder(event)">
            <div class="form-group">
                <input type="text" id="folderName" placeholder="Folder name" required>
            </div>
            <div class="modal-actions">
                <button type="submit" class="btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Upload from URL Modal -->
<div class="modal-overlay" id="urlUploadModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Upload from URL</h3>
            <button class="modal-close" onclick="closeUrlUpload()">&times;</button>
        </div>
        <form onsubmit="uploadFromUrl(event)">
            <div class="form-group">
                <label>Image URL</label>
                <input type="url" id="imageUrl" placeholder="https://example.com/image.jpg" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeUrlUpload()">Cancel</button>
                <button type="submit" class="btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>

<!-- Rename Modal -->
<div class="modal-overlay" id="renameModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Rename Media</h3>
            <button class="modal-close" onclick="closeRenameModal()">&times;</button>
        </div>
        <form onsubmit="saveRename(event)">
            <input type="hidden" id="renameMediaId">
            <div class="form-group">
                <label>New Filename</label>
                <input type="text" id="renameInput" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeRenameModal()">Cancel</button>
                <button type="submit" class="btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Context Menu -->
<div id="contextMenu" class="context-menu">
    <div class="context-menu-item" onclick="previewMedia()">
        <i class="fas fa-eye"></i> Preview
    </div>
    <div class="context-menu-item" onclick="openCropModal()">
        <i class="fas fa-crop"></i> Crop
    </div>
    <div class="context-menu-item" onclick="renameMedia()">
        <i class="fas fa-edit"></i> Rename
    </div>
    <div class="context-menu-separator"></div>
    <div class="context-menu-item" onclick="makeCopy()">
        <i class="fas fa-copy"></i> Make a copy
    </div>
    <div class="context-menu-item" onclick="editAltText()">
        <i class="fas fa-font"></i> ALT text
    </div>
    <div class="context-menu-separator"></div>
    <div class="context-menu-item" onclick="copyLink()">
        <i class="fas fa-link"></i> Copy Image Address
    </div>
    <div class="context-menu-item" onclick="copyIndirectLink()">
        <i class="fas fa-external-link-alt"></i> Copy Admin Link
    </div>
    <div class="context-menu-item" onclick="shareMedia()">
        <i class="fas fa-share-alt"></i> Share
    </div>
    <div class="context-menu-separator"></div>
    <div class="context-menu-item" onclick="toggleFavorite()">
        <i class="fas fa-star"></i> Add to favorite
    </div>
    <div class="context-menu-item" onclick="downloadMedia()">
        <i class="fas fa-download"></i> Download
    </div>
    <div class="context-menu-separator"></div>
    <div class="context-menu-item" onclick="moveToTrash()" style="color: #ef4444;">
        <i class="fas fa-trash"></i> Move to trash
    </div>
</div>

<!-- Crop Modal -->
<div class="modal-overlay" id="cropModal">
    <div class="modal" style="min-width: 800px; max-width: 90%;">
        <div class="modal-header">
            <h3>Crop Image</h3>
            <button class="modal-close" onclick="closeCropModal()">&times;</button>
        </div>
            </div>
            
            <!-- Crop Container -->
            <div style="max-height: 500px; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 0.5rem; background: #f8fafc;">
                <img id="cropImage" style="max-width: 100%; display: block;">
            </div>
            
            <!-- Actions -->
            <div style="margin-top: 1rem; display: flex; gap: 0.5rem; justify-content: flex-end;">
                <button class="btn-secondary" onclick="closeCropModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn-primary" onclick="saveCrop()">
                    <i class="fas fa-crop"></i> Crop & Save
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Full Screen Preview Modal -->
<div class="modal-overlay" id="previewModal" style="background: rgba(0, 0, 0, 0.9); z-index: 2500;" onclick="closePreviewModal(event)">
    <div style="position: relative; max-width: 90%; max-height: 90%; margin: auto; display: flex; align-items: center; justify-content: center;">
        <button onclick="closePreviewModal(event)" style="position: absolute; top: -40px; right: 0; background: none; border: none; color: white; font-size: 2rem; cursor: pointer;">&times;</button>
        <img id="previewImage" style="max-width: 100%; max-height: 90vh; border-radius: 4px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); display: none;">
        <video id="previewVideo" controls style="max-width: 100%; max-height: 90vh; border-radius: 4px; box-shadow: 0 5px 20px rgba(0,0,0,0.5); display: none;"></video>
    </div>
</div>

<!-- Cropper.js CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<!-- Inspector Panel -->
<div id="inspectorOverlay" class="inspector-overlay"></div>
<div id="inspectorPanel" class="inspector-panel">
    <div class="inspector-header">
        <h2>Media Details</h2>
        <button id="closeInspector" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer;">&times;</button>
    </div>
    <div class="inspector-body">
        <div id="inspectorPreview" class="inspector-preview">
            <img src="" alt="" style="display: none;">
            <video src="" controls style="display: none;"></video>
        </div>

        <form id="metadataForm">
            <input type="hidden" id="mediaId" name="media_id">
            
            <div class="form-group">
                <label>Filename</label>
                <input type="text" id="mediaFilename" readonly>
            </div>
            <div class="form-group">
                <label>Alt Text</label>
                <input type="text" id="mediaAlt" name="alt_text">
            </div>
            <div class="form-group">
                <label>Title</label>
                <input type="text" id="mediaTitle" name="title">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea id="mediaDescription" name="description" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Tags</label>
                <input type="text" id="mediaTags" name="tags" placeholder="Separate with commas">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem; font-size: 0.875rem;">
                <div><span style="color: #64748b;">Size:</span> <strong id="mediaSize">-</strong></div>
                <div><span style="color: #64748b;">Dimensions:</span> <strong id="mediaDimensions">-</strong></div>
                <div><span style="color: #64748b;">Type:</span> <strong id="mediaType">-</strong></div>
                <div><span style="color: #64738b;">Uploaded:</span> <strong id="mediaUploaded">-</strong></div>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn-primary" style="flex: 1;"><i class="fas fa-save"></i> Save</button>
                <button type="button" id="btnCopyUrl" class="btn-secondary"><i class="fas fa-copy"></i></button>
                <button type="button" id="btnDeleteMedia" style="background: #ef4444; color: white; border: none; padding: 0.625rem; border-radius: 0.375rem; cursor: pointer;"><i class="fas fa-trash"></i></button>
            </div>
        </form>
    </div>
</div>

<script>
let currentMedia = [];
let currentFilter = 'all';
let currentView = 'all';
let currentSort = 'date_desc';
let currentFolderId = null;
let currentFolderName = '';
let folderPath = [];
let selectedMedia = new Set();

// Selection functions
function toggleSelection(mediaId, event) {
    event.stopPropagation();
    
    if (selectedMedia.has(mediaId)) {
        selectedMedia.delete(mediaId);
        document.querySelector(`[data-media-id="${mediaId}"]`)?.classList.remove('selected');
    } else {
        selectedMedia.add(mediaId);
        document.querySelector(`[data-media-id="${mediaId}"]`)?.classList.add('selected');
    }
    
    updateBulkActionsBar();
}

function clearSelection() {
    selectedMedia.clear();
    document.querySelectorAll('.media-card.selected').forEach(card => card.classList.remove('selected'));
    document.querySelectorAll('.media-checkbox').forEach(cb => cb.checked = false);
    updateBulkActionsBar();
}

function updateBulkActionsBar() {
    const count = selectedMedia.size;
    document.getElementById('selectedCount').textContent = count;
    
    // Update selection mode counter if in selection mode
    const selectionCounter = document.getElementById('selectedCountDisplay');
    if (selectionCounter) {
        selectionCounter.textContent = `${count} selected`;
    }
    
    // Show/hide restore button based on view
    const btnRestore = document.getElementById('btnBulkRestore');
    const btnDelete = document.getElementById('btnBulkDelete');
    
    if (currentView === 'trash') {
        btnRestore.style.display = count > 0 ? 'inline-flex' : 'none';
        btnDelete.style.display = 'none';
    } else {
        btnRestore.style.display = 'none';
        btnDelete.style.display = count > 0 ? 'inline-flex' : 'none';
    }
    
    if (count > 0) {
        document.getElementById('bulkActionsBar').classList.add('show');
    } else {
        document.getElementById('bulkActionsBar').classList.remove('show');
    }
}

// Import selected images to blog editor (selection mode)
function importSelectedImages() {
    console.log('importSelectedImages called');
    console.log('selectedMedia Set:', Array.from(selectedMedia));
    console.log('currentMedia array:', currentMedia);
    
    if (selectedMedia.size === 0) {
        alert('Please select at least one image');
        return;
    }
    
    // Get selected media URLs (same way as copyLink)
    const selectedPaths = [];
    selectedMedia.forEach(mediaId => {
        console.log('Looking for mediaId:', mediaId);
        const media = currentMedia.find(m => m.id === mediaId);
        console.log('Found media:', media);
        
        if (media && media.cdn_url) {
            // Use cdn_url like copyLink does
            let url = media.cdn_url;
            // Make sure it's a relative path for insertion
            if (url.startsWith('http')) {
                url = new URL(url).pathname;
            }
            selectedPaths.push(url);
        }
    });
    
    console.log('selectedPaths:', selectedPaths);
    
    if (selectedPaths.length === 0) {
        alert('No images selected or image paths not found');
        console.error('No paths found. Media items:', Array.from(selectedMedia).map(id => currentMedia.find(m => m.id === id)));
        return;
    }
    
    // Call parent window function to insert images
    console.log('Calling parent.insertImagesToEditor');
    if (window.parent && window.parent.insertImagesToEditor) {
        window.parent.insertImagesToEditor(selectedPaths);
    } else {
        console.error('parent.insertImagesToEditor not found');
        alert('Error: Cannot communicate with editor');
    }
}

function bulkDelete() {
    if (selectedMedia.size === 0) return;
    
    if (!confirm(`Delete ${selectedMedia.size} item(s)? They will be moved to trash.`)) return;
    
    const formData = new FormData();
    formData.append('media_ids', JSON.stringify(Array.from(selectedMedia)));
    
    fetch('/admin/handlers/media_bulk_delete.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`Deleted ${data.deleted} item(s)`);
            clearSelection();
            loadMedia();
        } else {
            alert('Failed to delete: ' + (data.error || 'Unknown error'));
        }
    });
}

// Actions Menu Functions
function previewMedia() {
    if (selectedMedia.size !== 1) {
        alert('Please select exactly one media item to preview');
        return;
    }
    
    const mediaId = Array.from(selectedMedia)[0];
    const media = currentMedia.find(m => m.id === mediaId);
    if (!media) return;

    const modal = document.getElementById('previewModal');
    const img = document.getElementById('previewImage');
    const vid = document.getElementById('previewVideo');

    // Reset
    img.style.display = 'none';
    img.src = '';
    vid.style.display = 'none';
    vid.src = '';

    if ((media.mime_type && media.mime_type.startsWith('video')) || media.type === 'video') {
        vid.src = media.cdn_url;
        vid.style.display = 'block';
    } else {
        img.src = media.cdn_url;
        img.style.display = 'block';
    }

    modal.classList.add('show');
}

function closePreviewModal(e) {
    if (e.target === e.currentTarget || e.target.tagName === 'BUTTON') {
        const modal = document.getElementById('previewModal');
        modal.classList.remove('show');
        document.getElementById('previewVideo').pause();
        document.getElementById('previewVideo').src = '';
    }
}

function renameMedia() {
    if (selectedMedia.size !== 1) {
        alert('Please select exactly one media item to rename');
        return;
    }
    
    const mediaId = Array.from(selectedMedia)[0];
    const media = currentMedia.find(m => m.id === mediaId);
    if (!media) return;
    
    document.getElementById('renameMediaId').value = mediaId;
    document.getElementById('renameInput').value = media.original_filename || media.filename;
    document.getElementById('renameModal').classList.add('show');
}

function closeRenameModal() {
    document.getElementById('renameModal').classList.remove('show');
}

function saveRename(e) {
    e.preventDefault();
    
    const mediaId = document.getElementById('renameMediaId').value;
    const newName = document.getElementById('renameInput').value;
    
    const formData = new FormData();
    formData.append('media_id', mediaId);
    formData.append('filename', newName);
    
    fetch('/admin/handlers/media_rename.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Renamed successfully!');
            closeRenameModal();
            loadMedia();
        } else {
            alert('Rename failed: ' + (data.error || 'Unknown error'));
        }
    });
}

function makeCopy() {
    if (selectedMedia.size !== 1) {
        alert('Please select exactly one media item to copy');
        return;
    }
    
    const mediaId = Array.from(selectedMedia)[0];
    
    if (!confirm('Create a duplicate of this file?')) return;
    
    const formData = new FormData();
    formData.append('media_id', mediaId);
    
    fetch('/admin/handlers/media_duplicate.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('File duplicated successfully!');
            loadMedia();
        } else {
            alert('Copy failed: ' + (data.error || 'Unknown error'));
        }
    });
}

function editAltText() {
    if (selectedMedia.size !== 1) {
        alert('Please select exactly one media item');
        return;
    }
    
    const mediaId = Array.from(selectedMedia)[0];
    openInspector(mediaId);
}

function copyLink() {
    if (selectedMedia.size !== 1) {
        alert('Please select exactly one media item');
        return;
    }
    
    const mediaId = Array.from(selectedMedia)[0];
    const media = currentMedia.find(m => m.id === mediaId);
    if (!media) return;
    
    // Ensure absolute URL
    let url = media.cdn_url;
    if (!url.startsWith('http')) {
        url = window.location.origin + '/' + url.replace(/^\/+/, '');
    }

    navigator.clipboard.writeText(url).then(() => {
        alert('Image Address copied!\n\n' + url);
    });
}

function copyIndirectLink() {
    if (selectedMedia.size !== 1) {
        alert('Please select exactly one media item');
        return;
    }
    
    const mediaId = Array.from(selectedMedia)[0];
    const indirectUrl = window.location.origin + '/admin/media.php?id=' + mediaId;
    
    navigator.clipboard.writeText(indirectUrl).then(() => {
        alert('Indirect link copied to clipboard!');
    });
}

function shareMedia() {
    if (selectedMedia.size !== 1) {
        alert('Please select exactly one media item');
        return;
    }
    
    const mediaId = Array.from(selectedMedia)[0];
    const media = currentMedia.find(m => m.id === mediaId);
    if (!media) return;
    
    if (navigator.share) {
        navigator.share({
            title: media.filename,
            url: media.cdn_url
        });
    } else {
        copyLink();
    }
}

function toggleFavorite() {
    if (selectedMedia.size === 0) {
        alert('Please select at least one media item');
        return;
    }
    
    const formData = new FormData();
    formData.append('media_ids', JSON.stringify(Array.from(selectedMedia)));
    
    fetch('/admin/handlers/media_toggle_favorite.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            clearSelection();
            loadMedia();
        } else {
            alert('Failed to update favorites: ' + (data.error || 'Unknown error'));
        }
    });
}

function downloadMedia() {
    if (selectedMedia.size === 0) {
        alert('Please select at least one media item');
        return;
    }
    
    selectedMedia.forEach(mediaId => {
        const media = currentMedia.find(m => m.id === mediaId);
        if (media) {
            const a = document.createElement('a');
            a.href = media.cdn_url;
            a.download = media.filename;
            a.click();
        }
    });
}

function moveToTrash() {
    if (selectedMedia.size === 0) {
        alert('Please select at least one media item');
        return;
    }
    
    bulkDelete();
}

function bulkRestore() {
    if (selectedMedia.size === 0) return;
    
    if (!confirm(`Restore ${selectedMedia.size} item(s) from trash?`)) return;
    
    const formData = new FormData();
    formData.append('media_ids', JSON.stringify(Array.from(selectedMedia)));
    
    fetch('/admin/handlers/media_restore_from_trash.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`Restored ${data.restored} item(s)`);
            clearSelection();
            loadMedia();
        } else {
            alert('Failed to restore: ' + (data.error || 'Unknown error'));
        }
    });
}

// Dropdown toggles
document.getElementById('btnUpload').addEventListener('click', (e) => {
    e.stopPropagation();
    toggleDropdown('uploadMenu');
});

document.getElementById('btnFilter').addEventListener('click', (e) => {
    e.stopPropagation();
    toggleDropdown('filterMenu');
});

document.getElementById('btnRefresh').addEventListener('click', () => {
    const icon = document.querySelector('#btnRefresh i');
    icon.style.animation = 'spin 0.5s linear';
    loadMedia();
    setTimeout(() => icon.style.animation = '', 500);
});

document.getElementById('btnView').addEventListener('click', (e) => {
    e.stopPropagation();
    toggleDropdown('viewMenu');
});

document.getElementById('btnSort').addEventListener('click', (e) => {
    e.stopPropagation();
    toggleDropdown('sortMenu');
});

document.getElementById('btnActions').addEventListener('click', (e) => {
    e.stopPropagation();
    toggleDropdown('actionsMenu');
});

function toggleDropdown(id) {
    document.querySelectorAll('.dropdown-menu').forEach(m => {
        if (m.id !== id) m.classList.remove('show');
    });
    document.getElementById(id).classList.toggle('show');
}

document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
});

// Filter functions
function setFilter(type, label) {
    currentFilter = type;
    document.getElementById('filterLabel').textContent = label;
    loadMedia();
}

function setView(view, label) {
    currentView = view;
    document.getElementById('viewLabel').textContent = label;
    document.getElementById('contentTitle').textContent = label;
    loadMedia();
}

function setSort(sort) {
    currentSort = sort;
    loadMedia();
}

// Upload functions
function openFileUpload() {
    document.getElementById('uploadZone').style.display = 'block';
    document.getElementById('fileInput').click();
}

function openUrlUpload() {
    document.getElementById('urlUploadModal').classList.add('show');
}

function closeUrlUpload() {
    document.getElementById('urlUploadModal').classList.remove('show');
}

function uploadFromUrl(e) {
    e.preventDefault();
    const url = document.getElementById('imageUrl').value;
    
    const formData = new FormData();
    formData.append('image_url', url);
    if (currentFolderId) {
        formData.append('folder_id', currentFolderId);
    }
    
    fetch('/admin/handlers/media_upload_url.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('URL uploaded successfully!');
            closeUrlUpload();
            document.getElementById('imageUrl').value = '';
            loadMedia();
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Upload failed');
    });
}

let cropper = null;
let currentCropMediaId = null;

function openCropModal() {
    if (selectedMedia.size !== 1) {
        alert('Please select exactly one image to crop');
        return;
    }
    
    const mediaId = Array.from(selectedMedia)[0];
    const media = currentMedia.find(m => m.id === mediaId);
    
    if (!media || !media.mime_type.startsWith('image')) {
        alert('Please select an image file');
        return;
    }
    
    currentCropMediaId = mediaId;
    
    // Load image
    const img = document.getElementById('cropImage');
    img.src = media.cdn_url;
    
    document.getElementById('cropModal').classList.add('show');
    
    // Initialize cropper after image loads
    img.onload = function() {
        if (cropper) {
            cropper.destroy();
        }
        
        cropper = new Cropper(img, {
            aspectRatio: NaN,
            viewMode: 1,
            autoCropArea: 0.8,
            responsive: true,
            background: false,
            movable: true,
            zoomable: true,
            scalable: true,
            rotatable: false,
            cropBoxResizable: true,
            cropBoxMovable: true,
            toggleDragModeOnDblclick: false
        });
    };
}

function setCropAspect(ratio) {
    if (cropper) {
        cropper.setAspectRatio(ratio);
    }
}

function saveCrop() {
    if (!cropper || !currentCropMediaId) return;
    
    const cropData = cropper.getData(true);
    
    if (!confirm('This will replace the original image. Continue?')) return;
    
    const formData = new FormData();
    formData.append('media_id', currentCropMediaId);
    formData.append('x', Math.round(cropData.x));
    formData.append('y', Math.round(cropData.y));
    formData.append('width', Math.round(cropData.width));
    formData.append('height', Math.round(cropData.height));
    
    fetch('/admin/handlers/media_crop.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Image cropped successfully!');
            closeCropModal();
            loadMedia();
        } else {
            alert('Crop failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Crop failed');
    });
}

function closeCropModal() {
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    currentCropMediaId = null;
    document.getElementById('cropModal').classList.remove('show');
}

// Folder modal
function openCreateFolder() {
    document.getElementById('folderModal').classList.add('show');
}

function closeFolderModal() {
    document.getElementById('folderModal').classList.remove('show');
}

// Sync all media - Auto-import existing images into folders
function syncAllMedia() {
    if (!confirm('This will auto-organize ALL existing product, category, and banner images into folders.\n\nThis may take a few minutes. Continue?')) {
        return;
    }
    
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
    btn.disabled = true;
    
    fetch('/admin/sync_media_folders.php')
        .then(r => r.json())
        .then(data => {
            btn.innerHTML = originalHTML;
            btn.disabled = false;
            
            if (data.success) {
                const results = data.results;
                alert(`✅ Sync Complete!\n\n` +
                      `📁 Folders Created: ${results.created_folders}\n` +
                      `📸 Images Imported: ${results.imported_images}\n` +
                      `📦 Products: ${results.products}\n` +
                      `🏷️ Categories: ${results.categories}\n` +
                      `🎨 Banners: ${results.banners}\n\n` +
                      (results.errors.length > 0 ? `⚠️ Errors: ${results.errors.length}` : ''));
                loadMedia();
            } else {
                alert('Sync failed: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(err => {
            btn.innerHTML = originalHTML;
            btn.disabled = false;
            alert('Sync failed: ' + err.message);
        });
}

function createFolder(e) {
    e.preventDefault();
    const name = document.getElementById('folderName').value;
    
    const formData = new FormData();
    formData.append('folder_name', name);
    if (currentFolderId) {
        formData.append('parent_id', currentFolderId);
    }
    
    fetch('/admin/handlers/media_create_folder.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Folder created!');
            closeFolderModal();
            document.getElementById('folderName').value = '';
            loadMedia();
        } else {
            alert('Failed to create folder: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error creating folder');
    });
}

// File upload
document.getElementById('uploadZone').addEventListener('click', () => {
    document.getElementById('fileInput').click();
});

const uploadZone = document.getElementById('uploadZone');
['dragenter', 'dragover'].forEach(evt => {
    uploadZone.addEventListener(evt, (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = '#3b82f6';
    });
});

['dragleave', 'drop'].forEach(evt => {
    uploadZone.addEventListener(evt, (e) => {
        e.preventDefault();
        uploadZone.style.borderColor = '#cbd5e1';
    });
});

uploadZone.addEventListener('drop', (e) => {
    handleFiles(e.dataTransfer.files);
});

document.getElementById('fileInput').addEventListener('change', (e) => {
    handleFiles(e.target.files);
});

function handleFiles(files) {
    const formData = new FormData();
    Array.from(files).forEach(file => formData.append('files[]', file));
    
    // Add current folder ID if inside a folder
    if (currentFolderId) {
        formData.append('folder_id', currentFolderId);
    }

    fetch('/admin/handlers/media_upload.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`Uploaded ${data.uploaded.length} file(s)!`);
            loadMedia();
            document.getElementById('uploadZone').style.display = 'none';
        } else {
            alert('Upload failed');
        }
    });
}

// Load media
let currentFolders = [];

function loadMedia() {
    const search = document.getElementById('mediaSearch').value;
    const params = new URLSearchParams({ 
        search, 
        type: currentFilter === 'all' ? '' : currentFilter,
        sort: currentSort,
        view: currentView,
        folder_id: currentFolderId || '',
        page: 1, 
        per_page: 100 
    });
    
    // Update breadcrumb
    updateBreadcrumb();

    // Load folders
    const folderParams = new URLSearchParams({ parent_id: currentFolderId || '' });
    fetch(`/admin/handlers/media_list_folders.php?${folderParams}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                currentFolders = data.folders || [];
            }
        })
        .then(() => {
            // Load media files
            return fetch(`/admin/handlers/media_list.php?${params}`);
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                currentMedia = data.data || [];
                renderMedia();
            }
        });
}

// Open folder
function openFolder(folderId, folderName) {
    currentFolderId = folderId;
    currentFolderName = folderName;
    folderPath.push({ id: folderId, name: folderName });
    loadMedia();
}

// Go back to parent folder
function goToFolder(index) {
    if (index === -1) {
        // Go to root
        currentFolderId = null;
        currentFolderName = '';
        folderPath = [];
    } else {
        folderPath = folderPath.slice(0, index + 1);
        const folder = folderPath[index];
        currentFolderId = folder.id;
        currentFolderName = folder.name;
    }
    loadMedia();
}

// Update breadcrumb
function updateBreadcrumb() {
    const breadcrumb = document.querySelector('.breadcrumb');
    let html = '<a href="/admin/dashboard.php">DASHBOARD</a> / ';
    html += '<a href="javascript:goToFolder(-1)" style="cursor: pointer; color: #3b82f6; text-decoration: none;">MEDIA</a>';
    
    folderPath.forEach((folder, index) => {
        html += ' / <a href="javascript:goToFolder(' + index + ')" style="cursor: pointer; color: #3b82f6; text-decoration: none;">' + folder.name.toUpperCase() + '</a>';
    });
    
    breadcrumb.innerHTML = html;
    
    // Show/hide back button
    const btnBack = document.getElementById('btnBack');
    if (currentFolderId) {
        btnBack.style.display = 'flex';
        // Calculate parent index
        // folderPath has [Root(implicit), Folder1, Folder2]
        // If we are at Folder2 (index 1), parent is Folder1 (index 0). 
        // If we are at Folder1 (index 0), parent is Root (-1).
        const parentIndex = folderPath.length - 2;
        btnBack.setAttribute('onclick', `goToFolder(${parentIndex})`);
    } else {
        btnBack.style.display = 'none';
    }
}

// Context Menu Logic
const contextMenu = document.getElementById('contextMenu');

document.addEventListener('click', (e) => {
    if (!contextMenu.contains(e.target)) {
        contextMenu.classList.remove('show');
    }
});

function renderMedia() {
    const grid = document.getElementById('mediaGrid');
    
    if (currentFolders.length === 0 && currentMedia.length === 0) {
        grid.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-images"></i>
                <p>No media files found</p>
                <button class="btn-primary" onclick="openFileUpload()">
                    <i class="fas fa-upload"></i>
                    Upload Files
                </button>
            </div>
        `;
        return;
    }

    // Render folders first
    let html = currentFolders.map(folder => `
        <div class="media-card" onclick="openFolder('${folder.id}', '${folder.name.replace(/'/g, "\\'")}')" style="cursor: pointer;">
            <div class="media-thumbnail" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-folder" style="font-size: 3.5rem; color: white;"></i>
            </div>
            <div class="media-card-name">
                <i class="fas fa-folder" style="margin-right: 0.25rem; color: #667eea;"></i>
                ${folder.name}
            </div>
        </div>
    `).join('');

    // Then render media files
    html += currentMedia.map(media => `
        <div class="media-card ${selectedMedia.has(media.id) ? 'selected' : ''}" 
             data-media-id="${media.id}" 
             onclick="openInspector('${media.id}')"
             oncontextmenu="openContextMenu(event, '${media.id}')">
            <input type="checkbox" 
                   class="media-checkbox" 
                   ${selectedMedia.has(media.id) ? 'checked' : ''}
                   onclick="toggleSelection('${media.id}', event)">
            <div class="media-thumbnail">
                ${(media.mime_type && media.mime_type.startsWith('image')) || media.type === 'image'
                    ? `<img src="${media.thumb_url || media.cdn_url}" loading="lazy" alt="${media.alt || media.filename}">`
                    : (media.mime_type && media.mime_type.startsWith('video')) || media.type === 'video'
                    ? `<video src="${media.cdn_url}" muted loop onmouseover="this.play()" onmouseout="this.pause()"></video>`
                    : `<i class="fas fa-file" style="font-size: 3rem; color: #cbd5e1;"></i>`
                }
            </div>
            <div class="media-card-name" title="${media.filename}">
                ${media.filename}
            </div>
        </div>
    `).join('');
    
    grid.innerHTML = html;
}

function openContextMenu(e, mediaId) {
    e.preventDefault();
    
    // Select the item if not already selected
    if (!selectedMedia.has(mediaId)) {
        clearSelection();
        selectedMedia.add(mediaId);
        document.querySelector(`[data-media-id="${mediaId}"]`)?.classList.add('selected');
        updateBulkActionsBar();
    }
    
    // Position menu
    contextMenu.style.top = `${e.clientY}px`;
    contextMenu.style.left = `${e.clientX}px`;
    contextMenu.classList.add('show');
}

function openInspector(mediaId) {
    fetch(`/admin/handlers/media_get.php?id=${mediaId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                populateInspector(data.data);
                document.getElementById('inspectorPanel').classList.add('open');
                document.getElementById('inspectorOverlay').classList.add('visible');
            }
        });
}

function populateInspector(media) {
    document.getElementById('mediaId').value = media.id;
    document.getElementById('mediaFilename').value = media.filename;
    document.getElementById('mediaAlt').value = media.alt_text || '';
    document.getElementById('mediaTitle').value = media.title || '';
    document.getElementById('mediaDescription').value = media.description || '';
    document.getElementById('mediaTags').value = media.tags ? media.tags.join(', ') : '';
    document.getElementById('mediaSize').textContent = formatBytes(media.size);
    document.getElementById('mediaDimensions').textContent = media.width && media.height ? `${media.width}×${media.height}` : 'N/A';
    document.getElementById('mediaType').textContent = media.mime_type;
    document.getElementById('mediaUploaded').textContent = formatDate(media.uploaded_at);

    const imgPreview = document.querySelector('#inspectorPreview img');
    const videoPreview = document.querySelector('#inspectorPreview video');
    
    if (media.mime_type.startsWith('image')) {
        imgPreview.src = media.cdn_url;
        imgPreview.style.display = 'block';
        videoPreview.style.display = 'none';
    } else if (media.mime_type.startsWith('video')) {
        videoPreview.src = media.cdn_url;
        videoPreview.style.display = 'block';
        imgPreview.style.display = 'none';
    }
}

document.getElementById('closeInspector').addEventListener('click', () => {
    document.getElementById('inspectorPanel').classList.remove('open');
    document.getElementById('inspectorOverlay').classList.remove('visible');
});

document.getElementById('inspectorOverlay').addEventListener('click', () => {
    document.getElementById('closeInspector').click();
});

document.getElementById('metadataForm').addEventListener('submit', (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);

    fetch('/admin/handlers/media_update.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Media updated!');
            loadMedia();
        }
    });
});

document.getElementById('btnCopyUrl').addEventListener('click', () => {
    const url = document.querySelector('#inspectorPreview img').src || document.querySelector('#inspectorPreview video').src;
    navigator.clipboard.writeText(url).then(() => alert('URL copied!'));
});

document.getElementById('btnDeleteMedia').addEventListener('click', () => {
    if (!confirm('Move to trash?')) return;
    
    const formData = new FormData();
    formData.append('media_id', document.getElementById('mediaId').value);

    fetch('/admin/handlers/media_delete.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Moved to trash');
            document.getElementById('closeInspector').click();
            loadMedia();
        }
    });
});

document.getElementById('mediaSearch').addEventListener('input', loadMedia);

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

loadMedia();

// Check for indirect link (id param)
const urlParams = new URLSearchParams(window.location.search);
const sharedMediaId = urlParams.get('id');
if (sharedMediaId) {
    // Wait a bit to ensure everything is ready, then open
    setTimeout(() => {
        openInspector(sharedMediaId);
    }, 500);
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
