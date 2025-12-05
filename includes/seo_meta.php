<?php
/**
 * SEO Meta Tags Helper
 * Generate comprehensive SEO meta tags for DevElixir Natural Cosmetics
 */

// Business Constants
define('SITE_NAME', 'DevElixir Natural Cosmetics');
define('SITE_URL', 'https://develixirs.com');
define('SITE_DESCRIPTION', 'Authentic Ayurvedic beauty products, natural cosmetics, and herbal skincare. Ancient secrets for modern glow since 2005. Shop online for baby care, hair care, and skin care.');
define('BUSINESS_PHONE', '+91 9500650454');
define('BUSINESS_EMAIL', 'sales@develixirs.com');
define('BUSINESS_ADDRESS', 'No.164, Kovaipudur Main Road, Near Arshiya Hospital, Kovaipudur, Coimbatore - 641042');
define('BUSINESS_CITY', 'Coimbatore');
define('BUSINESS_STATE', 'Tamil Nadu');
define('BUSINESS_COUNTRY', 'India');
define('BUSINESS_POSTAL_CODE', '641042');

/**
 * Generate SEO meta tags
 * @param array $options Configuration options
 * @return string HTML meta tags
 */
function generate_seo_meta($options = []) {
    $defaults = [
        'title' => SITE_NAME . ' | Authentic Ayurvedic Beauty Products Online',
        'description' => SITE_DESCRIPTION,
        'keywords' => 'ayurvedic beauty products, natural cosmetics, herbal skincare, organic beauty, baby care products, hair care online, skin care india',
        'url' => SITE_URL,
        'image' => SITE_URL . '/assets/images/logo.png',
        'type' => 'website',
        'author' => SITE_NAME,
        'robots' => 'index, follow',
        'canonical' => null
    ];
    
    $meta = array_merge($defaults, $options);
    
    // Truncate title and description for optimal SEO
    $meta['title'] = mb_substr($meta['title'], 0, 60);
    $meta['description'] = mb_substr($meta['description'], 0, 160);
    
    $html = "\n<!-- SEO Meta Tags -->\n";
    $html .= '<meta charset="UTF-8">' . "\n";
    $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
    $html .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">' . "\n";
    $html .= '<title>' . htmlspecialchars($meta['title']) . '</title>' . "\n";
    $html .= '<meta name="description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
    $html .= '<meta name="keywords" content="' . htmlspecialchars($meta['keywords']) . '">' . "\n";
    $html .= '<meta name="author" content="' . htmlspecialchars($meta['author']) . '">' . "\n";
    $html .= '<meta name="robots" content="' . htmlspecialchars($meta['robots']) . '">' . "\n";
    
    // Canonical URL
    $canonical = $meta['canonical'] ?? $meta['url'];
    $html .= '<link rel="canonical" href="' . htmlspecialchars($canonical) . '">' . "\n";
    
    // Open Graph Tags
    $html .= "\n<!-- Open Graph / Facebook -->\n";
    $html .= '<meta property="og:type" content="' . htmlspecialchars($meta['type']) . '">' . "\n";
    $html .= '<meta property="og:url" content="' . htmlspecialchars($meta['url']) . '">' . "\n";
    $html .= '<meta property="og:title" content="' . htmlspecialchars($meta['title']) . '">' . "\n";
    $html .= '<meta property="og:description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
    $html .= '<meta property="og:image" content="' . htmlspecialchars($meta['image']) . '">' . "\n";
    $html .= '<meta property="og:site_name" content="' . SITE_NAME . '">' . "\n";
    
    // Twitter Card Tags
    $html .= "\n<!-- Twitter -->\n";
    $html .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $html .= '<meta name="twitter:url" content="' . htmlspecialchars($meta['url']) . '">' . "\n";
    $html .= '<meta name="twitter:title" content="' . htmlspecialchars($meta['title']) . '">' . "\n";
    $html .= '<meta name="twitter:description" content="' . htmlspecialchars($meta['description']) . '">' . "\n";
    $html .= '<meta name="twitter:image" content="' . htmlspecialchars($meta['image']) . '">' . "\n";
    
    return $html;
}

/**
 * Generate Product Schema.org JSON-LD
 * @param array $product Product data
 * @return string JSON-LD script tag
 */
