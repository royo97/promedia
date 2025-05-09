-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 09-05-2025 a las 18:26:45
-- Versión del servidor: 10.4.28-MariaDB
-- Versión de PHP: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `streaming_system`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `is_sold` tinyint(1) DEFAULT 0,
  `sold_to` int(11) DEFAULT NULL,
  `sold_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `accounts`
--

INSERT INTO `accounts` (`id`, `product_id`, `email`, `password`, `is_sold`, `sold_to`, `sold_at`, `created_at`) VALUES
(1, 1, 'prueba@stream.com', '12345678', 1, 3, '2025-05-09 02:49:57', '2025-05-09 01:52:21'),
(2, 1, 'roberdavidbracamonte@gmail.com', '62728281', 0, NULL, NULL, '2025-05-09 15:12:53');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `account_profiles`
--

CREATE TABLE `account_profiles` (
  `id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `profile_name` varchar(50) NOT NULL,
  `pin` varchar(20) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `account_profiles`
--

INSERT INTO `account_profiles` (`id`, `account_id`, `profile_name`, `pin`, `is_available`) VALUES
(1, 1, '1', '1001', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `balance_transactions`
--

CREATE TABLE `balance_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('purchase','admin_add','admin_subtract') NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `balance_transactions`
--

INSERT INTO `balance_transactions` (`id`, `user_id`, `amount`, `type`, `admin_id`, `created_at`) VALUES
(1, 3, 30000.00, 'admin_add', 2, '2025-05-09 02:46:40'),
(2, 3, 30000.00, 'admin_add', 2, '2025-05-09 13:27:49'),
(3, 3, 30000.00, 'admin_add', 2, '2025-05-09 13:28:16'),
(4, 3, 30000.00, 'admin_add', 2, '2025-05-09 13:30:20');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(14, 3, 2, 1, '2025-05-09 15:29:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `total`, `status`, `created_at`) VALUES
(1, 3, 7000.00, 'completed', '2025-05-09 02:49:57'),
(5, 3, 7000.00, 'completed', '2025-05-09 15:29:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `profile_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `account_id`, `price`, `profile_id`) VALUES
(1, 1, 1, 7000.00, NULL),
(2, 5, 1, 7000.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `profiles_available` int(11) NOT NULL DEFAULT 0,
  `has_pin` tinyint(1) DEFAULT 1,
  `profiles_count` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `price`, `profiles_available`, `has_pin`, `profiles_count`, `created_at`, `updated_at`) VALUES
(1, 'ORIGINAL NETFLIX PANTALLA', 'Pantallas', 7000.00, 5, 1, 6, '2025-05-09 01:50:40', '2025-05-09 15:29:07'),
(2, 'ORIGINAL NETFLIX PANTALLA', 'Pantallas', 7000.00, 6, 1, 6, '2025-05-09 01:51:09', '2025-05-09 01:51:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `balance`, `is_admin`, `created_at`) VALUES
(1, 'rober', 'rober@gmail.com', 'Rober*2001', 0.00, 1, '2025-05-08 22:54:00'),
(2, 'Juan', 'roberdavidbracamonte@gmail.com', '$2y$10$DKmVpVWVJoRHzRgvwe3g9.3MqbqYMOFSvVCk9FZydRbyEs4tJwt2K', 0.00, 1, '2025-05-08 23:16:01'),
(3, 'ines', 'carlos@gmail.com', '$2y$10$8Ddor32igzaWnEDk2cy6oeCxPUSpJKN.8V6totopJd25cGHy6JI2i', 106000.00, 0, '2025-05-08 23:48:43');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `sold_to` (`sold_to`);

--
-- Indices de la tabla `account_profiles`
--
ALTER TABLE `account_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indices de la tabla `balance_transactions`
--
ALTER TABLE `balance_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indices de la tabla `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indices de la tabla `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indices de la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `profile_id` (`profile_id`);

--
-- Indices de la tabla `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `account_profiles`
--
ALTER TABLE `account_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `balance_transactions`
--
ALTER TABLE `balance_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `accounts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `accounts_ibfk_2` FOREIGN KEY (`sold_to`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `account_profiles`
--
ALTER TABLE `account_profiles`
  ADD CONSTRAINT `account_profiles_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Filtros para la tabla `balance_transactions`
--
ALTER TABLE `balance_transactions`
  ADD CONSTRAINT `balance_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `balance_transactions_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Filtros para la tabla `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`profile_id`) REFERENCES `account_profiles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
