<?php
// =================== DB CONNECTION ===================
// Change these according to your setup
$host   = 'localhost';
$dbname = 'develixirs_db';   // your DB name
$user   = 'root';            // your DB username
$pass   = '';                // your DB password

$pdo = null;
$allProducts   = [];
$newProducts   = [];
$bestProducts  = [];
$picksProducts = [];
$tabLatest     = [];
$tabTrendy     = [];
$tabSale       = [];
$tabTop        = [];

try {
    $dsn  = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, $user, $pass, $opts);

    // Get up to 20 products (latest first)
    // Adjust the query if you have created_at or another field
    $stmt = $pdo->query("SELECT id, name, price, image_url FROM products ORDER BY id DESC LIMIT 20");
    $allProducts = $stmt->fetchAll();

    // Now slice for different sections (all from same DB list)
    $newProducts   = array_slice($allProducts, 0, 4);  // New Herbal Products
    $bestProducts  = array_slice($allProducts, 4, 4);  // Best Sellers
    $picksProducts = array_slice($allProducts, 0, 2);  // Devilixirs Picks (sidebar)

    // Tabbed sections – reuse same array in different slices
    $tabLatest = array_slice($allProducts, 0, 3);
    $tabTrendy = array_slice($allProducts, 3, 3);
    $tabSale   = array_slice($allProducts, 6, 3);
    $tabTop    = array_slice($allProducts, 9, 3);

} catch (PDOException $e) {
    // If DB fails, everything stays empty; page still works, no products shown
    // Uncomment if you want to debug:
    // echo "DB error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Devilixirs – Herbal Shop</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>

  <style>
    :root{
      /* Devilixirs Gold + Silver Theme */
      --primary:#D4AF37;        /* Header gold */
      --primary-dark:#B89026;   /* Slightly darker gold for hover */
      --accent:#D4AF37;         /* Use same gold as accent */

      --text:#1a1a1a;
      --muted:#6b6b6b;

      --bg:#B5B5B5;             /* Silver body tone */
      --card-bg:#ffffff;
      --border:#d0d0d0;
    }

    *{
      margin:0;
      padding:0;
      box-sizing:border-box;
    }

    body{
      font-family:'Poppins',sans-serif;
      color:var(--text);
      background:#B5B5B5;
    }

    a{
      text-decoration:none;
      color:inherit;
    }

    ul{
      list-style:none;
    }

    img{
      max-width:100%;
      display:block;
    }

    .wrapper{
      max-width:1200px;
      margin:0 auto;
      padding:0 15px 60px;
    }

    /* TOP BAR */
    .top-bar{
      background:var(--primary-dark);
      color:#fff;
      font-size:12px;
    }
    .top-bar-inner{
      max-width:1200px;
      margin:0 auto;
      padding:8px 15px;
      display:flex;
      align-items:center;
      justify-content:space-between;
    }
    .top-left span{
      margin-right:20px;
      cursor:pointer;
    }
    .top-left i{
      margin-left:4px;
      font-size:10px;
    }
    .top-right{
      display:flex;
      align-items:center;
      gap:12px;
      font-size:13px;
    }
    .top-right a{
      opacity:.9;
    }
    .top-right a:hover{
      opacity:1;
    }

    /* MAIN HEADER */
    .header{
      background:var(--primary);
      padding:18px 0;
      color:#fff;
      position:relative;
      z-index:50;
    }
    .header-inner{
      max-width:1200px;
      margin:0 auto;
      padding:0 15px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:30px;
    }
    .logo{
      font-size:24px;
      font-weight:700;
      letter-spacing:.16em;
    }
    .logo span{
      display:block;
      font-size:11px;
      letter-spacing:.26em;
      font-weight:400;
      margin-top:2px;
    }
    .logo img{
      max-height:100px;
      width:auto;
      display:block;
    }

    .search-box{
      flex:1;
      max-width:600px;
      display:flex;
      background:#fff;
      border-radius:999px;
      overflow:hidden;
      box-shadow:0 4px 14px rgba(0,0,0,.12);
    }
    .search-category{
      background:#f7faf8;
      padding:12px 18px;
      border-right:1px solid var(--border);
      display:flex;
      align-items:center;
      gap:6px;
      font-size:13px;
      color:var(--muted);
      white-space:nowrap;
    }
    .search-input{
      flex:1;
      border:none;
      padding:0 14px;
      font-size:13px;
      outline:none;
    }
    .search-button{
      width:52px;
      border:none;
      background:var(--primary);
      cursor:pointer;
      font-size:16px;
      color:#fff;
      transition:.2s ease;
    }
    .search-button:hover{
      background:var(--primary-dark);
    }

    .header-icons{
      display:flex;
      align-items:center;
      gap:18px;
      font-size:14px;
    }
    .header-icons .icon-btn{
      cursor:pointer;
      display:flex;
      align-items:center;
      gap:6px;
      font-size:13px;
      position:relative;
    }
    .header-icons .icon-btn i{
      font-size:18px;
    }
    .cart-count{
      position:absolute;
      top:-5px;
      right:-10px;
      background:#000;
      color:#fff;
      border-radius:50%;
      font-size:10px;
      width:16px;
      height:16px;
      display:flex;
      align-items:center;
      justify-content:center;
    }

    /* NAVBAR */
    .nav{
      background:var(--primary);
      border-top:1px solid rgba(255,255,255,.15);
      position:relative;
      z-index:40;
    }
    .nav-inner{
      max-width:1200px;
      margin:0 auto;
      padding:0 15px;
      position:relative;
    }

    .nav ul{
      display:flex;
      gap:32px;
      align-items:center;
      font-size:13px;
      color:#fff;
    }
    .nav li{
      padding:14px 0;
      position:relative;
      cursor:pointer;
    }
    .nav li.active{
      font-weight:600;
    }
    .nav li.active::after{
      content:'';
      position:absolute;
      left:0;
      bottom:0;
      width:100%;
      height:3px;
      background:#fff;
    }

    /* ===== MEGA MENU (SHOP DROPDOWN) ===== */
    .nav-inner{
      position:relative;
    }

    .nav li.has-mega{
      position:static; /* allow dropdown full width */
    }

    .mega-menu{
      position:absolute;
      left:0;
      right:0;
      top:100%;
      margin-top:0;
      background:#ffffff;
      border-top:1px solid var(--border);
      box-shadow:0 14px 35px rgba(0,0,0,.14);
      padding:22px 0 26px;
      display:none;
      z-index:60;
    }

    .nav li.has-mega:hover .mega-menu{
      display:block;
    }

    .mega-menu-inner{
      max-width:1200px;
      margin:0 auto;
      padding:0 15px;
      display:grid;
      grid-template-columns:repeat(3,minmax(0,1fr));
      gap:40px;
    }

    .mega-column-title{
      font-size:12px;
      text-transform:uppercase;
      margin-bottom:10px;
      font-weight:600;
      letter-spacing:.12em;
      color:var(--text);
    }

    .mega-list{
      list-style:none;
      margin:0;
      padding:0;
      display:block;
    }

    .mega-menu .mega-list li{
      display:block;
      padding:4px 0;
    }

    .mega-item-link{
      display:block;
      padding:3px 0;
      font-size:13px;
      color:#333;
      transition:.2s ease;
    }

    .mega-item-link:hover{
      color:var(--primary-dark);
    }

    .mega-demo{
      display:block;
      font-size:11px;
      text-transform:uppercase;
      letter-spacing:.18em;
      color:var(--muted);
    }

    .mega-name{
      display:block;
      font-size:13px;
      font-weight:500;
      color:var(--text);
      margin-top:2px;
    }

    .mega-label-e{
      display:inline-block;
      font-size:9px;
      padding:1px 5px;
      border-radius:3px;
      background:#f0f0f0;
      color:#444;
      margin-left:6px;
    }

    /* MAIN CONTENT */
    .main{
      background:linear-gradient(180deg, #B5B5B5 0%, #ffffff 55%);
    }

    .hero-section{
      padding:40px 0 30px;
    }
    .hero-grid{
      display:grid;
      grid-template-columns:260px 1fr;
      gap:25px;
      align-items:stretch;
    }

    /* CARD BASE */
    .card{
      background:var(--card-bg);
      border:1px solid var(--border);
      box-shadow:0 2px 6px rgba(0,0,0,.04);
    }
    .card + .card{
      margin-top:24px;
    }
    .card-header{
      background:var(--primary);
      color:#fff;
      padding:12px 16px;
      font-size:13px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      text-transform:uppercase;
      font-weight:600;
      cursor:default;
    }
    .card-header .label{
      display:flex;
      align-items:center;
      gap:8px;
    }
    .card-body{
      padding:12px 0;
    }

    /* Categories specific */
    .categories-card .card-header{
      cursor:pointer;
    }
    .categories-card .card-header .toggle-icon{
      font-size:14px;
      transition:transform .2s ease;
    }
    .categories-card.collapsed .card-body{
      display:none;
    }
    .categories-card.collapsed .toggle-icon{
      transform:rotate(90deg);
    }

    .category-list li{
      padding:10px 18px;
      display:flex;
      align-items:center;
      gap:10px;
      font-size:13px;
      border-top:1px solid var(--border);
      cursor:pointer;
    }
    .category-list li:first-child{
      border-top:none;
    }
    .category-list li i{
      color:var(--primary);
      width:16px;
      text-align:center;
    }
    .category-list li:hover{
      background:var(--bg);
    }

    /* HERO RIGHT */
    .hero-banner{
      background:linear-gradient(90deg,#f7faf8,#ffffff);
      border:1px solid var(--border);
      min-height:220px;
      display:flex;
      align-items:center;
      padding:40px 60px;
      box-shadow:0 4px 18px rgba(0,0,0,.06);
      position:relative;
      overflow:hidden;
    }
    .hero-banner::before{
      content:'';
      position:absolute;
      right:-80px;
      top:-80px;
      width:230px;
      height:230px;
      border-radius:50%;
      background:radial-gradient(circle at 30% 30%,#e5f6ec,#c3ecd7);
      opacity:.8;
    }
    .hero-text{
      position:relative;
      z-index:1;
    }
    .hero-text small{
      font-size:11px;
      letter-spacing:.2em;
      text-transform:uppercase;
      color:var(--muted);
      display:block;
      margin-bottom:10px;
    }
    .hero-text h1{
      font-size:30px;
      letter-spacing:.04em;
      margin-bottom:10px;
    }
    .hero-text p{
      font-size:13px;
      color:var(--muted);
      max-width:380px;
      margin-bottom:22px;
    }

    .btn-primary{
      background:linear-gradient(145deg, #014d40, #0b6b5a);
      border:1px solid var(--accent);
      color:#fff;
      padding:11px 26px;
      font-size:13px;
      border-radius:999px;
      border:none;
      text-transform:uppercase;
      font-weight:500;
      cursor:pointer;
      transition:.2s ease;
      box-shadow:0 4px 14px rgba(12,140,85,.35);
    }
    .btn-primary:hover{
      background:var(--primary-dark);
      transform:translateY(-1px);
      box-shadow:0 6px 18px rgba(0,0,0,.25);
    }

    /* CONTENT GRID */
    .content-grid{
      display:grid;
      grid-template-columns:260px 1fr;
      gap:25px;
    }

    /* BANNERS ROW */
    .banner-row{
      display:grid;
      grid-template-columns:2fr 2fr 2fr;
      gap:18px;
      margin-bottom:26px;
    }
    .banner-item{
      position:relative;
      overflow:hidden;
      background:#eee;
      border:1px solid var(--border);
    }
    .banner-item img{
      width:100%;
      height:100%;
      object-fit:cover;
      transition:transform .3s ease;
    }
    .banner-item:hover img{
      transform:scale(1.04);
    }
    .banner-caption{
      position:absolute;
      left:20px;
      bottom:22px;
      color:#fff;
      text-shadow:0 1px 2px rgba(0,0,0,.3);
      max-width:70%;
    }
    .banner-caption h4{
      font-size:18px;
      font-weight:600;
      margin-bottom:3px;
    }
    .banner-caption span{
      font-size:12px;
    }

    /* SECTION TITLE ROW */
    .section-header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      margin-bottom:14px;
    }
    .section-header h3{
      font-size:15px;
      text-transform:uppercase;
      position:relative;
      padding-left:14px;
    }
    .section-header h3::before{
      content:'';
      position:absolute;
      left:0;
      top:50%;
      width:6px;
      height:60%;
      background:var(--primary);
      transform:translateY(-50%);
      border-radius:3px;
    }
    .section-arrows{
      display:flex;
      gap:6px;
      font-size:13px;
      color:var(--muted);
    }
    .section-arrows span{
      width:24px;
      height:24px;
      border-radius:50%;
      border:1px solid var(--border);
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:pointer;
    }
    .section-arrows span:hover{
      border-color:var(--primary);
      color:var(--primary);
    }

    /* PRODUCT GRID */
    .product-grid{
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:18px;
    }
    .product-card{
      border:1px solid var(--border);
      background:#fff;
      padding:14px;
      text-align:left;
      transition:box-shadow .2s ease, transform .2s ease;
    }
    .product-card:hover{
      box-shadow:0 2px 10px rgba(0,0,0,.08);
      transform:translateY(-2px);
    }
    .product-image{
      margin-bottom:14px;
    }
    .product-name{
      font-size:13px;
      margin-bottom:4px;
    }
    .product-price{
      font-weight:600;
      font-size:14px;
      margin-bottom:4px;
      color:var(--primary-dark);
    }
    .product-stars{
      font-size:10px;
      color:#ccc;
    }

    /* SIDE CARDS (SPECIAL / FEATURED / TAGS) */
    .mini-product{
      display:flex;
      align-items:center;
      gap:10px;
      padding:10px 16px;
      border-top:1px solid var(--border);
    }
    .mini-product:first-child{
      border-top:none;
    }
    .mini-product img{
      width:56px;
      height:56px;
      object-fit:cover;
      border-radius:8px;
      border:1px solid var(--border);
    }
    .mini-info{
      flex:1;
    }
    .mini-name{
      font-size:12px;
      margin-bottom:4px;
    }
    .mini-price{
      color:var(--primary);
      font-weight:600;
      font-size:13px;
    }

    .tags-wrap{
      padding:14px 16px;
      display:flex;
      flex-wrap:wrap;
      gap:8px;
    }
    .tag-item{
      padding:5px 9px;
      font-size:11px;
      background:#f0f5f2;
      border-radius:999px;
      cursor:pointer;
    }
    .tag-item:hover{
      background:var(--primary);
      color:#fff;
    }

    /* BANNER STRIP */
    .wide-banner{
      margin-top:30px;
      border:1px solid var(--border);
      position:relative;
      overflow:hidden;
      background:#eee;
      border-radius:8px;
      box-shadow:0 4px 16px rgba(0,0,0,.08);
    }
    .wide-banner img{
      width:100%;
      height:220px;
      object-fit:cover;
      filter:brightness(.88);
    }
    .wide-banner-text{
      position:absolute;
      inset:0;
      display:flex;
      flex-direction:column;
      align-items:center;
      justify-content:center;
      color:#fff;
      text-align:center;
      text-shadow:0 1px 3px rgba(0,0,0,.4);
    }
    .wide-banner-text h2{
      font-size:20px;
      letter-spacing:.2em;
      text-transform:uppercase;
      margin-bottom:8px;
    }
    .wide-banner-text p{
      font-size:13px;
    }

    /* LATEST BLOG */
    .latest-blog-section{
      margin-top:40px;
    }
    .blog-grid{
      display:grid;
      grid-template-columns:repeat(2,minmax(0,1fr));
      gap:24px;
    }
    .blog-card{
      border:1px solid var(--border);
      background:#fff;
      box-shadow:0 2px 6px rgba(0,0,0,.04);
    }
    .blog-card-image{
      background:#f3f3f3;
    }
    .blog-card-body{
      padding:18px 22px 20px;
    }
    .blog-title{
      font-size:13px;
      font-weight:600;
      text-transform:uppercase;
      margin-bottom:10px;
    }
    .blog-meta{
      font-size:11px;
      margin-bottom:10px;
    }
    .blog-meta span{
      margin-right:10px;
    }
    .blog-meta a{
      color:var(--primary);
    }
    .blog-excerpt{
      font-size:12px;
      color:var(--muted);
      line-height:1.6;
    }

    /* TABBED PRODUCTS SECTION */
    .tabbed-products-section{
      margin-top:40px;
      border:1px solid var(--border);
      background:#fff;
      box-shadow:0 2px 6px rgba(0,0,0,.04);
      padding:0 18px 20px;
    }
    .tabs-bar{
      display:flex;
      flex-wrap:wrap;
      margin:-1px -18px 18px;
    }
    .tab-ribbon{
      position:relative;
      padding:10px 26px;
      font-size:12px;
      text-transform:uppercase;
      background:var(--primary);
      color:#fff;
      font-weight:500;
      cursor:pointer;
      margin-right:4px;
    }
    .tab-ribbon::after{
      content:'';
      position:absolute;
      top:0;
      right:-16px;
      width:0;
      height:0;
      border-top:20px solid transparent;
      border-bottom:20px solid transparent;
      border-left:16px solid var(--primary);
    }
    .tab-ribbon:last-child{
      margin-right:0;
    }
    .tab-ribbon:last-child::after{
      display:none;
    }
    .tab-ribbon.inactive{
      background:#f3f3f3;
      color:#333;
    }
    .tab-ribbon.inactive::after{
      border-left-color:#f3f3f3;
    }

    .tabbed-columns{
      display:grid;
      grid-template-columns:repeat(4,minmax(0,1fr));
      gap:18px;
    }
    .tab-column{
      background:#fff;
    }
    .tab-column-card{
      border:1px solid var(--border);
      padding:10px 12px 12px;
      margin-bottom:12px;
    }
    .tab-column-card:last-child{
      margin-bottom:0;
    }
    .tab-col-product{
      display:flex;
      align-items:center;
      gap:10px;
    }
    .tab-col-product img{
      width:46px;
      height:46px;
      object-fit:cover;
      border-radius:6px;
      border:1px solid var(--border);
    }
    .tab-col-info{
      flex:1;
      font-size:12px;
    }
    .tab-col-name{
      margin-bottom:4px;
    }
    .tab-col-price{
      color:var(--primary);
      font-weight:600;
      font-size:12px;
    }
    .tab-col-stars{
      font-size:10px;
      color:#ccc;
      margin-top:3px;
    }

    /* BRANDS ROW */
    .brands-row{
      margin-top:40px;
      padding:22px 0 10px;
      display:flex;
      justify-content:center;
      flex-wrap:wrap;
      gap:50px;
      border-top:1px solid var(--border);
    }
    .brand-item{
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:13px;
      text-transform:lowercase;
      opacity:.9;
    }

    /* FOOTER */
    .footer{
      background:#050b0b;
      color:#ddd;
      padding:40px 0 0;
      margin-top:40px;
    }
    .footer-inner{
      max-width:1200px;
      margin:0 auto;
      padding:0 15px 30px;
      display:grid;
      grid-template-columns:2fr 1.4fr 1.4fr 1.6fr 2fr;
      gap:30px;
    }
    .footer-logo{
      font-size:22px;
      font-weight:700;
      letter-spacing:.14em;
      margin-bottom:8px;
    }
    .footer-logo span{
      display:block;
      font-size:10px;
      letter-spacing:.25em;
      font-weight:400;
    }
    .footer-about{
      font-size:12px;
      line-height:1.7;
      margin-bottom:16px;
      color:#bfbfbf;
    }
    .footer-contact-item{
      display:flex;
      align-items:flex-start;
      gap:10px;
      font-size:12px;
      margin-bottom:8px;
    }
    .footer-contact-item i{
      width:22px;
      height:22px;
      border-radius:2px;
      background:#1a241d;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:11px;
      margin-top:2px;
    }
    .footer-title{
      font-size:13px;
      text-transform:uppercase;
      margin-bottom:14px;
    }
    .footer-links li{
      font-size:12px;
      margin-bottom:6px;
      color:#bfbfbf;
      cursor:pointer;
    }
    .footer-links li:hover{
      color:#fff;
    }
    .footer-links li::before{
      content:'\f105';
      font-family:'Font Awesome 6 Free';
      font-weight:900;
      margin-right:6px;
      font-size:10px;
    }
    .footer-gallery{
      display:grid;
      grid-template-columns:repeat(3,1fr);
      gap:6px;
    }
    .footer-gallery img{
      width:100%;
      height:60px;
      object-fit:cover;
      border-radius:4px;
    }
    .footer-bottom{
      border-top:1px solid #222c24;
      margin-top:10px;
      padding:12px 15px 16px;
      max-width:1200px;
      margin-left:auto;
      margin-right:auto;
      display:flex;
      align-items:center;
      justify-content:space-between;
      font-size:11px;
      color:#bfbfbf;
    }
    .footer-payments{
      display:flex;
      gap:8px;
      font-size:11px;
    }
    .footer-payments span{
      padding:4px 7px;
      border-radius:2px;
      background:#1b241d;
    }

    /* BACK TO TOP */
    .back-top{
      position:fixed;
      right:25px;
      bottom:25px;
      width:42px;
      height:42px;
      border-radius:50%;
      background: #A41B42;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-size:18px;
      cursor:pointer;
      box-shadow:0 3px 12px rgba(0,0,0,.25);
      z-index:10;
    }

    /* RESPONSIVE */
    @media (max-width:992px){
      .hero-grid,
      .content-grid{
        grid-template-columns:1fr;
      }
      .banner-row{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
      .product-grid{
        grid-template-columns:repeat(3,minmax(0,1fr));
      }
      .blog-grid{
        grid-template-columns:1fr;
      }
      .tabbed-columns{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
      .footer-inner{
        grid-template-columns:repeat(3,minmax(0,1fr));
      }
    }

    @media (max-width:768px){
      .header-inner{
        flex-direction:column;
        align-items:flex-start;
        gap:15px;
      }
      .banner-row{
        grid-template-columns:1fr;
      }
      .product-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
      .top-bar-inner{
        flex-direction:column;
        align-items:flex-start;
        gap:6px;
      }
      .nav ul{
        overflow-x:auto;
      }
      .tabbed-columns{
        grid-template-columns:1fr;
      }
      .footer-inner{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
    }

    @media (max-width:600px){
      .footer-inner{
        grid-template-columns:1fr;
      }
    }

    @media (max-width:480px){
      .product-grid{
        grid-template-columns:1fr;
      }
    }

    /* MOBILE MEGA MENU */
    @media (max-width: 768px){
      .nav ul{
        flex-direction:column;
        align-items:flex-start;
        gap:0;
      }
      .nav li{
        width:100%;
      }
      .mega-menu{
        position:static;
        box-shadow:none;
        display:none;
        padding:15px 0;
      }
      .nav li.has-mega.open .mega-menu{
        display:block;
      }
      .mega-menu-inner{
        grid-template-columns:1fr;
        gap:20px;
      }
      .mega-item-link{
        padding:6px 0;
      }
    }
  </style>
</head>
<body>

  <!-- TOP BAR -->
  <div class="top-bar">
    <div class="top-bar-inner">
      <div class="top-left">
        <span>USD <i class="fa-solid fa-caret-down"></i></span>
        <span>ENGLISH <i class="fa-solid fa-caret-down"></i></span>
      </div>
      <div class="top-right">
        <div class="social">
          <a href="#"><i class="fab fa-facebook-f"></i></a>
          <a href="#"><i class="fab fa-instagram"></i></a>
          <a href="#"><i class="fab fa-twitter"></i></a>
          <a href="#"><i class="fab fa-dribbble"></i></a>
          <a href="#"><i class="fab fa-pinterest-p"></i></a>
          <a href="#"><i class="fab fa-google-plus-g"></i></a>
          <a href="#"><i class="fab fa-behance"></i></a>
        </div>
        <a href="#">Login / Register</a>
      </div>
    </div>
  </div>

  <!-- HEADER -->
  <header class="header">
    <div class="header-inner">
      <div class="logo">
        <a href="index.php">
          <img src="logo.png" alt="Devilixirs Logo" style="height:180px; width: 130px;">
        </a>
      </div>

      <form class="search-box">
        <div class="search-category">
          All categories <i class="fa-solid fa-caret-down"></i>
        </div>
        <input class="search-input" type="text" placeholder="Search herbal products..." />
        <button class="search-button" type="submit">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
      </form>

      <div class="header-icons">
        <div class="icon-btn">
          <i class="fa-regular fa-heart"></i>
        </div>
        <div class="icon-btn">
          <i class="fa-solid fa-bag-shopping"></i>
          <span class="cart-count">0</span>
          <span>$0.00</span>
        </div>
      </div>
    </div>
  </header>

  <!-- NAV -->
  <nav class="nav">
    <div class="nav-inner">
      <ul>
        <li class="active">Home</li>

        <!-- SHOP WITH MEGA MENU -->
        <li class="has-mega">
          <span>Shop</span>
          <div class="mega-menu">
            <div class="mega-menu-inner">

              <!-- Column 1 -->
              <div>
                <div class="mega-column-title">Hair Care</div>
                <ul class="mega-list">
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 1 -</span>
                      <span class="mega-name">Herbal Oils <span class="mega-label-e">E</span></span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 2 -</span>
                      <span class="mega-name">Anti Hairfall</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 3 -</span>
                      <span class="mega-name">Growth Boost</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 4 -</span>
                      <span class="mega-name">Scalp Detox</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 5 -</span>
                      <span class="mega-name">Onion Range</span>
                    </a>
                  </li>
                </ul>
              </div>

              <!-- Column 2 -->
              <div>
                <div class="mega-column-title">Skin Care</div>
                <ul class="mega-list">
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 6 -</span>
                      <span class="mega-name">Face Wash</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 7 -</span>
                      <span class="mega-name">Serums</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 8 -</span>
                      <span class="mega-name">Moisturizers</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 9 -</span>
                      <span class="mega-name">Body Care</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 10 -</span>
                      <span class="mega-name">Sun Protection</span>
                    </a>
                  </li>
                </ul>
              </div>

              <!-- Column 3 -->
              <div>
                <div class="mega-column-title">Combos &amp; Specials</div>
                <ul class="mega-list">
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 11 -</span>
                      <span class="mega-name">Hair + Skin Kit</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 12 -</span>
                      <span class="mega-name">Bridal Combo</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 13 -</span>
                      <span class="mega-name">Travel Minis</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 14 -</span>
                      <span class="mega-name">Festival Offers</span>
                    </a>
                  </li>
                  <li>
                    <a href="#" class="mega-item-link">
                      <span class="mega-demo">Demo 15 -</span>
                      <span class="mega-name">Shopping 1 <span class="mega-label-e">E</span></span>
                    </a>
                  </li>
                </ul>
              </div>

            </div>
          </div>
        </li>

        <li>Blog</li>
        <li>Pages</li>
        <li>Portfolios</li>
        <li>Elements</li>
      </ul>
    </div>
  </nav>

  <!-- MAIN -->
  <main class="main">
    <div class="wrapper">

      <!-- HERO WITH CATEGORY SIDEBAR -->
      <section class="hero-section">
        <div class="hero-grid">
          <!-- Categories card -->
          <div>
            <div class="card categories-card">
              <div class="card-header categories-toggle">
                <div class="label">
                  <i class="fa-solid fa-bars"></i>
                  <span>Categories</span>
                </div>
                <i class="fa-solid fa-chevron-right toggle-icon"></i>
              </div>
              <div class="card-body">
                <ul class="category-list">
                  <li><i class="fa-solid fa-leaf"></i>Herbal Hair Oils</li>
                  <li><i class="fa-solid fa-seedling"></i>Hair Growth Serums</li>
                  <li><i class="fa-regular fa-face-smile"></i>Face Wash &amp; Cleansers</li>
                  <li><i class="fa-solid fa-spa"></i>Skin &amp; Body Care</li>
                  <li><i class="fa-solid fa-bottle-droplet"></i>Aloe Vera &amp; Gels</li>
                  <li><i class="fa-solid fa-mortar-pestle"></i>Ayurvedic Treatments</li>
                  <li><i class="fa-solid fa-heart-pulse"></i>Wellness &amp; Immunity</li>
                  <li><i class="fa-solid fa-gift"></i>Combo Kits &amp; Gifts</li>
                  <li><i class="fa-solid fa-lemon"></i>Essential Oils &amp; Extras</li>
                </ul>
              </div>
            </div>

            <!-- Special: Devilixirs Picks (from DB only) -->
            <div class="card">
              <div class="card-header">
                <div class="label">
                  <i class="fa-solid fa-trophy"></i>
                  <span>Devilixirs Picks</span>
                </div>
              </div>
              <div class="card-body">
                <?php if (!empty($picksProducts)): ?>
                  <?php foreach ($picksProducts as $p): ?>
                    <div class="mini-product">
                      <?php if (!empty($p['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($p['image_url']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                      <?php else: ?>
                        <div style="width:56px;height:56px;border-radius:8px;background:#eee;border:1px solid #ddd;"></div>
                      <?php endif; ?>
                      <div class="mini-info">
                        <div class="mini-name"><?php echo htmlspecialchars($p['name']); ?></div>
                        <div class="product-stars">★★★★★</div>
                        <div class="mini-price">
                          ₹<?php echo number_format((float)$p['price'], 2); ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div style="padding:10px 16px; font-size:12px; color:#777;">
                    No featured products available yet.
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Tags -->
            <div class="card">
              <div class="card-header">
                <div class="label">
                  <i class="fa-solid fa-tag"></i>
                  <span>Popular Tags</span>
                </div>
              </div>
              <div class="tags-wrap">
                <span class="tag-item">Hair fall</span>
                <span class="tag-item">Dandruff</span>
                <span class="tag-item">Growth</span>
                <span class="tag-item">Anti-acne</span>
                <span class="tag-item">Glow</span>
                <span class="tag-item">Aloe vera</span>
                <span class="tag-item">Cold pressed</span>
                <span class="tag-item">Combo</span>
              </div>
            </div>
          </div>

          <!-- Hero banner -->
          <div class="hero-banner">
            <div class="hero-text">
              <small>New Ayurvedic Range 2025</small>
              <h1>PURE HERBAL HAIR &amp; SKIN CARE</h1>
              <p>
                Nourish your routine with cold-pressed oils, aloe-based cleansers and gentle blends
                crafted to support healthy hair and glowing skin.
              </p>
              <button class="btn-primary">Shop Devilixirs</button>
            </div>
          </div>
        </div>
      </section>

      <!-- CONTENT GRID -->
      <section class="content-grid">
        <div></div> <!-- left col already used above -->

        <div>
          <!-- Top banners -->
          <div class="banner-row">
            <div class="banner-item">
              <img src="https://images.pexels.com/photos/3738335/pexels-photo-3738335.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
              <div class="banner-caption">
                <h4>Herbal Hair Ritual</h4>
                <span>Under ₹999</span>
              </div>
            </div>
            <div class="banner-item">
              <img src="https://images.pexels.com/photos/3738342/pexels-photo-3738342.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
              <div class="banner-caption">
                <h4>Glow with Aloe</h4>
                <span>Cleanse • Hydrate • Protect</span>
              </div>
            </div>
            <div class="banner-item">
              <img src="https://images.pexels.com/photos/3738344/pexels-photo-3738344.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
              <div class="banner-caption">
                <h4>Combo Kits</h4>
                <span>Save up to 30%</span>
              </div>
            </div>
          </div>

          <!-- New Products (DB only) -->
          <div class="section-header">
            <h3>New Herbal Products</h3>
            <div class="section-arrows">
              <span><i class="fa-solid fa-chevron-left"></i></span>
              <span><i class="fa-solid fa-chevron-right"></i></span>
            </div>
          </div>

          <div class="product-grid">
            <?php if (!empty($newProducts)): ?>
              <?php foreach ($newProducts as $p): ?>
                <div class="product-card">
                  <div class="product-image">
                    <?php if (!empty($p['image_url'])): ?>
                      <img src="<?php echo htmlspecialchars($p['image_url']); ?>"
                           alt="<?php echo htmlspecialchars($p['name']); ?>">
                    <?php else: ?>
                      <div style="width:100%;height:160px;border-radius:6px;background:#eee;"></div>
                    <?php endif; ?>
                  </div>
                  <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                  <div class="product-price">
                    ₹<?php echo number_format((float)$p['price'], 2); ?>
                  </div>
                  <div class="product-stars">★★★★★</div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p style="font-size:12px; color:#777;">No products available yet.</p>
            <?php endif; ?>
          </div>

          <!-- Wide banner -->
          <div class="wide-banner">
            <img src="https://images.pexels.com/photos/3738344/pexels-photo-3738344.jpeg?auto=compress&cs=tinysrgb&w=1200" alt="">
            <div class="wide-banner-text">
              <h2>We Make It Easy To Choose Clean Beauty</h2>
              <p>Plant-based formulas, no harsh chemicals – just Devilixirs.</p>
            </div>
          </div>

          <!-- Best Seller (DB only) -->
          <div style="margin-top:30px;">
            <div class="section-header">
              <h3>Best Sellers</h3>
              <div class="section-arrows">
                <span><i class="fa-solid fa-chevron-left"></i></span>
                <span><i class="fa-solid fa-chevron-right"></i></span>
              </div>
            </div>

            <div class="product-grid">
              <?php if (!empty($bestProducts)): ?>
                <?php foreach ($bestProducts as $p): ?>
                  <div class="product-card">
                    <div class="product-image">
                      <?php if (!empty($p['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($p['image_url']); ?>"
                             alt="<?php echo htmlspecialchars($p['name']); ?>">
                      <?php else: ?>
                        <div style="width:100%;height:160px;border-radius:6px;background:#eee;"></div>
                      <?php endif; ?>
                    </div>
                    <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                    <div class="product-price">
                      ₹<?php echo number_format((float)$p['price'], 2); ?>
                    </div>
                    <div class="product-stars">★★★★★</div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="font-size:12px; color:#777;">No best sellers available yet.</p>
              <?php endif; ?>
            </div>
          </div>

          <!-- LATEST BLOG SECTION (still static content) -->
          <div class="latest-blog-section">
            <div class="section-header">
              <h3>Latest Blog</h3>
              <div class="section-arrows">
                <span><i class="fa-solid fa-chevron-left"></i></span>
                <span><i class="fa-solid fa-chevron-right"></i></span>
              </div>
            </div>
            <div class="blog-grid">
              <article class="blog-card">
                <div class="blog-card-image">
                  <img src="https://images.pexels.com/photos/3738335/pexels-photo-3738335.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
                </div>
                <div class="blog-card-body">
                  <div class="blog-title">5 Herbs That Love Your Scalp</div>
                  <div class="blog-meta">
                    <span>January 7, 2025</span>
                    <span>By <a href="#">Devilixirs</a></span>
                  </div>
                  <p class="blog-excerpt">
                    Discover how neem, bhringraj, amla, fenugreek and hibiscus work together
                    to support thicker, healthier-looking hair when used consistently.
                  </p>
                </div>
              </article>

              <article class="blog-card">
                <div class="blog-card-image">
                  <img src="https://images.pexels.com/photos/3738342/pexels-photo-3738342.jpeg?auto=compress&cs=tinysrgb&w=800" alt="">
                </div>
                <div class="blog-card-body">
                  <div class="blog-title">Building A Clean Skin Routine</div>
                  <div class="blog-meta">
                    <span>January 6, 2025</span>
                    <span>By <a href="#">Devilixirs</a></span>
                  </div>
                  <p class="blog-excerpt">
                    A simple three-step ritual with sulfate-free cleansers, hydration boosters
                    and herbal moisturisers that your skin will thank you for.
                  </p>
                </div>
              </article>
            </div>
          </div>

          <!-- MULTI COLUMN TABBED PRODUCTS (from DB only) -->
          <div class="tabbed-products-section">
            <div class="tabs-bar">
              <div class="tab-ribbon">Latest Products</div>
              <div class="tab-ribbon inactive">Trendy Products</div>
              <div class="tab-ribbon inactive">Sale Products</div>
              <div class="tab-ribbon inactive">Top Rated</div>
            </div>

            <div class="tabbed-columns">
              <!-- Latest Products Column -->
              <div class="tab-column">
                <?php if (!empty($tabLatest)): ?>
                  <?php foreach ($tabLatest as $p): ?>
                    <div class="tab-column-card">
                      <div class="tab-col-product">
                        <?php if (!empty($p['image_url'])): ?>
                          <img src="<?php echo htmlspecialchars($p['image_url']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                        <?php else: ?>
                          <div style="width:46px;height:46px;border-radius:6px;background:#eee;"></div>
                        <?php endif; ?>
                        <div class="tab-col-info">
                          <div class="tab-col-name"><?php echo htmlspecialchars($p['name']); ?></div>
                          <div class="tab-col-price">₹<?php echo number_format((float)$p['price'], 2); ?></div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:11px; color:#777; padding:8px 0;">No latest products yet.</p>
                <?php endif; ?>
              </div>

              <!-- Trendy Products Column -->
              <div class="tab-column">
                <?php if (!empty($tabTrendy)): ?>
                  <?php foreach ($tabTrendy as $p): ?>
                    <div class="tab-column-card">
                      <div class="tab-col-product">
                        <?php if (!empty($p['image_url'])): ?>
                          <img src="<?php echo htmlspecialchars($p['image_url']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                        <?php else: ?>
                          <div style="width:46px;height:46px;border-radius:6px;background:#eee;"></div>
                        <?php endif; ?>
                        <div class="tab-col-info">
                          <div class="tab-col-name"><?php echo htmlspecialchars($p['name']); ?></div>
                          <div class="tab-col-price">₹<?php echo number_format((float)$p['price'], 2); ?></div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:11px; color:#777; padding:8px 0;">No trendy products yet.</p>
                <?php endif; ?>
              </div>

              <!-- Sale Products Column -->
              <div class="tab-column">
                <?php if (!empty($tabSale)): ?>
                  <?php foreach ($tabSale as $p): ?>
                    <div class="tab-column-card">
                      <div class="tab-col-product">
                        <?php if (!empty($p['image_url'])): ?>
                          <img src="<?php echo htmlspecialchars($p['image_url']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                        <?php else: ?>
                          <div style="width:46px;height:46px;border-radius:6px;background:#eee;"></div>
                        <?php endif; ?>
                        <div class="tab-col-info">
                          <div class="tab-col-name"><?php echo htmlspecialchars($p['name']); ?></div>
                          <div class="tab-col-price">₹<?php echo number_format((float)$p['price'], 2); ?></div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:11px; color:#777; padding:8px 0;">No sale products yet.</p>
                <?php endif; ?>
              </div>

              <!-- Top Rated Column -->
              <div class="tab-column">
                <?php if (!empty($tabTop)): ?>
                  <?php foreach ($tabTop as $p): ?>
                    <div class="tab-column-card">
                      <div class="tab-col-product">
                        <?php if (!empty($p['image_url'])): ?>
                          <img src="<?php echo htmlspecialchars($p['image_url']); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                        <?php else: ?>
                          <div style="width:46px;height:46px;border-radius:6px;background:#eee;"></div>
                        <?php endif; ?>
                        <div class="tab-col-info">
                          <div class="tab-col-name"><?php echo htmlspecialchars($p['name']); ?></div>
                          <div class="tab-col-price">₹<?php echo number_format((float)$p['price'], 2); ?></div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <p style="font-size:11px; color:#777; padding:8px 0;">No top rated products yet.</p>
                <?php endif; ?>
              </div>

            </div>
          </div>

        </div>
      </section>

      <!-- BRANDS ROW -->
      <div class="brands-row">
        <div class="brand-item">photodune</div>
        <div class="brand-item">themeforest</div>
        <div class="brand-item">codecanyon</div>
        <div class="brand-item">audiojungle</div>
        <div class="brand-item">activeden</div>
      </div>

    </div>
  </main>

  <!-- FOOTER -->
  <footer class="footer">
    <div class="footer-inner">
      <div>
        <div class="footer-logo">
          DEVILIXIRS
          <span>HERBAL&nbsp;CARE</span>
        </div>
        <p class="footer-about">
          Devilixirs brings together traditional herbs and clean formulations to help you build
          a simple, effective hair and skin routine.
        </p>
        <div class="footer-contact-item">
          <i class="fa-solid fa-location-dot"></i>
          <span>123 Herbal Street, Chennai, India</span>
        </div>
        <div class="footer-contact-item">
          <i class="fa-solid fa-phone"></i>
          <span>Support: +91 90000 00000</span>
        </div>
        <div class="footer-contact-item">
          <i class="fa-solid fa-envelope"></i>
          <span>care@devilixirs.com</span>
        </div>
      </div>

      <div>
        <h4 class="footer-title">Informations</h4>
        <ul class="footer-links">
          <li>About Devilixirs</li>
          <li>Our Ingredients</li>
          <li>How We Formulate</li>
          <li>New Arrivals</li>
          <li>Best Sellers</li>
          <li>Gift Cards</li>
          <li>Newsletter</li>
        </ul>
      </div>

      <div>
        <h4 class="footer-title">Links</h4>
        <ul class="footer-links">
          <li>Shop All</li>
          <li>Hair Care</li>
          <li>Skin Care</li>
          <li>Combos</li>
          <li>Track Order</li>
          <li>Terms &amp; Conditions</li>
          <li>Privacy Policy</li>
        </ul>
      </div>

      <div>
        <h4 class="footer-title">Customer Service</h4>
        <ul class="footer-links">
          <li>Contact Us</li>
          <li>Returns &amp; Refunds</li>
          <li>Shipping Info</li>
          <li>FAQs</li>
          <li>My Account</li>
          <li>Order History</li>
        </ul>
      </div>

      <div>
        <h4 class="footer-title">Gallery</h4>
        <div class="footer-gallery">
          <img src="https://images.pexels.com/photos/3738341/pexels-photo-3738341.jpeg?auto=compress&cs=tinysrgb&w=400" alt="">
          <img src="https://images.pexels.com/photos/3738343/pexels-photo-3738343.jpeg?auto=compress&cs=tinysrgb&w=400" alt="">
          <img src="https://images.pexels.com/photos/3738344/pexels-photo-3738344.jpeg?auto=compress&cs=tinysrgb&w=400" alt="">
          <img src="https://images.pexels.com/photos/3738338/pexels-photo-3738338.jpeg?auto=compress&cs=tinysrgb&w=400" alt="">
          <img src="https://images.pexels.com/photos/3738339/pexels-photo-3738339.jpeg?auto=compress&cs=tinysrgb&w=400" alt="">
          <img src="https://images.pexels.com/photos/3738340/pexels-photo-3738340.jpeg?auto=compress&cs=tinysrgb&w=400" alt="">
          <img src="https://images.pexels.com/photos/3738335/pexels-photo-3738335.jpeg?auto=compress&cs=tinysrgb&w=400" alt="">
          <img src="https://images.pexels.com/photos/3738336/pexels-photo-3738336.jpeg?auto=compress&cs=tinysrgb&w=400" alt="">
          <img src="https://images.pexels.com/photos/3738342/pexels-photo-3738342.jpeg?auto=compress&cs=tinysrgb&w=400" alt="">
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <span>Copyright © 2025 <strong>Devilixirs</strong>. All Rights Reserved.</span>
      <div class="footer-payments">
        <span>UPI</span>
        <span>Rupay</span>
        <span>MasterCard</span>
        <span>Visa</span>
      </div>
    </div>
  </footer>

  <!-- Back to top button (static icon) -->
  <div class="back-top">
    <i class="fa-solid fa-angle-up"></i>
  </div>

  <!-- Simple JS -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const catCard = document.querySelector('.categories-card');
      const toggleHeader = document.querySelector('.categories-toggle');

      if (catCard && toggleHeader) {
        toggleHeader.addEventListener('click', function () {
          catCard.classList.toggle('collapsed');
        });
      }

      // mobile mega menu
      const shopMenu = document.querySelector('.nav li.has-mega');
      if(window.innerWidth <= 768 && shopMenu){
        shopMenu.addEventListener('click', function(e){
          e.stopPropagation();
          this.classList.toggle('open');
        });
      }
    });
  </script>

</body>
</html>
