-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2025 at 01:42 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `meal_plan_recommender_main`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `survey_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `survey_id`, `is_read`, `created_at`) VALUES
(21, 14, 1, '2025-06-02 11:13:43'),
(22, 14, 1, '2025-06-02 11:19:08');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meal_plans`
--

CREATE TABLE `meal_plans` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `breakfast_start` time DEFAULT NULL,
  `breakfast_end` time DEFAULT NULL,
  `lunch_start` time DEFAULT NULL,
  `lunch_end` time DEFAULT NULL,
  `dinner_start` time DEFAULT NULL,
  `dinner_end` time DEFAULT NULL,
  `time_from` time NOT NULL DEFAULT '00:00:00',
  `time_to` time NOT NULL DEFAULT '00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meal_plans`
--

INSERT INTO `meal_plans` (`id`, `admin_id`, `title`, `description`, `created_at`, `breakfast_start`, `breakfast_end`, `lunch_start`, `lunch_end`, `dinner_start`, `dinner_end`, `time_from`, `time_to`) VALUES
(4, 1, 'Mag Cr', 'Mag cr \r\nmag cr', '2025-05-28 03:33:38', NULL, NULL, NULL, NULL, NULL, NULL, '00:00:00', '00:00:00'),
(5, 1, 'Mag relapse', 'Basta kana\r\nBasta kaog bato\r\nKaog kunimo\r\nKaog butilya', '2025-05-28 03:52:10', '08:10:00', '09:52:00', '12:00:00', '13:10:00', '20:00:00', '21:50:00', '00:00:00', '00:00:00'),
(6, 1, 'Basta', 'basta', '2025-05-28 04:08:44', '20:07:00', '08:08:00', '12:07:00', '12:08:00', '20:10:00', '20:24:00', '00:00:00', '00:00:00'),
(7, 1, 'Basta Meal Plan', 'Kaog Bato\r\nkago kinumo', '2025-05-28 04:32:17', '08:31:00', '20:32:00', '21:31:00', '21:32:00', '20:35:00', '20:36:00', '00:00:00', '00:00:00'),
(8, 1, 'Basta Meal Plan', 'Basta kana \r\nbasta Kana', '2025-05-28 04:58:55', '20:58:00', '08:59:00', '12:26:00', '12:58:00', '20:58:00', '21:59:00', '00:00:00', '00:00:00'),
(9, 1, 'wew', 'wew', '2025-06-02 08:16:43', '00:16:00', '12:17:00', '00:16:00', '00:17:00', '01:17:00', '01:16:00', '00:00:00', '00:00:00'),
(10, 1, 'Muscle Gain Plan', 'This meal plan is optimized for muscle growth:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- 3-4 whole eggs with whole grain toast\r\n- Protein smoothie (banana, protein powder, milk, peanut butter)\r\n- Oatmeal with honey and nuts\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Grilled chicken breast or lean beef\r\n- Brown rice or sweet potato\r\n- Mixed vegetables\r\n- Greek yogurt\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Salmon or lean meat\r\n- Quinoa or brown rice\r\n- Steamed vegetables\r\n- Avocado or olive oil\r\n\r\nPre/Post Workout:\r\n- Protein shake\r\n- Banana or apple\r\n- Greek yogurt with honey\r\n\r\nTips:\r\n- Eat every 3-4 hours\r\n- Include protein with every meal\r\n- Stay hydrated\r\n- Focus on whole foods\r\n- Consider protein timing around workouts', '2025-06-02 08:40:57', '07:00:00', '08:00:00', '12:00:00', '13:00:00', '18:40:00', '19:00:00', '00:00:00', '00:00:00'),
(11, 1, 'Muscle Gain', 'This meal plan is optimized for muscle growth:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- 3-4 whole eggs with whole grain toast\r\n- Protein smoothie (banana, protein powder, milk, peanut butter)\r\n- Oatmeal with honey and nuts\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Grilled chicken breast or lean beef\r\n- Brown rice or sweet potato\r\n- Mixed vegetables\r\n- Greek yogurt\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Salmon or lean meat\r\n- Quinoa or brown rice\r\n- Steamed vegetables\r\n- Avocado or olive oil\r\n\r\nPre/Post Workout:\r\n- Protein shake\r\n- Banana or apple\r\n- Greek yogurt with honey\r\n\r\nTips:\r\n- Eat every 3-4 hours\r\n- Include protein with every meal\r\n- Stay hydrated\r\n- Focus on whole foods\r\n- Consider protein timing around workouts', '2025-06-02 09:09:04', '07:00:00', '08:00:00', '12:00:00', '12:00:00', '18:00:00', '19:00:00', '00:00:00', '00:00:00'),
(12, 1, 'wew', 'This meal plan is optimized for muscle growth:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- 3-4 whole eggs with whole grain toast\r\n- Protein smoothie (banana, protein powder, milk, peanut butter)\r\n- Oatmeal with honey and nuts\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Grilled chicken breast or lean beef\r\n- Brown rice or sweet potato\r\n- Mixed vegetables\r\n- Greek yogurt\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Salmon or lean meat\r\n- Quinoa or brown rice\r\n- Steamed vegetables\r\n- Avocado or olive oil\r\n\r\nPre/Post Workout:\r\n- Protein shake\r\n- Banana or apple\r\n- Greek yogurt with honey\r\n\r\nTips:\r\n- Eat every 3-4 hours\r\n- Include protein with every meal\r\n- Stay hydrated\r\n- Focus on whole foods\r\n- Consider protein timing around workouts', '2025-06-02 09:19:32', '01:29:00', '02:19:00', '13:19:00', '13:21:00', '19:19:00', '20:25:00', '00:00:00', '00:00:00'),
(13, 1, 'wewwe', 'This meal plan is optimized for muscle growth:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- 3-4 whole eggs with whole grain toast\r\n- Protein smoothie (banana, protein powder, milk, peanut butter)\r\n- Oatmeal with honey and nuts\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Grilled chicken breast or lean beef\r\n- Brown rice or sweet potato\r\n- Mixed vegetables\r\n- Greek yogurt\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Salmon or lean meat\r\n- Quinoa or brown rice\r\n- Steamed vegetables\r\n- Avocado or olive oil\r\n\r\nPre/Post Workout:\r\n- Protein shake\r\n- Banana or apple\r\n- Greek yogurt with honey\r\n\r\nTips:\r\n- Eat every 3-4 hours\r\n- Include protein with every meal\r\n- Stay hydrated\r\n- Focus on whole foods\r\n- Consider protein timing around workouts', '2025-06-02 09:21:16', '01:30:00', '01:38:00', '13:21:00', '13:22:00', '19:21:00', '20:24:00', '00:00:00', '00:00:00'),
(14, 1, 'wew', 'This meal plan promotes overall health:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- Overnight oats with fruits and nuts\r\n- Greek yogurt with honey\r\n- Green smoothie (spinach, banana, berries)\r\n- Herbal tea\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Mediterranean bowl (quinoa, chickpeas, vegetables)\r\n- Grilled chicken or fish\r\n- Large mixed salad\r\n- Olive oil dressing\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Baked salmon or lean protein\r\n- Roasted vegetables\r\n- Brown rice or sweet potato\r\n- Herbal tea\r\n\r\nSnacks:\r\n- Fresh fruits\r\n- Raw vegetables with hummus\r\n- Mixed nuts and seeds\r\n\r\nTips:\r\n- Include a variety of colorful vegetables\r\n- Choose whole grains over refined\r\n- Stay hydrated with water\r\n- Limit processed foods\r\n- Practice mindful eating', '2025-06-02 09:47:21', '01:50:00', '01:55:00', '13:47:00', '13:48:00', '16:47:00', '18:47:00', '00:00:00', '00:00:00'),
(15, 1, 'wew', 'This meal plan promotes overall health:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- Overnight oats with fruits and nuts\r\n- Greek yogurt with honey\r\n- Green smoothie (spinach, banana, berries)\r\n- Herbal tea\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Mediterranean bowl (quinoa, chickpeas, vegetables)\r\n- Grilled chicken or fish\r\n- Large mixed salad\r\n- Olive oil dressing\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Baked salmon or lean protein\r\n- Roasted vegetables\r\n- Brown rice or sweet potato\r\n- Herbal tea\r\n\r\nSnacks:\r\n- Fresh fruits\r\n- Raw vegetables with hummus\r\n- Mixed nuts and seeds\r\n\r\nTips:\r\n- Include a variety of colorful vegetables\r\n- Choose whole grains over refined\r\n- Stay hydrated with water\r\n- Limit processed foods\r\n- Practice mindful eating', '2025-06-02 09:55:16', '01:54:00', '01:55:00', '13:54:00', '16:56:00', '15:58:00', '15:57:00', '00:00:00', '00:00:00'),
(16, 1, 'wew', 'This meal plan promotes overall health:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- Overnight oats with fruits and nuts\r\n- Greek yogurt with honey\r\n- Green smoothie (spinach, banana, berries)\r\n- Herbal tea\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Mediterranean bowl (quinoa, chickpeas, vegetables)\r\n- Grilled chicken or fish\r\n- Large mixed salad\r\n- Olive oil dressing\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Baked salmon or lean protein\r\n- Roasted vegetables\r\n- Brown rice or sweet potato\r\n- Herbal tea\r\n\r\nSnacks:\r\n- Fresh fruits\r\n- Raw vegetables with hummus\r\n- Mixed nuts and seeds\r\n\r\nTips:\r\n- Include a variety of colorful vegetables\r\n- Choose whole grains over refined\r\n- Stay hydrated with water\r\n- Limit processed foods\r\n- Practice mindful eating', '2025-06-02 10:03:35', '02:02:00', '02:03:00', '12:03:00', '14:05:00', '19:03:00', '08:03:00', '00:00:00', '00:00:00'),
(17, 1, 'wew', 'This meal plan is optimized for muscle growth:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- 3-4 whole eggs with whole grain toast\r\n- Protein smoothie (banana, protein powder, milk, peanut butter)\r\n- Oatmeal with honey and nuts\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Grilled chicken breast or lean beef\r\n- Brown rice or sweet potato\r\n- Mixed vegetables\r\n- Greek yogurt\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Salmon or lean meat\r\n- Quinoa or brown rice\r\n- Steamed vegetables\r\n- Avocado or olive oil\r\n\r\nPre/Post Workout:\r\n- Protein shake\r\n- Banana or apple\r\n- Greek yogurt with honey\r\n\r\nTips:\r\n- Eat every 3-4 hours\r\n- Include protein with every meal\r\n- Stay hydrated\r\n- Focus on whole foods\r\n- Consider protein timing around workouts', '2025-06-02 10:09:22', '02:08:00', '02:09:00', '12:08:00', '00:12:00', '19:09:00', '20:09:00', '00:00:00', '00:00:00'),
(18, 1, 'Muscle Gain Plan', 'This meal plan is optimized for muscle growth:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- 3-4 whole eggs with whole grain toast\r\n- Protein smoothie (banana, protein powder, milk, peanut butter)\r\n- Oatmeal with honey and nuts\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Grilled chicken breast or lean beef\r\n- Brown rice or sweet potato\r\n- Mixed vegetables\r\n- Greek yogurt\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Salmon or lean meat\r\n- Quinoa or brown rice\r\n- Steamed vegetables\r\n- Avocado or olive oil\r\n\r\nPre/Post Workout:\r\n- Protein shake\r\n- Banana or apple\r\n- Greek yogurt with honey\r\n\r\nTips:\r\n- Eat every 3-4 hours\r\n- Include protein with every meal\r\n- Stay hydrated\r\n- Focus on whole foods\r\n- Consider protein timing around workouts', '2025-06-02 10:29:57', '02:29:00', '02:30:00', '12:29:00', '12:31:00', '07:29:00', '08:29:00', '00:00:00', '00:00:00'),
(19, 1, 'wew', 'This meal plan promotes overall health:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- Overnight oats with fruits and nuts\r\n- Greek yogurt with honey\r\n- Green smoothie (spinach, banana, berries)\r\n- Herbal tea\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Mediterranean bowl (quinoa, chickpeas, vegetables)\r\n- Grilled chicken or fish\r\n- Large mixed salad\r\n- Olive oil dressing\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Baked salmon or lean protein\r\n- Roasted vegetables\r\n- Brown rice or sweet potato\r\n- Herbal tea\r\n\r\nSnacks:\r\n- Fresh fruits\r\n- Raw vegetables with hummus\r\n- Mixed nuts and seeds\r\n\r\nTips:\r\n- Include a variety of colorful vegetables\r\n- Choose whole grains over refined\r\n- Stay hydrated with water\r\n- Limit processed foods\r\n- Practice mindful eating', '2025-06-02 10:34:25', '02:37:00', '04:34:00', '14:36:00', '14:34:00', '19:34:00', '09:40:00', '00:00:00', '00:00:00'),
(20, 1, 'wew', 'This meal plan promotes overall health:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- Overnight oats with fruits and nuts\r\n- Greek yogurt with honey\r\n- Green smoothie (spinach, banana, berries)\r\n- Herbal tea\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Mediterranean bowl (quinoa, chickpeas, vegetables)\r\n- Grilled chicken or fish\r\n- Large mixed salad\r\n- Olive oil dressing\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Baked salmon or lean protein\r\n- Roasted vegetables\r\n- Brown rice or sweet potato\r\n- Herbal tea\r\n\r\nSnacks:\r\n- Fresh fruits\r\n- Raw vegetables with hummus\r\n- Mixed nuts and seeds\r\n\r\nTips:\r\n- Include a variety of colorful vegetables\r\n- Choose whole grains over refined\r\n- Stay hydrated with water\r\n- Limit processed foods\r\n- Practice mindful eating', '2025-06-02 10:42:54', '02:42:00', '02:45:00', '12:42:00', '12:52:00', '19:42:00', '20:42:00', '00:00:00', '00:00:00'),
(21, 1, 'wew', 'This meal plan is optimized for muscle growth:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- 3-4 whole eggs with whole grain toast\r\n- Protein smoothie (banana, protein powder, milk, peanut butter)\r\n- Oatmeal with honey and nuts\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Grilled chicken breast or lean beef\r\n- Brown rice or sweet potato\r\n- Mixed vegetables\r\n- Greek yogurt\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Salmon or lean meat\r\n- Quinoa or brown rice\r\n- Steamed vegetables\r\n- Avocado or olive oil\r\n\r\nPre/Post Workout:\r\n- Protein shake\r\n- Banana or apple\r\n- Greek yogurt with honey\r\n\r\nTips:\r\n- Eat every 3-4 hours\r\n- Include protein with every meal\r\n- Stay hydrated\r\n- Focus on whole foods\r\n- Consider protein timing around workouts', '2025-06-02 11:22:35', '03:23:00', '03:22:00', '03:22:00', '03:22:00', '03:22:00', '03:22:00', '00:00:00', '00:00:00'),
(22, 1, 'Muscle Gain', 'This meal plan is optimized for muscle growth:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- 3-4 whole eggs with whole grain toast\r\n- Protein smoothie (banana, protein powder, milk, peanut butter)\r\n- Oatmeal with honey and nuts\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Grilled chicken breast or lean beef\r\n- Brown rice or sweet potato\r\n- Mixed vegetables\r\n- Greek yogurt\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Salmon or lean meat\r\n- Quinoa or brown rice\r\n- Steamed vegetables\r\n- Avocado or olive oil\r\n\r\nPre/Post Workout:\r\n- Protein shake\r\n- Banana or apple\r\n- Greek yogurt with honey\r\n\r\nTips:\r\n- Eat every 3-4 hours\r\n- Include protein with every meal\r\n- Stay hydrated\r\n- Focus on whole foods\r\n- Consider protein timing around workouts', '2025-06-02 11:23:11', '03:22:00', '03:23:00', '12:22:00', '12:28:00', '20:23:00', '21:23:00', '00:00:00', '00:00:00'),
(23, 1, 'wew', 'This meal plan is optimized for muscle growth:\r\n\r\nBreakfast (7:00 AM - 8:00 AM):\r\n- 3-4 whole eggs with whole grain toast\r\n- Protein smoothie (banana, protein powder, milk, peanut butter)\r\n- Oatmeal with honey and nuts\r\n\r\nLunch (12:00 PM - 1:00 PM):\r\n- Grilled chicken breast or lean beef\r\n- Brown rice or sweet potato\r\n- Mixed vegetables\r\n- Greek yogurt\r\n\r\nDinner (6:00 PM - 7:00 PM):\r\n- Salmon or lean meat\r\n- Quinoa or brown rice\r\n- Steamed vegetables\r\n- Avocado or olive oil\r\n\r\nPre/Post Workout:\r\n- Protein shake\r\n- Banana or apple\r\n- Greek yogurt with honey\r\n\r\nTips:\r\n- Eat every 3-4 hours\r\n- Include protein with every meal\r\n- Stay hydrated\r\n- Focus on whole foods\r\n- Consider protein timing around workouts', '2025-06-02 11:33:35', '03:33:00', '03:34:00', '12:33:00', '12:36:00', '19:33:00', '20:38:00', '00:00:00', '00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `notify_time` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `notify_time`, `created_at`) VALUES
(7, 17, 'Your survey has been submitted. Waiting for admin to recommend a meal plan.', 1, '2025-06-02 03:13:43', '2025-06-02 11:13:43'),
(8, 17, 'Your survey has been submitted. Waiting for admin to recommend a meal plan.', 1, '2025-06-02 03:19:08', '2025-06-02 11:19:08'),
(9, 17, 'A new meal plan has been recommended for your Muscle Gain goal!', 1, '2025-06-02 03:22:35', '2025-06-02 11:22:35'),
(10, 17, 'A new meal plan has been recommended for your Muscle Gain goal!', 1, '2025-06-02 03:23:11', '2025-06-02 11:23:11');

-- --------------------------------------------------------

--
-- Table structure for table `surveys`
--

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `goal` enum('lose_weight','muscle_gain','maintain_weight','other') NOT NULL,
  `age` int(11) DEFAULT NULL,
  `height_cm` int(11) DEFAULT NULL,
  `weight_kg` float DEFAULT NULL,
  `activity_level` enum('sedentary','light','moderate','active','very_active') DEFAULT NULL,
  `dietary_restrictions` text DEFAULT NULL,
  `additional_notes` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submitted_by_admin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `surveys`
--

INSERT INTO `surveys` (`id`, `user_id`, `goal`, `age`, `height_cm`, `weight_kg`, `activity_level`, `dietary_restrictions`, `additional_notes`, `submitted_at`, `submitted_by_admin`) VALUES
(14, 17, 'muscle_gain', 15, 157, 55, 'sedentary', '', '', '2025-06-02 11:13:43', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$urz.FfYmmsqid2z6/cXqx.oA.tjj8FFewVwTVOJnfkXN/nXlIaqCu', 'admin', '2025-05-28 02:59:45'),
(17, 'Princess', 'princess@gmail.com', '$2y$10$vmEWBcW8W1F/dQd5wArBcuuO8W8bQ63cF5qcPK5Zg1KWjPly/qS4K', 'user', '2025-06-02 11:07:02');

-- --------------------------------------------------------

--
-- Table structure for table `user_meal_plans`
--

CREATE TABLE `user_meal_plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meal_plan_id` int(11) NOT NULL,
  `recommended_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_viewed` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_meal_plans`
--

INSERT INTO `user_meal_plans` (`id`, `user_id`, `meal_plan_id`, `recommended_at`, `is_viewed`, `created_at`) VALUES
(19, 17, 21, '2025-06-02 11:22:35', 0, '2025-06-02 03:22:35'),
(20, 17, 22, '2025-06-02 11:23:11', 1, '2025-06-02 03:23:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `survey_id` (`survey_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_date_unique` (`user_id`,`attendance_date`);

--
-- Indexes for table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `surveys`
--
ALTER TABLE `surveys`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_meal_plans`
--
ALTER TABLE `user_meal_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `meal_plan_id` (`meal_plan_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `meal_plans`
--
ALTER TABLE `meal_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `surveys`
--
ALTER TABLE `surveys`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_meal_plans`
--
ALTER TABLE `user_meal_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD CONSTRAINT `admin_notifications_ibfk_1` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD CONSTRAINT `meal_plans_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `surveys`
--
ALTER TABLE `surveys`
  ADD CONSTRAINT `surveys_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_meal_plans`
--
ALTER TABLE `user_meal_plans`
  ADD CONSTRAINT `user_meal_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_meal_plans_ibfk_2` FOREIGN KEY (`meal_plan_id`) REFERENCES `meal_plans` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
