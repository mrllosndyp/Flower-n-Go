-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 04, 2025 at 05:50 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `flower_shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`, `image`, `created_at`) VALUES
(1, 'Roses', 'Classic roses in various colors and arrangements', 'https://via.placeholder.com/300x300?text=Roses', '2025-11-21 14:22:18'),
(2, 'Tulips', 'Elegant tulips for any occasion', 'https://via.placeholder.com/300x300?text=Tulips', '2025-11-21 14:22:18'),
(3, 'Sunflowers', 'Bright and cheerful sunflower arrangements', 'https://via.placeholder.com/300x300?text=Sunflowers', '2025-11-21 14:22:18'),
(4, 'Mixed Bouquets', 'Beautiful mixed flower arrangements', 'https://via.placeholder.com/300x300?text=Mixed+Flowers', '2025-11-21 14:22:18'),
(5, 'Peonies', 'Luxurious peonies for special occasions', 'https://via.placeholder.com/300x300?text=Peonies', '2025-11-21 14:22:18');

-- --------------------------------------------------------

--
-- Table structure for table `customizations`
--

CREATE TABLE `customizations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('wrapper','addon','size') NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customizations`
--

INSERT INTO `customizations` (`id`, `name`, `type`, `price`, `description`, `image`, `is_active`, `created_at`) VALUES
(1, 'Paper Wrapper', 'wrapper', 2.00, 'Elegant paper wrapping', 'https://via.placeholder.com/150x150?text=Paper+Wrap', 1, '2025-11-21 14:22:18'),
(2, 'Fabric Wrap', 'wrapper', 3.50, 'Soft fabric wrapping', 'https://via.placeholder.com/150x150?text=Fabric+Wrap', 1, '2025-11-21 14:22:18'),
(3, 'Kraft Paper', 'wrapper', 1.50, 'Natural kraft paper wrapping', 'https://via.placeholder.com/150x150?text=Kraft+Wrap', 1, '2025-11-21 14:22:18'),
(4, 'Daisies', 'addon', 2.50, 'Beautiful daisies', 'https://via.placeholder.com/150x150?text=Daisies', 1, '2025-11-21 14:22:18'),
(5, 'Baby\'s Breath', 'addon', 1.50, 'Delicate baby\'s breath', 'https://via.placeholder.com/150x150?text=Baby\'s+Breath', 1, '2025-11-21 14:22:18'),
(6, 'Eucalyptus', 'addon', 2.00, 'Fragrant eucalyptus', 'https://via.placeholder.com/150x150?text=Eucalyptus', 1, '2025-11-21 14:22:18'),
(7, 'Ribbon', 'addon', 1.00, 'Decorative ribbon', 'https://via.placeholder.com/150x150?text=Ribbon', 1, '2025-11-21 14:22:18'),
(8, 'Small', 'size', 0.00, 'Small bouquet', 'https://via.placeholder.com/150x150?text=Small', 1, '2025-11-21 14:22:18'),
(9, 'Medium', 'size', 5.00, 'Medium bouquet', 'https://via.placeholder.com/150x150?text=Medium', 1, '2025-11-21 14:22:18'),
(10, 'Large', 'size', 10.00, 'Large bouquet', 'https://via.placeholder.com/150x150?text=Large', 1, '2025-11-21 14:22:18');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) DEFAULT NULL,
  `status` enum('to_ship','to_receive','completed','cancelled') DEFAULT 'to_ship',
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `payment` varchar(50) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `delivery_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `delivery_time` varchar(50) DEFAULT NULL,
  `instructions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `address`, `phone`, `payment`, `city`, `postal_code`, `country`, `notes`, `order_date`, `delivery_date`, `created_at`, `updated_at`, `delivery_time`, `instructions`) VALUES
