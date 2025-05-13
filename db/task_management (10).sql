-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 13, 2025 at 11:32 AM
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
-- Database: `task_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbl_archive`
--

CREATE TABLE `tbl_archive` (
  `u_id` int(50) NOT NULL,
  `u_fname` varchar(200) NOT NULL,
  `u_lname` varchar(200) NOT NULL,
  `u_email` varchar(200) NOT NULL,
  `u_username` varchar(200) NOT NULL,
  `u_password` varchar(200) NOT NULL,
  `u_type` varchar(200) NOT NULL,
  `u_status` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_archive`
--

INSERT INTO `tbl_archive` (`u_id`, `u_fname`, `u_lname`, `u_email`, `u_username`, `u_password`, `u_type`, `u_status`) VALUES
(26, 'Xyla', 'Salinas', 'qiko@mailinator.com', 'qovalony', '$2y$10$1w/hBAF/J5QdrUtj4mtMhuBXuoPDIri6WAm4lYW.MXclYSDsOrit6', 'admin', 'pending'),
(27, 'Fiona', 'Rogers', 'fiona@gmail.com', 'Fiona Chan', '$2y$10$mJbipq.7nSIizFJAXf04POmIqSQdEQcDGat7lBXb9rbFdm35aHoTu', 'staff', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_borrowed`
--

CREATE TABLE `tbl_borrowed` (
  `b_id` int(50) NOT NULL,
  `b_assets_name` varchar(100) NOT NULL,
  `b_quantity` int(50) NOT NULL,
  `b_technician_name` varchar(100) NOT NULL,
  `b_date` date NOT NULL,
  `b_technician_id` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_borrowed`
--

INSERT INTO `tbl_borrowed` (`b_id`, `b_assets_name`, `b_quantity`, `b_technician_name`, `b_date`, `b_technician_id`) VALUES
(17, 'Bukog', 0, 'Yumi Chan', '2025-04-24', 45),
(18, 'Fiber Optic Cable', 3, 'Tatyana Hayden', '2025-04-24', 37),
(19, 'Fiber Optic Cable', 2, 'Zia Jackson', '2025-04-26', 42),
(20, 'Fiber Optic Cable', 5, 'Yumi Chan', '2025-04-24', 45);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_borrow_assets`
--

CREATE TABLE `tbl_borrow_assets` (
  `a_id` int(50) NOT NULL,
  `a_name` varchar(200) NOT NULL,
  `a_status` varchar(200) NOT NULL,
  `a_date` date NOT NULL,
  `a_quantity` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_borrow_assets`
--

INSERT INTO `tbl_borrow_assets` (`a_id`, `a_name`, `a_status`, `a_date`, `a_quantity`) VALUES
(1, 'Router', 'Borrowing', '2025-03-21', 0),
(2, 'Bukog', 'Borrowing', '2025-02-26', 2),
(3, 'Modems', 'Borrowing', '2025-03-25', 0),
(4, 'Example', 'Borrowing', '2025-03-25', 0),
(5, 'Fiber Optic Cable', 'Borrowing', '2025-03-31', 27);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer`
--

CREATE TABLE `tbl_customer` (
  `c_id` int(50) NOT NULL,
  `c_fname` varchar(200) NOT NULL,
  `c_lname` varchar(200) NOT NULL,
  `c_address` varchar(200) NOT NULL,
  `c_contact` int(50) NOT NULL,
  `c_email` varchar(200) NOT NULL,
  `c_date` date NOT NULL,
  `c_napname` varchar(200) NOT NULL,
  `c_napport` int(100) NOT NULL,
  `c_macaddress` varchar(200) NOT NULL,
  `c_status` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_customer`
--

INSERT INTO `tbl_customer` (`c_id`, `c_fname`, `c_lname`, `c_address`, `c_contact`, `c_email`, `c_date`, `c_napname`, `c_napport`, `c_macaddress`, `c_status`) VALUES
(1, 'awawawa', 'wawawawaw', 'awawawawaw', 12121212, 'awaw@gmail.com', '2000-02-21', 'awaaw', 12, 'awawaw', 'awawaw'),
(2, 'awawawa', 'wawaw', 'awawaw', 1212121212, 'awaw@gmail.com', '2000-02-21', 'awaw', 12, 'awawaw', 'awawawawa'),
(4, 'awawa', 'wawa', 'wawaw', 121212112, 'awaw@gmail.com', '2000-02-21', 'awawawa', 121, 'awaw', 'awawaw'),
(5, 'awawa', 'wawawaw', 'awawawa', 0, 'awawawawaw@gmail.com', '2000-02-21', 'awawaw', 12, 'awaw', 'awawaw'),
(6, 'awawa', 'wawawa', 'awaww', 0, 'wawa', '2000-02-21', 'awaw', 0, 'aww', 'awaw'),
(7, 'awawa', 'wawa', 'wawaw', 0, 'waw', '2000-02-21', 'awaw', 0, 'waw', 'awaw'),
(8, 'awawaw', 'awawa', 'wawa', 0, 'awa', '2000-02-21', 'awaw', 0, 'waw', 'awaw'),
(9, 'Latifah', 'Sims', 'Perspiciatis ea dol', 0, 'zehid@mailinator.com', '1970-01-21', 'In sequi eum maxime', 0, 'Nulla magna porro al', 'Aute eum maxime aper'),
(10, 'Mohammad', 'Mcdaniel', 'Vel ut a et deserunt', 939990939, 'lidyb@mailinator.com', '2024-08-31', 'Laborum voluptatem t', 0, 'Mollit quo deserunt', 'Sapiente suscipit no'),
(11, 'Lunea', 'Mendez', 'Dolores accusamus mo', 93442324, 'jupev@mailinator.com', '2025-03-20', 'Est et molestiae qui', 2147483647, 'Ad eos nesciunt ir', 'Ut dolorem quia est'),
(12, 'Leila', 'Swanson', 'Pakigne Minglanilla', 939990939, 'lyrusofor@mailinator.com', '2025-04-28', 'Testing', 123490, 'Test1', 'Active'),
(13, 'Cara', 'Blackwell', 'Possimus ab dolores', 2147483647, 'williammayormita69@gmail.com', '2025-05-13', 'SukiFinch', 12, 'Eum ratione asperior', 'ARCHIVED:Active'),
(15, 'Donovan', 'Montgomery', 'Suscipit ut et ut ex', 10292993, 'williammayormita69@gmail.com', '2025-05-13', 'RafaelGillespie', 1213232, 'Nobisquamcumquae', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_deployed`
--

CREATE TABLE `tbl_deployed` (
  `d_id` int(50) NOT NULL,
  `d_assets_name` varchar(100) NOT NULL,
  `d_quantity` int(50) NOT NULL,
  `d_technician_name` varchar(100) NOT NULL,
  `d_date` varchar(100) NOT NULL,
  `d_technician_id` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_deployed`
--

INSERT INTO `tbl_deployed` (`d_id`, `d_assets_name`, `d_quantity`, `d_technician_name`, `d_date`, `d_technician_id`) VALUES
(1, 'Modem', 5, 'Tatyana Hayden', '2025-04-21', 37);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_deployment_assets`
--

CREATE TABLE `tbl_deployment_assets` (
  `a_id` int(50) NOT NULL,
  `a_name` varchar(200) NOT NULL,
  `a_status` varchar(200) NOT NULL,
  `a_date` date NOT NULL,
  `a_quantity` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_deployment_assets`
--

INSERT INTO `tbl_deployment_assets` (`a_id`, `a_name`, `a_status`, `a_date`, `a_quantity`) VALUES
(1, 'Wire', 'Deployment', '2025-03-20', 0),
(2, 'Wire', 'Deployment', '2025-03-20', 0),
(3, 'Modem', 'Deployment', '2025-03-25', 5);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_logs`
--

CREATE TABLE `tbl_logs` (
  `I_id` int(50) NOT NULL,
  `l_stamp` varchar(200) NOT NULL,
  `l_description` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_logs`
--

INSERT INTO `tbl_logs` (`I_id`, `l_stamp`, `l_description`) VALUES
(0, '2025-04-07 00:14:29', 'user \"Latifah Sims\" has successfully logged in'),
(0, '2025-04-07 00:15:56', 'user \"Latifah Sims\" created ticket with ref#-07-04-2025-484929'),
(0, '2025-05-03 20:32:50', 'user \"Latifah Sims\" has successfully logged in'),
(0, '2025-05-13 16:42:14', 'user \"Donovan Montgomery\" has successfully logged in'),
(0, '2025-05-13 17:03:36', 'user \"Donovan Montgomery\" has successfully logged in'),
(0, '2025-05-13 17:17:26', 'user \"Donovan Montgomery\" has successfully logged in'),
(0, '2025-05-13 17:21:38', 'user \"Donovan Montgomery\" has successfully logged in'),
(0, '2025-05-13 17:30:11', 'Staff Germaine has successfully logged in');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_returned`
--

CREATE TABLE `tbl_returned` (
  `r_id` int(50) NOT NULL,
  `r_assets_name` varchar(200) NOT NULL,
  `r_quantity` int(50) NOT NULL,
  `r_technician_name` varchar(200) NOT NULL,
  `r_technician_id` int(50) NOT NULL,
  `r_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_returned`
--

INSERT INTO `tbl_returned` (`r_id`, `r_assets_name`, `r_quantity`, `r_technician_name`, `r_technician_id`, `r_date`) VALUES
(1, 'Modems', 5, 'Tatyana Hayden', 37, '2025-03-31'),
(2, 'Example', 5, 'Tatyana Hayden', 37, '2025-03-31'),
(3, 'Modems', 5, 'Tatyana Hayden', 37, '2025-03-31'),
(4, 'Example', 3, 'Tatyana Hayden', 37, '2025-03-31'),
(5, 'Modems', 5, 'Tatyana Hayden', 37, '2025-03-31'),
(6, 'Modems', 8, 'Tatyana Hayden', 37, '2025-03-31'),
(7, 'Modems', 2, 'Tatyana Hayden', 37, '2025-03-31'),
(17, 'Modems', 4, 'Tatyana Hayden', 37, '2025-03-31'),
(18, 'Bukog', 1, 'Tatyana Hayden', 37, '2025-03-31'),
(21, 'Fiber Optic Cable', 15, 'Tatyana Hayden', 37, '2025-04-05'),
(22, 'Fiber Optic Cable', 30, 'Tatyana Hayden', 37, '2025-04-05'),
(23, 'Fiber Optic Cable', 20, 'Tatyana Hayden', 37, '2025-04-10'),
(24, 'Fiber Optic Cable', 1, 'Yumi Chan', 45, '2025-04-26'),
(25, 'Fiber Optic Cable', 1, 'Yumi Chan', 45, '2025-04-25'),
(26, 'Bukog', 2, 'Yumi Chan', 45, '2025-04-24'),
(27, 'Fiber Optic Cable', 1, 'Tatyana Hayden', 37, '2025-04-24'),
(28, 'Fiber Optic Cable', 5, 'Yumi Chan', 45, '2025-04-24');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_supp_tickets`
--

CREATE TABLE `tbl_supp_tickets` (
  `id` int(50) NOT NULL,
  `c_id` int(50) NOT NULL,
  `c_lname` varchar(200) NOT NULL,
  `c_fname` varchar(200) NOT NULL,
  `s_subject` varchar(200) NOT NULL,
  `s_type` varchar(200) NOT NULL,
  `s_message` varchar(200) NOT NULL,
  `s_status` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_supp_tickets`
--

INSERT INTO `tbl_supp_tickets` (`id`, `c_id`, `c_lname`, `c_fname`, `s_subject`, `s_type`, `s_message`, `s_status`) VALUES
(1, 0, 'awawawa', 'ref#-23-03-2025-211213', 'aqaqaqaqaq', '0', '', ''),
(4, 0, 'awawa', 'ref#-23-03-2025-558320', 'awaw', '1', '', ''),
(5, 0, 'awawa', 'ref#-23-03-2025-590610', 'awawawawaw', '0', '', ''),
(9, 0, '', 'ref#-23-03-2025-155334', 'awaw', '1', '', ''),
(10, 0, '', 'ref#-23-03-2025-702429', 'aw', '1', '', ''),
(11, 0, '', 'ref#-23-03-2025-707144', 'awaw', '1', '', ''),
(12, 8, 'awawa', 'awawaw', 'ref#-23-03-2025-610925', 'awaw', '1', ''),
(13, 8, 'awawa', 'awawaw', 'ref#-23-03-2025-469749', 'awawaw', '1', ''),
(14, 8, 'awawa', 'awawaw', 'ref#-23-03-2025-859139', 'awawaw', '1', ''),
(15, 10, 'Mcdaniel', 'Mohammad', 'ref#-23-03-2025-786879', 'awawawaw', '1', ''),
(16, 9, 'Sims', 'Latifah', 'ref#-07-04-2025-484929', 'Critical', 'awaw', 'Open'),
(17, 15, 'Montgomery', 'Donovan', '', 'Critical', 'rtyhtrhrthth', 'Open'),
(18, 15, 'Montgomery', 'Donovan', 'ref#-13-05-2025-618934', 'Minor', 'No wifi', 'Closed');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_ticket`
--

CREATE TABLE `tbl_ticket` (
  `t_id` int(50) NOT NULL,
  `t_aname` varchar(200) NOT NULL,
  `t_type` varchar(200) NOT NULL,
  `t_status` varchar(200) NOT NULL,
  `t_date` date NOT NULL,
  `t_details` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_ticket`
--

INSERT INTO `tbl_ticket` (`t_id`, `t_aname`, `t_type`, `t_status`, `t_date`, `t_details`) VALUES
(1, 'Sydnee Kramer', 'Minor', 'open', '2025-03-10', 'its over now najud hahays'),
(2, 'Portia Whitfield', 'Critical', 'archived', '2025-03-09', 'hahays sige nalang, naa ra lage para sa atoa unya'),
(3, 'Samantha Hooper', 'Critical', 'archived', '2025-03-10', 'Ako e fight ang ako karapatan sa iyaha love'),
(4, 'Shaeleigh Baker', 'Critical', 'Open', '2025-03-08', 'Balik kana plsssssssssss'),
(5, 'Wilyam Sama', 'Minor', 'Open', '2025-03-16', 'nahutdan kog pang bayad sa amoa wifi, pwedi pa utang'),
(6, 'Ryan', 'Critical', 'Closed', '2025-03-16', 'naboang naman ko oy'),
(7, 'awawaaw', 'Critical', 'Open', '2000-02-21', 'Kung tayo ? tayo'),
(8, 'awawawaw', 'Critical', 'Open', '2000-02-21', 'relapse time'),
(9, 'Gwapo', 'Critical', 'Open', '2025-03-20', 'dinajud mada ang gibati'),
(10, 'Finn Melton', 'Minor', 'Closed', '2013-08-12', 'Adipisicing molestia'),
(11, 'Hedy Rogers', 'Minor', 'Closed', '1987-07-25', 'Ullam magni culpa fu'),
(12, 'Cyrus Steele', 'Critical', 'archived', '2016-07-03', 'Aut qui quo earum se'),
(13, 'Erin Finley', 'Critical', 'Open', '2025-04-10', 'back to being friends hahays'),
(14, 'Lunea Mendez', 'Critical', 'Open', '2025-04-10', 'example rani love ha'),
(15, 'Leila Swanson', 'Moderate', 'Open', '2025-05-01', 'testing lang');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user`
--

CREATE TABLE `tbl_user` (
  `u_id` int(50) NOT NULL,
  `u_fname` varchar(200) NOT NULL,
  `u_lname` varchar(200) NOT NULL,
  `u_email` varchar(200) NOT NULL,
  `u_username` varchar(200) NOT NULL,
  `u_password` varchar(200) NOT NULL,
  `u_type` varchar(200) NOT NULL,
  `u_status` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_user`
--

INSERT INTO `tbl_user` (`u_id`, `u_fname`, `u_lname`, `u_email`, `u_username`, `u_password`, `u_type`, `u_status`) VALUES
(16, 'Huh', 'Yunjin', 'huhjennifer@gmail.com', 'Rizee', '$2y$10$Og60E6rElNb8jpKCV51OOeNb6Hx2iilWoYCxFH3NV42TjsPEQs2Ea', 'admin', 'active'),
(17, 'John William', 'Mayormita', 'jonwilyammayormita@gmail.com', 'Astark', '$2y$10$TIr2Iy4HbODOaqgZ0akJbeibY.n8TCmEP5sbHGAfpbE1uYl2Mco1u', 'admin', 'pending'),
(18, 'Scarlett', 'Williams', 'bukeke@mailinator.com', 'kogicosa', 'Pa$$w0rd!', 'admin', 'pending'),
(19, 'Rose', 'Donovan', 'lywawusype@mailinator.com', 'fuqum', '$2y$10$IVgruo96Y8NbSfhn/CYqKOn6Ma..swAN1NphEeL/DAOb0tqkYhnPe', 'Admin', 'Pending'),
(20, 'Rize', 'Chan', 'Rize@gmail.com', 'Rizee', '$2y$10$.Pr5z4b2D4EWEq5SbYozlepjWFxEAhGeBpThqWi4O793b0rkGp80m', 'admin', 'Active'),
(21, 'Astark', 'Mayormita', 'larraverallo@gmail.com', 'Astark', '$2y$10$tacqlNHSmJWBh3M4dgWJNe3PJmYfkIeUllONrdTARDaYg8NSadOZS', 'admin', 'Active'),
(22, 'John', 'Wilyam', 'jonwilyammayormita@gmail.com', 'Stark', '$2y$10$z.cDRaq6kxiXAB6CAXHM4OUch5jFsrGbRYlwdQ6SONixDKcSrb6C6', 'admin', 'active'),
(23, 'Ryan', 'Cansancio', 'jhonsepayla@yahoo.com', 'Ryan', '$2y$10$PxP0Kuq6wRU4J0QXLxuwFerGro3.cQoL6nc9shd0KIlUSKeix0if6', 'admin', 'Active'),
(25, 'John Wilyam', 'Wilyam', 'xugecev@mailinator.com', 'WilyamSama', '$2y$10$izLTdpzNFvJ7mfjs014Jj.7IzygMHQ6wCABcpbDFIn3PaP5/Ihs1O', 'admin', 'pending'),
(28, 'Illana', 'Alston', 'sijirugy@mailinator.com', 'meminubof', '$2y$10$RRcEmlzVVhhi6Uk.4Hh24OLfN0KkdCiJ4q5bnIcUKav7z4n8E85dq', 'admin', 'pending'),
(29, 'Brianna', 'Macias', 'dixypof@mailinator.com', 'Test', '$2y$10$b6e7YnYWZmwukVMoYV7X.eNkszNxKp3dHnr7dV4MYZ7kf57BClCli', 'staff', 'active'),
(30, 'Ursula', 'Walls', 'qofowoxoto@mailinator.com', 'Meowa', '$2y$10$VH3kXpwA6guV8aV4wg30Ne.grXF14qCOK9jImO7BjQzFhER3wGQye', 'admin', 'active'),
(31, 'Meredith', 'Dunlap', 'wojyx@mailinator.com', 'gypewa', '$2y$10$E2is5g3ncyrtNHdWXU8pH.aIig1PaeCxUCNozYLHvcYDPvG0iZxzG', 'admin', 'pending'),
(32, 'awawawa', 'wawawawawa', 'waw@gmail.com', 'oicnasnac', '$2y$10$EUZJMcTTpel2tHpMjIgT6.kDty.VTXcPU2rWbBWuARUFCdZCMao/W', 'admin', 'active'),
(33, 'awawawawa', 'wawawawaawaa', 'waw@gmail.com', 'oicnasnac12', '$2y$10$lNctiWTaf5CP1FYhlhgl3ehntsiEOMfsBnjnbjlq2BKHb9WOAYPa.', 'staff', 'active'),
(34, 'awawawawa', 'wawawawaawaa', 'waw@gmail.com', 'oicnasnac1234', '$2y$10$xVlsAy4RAGbUNwPIxTy6BuO2kCO3eoZ8aDJWw10h64CQ.RuPh6maC', 'staff', 'active'),
(35, 'awawawa', 'wawawawawa', 'waw@gmail.com', 'oicnasnac123', '$2y$10$HrYeqM5sk0e8gTZaRnJDau0JSPQp1NAe6UC4r4NllomMSaFbPvFJG', 'admin', 'active'),
(36, 'Nattyy De Coco', 'Tillman', 'rage@mailinator.com', 'Natty', '$2y$10$JJd2ZIQG6nEBu8dZ7SRULufGWNQb9fl/OSeXzFi63/T6UguImRE/G', 'staff', 'active'),
(37, 'Tatyana', 'Hayden', 'jimeruveby@mailinator.com', 'Test ni ha', '$2y$10$GiKnS6yTtPo6qudbPLqITeo4fFbcBUjN2PucdtFhfPJnjYrQbyJP6', 'technician', 'active'),
(38, 'awaw', 'awawa', 'wwawaw@gmail.com', 'hahaha123', '$2y$10$S.xL.mPyVp/dlHpgSrbm7ugtLIrR9XYhNhj1mm6egp0GN4.drzP7u', 'admin', 'active'),
(39, 'awaww', 'awawaww', 'wawa@gmail.com', 'haha12', '$2y$10$xwO0/0PFHhAk7weRGYyilefdTDQZomFIeH1HWRGswsasu2TLE86Vy', 'staff', 'active'),
(40, 'Mikha', 'Lim', 'mikhamylove@gmail.com', 'Mikha Lim', '$2y$10$wgKdbJ5BKYDxrU0GcdOTiuHchprGX8eKjWkB.cTtONksqZKRshZ4S', 'staff', 'active'),
(41, 'Kolehe', 'Kai', 'kolohekai@gmail.com', 'Kolohe', '$2y$10$p0uaBuH3RfB6BxHsbgIguevieEhZ9CplkIFXB50PetTP.0eFfmfkm', 'technician', 'active'),
(42, 'Zia', 'Jackson', 'cohulyjuha@mailinator.com', 'Zia', '$2y$10$ll3uoOyEKUDsdZeOOdPpAezyfFEVxkM0amJp3ILjtqWy/1fNWlRr.', 'technician', 'active'),
(43, 'Suzy', 'Bae', 'devizipot@mailinator.com', 'Suzyy', '$2y$10$AZueEfn.JBOkOzacbym8/OIANPAxZHMJpBmlX2FLgWPghFNTFZlU.', 'staff', 'active'),
(44, 'Admin', 'Test', 'admin@gmail.com', 'Admin', '$2y$10$LBq.HvsUTSzqerTOqSIfa.TGAsjEa3VokSXfB4NpNHhnQNRAC.dPO', 'admin', 'active'),
(45, 'Yumi', 'Chan', 'sohynyq@gmail.com', 'Yumi Chan', '$2y$10$GInrYkcHcu9PIrbehLTksO0MmcG4lAAZXC7Tgfiy0iaDXBJQ5mo0S', 'technician', 'active'),
(51, 'Aiah', 'Love', 'aiahlove@gmail.com', 'Aiahkins', '$2y$10$zthsh5racOm7RvvGDHj2i.xQV4jHp/.yS9gemChTZtsHfABSb/WsO', 'staff', 'active'),
(52, 'Miriam', 'Cain', 'zirozak@mailinator.com', 'AdminTest', '$2y$10$QCyUE/GnyoYL.ot7nSz7OuX.3lf1BISAne/z9DUz1wNCBfutoIuAC', 'admin', 'active'),
(53, 'Zeph', 'Patel', 'huhezeru@mailinator.com', 'ADMINN', '$2y$10$rCAUF7yizAqKOlXGNmlf5OuO.wX.X5ZV2I4FBrBHMR3J3.qRo6V2u', 'admin', 'active'),
(54, 'Joelle', 'Graves', 'vefud@mailinator.com', 'TestT', '$2y$10$YcCrLftfHHg/JVNrXCJsO.x0pJ3vXRtukqGjj8OS00jDuA2JQez3i', 'technician', 'active'),
(55, 'Germaine', 'Gray', 'williammayormita69@gmail.com', 'Jawil', '$2y$10$8ZbZIaaUs001K4sgyso0VeizbeCokz.6bTY2hIzonOsr3.lLhZYWy', 'staff', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_borrowed`
--
ALTER TABLE `tbl_borrowed`
  ADD PRIMARY KEY (`b_id`);

--
-- Indexes for table `tbl_borrow_assets`
--
ALTER TABLE `tbl_borrow_assets`
  ADD PRIMARY KEY (`a_id`);

--
-- Indexes for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  ADD PRIMARY KEY (`c_id`);

--
-- Indexes for table `tbl_deployed`
--
ALTER TABLE `tbl_deployed`
  ADD PRIMARY KEY (`d_id`);

--
-- Indexes for table `tbl_deployment_assets`
--
ALTER TABLE `tbl_deployment_assets`
  ADD PRIMARY KEY (`a_id`);

--
-- Indexes for table `tbl_returned`
--
ALTER TABLE `tbl_returned`
  ADD PRIMARY KEY (`r_id`);

--
-- Indexes for table `tbl_supp_tickets`
--
ALTER TABLE `tbl_supp_tickets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbl_ticket`
--
ALTER TABLE `tbl_ticket`
  ADD PRIMARY KEY (`t_id`);

--
-- Indexes for table `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`u_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbl_borrowed`
--
ALTER TABLE `tbl_borrowed`
  MODIFY `b_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tbl_borrow_assets`
--
ALTER TABLE `tbl_borrow_assets`
  MODIFY `a_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  MODIFY `c_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tbl_deployed`
--
ALTER TABLE `tbl_deployed`
  MODIFY `d_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_deployment_assets`
--
ALTER TABLE `tbl_deployment_assets`
  MODIFY `a_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tbl_returned`
--
ALTER TABLE `tbl_returned`
  MODIFY `r_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `tbl_supp_tickets`
--
ALTER TABLE `tbl_supp_tickets`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tbl_ticket`
--
ALTER TABLE `tbl_ticket`
  MODIFY `t_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `u_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
