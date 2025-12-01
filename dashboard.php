<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Flower 'n GO | Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Segoe UI,sans-serif}

/* BODY */
body{
  background:#0d0a08;
  color:white;
}

/* HEADER */
.header{
  background:#121212;
  padding:15px 40px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  position:sticky;
  top:0;
  z-index:1000;
}

.search{
 padding:8px 14px;
 border-radius:20px;
 border:none;
 outline:none;
}

.logo{
  font-size:20px;
  color:#D4AF37;
}

.nav a{
  color:white;
  margin-left:20px;
  text-decoration:none;
}
.nav a:hover{color:#D4AF37}

.logout{
  background:#D4AF37;
  color:black;
  padding:6px 14px;
  border-radius:16px;
}

/* HERO */
.hero{
  padding:60px 80px;
  text-align:center;
}
.hero h1{
  font-size:38px;
  color:#D4AF37;
}
.hero p{
  color:#ccc;
  margin-top:10px;
}

/* ITEMS */
.items{
  padding:60px 80px;
}

.items h2{
  font-size:28px;
  margin-bottom:25px;
  border-left:4px solid #D4AF37;
  padding-left:12px;
}

.grid{
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:25px;
}

.card{
  background:#1c120d;
  border-radius:16px;
  overflow:hidden;
  box-shadow:0 10px 25px black;
  transition:.3s;
}

.card:hover{
  transform:translateY(-8px);
  box-shadow:0 12px 35px rgba(212,175,55,.4);
}

.card img{
  width:100%;
  height:200px;
  object-fit:cover;
}

.card-content{
  padding:15px;
}

.card-content h3{color:white}
.card-content p{
  color:#aaa;
  margin:6px 0;
  font-size:13px;
}

.price{
  color:#D4AF37;
  font-weight:bold;
  margin:6px 0;
}

.buy{
  width:100%;
  background:#D4AF37;
  border:none;
  padding:9px;
  margin-top:8px;
  border-radius:20px;
  cursor:pointer;
}

.buy:hover{
  background:white;
}

.grid {
  grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
}

/* FOOTER */
.footer{
  text-align:center;
  background:#1b120d;
  color:#bbb;
  padding:18px;
  margin-top:50px;
}

@media(max-width:850px){
  .hero,.items{padding:40px}
}
</style>
</head>

<body>

<?php
include "db.php";

$user_id = $_SESSION['user_id'];
$query = mysqli_query($conn, "SELECT name FROM users WHERE id='$user_id'");
$row = mysqli_fetch_assoc($query);
$user_name = $row['name'];
?>

<!-- HEADER -->
<div class="header">
  <div class="logo">🌸 Flower 'n GO</div>

  <div class="nav">
    <form method="GET" action="dashboard.php">
  <input type="text" name="search" placeholder="Search flowers..." class="search">
</form>
    <span>Hello, <strong><?php echo htmlspecialchars($user_name); ?></strong></span>
    <a href="profile.php">Profile</a>
    <a href="view_cart.php">Cart</a>
    <a href="orders.php">My Orders</a>
    <a href="logout.php" class="logout">Logout</a>
  </div>
</div>



<!-- WELCOME -->
<section class="hero">
<h1>Welcome to Flower 'n GO</h1>
<p>Select your favorite flowers and customize your bouquet.</p>
</section>


<!-- ITEMS -->
<section class="items">

<h2>Available Flowers</h2>

<?php
include "db.php";

// fetch active products
$sql = "SELECT * FROM products WHERE is_active = 1";
$result = mysqli_query($conn, $sql);

while($row = mysqli_fetch_assoc($result)):
?>

<div class="card">
  <img src="uploads/<?= $row['image'] ?>">

  <div class="card-content">
    <h3><?= htmlspecialchars($row['name']) ?></h3>
    <p><?= htmlspecialchars($row['description']) ?></p>
    <div class="price">₱<?= number_format($row['price'],2) ?></div>
  
    <form method="POST" action="add_to_cart.php">
    <input type="hidden" name="product_id" value="<?= $row['id'] ?>">

    <input type="number" 
          name="quantity" 
          value="1" 
          min="1"
          style="width:60px;text-align:center;margin-bottom:5px;border-radius:8px;border:none;padding:4px;">

    <button class="buy">Add to Cart</button>
  </form>

  </div>
</div>

<?php endwhile; ?>

</div>
</section>


<!-- FOOTER -->
<div class="footer">
© 2025 Flower 'n GO — All Rights Reserved
</div>

</body>
</html>