(1, 2, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-02 06:20:07', NULL, '2025-12-02 06:20:07', '2025-12-02 06:20:07', NULL, NULL),
(2, 2, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-02 06:57:34', NULL, '2025-12-02 06:57:34', '2025-12-02 06:57:34', NULL, NULL),
(3, 2, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-02 08:18:10', NULL, '2025-12-02 08:18:10', '2025-12-02 08:18:10', NULL, NULL),
(4, 2, 28499.94, 'to_ship', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-12-03 18:54:24', NULL, '2025-12-03 18:54:24', '2025-12-03 18:54:24', NULL, NULL),
(5, 2, 34198.93, 'to_ship', 'Daraga', '09663956793', 'cod', 'Daraga', '4501', 'PHILIPPINES', NULL, '2025-12-04 14:03:47', '2025-12-05', '2025-12-04 14:03:47', '2025-12-04 14:03:47', '10am-1pm', '');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `customization_ids` text DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `customization_ids`, `quantity`, `price`, `total_price`) VALUES
(1, 1, 1, NULL, 2, NULL, NULL),
(2, 1, 3, NULL, 1, NULL, NULL),
(3, 1, 2, NULL, 1, NULL, NULL),
(4, 3, 1, NULL, 3, NULL, NULL),
(5, 4, 1, NULL, 2, 2229.99, NULL),
(6, 4, 7, NULL, 1, 12334.99, NULL),
(7, 4, 6, NULL, 1, 5024.99, NULL),
(8, 4, 8, NULL, 2, 3339.99, NULL),
(9, 5, 3, NULL, 6, 4328.99, NULL),
(10, 5, 4, NULL, 1, 8024.99, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `image`, `category_id`, `stock`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Red Roses Bouquet', 'Beautiful red roses for your special someone', 2229.99, 'https://i.pinimg.com/736x/68/e5/59/68e55968b9a805a17d6cd279f3d1205b.jpg', 1, 10, 1, '2025-11-21 14:22:18', '2025-12-01 06:30:02'),
(2, 'Pink Roses Bouquet', 'Delicate pink roses for a gentle touch', 1327.99, 'https://www.philflower.com/images/detailed/23/24_Pink_Bouquet_Roses_Bouquet_c5cc-bt.jpg', 1, 8, 1, '2025-11-21 14:22:18', '2025-12-01 06:30:11'),
(3, 'White Roses Bouquet', 'Pure white roses for elegance', 4328.99, 'https://cdn.florista.ph/uploads/product/floristaph/MAY2025/5089-1746436285615.webp', 1, 12, 1, '2025-11-21 14:22:18', '2025-12-01 06:30:21'),
(4, 'Yellow Tulips', 'Cheerful yellow tulips for a bright day', 8024.99, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQk6F4yQ0MeIvjK1a5KpiErxkyR2YGfXQNFHA&s', 2, 15, 1, '2025-11-21 14:22:18', '2025-12-01 06:30:29'),
(5, 'Red Tulips', 'Vibrant red tulips for passion', 2323.99, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTmoZvLofHZEmi4C7T0xo40HPrBjMyqwqq4Xg&s', 2, 10, 1, '2025-11-21 14:22:18', '2025-12-01 06:30:37'),
(6, 'Sunflower Arrangement', 'Bright and cheerful sunflower bouquet', 5024.99, 'https://cdn.florista.ph/uploads/product/floristaph/Sunny_Bouquet_18_4990.webp', 3, 8, 1, '2025-11-21 14:22:18', '2025-12-01 06:30:44'),
(7, 'Mixed Flower Bouquet', 'Assorted colorful flowers in a beautiful arrangement', 12334.99, 'https://cdn.uaeflowers.com/uploads/product/uaeflowers/8809_56_8809.webp', 4, 12, 1, '2025-11-21 14:22:18', '2025-12-01 06:30:51'),
(8, 'Peony Bouquet', 'Luxurious peonies for special occasions', 3339.99, 'https://labellarosaflowers.com/cdn/shop/products/F11D6975-6EC7-4A2B-A7E0-36154F17BA7C.jpg?v=1659338315&width=1445', 5, 6, 1, '2025-11-21 14:22:18', '2025-12-01 06:30:58');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `country` varchar(50) DEFAULT 'USA',
  `role` enum('customer','admin') DEFAULT 'customer',
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(100) DEFAULT NULL,
  `reset_password_token` varchar(100) DEFAULT NULL,
  `reset_password_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `city`, `postal_code`, `country`, `role`, `email_verified`, `email_verification_token`, `reset_password_token`, `reset_password_expires`, `created_at`, `updated_at`, `avatar`) VALUES
(1, 'Facebook User', 'facebook_user@example.com', '$2y$10$0pyxdXvcMPE/HjBs2bqBseS.XL9ek1LU41EcyMoW4Na6nk0NIndQS', NULL, NULL, NULL, NULL, 'USA', 'customer', 1, NULL, NULL, NULL, '2025-11-27 14:46:20', '2025-11-27 14:46:20', NULL),
(2, 'marial angel zuniega', 'zuniegamarialangelsaa@gmail.com', '$2y$10$VdGN.TT9N2bBno6RR.oPVu8Ux9HtG38x0hS20lwhn1ji9HOBmynhG', '', '', '', '', '', 'customer', 0, NULL, NULL, NULL, '2025-11-29 10:53:13', '2025-11-29 10:53:13', NULL),
(3, 'marial angel zuniega', 'abbyllaguno5@gmail.com', '$2y$10$MRUwrWIqAfB6fH3HBFZo.el2pTFsI6euVVQGorr11e.WIdp3xU4sK', '+639663956793', 'Daraga, Albay', 'Legazpi', '4501', 'PHILIPPINES', 'customer', 0, NULL, NULL, NULL, '2025-11-30 17:01:20', '2025-11-30 17:01:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customizations`
--
ALTER TABLE `customizations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customizations`
--
ALTER TABLE `customizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
