// Quill Font Configuration with 50+ Fonts
// This file contains the comprehensive font configuration for Quill editors

window.QuillFontConfig = {
  // Complete list of 50+ fonts with their display names and CSS font-family values
  fonts: [
    { value: 'roboto', label: 'Roboto', family: "'Roboto', sans-serif" },
    { value: 'open-sans', label: 'Open Sans', family: "'Open Sans', sans-serif" },
    { value: 'lato', label: 'Lato', family: "'Lato', sans-serif" },
    { value: 'montserrat', label: 'Montserrat', family: "'Montserrat', sans-serif" },
    { value: 'raleway', label: 'Raleway', family: "'Raleway', sans-serif" },
    { value: 'poppins', label: 'Poppins', family: "'Poppins', sans-serif" },
    { value: 'ubuntu', label: 'Ubuntu', family: "'Ubuntu', sans-serif" },
    { value: 'nunito', label: 'Nunito', family: "'Nunito', sans-serif" },
    { value: 'pt-sans', label: 'PT Sans', family: "'PT Sans', sans-serif" },
    { value: 'oswald', label: 'Oswald', family: "'Oswald', sans-serif" },
    { value: 'work-sans', label: 'Work Sans', family: "'Work Sans', sans-serif" },
    { value: 'quicksand', label: 'Quicksand', family: "'Quicksand', sans-serif" },
    { value: 'source-sans-pro', label: 'Source Sans Pro', family: "'Source Sans Pro', sans-serif" },
    { value: 'noto-sans', label: 'Noto Sans', family: "'Noto Sans', sans-serif" },
    { value: 'rubik', label: 'Rubik', family: "'Rubik', sans-serif" },
    { value: 'inter', label: 'Inter', family: "'Inter', sans-serif" },
    { value: 'mulish', label: 'Mulish', family: "'Mulish', sans-serif" },
    { value: 'karla', label: 'Karla', family: "'Karla', sans-serif" },
    { value: 'barlow', label: 'Barlow', family: "'Barlow', sans-serif" },
    { value: 'titillium-web', label: 'Titillium Web', family: "'Titillium Web', sans-serif" },
    { value: 'josefin-sans', label: 'Josefin Sans', family: "'Josefin Sans', sans-serif" },
    { value: 'mukta', label: 'Mukta', family: "'Mukta', sans-serif" },
    { value: 'merriweather-sans', label: 'Merriweather Sans', family: "'Merriweather Sans', sans-serif" },
    { value: 'hind', label: 'Hind', family: "'Hind', sans-serif" },
    { value: 'oxygen', label: 'Oxygen', family: "'Oxygen', sans-serif" },
    { value: 'cabin', label: 'Cabin', family: "'Cabin', sans-serif" },
    { value: 'arimo', label: 'Arimo', family: "'Arimo', sans-serif" },
    { value: 'red-hat-display', label: 'Red Hat Display', family: "'Red Hat Display', sans-serif" },
    { value: 'dm-sans', label: 'DM Sans', family: "'DM Sans', sans-serif" },
    { value: 'ibm-plex-sans', label: 'IBM Plex Sans', family: "'IBM Plex Sans', sans-serif" },
    { value: 'fira-sans', label: 'Fira Sans', family: "'Fira Sans', sans-serif" },
    { value: 'libre-franklin', label: 'Libre Franklin', family: "'Libre Franklin', sans-serif" },
    { value: 'yanone', label: 'Yanone Kaffeesatz', family: "'Yanone Kaffeesatz', sans-serif" },
    { value: 'saira', label: 'Saira', family: "'Saira', sans-serif" },

    // Serif fonts
    { value: 'playfair', label: 'Playfair Display', family: "'Playfair Display', serif" },
    { value: 'merriweather', label: 'Merriweather', family: "'Merriweather', serif" },
    { value: 'crimson', label: 'Crimson Text', family: "'Crimson Text', serif" },
    { value: 'libre-baskerville', label: 'Libre Baskerville', family: "'Libre Baskerville', serif" },
    { value: 'ibm-plex-serif', label: 'IBM Plex Serif', family: "'IBM Plex Serif', serif" },
    { value: 'spectral', label: 'Spectral', family: "'Spectral', serif" },

    // Display fonts
    { value: 'fjalla-one', label: 'Fjalla One', family: "'Fjalla One', sans-serif" },
    { value: 'bebas-neue', label: 'Bebas Neue', family: "'Bebas Neue', cursive" },
    { value: 'abril-fatface', label: 'Abril Fatface', family: "'Abril Fatface', cursive" },
    { value: 'alfa-slab-one', label: 'Alfa Slab One', family: "'Alfa Slab One', cursive" },
    { value: 'anton', label: 'Anton', family: "'Anton', sans-serif" },
    { value: 'lobster', label: 'Lobster', family: "'Lobster', cursive" },
    { value: 'righteous', label: 'Righteous', family: "'Righteous', cursive" },
    { value: 'fredoka-one', label: 'Fredoka One', family: "'Fredoka One', cursive" },
    { value: 'bungee', label: 'Bungee', family: "'Bungee', cursive" },

    // Handwriting & Script fonts
    { value: 'architects-daughter', label: 'Architects Daughter', family: "'Architects Daughter', cursive" },
    { value: 'pacifico', label: 'Pacifico', family: "'Pacifico', cursive" },
    { value: 'dancing-script', label: 'Dancing Script', family: "'Dancing Script', cursive" },
    { value: 'satisfy', label: 'Satisfy', family: "'Satisfy', cursive" },
    { value: 'great-vibes', label: 'Great Vibes', family: "'Great Vibes', cursive" },
    { value: 'caveat', label: 'Caveat', family: "'Caveat', cursive" },
    { value: 'indie-flower', label: 'Indie Flower', family: "'Indie Flower', cursive" },
    { value: 'shadows-into-light', label: 'Shadows Into Light', family: "'Shadows Into Light', cursive" },
    { value: 'kalam', label: 'Kalam', family: "'Kalam', cursive" },
    { value: 'permanent-marker', label: 'Permanent Marker', family: "'Permanent Marker', cursive" },

    // Monospace
    { value: 'ibm-plex-mono', label: 'IBM Plex Mono', family: "'IBM Plex Mono', monospace" }
  ],

  // Get font values array
  getFontValues: function () {
    return this.fonts.map(f => f.value);
  },

  // Generate CSS for font pickers
  generateCSS: function () {
    let css = '';
    this.fonts.forEach(font => {
      css += `
        .ql-snow .ql-picker.ql-font .ql-picker-label[data-value="${font.value}"]::before,
        .ql-snow .ql-picker.ql-font .ql-picker-item[data-value="${font.value}"]::before {
          content: '${font.label}';
          font-family: ${font.family};
        }
        .ql-font-${font.value} { font-family: ${font.family}; }
      `;
    });

    // Add scrollable dropdown styles
    css += `
      .ql-snow .ql-picker.ql-font .ql-picker-options {
        max-height: 250px;
        overflow-y: auto;
      }
    `;

    return css;
  },

  // Initialize searchable font picker
  initSearchablePicker: function () {
    // Add search input to font dropdown
    document.addEventListener('DOMContentLoaded', function () {
      setTimeout(() => {
        const fontPickers = document.querySelectorAll('.ql-font');
        fontPickers.forEach(picker => {
          const options = picker.querySelector('.ql-picker-options');
          if (options && !options.querySelector('.font-search-input')) {
            // Create search input
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'font-search-input';
            searchInput.placeholder = 'Search fonts...';
            searchInput.style.cssText = 'width: 100%; padding: 8px; border: none; border-bottom: 1px solid #ccc; outline: none; position: sticky; top: 0; background: white; z-index: 1;';

            // Insert at top of options
            options.insertBefore(searchInput, options.firstChild);

            // Search functionality
            searchInput.addEventListener('input', function (e) {
              const searchTerm = e.target.value.toLowerCase();
              const items = options.querySelectorAll('.ql-picker-item');

              items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                  item.style.display = '';
                } else {
                  item.style.display = 'none';
                }
              });
            });

            // Prevent closing dropdown when clicking search
            searchInput.addEventListener('mousedown', function (e) {
              e.stopPropagation();
            });

            // Clear search when dropdown closes
            picker.addEventListener('mousedown', function () {
              setTimeout(() => {
                if (!picker.classList.contains('ql-expanded')) {
                  searchInput.value = '';
                  const items = options.querySelectorAll('.ql-picker-item');
                  items.forEach(item => item.style.display = '');
                }
              }, 100);
            });
          }
        });
      }, 500); // Delay to ensure Quill is fully initialized
    });
  }
};
