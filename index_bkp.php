<?php
// dove-shop-full.php - Full professional single-file PHP template built from scratch
// Uses uploaded screenshot as sample assets. Replace these paths with real images if needed.
$asset = '/mnt/data/Screenshot 2025-11-20 at 1.14.41 PM.jpeg';
// Banners (use multiple copies of the uploaded asset for validation)
$banners = [$asset, $asset, $asset];
// Sample product set (6 items) using the same placeholder for quick validation
$products = [
    ['id'=>101,'title'=>'Handmade Knit Hat','price'=>'$150.00','img'=>$asset,'label'=>'NEW','desc'=>'Soft wool knit hat.'],
    ['id'=>102,'title'=>'Woven Basket','price'=>'$85.00','img'=>$asset,'label'=>'SALE','desc'=>'Handwoven basket for decor.'],
    ['id'=>103,'title'=>'Ceramic Bowl','price'=>'$42.00','img'=>$asset,'label'=>'POPULAR','desc'=>'Glazed ceramic bowl.'],
    ['id'=>104,'title'=>'Decorative Plate','price'=>'$120.00','img'=>$asset,'label'=>'NEW','desc'=>'Hand painted plate.'],
    ['id'=>105,'title'=>'Cozy Throw','price'=>'$99.00','img'=>$asset,'label'=>'LIMITED','desc'=>'Warm cotton throw.'],
    ['id'=>106,'title'=>'Artisan Mug','price'=>'$34.00','img'=>$asset,'label'=>'BEST','desc'=>'Stoneware artisan mug.']
];
// Featured and blog placeholders
$featured = array_slice($products,0,3);
$blogs = [
    ['title'=>'Tips to Style Handmade Pieces','img'=>$asset,'date'=>'Jan 7, 2025'],
    ['title'=>'Care for Natural Materials','img'=>$asset,'date'=>'Jan 3, 2025']
];
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dove Handmade Shop — Full Design</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
<style>
:root{--accent:#3aa18a;--accent-700:#2f8f7f;--muted:#8f9b98;--bg:#f6f8f6;--card:#fff}
*{box-sizing:border-box}
body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Arial;background:var(--bg);color:#223232}
.container{max-width:1280px;margin:0 auto;padding:0 20px}
.topbar{background:var(--accent);color:#fff;padding:10px 0;font-size:14px}
.topbar .inner{display:flex;align-items:center;gap:12px}
.header{display:flex;align-items:center;gap:24px;padding:22px 0}
.logo{display:flex;align-items:center;gap:14px}
.logo img{width:74px;height:74px;border-radius:8px;object-fit:cover}
.brand{font-weight:700;font-family:Montserrat}
.searchbar{flex:1;display:flex;justify-content:center}
.searchbox{width:720px;background:#fff;padding:12px;border-radius:6px;border:1px solid #e8f0ee;display:flex;align-items:center;gap:8px}
.searchbox input{flex:1;border:0;outline:none;padding:8px 10px}
.actions{display:flex;gap:14px;align-items:center}
nav{background:var(--accent);border-radius:6px;padding:10px;margin-bottom:14px}
nav ul{display:flex;gap:22px;list-style:none;margin:0;padding:0;color:#fff;font-weight:700}

.main-grid{display:grid;grid-template-columns:260px 1fr;gap:28px;margin-top:18px}
.card{background:var(--card);border-radius:10px;padding:12px;border:1px solid rgba(0,0,0,0.04)}

/* Sidebar */
.sidebar .widget + .widget{margin-top:18px}
.cat-list li{padding:12px 0;border-bottom:1px dashed rgba(0,0,0,0.06);display:flex;align-items:center;gap:12px}
.cat-icon{width:18px;height:18px;display:inline-block;background:#e9f2ef;border-radius:4px}

/* Hero area */
.hero{display:flex;align-items:center;gap:24px;padding:40px;border-radius:10px;overflow:hidden}
.hero-copy{flex:1}
.hero-copy .eyebrow{color:var(--muted);font-size:13px;font-weight:600}
.hero-copy h1{font-family:Montserrat;font-size:36px;margin:10px 0}
.hero-copy p{color:var(--muted);line-height:1.6}
.btn{background:var(--accent-700);color:#fff;padding:11px 16px;border-radius:6px;font-weight:700;display:inline-block;margin-top:14px}
.hero-image{flex:1}
.hero-image img{width:100%;height:320px;object-fit:cover;border-radius:8px}

/* promos */
.promos{display:flex;gap:16px;margin-top:20px}
.promo{flex:1;padding:20px;border-radius:8px;text-align:center}

/* products */
.section-title{display:flex;justify-content:space-between;align-items:center}
.product-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:12px}
.product{border-radius:8px;overflow:hidden;border:1px solid rgba(0,0,0,0.04);background:#fff}
.product .thumb{height:200px;overflow:hidden}
.product img{width:100%;height:100%;object-fit:cover}
.product-body{padding:12px}
.product h4{margin:6px 0 4px;font-size:16px}
.price{color:var(--accent-700);font-weight:700}
.badge{display:inline-block;background:var(--accent);color:#fff;padding:6px 10px;border-radius:6px;font-weight:700;position:absolute;left:12px;top:12px}
.card-wrap{position:relative}
.product .actions{display:flex;gap:8px;margin-top:10px}
.btn-outline{padding:9px 12px;border-radius:6px;border:1px solid #e6f0ec;background:#fff;font-weight:700}

/* blog */
.blogs{display:grid;grid-template-columns:repeat(2,1fr);gap:18px;margin-top:16px}
.blog-item img{width:100%;height:140px;object-fit:cover;border-radius:8px}
.blog-item h5{margin:10px 0 6px}
.meta{color:var(--muted);font-size:13px}

footer{margin-top:28px;padding:18px 0;color:var(--muted)}

/* responsive */
@media (max-width:1100px){.searchbox{width:520px}}
@media (max-width:900px){.main-grid{grid-template-columns:1fr}.product-grid{grid-template-columns:repeat(2,1fr)}.promos{flex-direction:column}}
@media (max-width:600px){.searchbox{width:100%}.product-grid{grid-template-columns:1fr}.hero{flex-direction:column}.hero-image img{height:220px}}
</style>

<script>
// small carousel auto-rotate using transform
document.addEventListener('DOMContentLoaded',function(){
  const slides = document.querySelectorAll('.slide');
  if(!slides || slides.length===0) return;
  const track = document.querySelector('.slides');
  let idx = 0;
  setInterval(()=>{ idx = (idx+1) % slides.length; track.style.transform = 'translateX(' + (-idx*100) + '%)'; },4500);
});
</script>
</head>
<body>
  <div class="topbar"><div class="container"><div class="inner">USD ▾ &nbsp; ENGLISH ▾ <div style="margin-left:auto;font-weight:600">Login / Register • Cart (0)</div></div></div></div>

  <div class="container">
    <div class="header">
      <div class="logo"><img src="<?= $asset ?>" alt="logo"><div><div class="brand">DOVE</div><small style="color:var(--muted)">HANDMADE SHOP</small></div></div>
      <div class="searchbar">
        <div class="searchbox">
          <select style="border:0;background:transparent;padding:8px;font-weight:600">
            <option>All categories</option>
          </select>
          <input placeholder="Search ...">
          <button style="background:var(--accent-700);color:#fff;border:0;padding:10px 12px;border-radius:6px;font-weight:700">Search</button>
        </div>
      </div>
      <div class="actions">
        <div style="color:var(--muted)">♡</div>
        <div style="color:var(--muted)">🛒 $0.00</div>
      </div>
    </div>

    <nav><ul><li>HOME</li><li>SHOP</li><li>BLOG</li><li>PAGES</li><li>PORTFOLIOS</li><li>ELEMENTS</li></ul></nav>

    <div class="main-grid">
      <aside class="sidebar">
        <div class="card">
          <div style="display:flex;align-items:center;gap:10px"><div style="background:var(--accent);width:12px;height:12px;border-radius:3px"></div><strong>Categories</strong></div>
          <ul class="cat-list" style="margin-top:12px;list-style:none;padding:0">
            <li><span class="cat-icon"></span> Mobile</li>
            <li><span class="cat-icon"></span> Smartphones Accessories</li>
            <li><span class="cat-icon"></span> Electronics</li>
            <li><span class="cat-icon"></span> Computers & Networking</li>
            <li><span class="cat-icon"></span> Car Accessories</li>
            <li><span class="cat-icon"></span> Lights & Lighting</li>
            <li><span class="cat-icon"></span> Home & Office</li>
            <li><span class="cat-icon"></span> Sports & Outdoors</li>
          </ul>
        </div>

        <div class="card" style="margin-top:18px">
          <strong style="color:var(--accent)">Special</strong>
          <?php foreach(array_slice($products,0,3) as $p): ?>
            <div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-top:1px dashed rgba(0,0,0,0.04)">
              <img src="<?= $p['img'] ?>" style="width:64px;height:64px;object-fit:cover;border-radius:6px">
              <div>
                <div style="font-weight:700"><?= $p['title'] ?></div>
                <div style="color:var(--muted)"><?= $p['price'] ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="card" style="margin-top:18px">
          <strong style="color:var(--accent)">Featured Products</strong>
          <?php foreach($featured as $f): ?>
            <div style="display:flex;gap:12px;align-items:center;padding:12px 0;border-top:1px dashed rgba(0,0,0,0.04)">
              <img src="<?= $f['img'] ?>" style="width:56px;height:56px;object-fit:cover;border-radius:6px">
              <div>
                <div style="font-weight:700"><?= $f['title'] ?></div>
                <div style="color:var(--muted)"><?= $f['price'] ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      </aside>

      <main>
        <div class="card">
          <div class="carousel">
            <div class="slides">
              <?php foreach($banners as $b): ?>
                <div class="slide">
                  <div class="hero-copy">
                    <div class="eyebrow">NEW ARRIVALS 2025</div>
                    <h1>HAND MADE &amp; CRAFT</h1>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curated artisan products for your home.</p>
                    <a class="btn" href="#">Shop now !</a>
                  </div>
                  <div class="hero-image"><img src="<?= $b ?>" alt="banner"></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <div class="promos">
          <div class="promo card"><strong>How to decorate</strong><div style="color:var(--muted)">Birthday gift box</div></div>
          <div class="promo card"><strong>Attractive shape</strong><div style="color:var(--muted)">New up to 30% off</div></div>
          <div class="promo card"><strong>Workshops</strong><div style="color:var(--muted)">Join our classes</div></div>
        </div>

        <section style="margin-top:20px">
          <div class="card">
            <div class="section-title">
              <h3 style="color:var(--accent);margin:0">New Products</h3>
              <div style="color:var(--muted)">Showing <?= count($products) ?> items</div>
            </div>

            <div class="product-grid">
              <?php foreach($products as $p): ?>
                <div class="product">
                  <div class="card-wrap">
                    <div class="thumb"><img src="<?= $p['img'] ?>" alt="product"></div>
                    <div style="padding:12px">
                      <h4><?= htmlspecialchars($p['title']) ?></h4>
                      <div class="price"><?= $p['price'] ?></div>
                      <div class="meta"><?= htmlspecialchars($p['desc']) ?></div>
                      <div class="actions">
                        <button class="btn-outline">Add to cart</button>
                        <button class="quickview">Quick view</button>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </section>

        <div class="card blogs">
          <?php foreach($blogs as $b): ?>
            <div class="blog-item">
              <img src="<?= $b['img'] ?>" alt="blog">
              <h5><?= $b['title'] ?></h5>
              <div class="meta"><?= $b['date'] ?></div>
            </div>
          <?php endforeach; ?>
        </div>

      </main>
    </div>

    <footer>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0">
        <div>&copy; <?= date('Y') ?> Dove Handmade Shop</div>
        <div style="color:var(--muted)">Design ready for validation — replace placeholders with final images to complete</div>
      </div>
    </footer>
  </div>
</body>
</html>