<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Flower 'n GO</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="logo">
    🌸 <span class="logo-text">Flower 'n GO</span>
  </div>
  <div class="nav-links">
    <a href="#">Home</a>
    <a href="#products">Products</a>
    <a href="#map">Locations</a>
    <a href="#footer">Contact</a>
  </div>
  <div class="auth-buttons">
  <?php if(isset($_SESSION['user'])): ?>
      <span style="color:#D4AF37">Hello, <?=$_SESSION['user']?></span>
      <a href="order.php" class="btn btn-order">Order Now</a>
      <a href="logout.php" class="btn btn-signin">Logout</a>
  <?php else: ?>
      <button class="btn btn-signin" onclick="location.href='signin.php'">Sign In</button>
<button class="btn btn-order" onclick="location.href='signup.php'">Sign Up</button>


  <?php endif ?>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
<div class="hero-content">
  <p class="tagline">Customize your bouquet</p>
  <h1 class="hero-title">Flower 'n GO<br><span>Crafted with Passion</span></h1>
  <p class="hero-subtitle">
    Fresh hand-picked blooms designed for moments that matter.
  </p>
  <div class="cta-buttons">
    <a href="#products" class="btn btn-primary">Explore Flowers</a>
    <a href="order.php" class="btn btn-secondary">Visit Shop</a>
  </div>
</div>

<div class="hero-image">
  <div class="coffee-cup">
    <img src="frontpageflower.jpg" alt="bouquet">
    <div class="steam-effect"></div>
  </div>
</div>
</section>

<!-- PRODUCTS -->
<section class="products-showcase" id="products">
<div class="product-grid">

<article class="product-card" onclick="location.href='order.php'">
<img src="Sunflower.jpg">
<span class="tag best">BESTSELLER</span>
<div class="product-info">
<h3>SUNFLOWER</h3>
<p>Bright cheerful bloom with warm summer notes.</p>
<div class="product-bottom">
<span class="price">₱2300.99</span>
<div class="stars">★★★★★</div>
</div>
</div>
</article>

<article class="product-card highlight" onclick="location.href='order.php'">
<img src="Stargazer.jpg">
<div class="product-info">
<h3>STARGAZER</h3>
<p>Romantic lilies with a sweet fragrance.</p>
<div class="product-bottom">
<span class="price">₱5400.99</span>
<div class="stars">★★★★☆</div>
</div>
</div>
</article>

<article class="product-card" onclick="location.href='order.php'">
<img src="Tulips.jpg">  
<span class="tag new">NEW</span>
<div class="product-info">
<h3>TULIPS</h3>
<p>Classic elegance with vibrant colors.</p>
<div class="product-bottom">
<span class="price">₱2500.99</span>
<div class="stars">★★★★★</div>
</div>
</div>
</article>

</div>
</section>

<!-- FOOTER -->
<footer class="footer" id="footer">

<div class="footer-container">

<!-- ABOUT -->
<div class="footer-col">
<h3>About Flower ’n GO</h3>
<span class="footer-line"></span>
<p>Creating handmade floral happiness with passion and care.</p>
<div class="socials">
<a href="https://facebook.com" target="_blank">f</a>
<a href="https://instagram.com" target="_blank">📷</a>
<a href="https://twitter.com" target="_blank">🐦</a>
<a href="https://pinterest.com" target="_blank">Ⓟ</a>
</div>
</div>

<!-- LINKS -->
<div class="footer-col">
<h3>Quick Links</h3>
<span class="footer-line"></span>
<ul>
<li><a href="#products">Ready bouquets</a></li>
<li><a href="#">How to order</a></li>
<li><a href="#">Sustainability</a></li>
<li><a href="#">Wholesale</a></li>
</ul>
</div>

<!-- CONTACT -->
<div class="footer-col">
<h3>Contact</h3>
<span class="footer-line"></span>
<ul>
<li>📍 Polangui, Albay</li>
<li>📞 0966-395-6793</li>
<li>✉ flowerngo@email.com</li>
<li>🕘 Mon-Sat 7am – 8pm</li>
</ul>
</div>

</div>

<!-- MAP -->
<div class="map" id="map">
<iframe src="https://www.google.com/maps?q=Polangui,Albay&output=embed"></iframe>
</div>

<!-- NEWSLETTER -->
<div class="newsletter">
<h3>Join Our Newsletter</h3>
<p>Exclusive discounts & updates</p>
<form method="POST" action="subscribe.php">
<input type="email" name="email" required placeholder="Your email">
<button name="subscribe">Subscribe</button>
</form>
</div>

<!-- BOTTOM -->
<div class="footer-bottom">
© 2025 Flower ’n GO

</div>

</footer>

<script src="script.js"></script>
</body>
</html>