function generate_product_schema($product) {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Product",
        "name" => $product['name'],
        "description" => $product['description'] ?? '',
        "image" => $product['image'] ?? SITE_URL . '/assets/images/product-default.jpg',
        "brand" => [
            "@type" => "Brand",
            "name" => SITE_NAME
        ],
        "offers" => [
            "@type" => "Offer",
            "url" => $product['url'] ?? SITE_URL,
            "priceCurrency" => "INR",
            "price" => $product['price'] ?? 0,
            "availability" => "https://schema.org/InStock",
            "seller" => [
                "@type" => "Organization",
                "name" => SITE_NAME
            ]
        ]
    ];
    
    // Add ratings if available
    if (isset($product['rating']) && isset($product['review_count'])) {
        $schema['aggregateRating'] = [
            "@type" => "AggregateRating",
            "ratingValue" => $product['rating'],
            "reviewCount" => $product['review_count']
        ];
    }
    
    // Add SKU if available
    if (isset($product['sku'])) {
        $schema['sku'] = $product['sku'];
    }
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate Organization Schema.org JSON-LD
 * @return string JSON-LD script tag
 */
function generate_organization_schema() {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "Organization",
        "name" => SITE_NAME,
        "url" => SITE_URL,
        "logo" => SITE_URL . "/assets/images/logo.png",
        "description" => SITE_DESCRIPTION,
        "contactPoint" => [
            "@type" => "ContactPoint",
            "telephone" => BUSINESS_PHONE,
            "contactType" => "Customer Service",
            "email" => BUSINESS_EMAIL,
            "areaServed" => "IN",
            "availableLanguage" => ["English", "Tamil", "Hindi"]
        ],
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => BUSINESS_ADDRESS,
            "addressLocality" => BUSINESS_CITY,
            "addressRegion" => BUSINESS_STATE,
            "postalCode" => BUSINESS_POSTAL_CODE,
            "addressCountry" => BUSINESS_COUNTRY
        ],
        "sameAs" => [
            // Add social media URLs here when available
        ]
    ];
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate LocalBusiness Schema.org JSON-LD
 * @return string JSON-LD script tag
 */
function generate_local_business_schema() {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "LocalBusiness",
        "name" => SITE_NAME,
        "image" => SITE_URL . "/assets/images/logo.png",
        "url" => SITE_URL,
        "telephone" => BUSINESS_PHONE,
        "email" => BUSINESS_EMAIL,
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => BUSINESS_ADDRESS,
            "addressLocality" => BUSINESS_CITY,
            "addressRegion" => BUSINESS_STATE,
            "postalCode" => BUSINESS_POSTAL_CODE,
            "addressCountry" => BUSINESS_COUNTRY
        ],
        "geo" => [
            "@type" => "GeoCoordinates",
            "latitude" => "11.0168",
            "longitude" => "76.9558"
        ],
        "priceRange" => "₹₹",
        "openingHoursSpecification" => [
            "@type" => "OpeningHoursSpecification",
            "dayOfWeek" => ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
            "opens" => "09:00",
            "closes" => "18:00"
        ]
    ];
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate Breadcrumb Schema.org JSON-LD
 * @param array $breadcrumbs Array of breadcrumb items [['name' => '', 'url' => ''], ...]
 * @return string JSON-LD script tag
 */
function generate_breadcrumb_schema($breadcrumbs) {
    $itemList = [];
    foreach ($breadcrumbs as $index => $item) {
        $itemList[] = [
            "@type" => "ListItem",
            "position" => $index + 1,
            "name" => $item['name'],
            "item" => $item['url']
        ];
    }
    
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => $itemList
    ];
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Generate WebSite Schema.org JSON-LD with site search
 * @return string JSON-LD script tag
 */
function generate_website_schema() {
    $schema = [
        "@context" => "https://schema.org",
        "@type" => "WebSite",
        "name" => SITE_NAME,
        "url" => SITE_URL,
        "potentialAction" => [
            "@type" => "SearchAction",
            "target" => [
                "@type" => "EntryPoint",
                "urlTemplate" => SITE_URL . "/shop.php?search={search_term_string}"
            ],
            "query-input" => "required name=search_term_string"
        ]
    ];
    
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}
