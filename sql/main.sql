-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2017 at 11:29 PM
-- Server version: 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `ca2`
--

-- --------------------------------------------------------

--
-- Table structure for table `image`
--

CREATE TABLE IF NOT EXISTS `image` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `file_name_hash` varchar(128) NOT NULL,
  `file_data_hash` varchar(128) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `location` varchar(120) NOT NULL,
  `extension` varchar(4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_dups_file_uniq` (`userid`,`file_name_hash`,`file_data_hash`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=33 ;

--
-- Dumping data for table `image`
--

INSERT INTO `image` (`id`, `userid`, `file_name_hash`, `file_data_hash`, `upload_date`, `location`, `extension`) VALUES
(22, 1, '046a90e8072bf3c1e82dc9c0b3fc568bc91901a7b7012d67c84b961f77b9dfef26bb7ff2dd86038385164fb32f77180c6d6700db151fb9439db47c876d0cee6b', '7b8b9adf2dc67871b06fb9094bcd81e8834643cd9af96a0af591c2978bbe2fb7f53ff9b54ae09099aed97db727cd42df4ef02662ef4c6d7cf8023561ddccc7f2', '2017-07-20 21:27:28', 'c8f195055426bea3a5ea6d7b1e78d167c1b7494e/d5cdbf71ce0baa1d6e5f0bc0f80b31f8108e9ef0', '.jpg'),
(23, 1, '90738d66acd1feeb2cad42bdfb28bc36f8df13eb7717f61a723a212088893b5b9ce53cbff2ae7c6f906b0af21245660e4a87c3d43ace1d4b51bd968229c7914d', '5d661a7855bc2cf0678aa7e0c4713d0f7f68c1b72bf14660ccee48e8994dbaa6431c81d81184bdbac6e0ee9bd38df06937255f1da277007d77603829465feca1', '2017-07-20 21:29:21', 'c8f195055426bea3a5ea6d7b1e78d167c1b7494e/d5cdbf71ce0baa1d6e5f0bc0f80b31f8108e9ef0', '.jpg'),
(24, 1, '039a56aecbe15353ac33a8bd1c24710370d1ffdd8b4fbbf3d3802d637345188dfdcb00b23cd09ab2436633e862053a11ad3f460577e093f5edc1d96ba002603b', '461414c190291b15ea9df4d98cc711837c8270e316486af5935bb3cddb22b1b507f95a57779a9c5f777a4eaaf515dba500c2570c2a77d83354b6cc052da34ca9', '2017-07-20 21:29:26', 'c8f195055426bea3a5ea6d7b1e78d167c1b7494e/d5cdbf71ce0baa1d6e5f0bc0f80b31f8108e9ef0', '.jpg'),
(26, 1, '4c59c23a96ea385b4a07f937ce8644b013de6b97eff83bb0ab114bd41412cc3a9fd7f8c081336b176f91184b729babd0cd349a099b72dc86bdbb7989bb6fd32f', 'b69615f8f303eed22fdf0677a8d57b4b61df3487e385b5c2f108774a75a195b6f0dee1f0161c46118821b6b4478af68450db8620e735d13c518a565f4708a680', '2017-07-20 21:30:33', 'c8f195055426bea3a5ea6d7b1e78d167c1b7494e/d5cdbf71ce0baa1d6e5f0bc0f80b31f8108e9ef0', '.jpg'),
(31, 3, '046a90e8072bf3c1e82dc9c0b3fc568bc91901a7b7012d67c84b961f77b9dfef26bb7ff2dd86038385164fb32f77180c6d6700db151fb9439db47c876d0cee6b', '7b8b9adf2dc67871b06fb9094bcd81e8834643cd9af96a0af591c2978bbe2fb7f53ff9b54ae09099aed97db727cd42df4ef02662ef4c6d7cf8023561ddccc7f2', '2017-07-21 16:21:53', '1e54cb795d6f658de1e3e551f765a961fe871737/b5d4d427daafb74c01da5f875db9d87a6fac559b', '.jpg'),
(32, 3, '8c245d242aa4cfcdb5bb8746938d7d0e66712fb5b4682e17e0acdd94f84131a025afa71f01b8d3424964d7eb8fdb6a2bb3c584b0bc16496b84315144976b7c36', '9bdd0c215a9be94f6f677f8ad952fcb5abe876b59a1a2f537c7d9f7668abf4ee47c85acd9e4873c0b474eb98d7b211c08fd8f86b9f695d88d62c9695d88de90a', '2017-07-21 17:06:34', '1e54cb795d6f658de1e3e551f765a961fe871737/b5d4d427daafb74c01da5f875db9d87a6fac559b', '.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(60) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(17) DEFAULT NULL,
  `active` smallint(1) DEFAULT '0',
  `verificationToken` varchar(60) NOT NULL,
  `resetToken` varchar(255) DEFAULT NULL,
  `resetComplete` smallint(1) DEFAULT '0',
  `regdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `username` (`username`),
  KEY `phone` (`phone`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `username`, `password`, `email`, `phone`, `active`, `verificationToken`, `resetToken`, `resetComplete`, `regdate`) VALUES
(1, 'Kevin', '$2y$10$3Cni7JMhnyIHCqNkCw/acea7f2ijBAaEuxoesNmqZs2ZONE5qKIDy', 'kevinhol@ie.ibm.com', '', 1, '069059b7ef840f0c74a814ec9237b6ec', NULL, 0, '2017-07-18 19:37:38'),
(3, 'andy', '$2y$10$4C5j0cb0xLfpqIiEG560Xe/vjFMEan6Awe9K6hFu4Aq5skXrol6LC', 'aaa@p.mm', '+353864028614', 1, '6610', NULL, 0, '2017-07-21 15:45:44');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
