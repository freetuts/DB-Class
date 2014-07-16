-- phpMyAdmin SQL Dump
-- version 4.2.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 05, 2014 at 09:07 
-- Server version: 5.5.36
-- PHP Version: 5.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `example_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `author`
--

CREATE TABLE IF NOT EXISTS `author` (
`id` int(11) NOT NULL,
  `username` varchar(150) NOT NULL,
  `profile` text NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `author`
--

INSERT INTO `author` (`id`, `username`, `profile`) VALUES
(1, 'john', 'John is some fictional user whom we made up for this example.'),
(2, 'ben', 'Ben is another fictional user whom we made up for this example.'),
(3, 'otto', 'Otto is third fictional user whom we made up for this example.'),
(4, 'kate', 'Kate is our lovely user.');

-- --------------------------------------------------------

--
-- Table structure for table `info`
--

CREATE TABLE IF NOT EXISTS `info` (
`id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `body` text NOT NULL,
  `extra` varchar(255) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `info`
--

INSERT INTO `info` (`id`, `title`, `body`, `extra`) VALUES
(1, 'This is some title', 'This is some main text', 'This is something extra'),
(2, 'This is some title', 'This is some main text', 'This is something extra'),
(3, 'This is some title', 'This is some main text', 'This is something extra'),
(4, 'This is some title', 'This is some main text', 'This is something extra'),
(5, 'This is some title', 'This is some main text', 'This is something extra');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE IF NOT EXISTS `news` (
`id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `note` text,
  `createTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `authorId` int(11) NOT NULL,
  `rating` int(11) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=8 ;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `body`, `note`, `createTime`, `authorId`, `rating`) VALUES
(1, 'This is our first news.', 'This is some text about this news.', 'Unofficial source!', '2014-06-13 19:02:19', 1, 3),
(2, 'This is another news made by John.', 'Some text.', 'Official source!', '2014-06-13 19:03:35', 1, 2),
(3, 'Cool news', 'This is some very cool news.', NULL, '2014-06-13 19:04:20', 4, 3),
(4, 'Ben made some news.', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi ut malesuada eros. Suspendisse vestibulum eu dui ac pretium. Curabitur lacinia cursus neque, non dapibus diam bibendum a.', 'Offcial source!', '2014-06-13 19:05:44', 2, 4),
(5, '4 8 15 16 23 42', 'These numbers are not cursed!', 'Unofficial Source!', '2014-06-13 19:06:28', 2, 5),
(6, 'Everybody loves Hugo!', 'Mauris commodo molestie mi eu euismod. Vivamus sagittis eget velit ac aliquam. In hac habitasse platea dictumst.', 'Official source!', '2014-06-13 19:08:11', 4, 4),
(7, 'Live together die alone!', 'Jack said: "Live together, die alone".', NULL, '2014-06-13 19:09:27', 1, 3);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `author`
--
ALTER TABLE `author`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `info`
--
ALTER TABLE `info`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
 ADD PRIMARY KEY (`id`), ADD KEY `news_ibfk_1` (`authorId`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `author`
--
ALTER TABLE `author`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `info`
--
ALTER TABLE `info`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=8;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `news`
--
ALTER TABLE `news`
ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`authorId`) REFERENCES `author` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
