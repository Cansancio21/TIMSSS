-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2025 at 07:26 AM
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
(17, 'John William', 'Mayormita', 'jonwilyammayormita@gmail.com', 'Astark', '$2y$10$TIr2Iy4HbODOaqgZ0akJbeibY.n8TCmEP5sbHGAfpbE1uYl2Mco1u', 'admin', 'pending'),
(18, 'Scarlett12', 'Williams', 'bukeke@mailinator.com', 'kogicosa', 'Pa$$w0rd!', 'admin', 'pending'),
(19, 'Rose', 'Donovan', 'lywawusype@mailinator.com', 'fuqum', '$2y$10$IVgruo96Y8NbSfhn/CYqKOn6Ma..swAN1NphEeL/DAOb0tqkYhnPe', 'Admin', 'Pending'),
(21, 'Astark', 'Mayormita', 'larraverallo@gmail.com', 'Astark', '$2y$10$tacqlNHSmJWBh3M4dgWJNe3PJmYfkIeUllONrdTARDaYg8NSadOZS', 'admin', 'Active'),
(22, 'John', 'Wilyam', 'jonwilyammayormita@gmail.com', 'Stark', '$2y$10$z.cDRaq6kxiXAB6CAXHM4OUch5jFsrGbRYlwdQ6SONixDKcSrb6C6', 'admin', 'active'),
(23, 'Ryan', 'Cansancio', 'jhonsepayla@yahoo.com', 'Ryan', '$2y$10$PxP0Kuq6wRU4J0QXLxuwFerGro3.cQoL6nc9shd0KIlUSKeix0if6', 'admin', 'Active'),
(24, 'Senpai', 'Kun', 'senpai@gmail.com', 'Senpai', '$2y$10$Lb.0nPGWVo1bDT6BBiEE9.r1EDmi/QiCFwy4GOi87O85ZHt7zwLzm', 'user', 'Active'),
(25, 'John Wilyam', 'Wilyam', 'xugecev@mailinator.com', 'WilyamSama', '$2y$10$izLTdpzNFvJ7mfjs014Jj.7IzygMHQ6wCABcpbDFIn3PaP5/Ihs1O', 'admin', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_borrow_assets`
--

CREATE TABLE `tbl_borrow_assets` (
  `a_id` int(50) NOT NULL,
  `a_name` varchar(200) NOT NULL,
  `a_status` varchar(200) NOT NULL,
  `a_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_borrow_assets`
--

INSERT INTO `tbl_borrow_assets` (`a_id`, `a_name`, `a_status`, `a_date`) VALUES
(1, 'Router', 'Borrowing', '2025-03-21'),
(2, 'Bukog', 'Borrowing', '2025-02-26');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_customer`
--

CREATE TABLE `tbl_customer` (
  `c_id` int(50) NOT NULL,
  `c_fname` varchar(200) NOT NULL,
  `c_lname` varchar(200) NOT NULL,
  `c_area` varchar(200) NOT NULL,
  `c_contact` int(50) NOT NULL,
  `c_email` varchar(200) NOT NULL,
  `c_date` date NOT NULL,
  `c_onu` varchar(200) NOT NULL,
  `c_caller` int(50) NOT NULL,
  `c_address` varchar(200) NOT NULL,
  `c_rem` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_customer`
--

INSERT INTO `tbl_customer` (`c_id`, `c_fname`, `c_lname`, `c_area`, `c_contact`, `c_email`, `c_date`, `c_onu`, `c_caller`, `c_address`, `c_rem`) VALUES
(1, 'awawawa', 'wawawawaw', 'awawawawaw', 12121212, 'awaw@gmail.com', '2000-02-21', 'awaaw', 12, 'awawaw', 'awawaw'),
(2, 'awawawa', 'wawaw', 'awawaw', 1212121212, 'awaw@gmail.com', '2000-02-21', 'awaw', 12, 'awawaw', 'awawawawa'),
(4, 'awawa', 'wawa', 'wawaw', 121212112, 'awaw@gmail.com', '2000-02-21', 'awawawa', 121, 'awaw', 'awawaw'),
(5, 'awawa', 'wawawaw', 'awawawa', 0, 'awawawawaw@gmail.com', '2000-02-21', 'awawaw', 12, 'awaw', 'awawaw'),
(6, 'awawa', 'wawawa', 'awaww', 0, 'wawa', '2000-02-21', 'awaw', 0, 'aww', 'awaw'),
(7, 'awawa', 'wawa', 'wawaw', 0, 'waw', '2000-02-21', 'awaw', 0, 'waw', 'awaw'),
(8, 'awawaw', 'awawa', 'wawa', 0, 'awa', '2000-02-21', 'awaw', 0, 'waw', 'awaw'),
(9, 'Latifah', 'Sims', 'Perspiciatis ea dol', 0, 'zehid@mailinator.com', '1970-01-21', 'In sequi eum maxime', 0, 'Nulla magna porro al', 'Aute eum maxime aper'),
(10, 'Mohammad', 'Mcdaniel', 'Vel ut a et deserunt', 939990939, 'lidyb@mailinator.com', '2024-08-31', 'Laborum voluptatem t', 0, 'Mollit quo deserunt', 'Sapiente suscipit no'),
(11, 'Lunea', 'Mendez', 'Dolores accusamus mo', 93442324, 'jupev@mailinator.com', '2025-03-20', 'Est et molestiae qui', 2147483647, 'Ad eos nesciunt ir', 'Ut dolorem quia est');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_deployment_assets`
--

CREATE TABLE `tbl_deployment_assets` (
  `a_id` int(50) NOT NULL,
  `a_name` varchar(200) NOT NULL,
  `a_status` varchar(200) NOT NULL,
  `a_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_deployment_assets`
--

INSERT INTO `tbl_deployment_assets` (`a_id`, `a_name`, `a_status`, `a_date`) VALUES
(1, 'Wire', 'Deployment', '2025-03-20'),
(2, 'Wire', 'Deployment', '2025-03-20');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_logs`
--

CREATE TABLE `tbl_logs` (
  `l_id` int(50) NOT NULL,
  `l_stamp` varchar(200) NOT NULL,
  `l_description` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_logs`
--

INSERT INTO `tbl_logs` (`l_id`, `l_stamp`, `l_description`) VALUES
(14, '2025-04-06 11:01:38', 'user \"awawwaawawwa\" has successfully logged in'),
(15, '2025-04-06 11:21:03', 'user \"awawwaawawwa\" has successfully logged in'),
(16, '2025-04-06 11:25:10', 'user \"Latifah Sims\" has successfully logged in'),
(17, '2025-04-06 11:25:18', 'user \"Latifah Sims\" created ticket with ref#-06-04-2025-168471');

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
(16, 9, 'Sims', 'Latifah', 'ref#-06-04-2025-901135', 'Critical', 'awawawawaw', 'Open'),
(17, 6, 'wawawa', 'awawa', 'ref#-06-04-2025-625338', 'Critical', 'awawaw', 'Open'),
(18, 8, 'awawa', 'awawaw', 'ref#-06-04-2025-976235', 'Critical', 'awawaw', 'Open'),
(19, 10, 'Mcdaniel', 'Mohammad', 'ref#-06-04-2025-826035', 'Critical', 'awaw', 'Open'),
(20, 9, 'Sims', 'Latifah', 'ref#-06-04-2025-168471', 'Critical', 'awaw', 'Open');

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
(1, 'Sydnee Kramer', 'Minor', 'archived', '2025-03-10', 'its over now najud hahays'),
(2, 'Portia Whitfield', 'Critical', 'Closed', '2025-03-09', 'hahays sige nalang, naa ra lage para sa atoa unya'),
(3, 'Samantha Hooper', 'Critical', 'Open', '2025-03-10', 'Ako e fight ang ako karapatan sa iyaha love'),
(4, 'Shaeleigh Baker', 'Critical', 'Open', '2025-03-08', 'Balik kana plsssssssssss'),
(5, 'Wilyam Sama', 'Minor', 'Open', '2025-03-16', 'nahutdan kog pang bayad sa amoa wifi, pwedi pa utang'),
(6, 'Ryan', 'Critical', 'Closed', '2025-03-16', 'naboang naman ko oy'),
(7, 'awawaaw', 'Critical', 'Open', '2000-02-21', 'Kung tayo ? tayo'),
(8, 'awawawaw', 'Critical', 'Open', '2000-02-21', 'relapse time'),
(9, 'Gwapo', 'Critical', 'Open', '2025-03-20', 'dinajud mada ang gibati'),
(10, 'Finn Melton', 'Minor', 'Closed', '2013-08-12', 'Adipisicing molestia'),
(11, 'Hedy Rogers', 'Minor', 'Closed', '1987-07-25', 'Ullam magni culpa fu'),
(12, 'Cyrus Steele', 'Critical', 'Closed', '2016-07-03', 'Aut qui quo earum se');

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
(16, 'Huh', 'Yunjin', 'huhjennifer@gmail.com', 'Rizee', '$2y$10$Og60E6rElNb8jpKCV51OOeNb6Hx2iilWoYCxFH3NV42TjsPEQs2Ea', 'admin', 'inactive'),
(20, 'Rize', 'Chan', 'Rize@gmail.com', 'Rizee', '$2y$10$.Pr5z4b2D4EWEq5SbYozlepjWFxEAhGeBpThqWi4O793b0rkGp80m', 'admin', 'inactive'),
(26, 'Xyla', 'Salinas', 'qiko@mailinator.com', 'qovalony', '$2y$10$1w/hBAF/J5QdrUtj4mtMhuBXuoPDIri6WAm4lYW.MXclYSDsOrit6', 'admin', 'pending'),
(27, 'Fiona', 'Rogers', 'fiona@gmail.com', 'Fiona Chan', '$2y$10$mJbipq.7nSIizFJAXf04POmIqSQdEQcDGat7lBXb9rbFdm35aHoTu', 'staff', 'active'),
(28, 'Illana', 'Alston', 'sijirugy@mailinator.com', 'meminubof', '$2y$10$RRcEmlzVVhhi6Uk.4Hh24OLfN0KkdCiJ4q5bnIcUKav7z4n8E85dq', 'admin', 'pending'),
(29, 'Brianna', 'Macias', 'dixypof@mailinator.com', 'Test', '$2y$10$b6e7YnYWZmwukVMoYV7X.eNkszNxKp3dHnr7dV4MYZ7kf57BClCli', 'staff', 'inactive'),
(30, 'Ursula', 'Walls', 'qofowoxoto@mailinator.com', 'Meowa', '$2y$10$VH3kXpwA6guV8aV4wg30Ne.grXF14qCOK9jImO7BjQzFhER3wGQye', 'admin', 'active'),
(31, 'Meredith', 'Dunlap', 'wojyx@mailinator.com', 'gypewa', '$2y$10$E2is5g3ncyrtNHdWXU8pH.aIig1PaeCxUCNozYLHvcYDPvG0iZxzG', 'admin', 'pending'),
(32, 'awawawa', 'wawawawawa', 'waw@gmail.com', 'oicnasnac', '$2y$10$EUZJMcTTpel2tHpMjIgT6.kDty.VTXcPU2rWbBWuARUFCdZCMao/W', 'admin', 'active'),
(33, 'awawawawa', 'wawawawaawaa', 'waw@gmail.com', 'oicnasnac12', '$2y$10$lNctiWTaf5CP1FYhlhgl3ehntsiEOMfsBnjnbjlq2BKHb9WOAYPa.', 'staff', 'active'),
(34, 'awawawawa', 'wawawawaawaa', 'waw@gmail.com', 'oicnasnac1234', '$2y$10$xVlsAy4RAGbUNwPIxTy6BuO2kCO3eoZ8aDJWw10h64CQ.RuPh6maC', 'staff', 'active'),
(35, 'awawawa', 'wawawawawa', 'waw@gmail.com', 'oicnasnac123', '$2y$10$HrYeqM5sk0e8gTZaRnJDau0JSPQp1NAe6UC4r4NllomMSaFbPvFJG', 'admin', 'active'),
(36, 'awawawaw', 'wawawaw', 'awaaw@gmail.com', 'oicnasnas', '$2y$10$icK5lZWvj5RVYo5egf8BDujUwVuui.tPfrd7.Bbitwb923pcF8kae', 'staff', 'active'),
(37, 'wawaw', 'awawa', 'w@gmail.com', 'haha', '$2y$10$FO2gaXFwMHatIuGicyrZ2ul85R7QQa4/OdHtjjjOILgSF2kkuBDii', 'staff', 'active'),
(38, 'wawawawwawa', 'awawawawaw', 'wawa@gmail.com', 'hahaha123', '$2y$10$NsqcjYr4xo3ZEpLZrI6SXOI0fd.3HrkD.CIS.UzX/dg.FQzlRnGG6', 'admin', 'active'),
(39, 'Sharon Tran', 'Lee Ratliff', 'nimopoji@mailinator.com', 'vadeca', '$2y$10$iVAn3kfvJuglo5.WiIc42eG4ERiadP4r.BDue20UBmt/Ght2IKW3S', 'admin', 'inactive'),
(40, 'Kyla Holden', 'Alec Randolph', 'jylojexud@mailinator.com', 'hokojuc', '$2y$10$xkhw6.OmwJiJBo/mKpJR4uIrIwhX955we8F3lYixyxlQjGow5hNUi', 'admin', 'pending'),
(41, 'awawwaawawwa', 'awwawawwaaw', 'awawaaw@gmail.com', 'haha123', '$2y$10$nxi4wpFWEA/712MCyrC7y.LAxgi/jRNtJBIkKzlfzHd8VFCPqAr..', 'staff', 'active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbl_archive`
--
ALTER TABLE `tbl_archive`
  ADD PRIMARY KEY (`u_id`);

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
-- Indexes for table `tbl_deployment_assets`
--
ALTER TABLE `tbl_deployment_assets`
  ADD PRIMARY KEY (`a_id`);

--
-- Indexes for table `tbl_logs`
--
ALTER TABLE `tbl_logs`
  ADD PRIMARY KEY (`l_id`);

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
-- AUTO_INCREMENT for table `tbl_archive`
--
ALTER TABLE `tbl_archive`
  MODIFY `u_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `tbl_borrow_assets`
--
ALTER TABLE `tbl_borrow_assets`
  MODIFY `a_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_customer`
--
ALTER TABLE `tbl_customer`
  MODIFY `c_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `tbl_deployment_assets`
--
ALTER TABLE `tbl_deployment_assets`
  MODIFY `a_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tbl_logs`
--
ALTER TABLE `tbl_logs`
  MODIFY `l_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `tbl_supp_tickets`
--
ALTER TABLE `tbl_supp_tickets`
  MODIFY `id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `tbl_ticket`
--
ALTER TABLE `tbl_ticket`
  MODIFY `t_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `u_id` int(50) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
