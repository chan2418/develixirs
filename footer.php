  <style>
    /* FOOTER STYLES */
    .footer{
      background:#111;
      color:#e0e0e0;
      padding:60px 0 20px;
      margin-top:60px;
      font-family: 'Poppins', sans-serif;
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
      display:inline-block;
      margin-bottom:8px;
    }
    .footer-about{
      font-size:14px;
      line-height:1.6;
      margin-bottom:20px;
      color:#ccc;
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
      font-size:16px;
      font-weight: 600;
      text-transform:uppercase;
      margin-bottom:20px;
      color: #fff;
      letter-spacing: 0.05em;
      position: relative;
      padding-bottom: 10px;
    }
    .footer-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: 0;
        width: 40px;
        height: 2px;
        background: #D4AF37; /* Gold color */
    }
    .footer-links{
      list-style: none;
      padding: 0;
      margin: 0;
    }
    .footer-links li{
      font-size:14px;
      margin-bottom:10px;
      color:#ccc;
      cursor:pointer;
      transition: all 0.3s ease;
    }
    .footer-links li:hover{
      color:#fff;
      padding-left: 5px;
    }
    .footer-links li a:hover{
      color:#D4AF37 !important;
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
    .footer-social{
      display:flex;
      gap:10px;
      margin-top:15px;
    }
    .footer-social a{
      width:32px;
      height:32px;
      border-radius:50%;
      background:#222;
      color:#fff;
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:14px;
      transition:0.3s;
    }
    .footer-social a:hover{
      background:#D4AF37;
      color:#fff;
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
      bottom:80px; /* Adjusted to be above mobile nav if present */
      width:42px;
      height:42px;
      border-radius:50%;
      background:#A41B42;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-size:18px;
      cursor:pointer;
      box-shadow:0 3px 12px rgba(0,0,0,.25);
      z-index:999;
      opacity: 0;
      visibility: hidden;
      transition: all 0.3s ease;
    }
    .back-top.visible{
      opacity: 1;
      visibility: visible;
    }

    /* MOBILE BOTTOM NAV */
    .mobile-bottom-nav{
      display:none;
      position:fixed;
      bottom:0;
      left:0;
      width:100%;
      background:#fff;
      border-top:1px solid #eee;
      padding:10px 0;
      z-index:1000;
      justify-content:space-around;
      box-shadow:0 -2px 10px rgba(0,0,0,0.05);
    }
    .mobile-bottom-nav a{
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:4px;
      color:#666;
      font-size:10px;
      text-decoration:none;
    }
    .mobile-bottom-nav a i{
      font-size:18px;
    }
    .mobile-bottom-nav a:hover, .mobile-bottom-nav a.active{
      color:#A41B42;
    }

    /* RESPONSIVE */
    @media (max-width:992px){
      .footer-inner{
        grid-template-columns:repeat(3,minmax(0,1fr));
      }
    }
    @media (max-width:768px){
      .footer-inner{
        grid-template-columns:repeat(2,minmax(0,1fr));
      }
      .mobile-bottom-nav{
        display:flex;
      }
      .footer{
        padding-bottom:80px; /* Space for bottom nav */
      }
    }
    @media (max-width:600px){
      .footer-inner{
        grid-template-columns:1fr;
      }
      .footer-bottom{
        flex-direction:column;
        gap:10px;
        text-align:center;
      }
    }
</style>

<!-- FOOTER -->
  <footer class="footer">
    <div class="footer-inner">
      <div>
        <a href="index.php" class="footer-logo">
          <img src="develixir-logo.png" alt="DevElixir Logo" style="height: 50px; margin-bottom: 15px;">
        </a>
        <p>DevElixir Natural Cosmetics - Pure, natural, and effective skincare solutions for you and your family.</p>
        <div class="footer-social">
          <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#"><i class="fa-brands fa-twitter"></i></a>
          <a href="#"><i class="fa-brands fa-instagram"></i></a>
          <a href="#"><i class="fa-brands fa-pinterest"></i></a>
        </div>
      </div>

      <div>
        <h4 class="footer-title">Informations</h4>
        <ul class="footer-links">
          <li><a href="about.php" style="color:inherit; text-decoration:none;">About Devilixirs</a></li>
          <li><a href="#" style="color:inherit; text-decoration:none;">Our Ingredients</a></li>
          <li><a href="#" style="color:inherit; text-decoration:none;">How We Formulate</a></li>
          <li><a href="product.php?sort=new" style="color:inherit; text-decoration:none;">New Arrivals</a></li>
          <li><a href="product.php?sort=best" style="color:inherit; text-decoration:none;">Best Sellers</a></li>
          <li><a href="#" style="color:inherit; text-decoration:none;">Gift Cards</a></li>
          <li><a href="#" style="color:inherit; text-decoration:none;">Newsletter</a></li>
        </ul>
      </div>

      <div>
        <h4 class="footer-title">Links</h4>
        <ul class="footer-links">
          <li><a href="product.php" style="color:inherit; text-decoration:none;">Shop All</a></li>
          <li><a href="product.php?cat=1" style="color:inherit; text-decoration:none;">Hair Care</a></li>
          <li><a href="product.php?cat=2" style="color:inherit; text-decoration:none;">Skin Care</a></li>
          <li><a href="product.php?cat=3" style="color:inherit; text-decoration:none;">Combos</a></li>
          <li><a href="track-order.php" style="color:inherit; text-decoration:none;">Track Order</a></li>
          <li><a href="terms.php" style="color:inherit; text-decoration:none;">Terms &amp; Conditions</a></li>
          <li><a href="privacy.php" style="color:inherit; text-decoration:none;">Privacy Policy</a></li>
        </ul>
      </div>

      <div>
        <h4 class="footer-title">Contact Us</h4>
        <ul class="footer-links">
          <li>DevElixir Natural Cosmetics ™</li>
          <li>No:6, 3rd Cross Street,</li>
          <li>Kamatchiamman Garden, Sethukkarai,</li>
          <li>Gudiyatham-632602, Vellore, Tamilnadu</li>
          <li>INDIA</li>
          <li>Email: sales@develixirs.com</li>
          <li>Phone: +91 95006 50454</li>
        </ul>
      </div>

      <div>
        <h4 class="footer-title">Gallery</h4>
        <div class="footer-gallery">
          <img src="assets/uploads/products/1167485b8dbb.jpg" alt="Product">
          <img src="assets/uploads/products/c28997524100.jpg" alt="Product">
          <img src="assets/uploads/products/84b062f7d8d2.jpg" alt="Product">
          <img src="assets/uploads/products/fb15b8e998ea.jpg" alt="Product">
          <img src="assets/uploads/products/8e2202201f76.jpg" alt="Product">
          <img src="assets/uploads/products/459a32ced2ab.jpg" alt="Product">
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <span>Copyright © 2025 <strong>DevElixir Natural Cosmetics</strong>. All Rights Reserved.</span>
      <div class="footer-payments">
        <span>UPI</span>
        <span>Rupay</span>
        <span>MasterCard</span>
        <span>Visa</span>
      </div>
    </div>
  </footer>

  <!-- Back to top button -->
  <div class="back-top">
    <i class="fa-solid fa-angle-up"></i>
  </div>

  <!-- MOBILE BOTTOM NAV -->
  <nav class="mobile-bottom-nav">
    <a href="index.php">
      <i class="fa-solid fa-house"></i>
      <span>Home</span>
    </a>
    <a href="product.php">
      <i class="fa-solid fa-store"></i>
      <span>Products</span>
    </a>
    <a href="cart.php">
      <i class="fa-solid fa-cart-shopping"></i>
      <span>Cart</span>
    </a>
    <a href="my-profile.php?tab=wishlist">
      <i class="fa-regular fa-heart"></i>
      <span>Wishlist</span>
    </a>
  </nav>

  <script>
    // Back to top
    const backTop = document.querySelector('.back-top');
    if(backTop){
      window.addEventListener('scroll', () => {
        if(window.scrollY > 300) backTop.classList.add('visible');
        else backTop.classList.remove('visible');
      });
      backTop.addEventListener('click', () => {
        window.scrollTo({top:0, behavior:'smooth'});
      });
    }
  </script>
  <script src="assets/js/navbar.js"></script>
