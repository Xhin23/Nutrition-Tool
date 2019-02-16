-- phpMyAdmin SQL Dump
-- version 3.5.8.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 15, 2019 at 09:01 PM
-- Server version: 5.5.33-cll-lve
-- PHP Version: 5.3.29

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `food`
--

-- --------------------------------------------------------

--
-- Table structure for table `food_groups`
--

CREATE TABLE IF NOT EXISTS `food_groups` (
  `groupid` int(4) DEFAULT NULL,
  `name` varchar(60) DEFAULT NULL,
  KEY `FdGrp_Cd` (`groupid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `food_groups`
--

INSERT INTO `food_groups` (`groupid`, `name`) VALUES
(100, 'Dairy and Egg Products'),
(200, 'Spices and Herbs'),
(400, 'Fats and Oils'),
(500, 'Poultry Products'),
(600, 'Soups, Sauces, and Gravies'),
(700, 'Sausages and Luncheon Meats'),
(800, 'Breakfast Cereals'),
(900, 'Fruits and Fruit Juices'),
(1000, 'Pork Products'),
(1100, 'Vegetables and Vegetable Products'),
(1200, 'Nut and Seed Products'),
(1300, 'Beef Products'),
(1400, 'Beverages'),
(1500, 'Finfish and Shellfish Products'),
(1600, 'Legumes and Legume Products'),
(1700, 'Lamb, Veal, and Game Products'),
(1800, 'Baked Products'),
(1900, 'Sweets'),
(2000, 'Cereal Grains and Pasta'),
(2100, 'Fast Foods'),
(2200, 'Meals, Entrees, and Side Dishes'),
(2500, 'Snacks'),
(3500, 'American Indian/Alaska Native Foods'),
(3600, 'Restaurant Foods');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
