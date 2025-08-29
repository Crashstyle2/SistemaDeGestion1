-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 22-08-2025 a las 12:49:55
-- Versión del servidor: 8.0.30
-- Versión de PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `mantenimiento_ups`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acuse_recibo`
--

CREATE TABLE `acuse_recibo` (
  `id` int NOT NULL,
  `local` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sector` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `documento` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto` mediumblob,
  `foto_ruta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jefe_encargado` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `firma_digital` text COLLATE utf8mb4_unicode_ci,
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `tecnico_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `distancias_cache`
--

CREATE TABLE `distancias_cache` (
  `id` int NOT NULL,
  `lat_origen` decimal(10,8) NOT NULL,
  `lon_origen` decimal(11,8) NOT NULL,
  `lat_destino` decimal(10,8) NOT NULL,
  `lon_destino` decimal(11,8) NOT NULL,
  `distancia_km` decimal(8,2) NOT NULL,
  `proveedor` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'osrm',
  `fecha_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fotos_informe_tecnico`
--

CREATE TABLE `fotos_informe_tecnico` (
  `id` int NOT NULL,
  `informe_id` int NOT NULL,
  `foto` longtext COLLATE utf8mb4_unicode_ci,
  `foto_ruta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('antes','despues') COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `informe_tecnico`
--

CREATE TABLE `informe_tecnico` (
  `id` int NOT NULL,
  `local` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sector` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `equipo_asistido` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orden_trabajo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `patrimonio` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jefe_turno` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `firma_digital` longtext COLLATE utf8mb4_unicode_ci,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tecnico_id` int NOT NULL,
  `foto_trabajo` longtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimientos`
--

CREATE TABLE `mantenimientos` (
  `id` int NOT NULL,
  `patrimonio_ups` varchar(50) CHARACTER SET armscii8 COLLATE armscii8_general_ci NOT NULL,
  `fecha_mantenimiento` date NOT NULL,
  `observaciones` text,
  `estado` enum('Pendiente','Realizado') NOT NULL DEFAULT 'Pendiente',
  `usuario_mantenimiento` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `mantenimientos`
--

INSERT INTO `mantenimientos` (`id`, `patrimonio_ups`, `fecha_mantenimiento`, `observaciones`, `estado`, `usuario_mantenimiento`) VALUES
(18, '1008897', '2025-06-25', 'cambio de bateria', 'Realizado', 'jucaceres'),
(19, '13643', '2025-06-25', 'cambio de bateria ', 'Realizado', 'jucaceres');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento_ups`
--

CREATE TABLE `mantenimiento_ups` (
  `patrimonio` varchar(50) NOT NULL,
  `cadena` varchar(100) DEFAULT NULL,
  `sucursal` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `tipo_bateria` varchar(50) DEFAULT NULL,
  `cantidad` int DEFAULT NULL,
  `potencia_ups` varchar(50) DEFAULT NULL,
  `fecha_ultimo_mantenimiento` date DEFAULT NULL,
  `fecha_proximo_mantenimiento` date DEFAULT NULL,
  `observaciones` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `estado_mantenimiento` enum('Pendiente','Realizado') DEFAULT 'Pendiente',
  `usuario_mantenimiento` varchar(100) DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=armscii8;

--
-- Volcado de datos para la tabla `mantenimiento_ups`
--

INSERT INTO `mantenimiento_ups` (`patrimonio`, `cadena`, `sucursal`, `marca`, `tipo_bateria`, `cantidad`, `potencia_ups`, `fecha_ultimo_mantenimiento`, `fecha_proximo_mantenimiento`, `observaciones`, `estado_mantenimiento`, `usuario_mantenimiento`, `fecha_registro`) VALUES
('001', 'prueba', 'prueba', 'prueba', ' 12V-5Ah ', 22, '20KVA', '2025-05-10', '2027-05-10', '', 'Pendiente', NULL, '2025-05-10 20:00:57'),
('1006544', 'S6', 'MBURUKUJA', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2019-01-10', '2021-01-10', NULL, NULL, NULL, '2025-05-01 09:41:39'),
('1006903', 'S6', 'LAMBARE', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2018-04-12', '2020-04-12', NULL, NULL, NULL, '2025-05-01 09:35:41'),
('1007673', 'S6', 'EL PORTAL', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2017-01-01', '2019-01-01', NULL, NULL, NULL, '2025-05-01 09:18:08'),
('1008341', 'S6', 'ENCARNACION', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2025-02-07', '2027-02-07', '', 'Pendiente', NULL, '2025-05-01 09:20:09'),
('1008897', 'STOCK', 'MINGA GUAZU', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2025-06-25', '2027-06-25', NULL, 'Realizado', NULL, '2025-04-30 17:52:19'),
('1009713', 'S6', 'FERNANDO DE LA MORA', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2018-10-25', '2020-10-25', NULL, NULL, NULL, '2025-05-01 09:28:59'),
('1013258', 'S6', 'AREGUA', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2025-12-12', '2027-12-12', '', 'Pendiente', NULL, '2025-05-01 09:09:46'),
('1017479', 'S6', 'VILLETA', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2025-01-20', '2027-01-20', '', 'Pendiente', NULL, '2025-05-01 09:49:13'),
('1019118', 'S6', 'GALERIA', 'APC - TORRE', '12 V 7A', 656, '20KVA ', '2016-05-05', '2018-05-05', '', 'Pendiente', NULL, '2025-05-01 09:31:34'),
('1022757', 'S6', 'CAMBACUA', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2016-06-06', '2018-06-06', NULL, NULL, NULL, '2025-05-01 09:12:39'),
('1023534', 'S6', 'ENCARNACION 2', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2025-02-08', '2027-02-08', '', 'Pendiente', NULL, '2025-05-01 09:21:18'),
('1024099', 'S6', 'LUQUE ROSARIO', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2025-01-01', '2027-01-01', '', 'Pendiente', NULL, '2025-05-01 09:40:41'),
('1024186', 'S6', 'LAURELTY', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2018-12-05', '2020-12-05', NULL, NULL, NULL, '2025-05-01 09:36:54'),
('1024283', 'S6', 'VILLARICA', 'APC - TORRE', '12V 5A', 64, '12V 7A', '2017-01-01', '2019-01-01', NULL, NULL, NULL, '2025-05-01 09:47:59'),
('1033765', 'S6', 'SAN BERNARDINO ', 'APC - RACKEABLE ', '12 V 7A', 32, '10KVA ', '2024-01-25', '2026-01-25', '', 'Pendiente', NULL, '2025-05-01 09:43:47'),
('105908', 'STOCK', 'LIMPIO', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2019-01-25', '2021-01-25', NULL, NULL, NULL, '2025-04-30 17:41:08'),
('108011', 'DELIMARKET', 'DELIMARKET', 'APC', '12 V 7A', 64, '10KVA ', '2025-04-01', '2027-04-01', NULL, NULL, NULL, '2025-04-30 17:04:53'),
('11362', 'STOCK', 'LUQUE', 'CONVEREX TORRE', '12V 7A', 28, '10KVA ', '2018-05-05', '2020-05-05', NULL, NULL, NULL, '2025-04-30 17:44:43'),
('126485', 'S6', 'SAN LORENZO', 'APC - TORRE', '12V 5A', 64, '20KVA ', '2023-09-08', '2025-09-08', '', 'Pendiente', NULL, '2025-05-01 09:44:49'),
('126486', 'STOCK', 'DEFENSORES', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2018-02-02', '2020-02-02', NULL, NULL, NULL, '2025-04-30 16:59:56'),
('126487', 'STOCK', 'DON BOSCO', 'APC - TORRE', '12V 5A', 64, '20KVA ', '2017-10-15', '2019-10-15', NULL, NULL, NULL, '2025-04-30 17:07:19'),
('126488', 'STOCK', 'BRITEZ BORGUES', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2017-12-10', '2019-12-10', NULL, NULL, NULL, '2025-04-30 16:29:12'),
('126489', 'STOCK', 'SACRAMENTO', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2017-10-06', '2019-10-06', NULL, NULL, NULL, '2025-04-30 18:02:48'),
('126490', 'STOCK', 'MARIANO ROQUE ALONSO II', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2017-11-11', '2019-11-11', NULL, NULL, NULL, '2025-04-30 17:54:49'),
('126491', 'STOCK', 'KM 14/5', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2017-09-05', '2019-09-05', NULL, NULL, NULL, '2025-04-30 17:57:52'),
('13093', 'STOCK', 'LAMBARE', 'LIEBERT EMERSON', '12V 12A', 40, '20KVA ', '2016-05-03', '2018-05-03', NULL, NULL, NULL, '2025-04-30 17:42:27'),
('13643', 'STOCK', 'CARRETERA DE LOPEZ', 'LIEBERT EMERSON', '12 V 7A', 80, '20KVA ', '2025-06-25', '2027-06-25', '', 'Realizado', NULL, '2025-04-30 16:48:11'),
('14250', 'STOCK', 'CORONEL OVIEDO', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2017-04-03', '2019-04-03', '', 'Pendiente', NULL, '2025-04-30 16:51:43'),
('151491', 'Stock', 'BRASILIA2', 'APC', ' 12V-5Ah ', 32, '10KVA', '2025-02-04', '2027-02-04', NULL, NULL, NULL, '2025-05-02 10:57:04'),
('156111', 'STOCK', 'PIQUETE CUE', 'APC - RACKEABLE ', '12V 5A', 32, '10KVA ', '2025-01-08', '2027-01-08', '', 'Pendiente', NULL, '2025-04-30 18:00:37'),
('156149', 'STOCK', 'CALLE\'I', 'APC - RACKEABLE ', '12V 5A', 32, '10KVA ', '2018-10-01', '2020-10-01', NULL, NULL, NULL, '2025-04-30 16:40:59'),
('156680', 'STOCK', 'BOQUERON', 'APS TORRE', '12V 5A', 64, '20KVA ', '2025-02-15', '2027-02-15', '', 'Pendiente', NULL, '2025-04-30 11:22:18'),
('158965', 'STOCK', 'PARAGUARI', 'APC - RACKEABLE ', '12V 5A', 32, '10KVA ', '2025-03-25', '2027-03-25', '', 'Pendiente', NULL, '2025-04-30 17:56:17'),
('159660', 'STOCK', 'JULIAN AUGUSTO SALDIVAR', 'APC - RACKEABLE ', '12V 5A', 32, '10KVA ', '2025-03-06', '2027-03-06', '', 'Pendiente', NULL, '2025-04-30 17:20:33'),
('162013', 'STOCK', 'SAN IGNACIO', 'APC - RACKEABLE ', '12V 5A', 32, '10KVA ', '2025-02-06', '2027-02-06', '', 'Pendiente', NULL, '2025-04-30 18:12:01'),
('162671', 'STOCK', 'CORONEL BOGADO', 'APC - RACKEABLE ', '12V 5A', 32, '10KVA ', '2018-11-08', '2020-11-08', NULL, NULL, NULL, '2025-04-30 16:55:12'),
('163336', 'STOCK', 'VILLA HAYES', 'APC - RACKEABLE ', '12V 5A', 32, '10KVA ', '2019-02-26', '2021-02-26', NULL, NULL, NULL, '2025-04-30 18:25:30'),
('163386', 'STOCK', 'UNICOMPRA', 'APC - RACKEABLE ', '12V 5A', 32, '10KVA ', '2019-03-01', '2021-03-01', NULL, NULL, NULL, '2025-04-30 18:22:02'),
('165571', 'STOCK', 'YPANE', 'APC - RACKEABLE ', '12V 5A', 32, '10KVA ', '2024-10-10', '2026-10-10', '', 'Pendiente', NULL, '2025-04-30 18:29:09'),
('166543', 'S6', 'VILLAMORRA', 'APC - RACKEABLE ', '12V 5A', 64, '20KVA ', '2025-05-05', '2027-05-05', '', 'Pendiente', NULL, '2025-05-01 09:46:55'),
('167614', 'STOCK', 'MALL EXELSIOR', 'APC - RACKEABLE ', '12 V 5A', 32, '10KVA ', '2025-03-21', '2027-03-21', '', 'Pendiente', NULL, '2025-04-30 17:49:11'),
('168531', 'S6', 'HIPERSEIS', 'APC - RACKEABLE ', '12 V 5A', 64, '20KVA ', '2024-08-06', '2026-08-06', '', 'Pendiente', NULL, '2025-05-01 09:32:58'),
('169534', 'STOCK', 'SAN LORENZO', 'APC - RACKEABLE ', '12V 5A', 64, '20KVA ', '2018-10-19', '2020-10-19', NULL, NULL, NULL, '2025-04-30 18:13:26'),
('18636', 'STOCK', 'CAAGUAZU', 'APC', '12 V 7A', 64, '20KVA ', '2019-02-18', '2021-02-18', '', 'Pendiente', NULL, '2025-04-30 16:36:20'),
('32848', 'Stock', 'Doña Bertha', 'APC', ' 12V-5Ah ', 64, '20KVA', '2022-01-27', '2024-01-27', '', 'Pendiente', NULL, '2025-05-02 11:34:39'),
('34655', 'Stock', 'ARTIGAS', 'APC', ' 12V-5Ah ', 32, '10KVA', '2025-01-13', '2027-01-13', NULL, NULL, NULL, '2025-05-02 10:49:20'),
('34772', 'S6', 'ÑEMBY', 'APC', ' 12V-5Ah ', 64, '20KVA', '2019-10-17', '2021-10-17', '', 'Pendiente', NULL, '2025-05-02 11:36:08'),
('38086', 'STOCK', 'ACCESO SUR', 'APC', '12 V 7A', 64, '20KVA ', '2016-06-25', '2018-06-25', '', 'Pendiente', NULL, '2025-04-30 11:03:44'),
('39651', 'STOCK', 'ITAGUA', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2016-05-25', '2018-05-25', NULL, NULL, NULL, '2025-04-30 17:15:17'),
('41549', 'S6', 'TOTAL', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2021-04-06', '2023-04-06', '', 'Pendiente', NULL, '2025-05-01 09:45:53'),
('43067', 'S6', 'MUNDIMARK', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2018-04-03', '2020-04-03', NULL, NULL, NULL, '2025-05-01 09:42:23'),
('43100', 'S6', 'LOS LAURELES', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2018-04-05', '2020-04-05', NULL, NULL, NULL, '2025-05-01 09:39:05'),
('45160', 'S6', 'DENIS ROA', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2018-10-12', '2020-10-12', NULL, NULL, NULL, '2025-05-01 09:16:03'),
('49941', 'STOCK', 'ITA', 'APC - TORRE', '12 V 7A', 66, '20KVA ', '2016-05-09', '2018-05-09', '', 'Pendiente', NULL, '2025-04-30 17:10:54'),
('50473', 'STOCK', 'CAPIATA R2', 'APC', '12 V 7A', 65, '20KVA ', '2016-05-09', '2018-05-09', '', 'Pendiente', NULL, '2025-04-30 16:45:21'),
('51182', 'STOCK', 'MARIANO ROQUE ALONSO I', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2025-02-04', '2027-02-04', '', 'Pendiente', NULL, '2025-04-30 17:53:39'),
('52478', 'STOCK', 'REPUBLICA ARGENTINA', 'WELLI', '12 V 7A', 20, '10KVA ', '2024-05-10', '2026-05-10', '', 'Pendiente', NULL, '2025-04-30 18:01:49'),
('68058', 'STOCK', 'CAPIATA R1', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2017-01-01', '2019-01-01', NULL, NULL, NULL, '2025-04-30 16:43:40'),
('68111', 'STOCK', 'VILLA ELISA', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2017-01-01', '2019-01-01', NULL, NULL, NULL, '2025-04-30 18:23:39'),
('79640', 'S6', 'LA NEGRITA', 'APC - TORRE', '12V 5A', 64, '20KVA ', '2024-12-19', '2026-12-19', '', 'Pendiente', NULL, '2025-05-01 09:34:46'),
('96205', 'STOCK', 'SAN ANTONIO', 'APC - TORRE', '12 V 7A', 64, '20KVA ', '2019-02-02', '2021-02-02', NULL, NULL, NULL, '2025-04-30 18:08:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reclamos_zonas`
--

CREATE TABLE `reclamos_zonas` (
  `id` int NOT NULL,
  `zona` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mes` int NOT NULL,
  `anio` int NOT NULL,
  `cantidad_reclamos` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reclamos_zonas`
--

INSERT INTO `reclamos_zonas` (`id`, `zona`, `mes`, `anio`, `cantidad_reclamos`, `created_at`, `updated_at`) VALUES
(122, 'ZONA 1', 5, 2025, 42, '2025-05-08 16:48:46', '2025-05-10 23:37:31'),
(123, 'ZONA 2', 5, 2025, 51, '2025-05-08 16:48:52', '2025-05-09 14:12:50'),
(124, 'ZONA 3', 5, 2025, 89, '2025-05-08 16:48:56', '2025-05-11 12:48:37'),
(125, 'ZONA 4', 5, 2025, 88, '2025-05-08 16:49:01', '2025-05-11 12:45:32'),
(126, 'ADM', 5, 2025, 30, '2025-05-08 16:49:06', '2025-05-08 16:49:06'),
(127, 'ALTO PARANA', 5, 2025, 56, '2025-05-08 16:49:12', '2025-05-09 14:50:16'),
(128, 'ITAPUA', 5, 2025, 100, '2025-05-08 16:49:19', '2025-05-11 12:45:56'),
(129, 'VCA OV CAA SANT', 5, 2025, 200, '2025-05-08 16:49:24', '2025-05-08 16:49:24'),
(130, 'ZONA 1', 4, 2025, 35, '2025-05-08 16:49:30', '2025-05-08 16:49:30'),
(131, 'ZONA 2', 4, 2025, 35, '2025-05-08 16:49:34', '2025-05-08 16:49:34'),
(132, 'ZONA 3', 4, 2025, 59, '2025-05-08 16:49:39', '2025-05-08 16:49:39'),
(133, 'ZONA 4', 4, 2025, 75, '2025-05-08 16:49:48', '2025-05-08 16:49:48'),
(134, 'ADM', 4, 2025, 60, '2025-05-08 16:49:56', '2025-05-08 16:49:56'),
(135, 'ALTO PARANA', 4, 2025, 30, '2025-05-08 16:50:00', '2025-05-08 16:50:00'),
(136, 'ITAPUA', 4, 2025, 99, '2025-05-08 16:50:05', '2025-05-08 16:50:05'),
(137, 'VCA OV CAA SANT', 4, 2025, 205, '2025-05-08 16:50:10', '2025-05-08 16:50:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `registro_actividades`
--

CREATE TABLE `registro_actividades` (
  `id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `accion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `modulo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_hora` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `registro_actividades`
--

INSERT INTO `registro_actividades` (`id`, `usuario_id`, `accion`, `descripcion`, `modulo`, `fecha_hora`, `ip_address`) VALUES
(380, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 1', 'uso_combustible', '2025-07-30 11:53:38', '::1'),
(381, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 2', 'uso_combustible', '2025-07-30 11:54:44', '::1'),
(382, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 3', 'uso_combustible', '2025-07-30 11:57:09', '::1'),
(383, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-07-30 12:52:34', '::1'),
(384, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 3', 'uso_combustible', '2025-07-30 12:53:50', '::1'),
(385, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 3', 'uso_combustible', '2025-07-30 13:00:59', '::1'),
(386, 1, 'eliminar', 'Registro de combustible eliminado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Fecha: 2025-07-30', 'uso_combustible', '2025-07-30 13:02:26', '::1'),
(387, 1, 'eliminar', 'Registro de combustible eliminado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Fecha: 2025-07-30', 'uso_combustible', '2025-07-30 13:02:32', '::1'),
(388, 1, 'eliminar', 'Registro de combustible eliminado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Fecha: 2025-07-30', 'uso_combustible', '2025-07-30 13:02:39', '::1'),
(389, 1, 'eliminar', 'Registro de combustible eliminado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Fecha: 2025-07-30', 'uso_combustible', '2025-07-30 13:02:45', '::1'),
(390, 1, 'eliminar', 'Registro de combustible eliminado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Fecha: 2025-07-30', 'uso_combustible', '2025-07-30 13:02:51', '::1'),
(391, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 3', 'uso_combustible', '2025-07-30 13:04:40', '::1'),
(392, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-07-30 13:35:23', '::1'),
(393, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-07-30 13:35:30', '::1'),
(394, 1, 'eliminar', 'Registro de combustible eliminado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Fecha: 2025-07-30', 'uso_combustible', '2025-07-30 13:40:50', '::1'),
(395, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB, Recorridos: 3', 'uso_combustible', '2025-07-30 13:41:47', '::1'),
(396, 1, 'eliminar', 'Registro de combustible eliminado - Conductor: Juan Caceres, Chapa: AAOB, Fecha: 2025-07-30', 'uso_combustible', '2025-07-30 13:50:28', '::1'),
(397, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB, Recorridos: 3', 'uso_combustible', '2025-07-30 13:51:23', '::1'),
(398, 1, 'eliminar', 'Registro de combustible eliminado - Conductor: Juan Caceres, Chapa: AAOB, Fecha: 2025-07-30', 'uso_combustible', '2025-07-30 14:00:06', '::1'),
(399, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB, Recorridos: 3', 'uso_combustible', '2025-07-30 14:01:09', '::1'),
(400, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-01 12:06:15', '::1'),
(401, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-01 13:37:23', '::1'),
(402, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 4', 'uso_combustible', '2025-08-01 13:38:47', '::1'),
(403, 1, 'eliminar', 'Sucursal eliminada - ID: 149, Segmento: DEPOSITO, Local: DEP HENMY', 'sucursales', '2025-08-01 14:36:47', '::1'),
(404, 1, 'crear', 'Nueva sucursal creada - Segmento: DEPOSITO, Local: DEP HENMY, CEBE: 5010020000', 'sucursales', '2025-08-01 14:37:26', '::1'),
(405, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-01 14:55:00', '::1'),
(406, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-02 09:20:38', '::1'),
(407, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 1', 'uso_combustible', '2025-08-02 09:29:13', '::1'),
(408, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-05 13:17:43', '::1'),
(409, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-05 19:02:59', '::1'),
(410, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-06 09:21:59', '::1'),
(411, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 1', 'uso_combustible', '2025-08-06 09:45:30', '::1'),
(412, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-06 10:07:37', '::1'),
(413, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-06 12:06:01', '::1'),
(414, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-06 18:39:00', '::1'),
(415, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-06 19:13:25', '::1'),
(416, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-06 19:13:31', '::1'),
(417, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-08 09:03:45', '::1'),
(418, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-08 10:55:38', '::1'),
(419, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-08 11:24:24', '::1'),
(420, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 3', 'uso_combustible', '2025-08-08 11:29:03', '::1'),
(421, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-08 11:49:47', '::1'),
(422, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-08 18:34:22', '::1'),
(423, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 2', 'uso_combustible', '2025-08-08 18:45:36', '::1'),
(424, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-08 19:22:55', '::1'),
(425, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-12 11:32:43', '::1'),
(426, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-12 18:39:14', '127.0.0.1'),
(427, 1, 'editar', 'Usuario actualizado - Username: talcaraz, Nombre: Jonathan Alcaraz, Rol: tecnico, Estado: activo', 'usuarios', '2025-08-12 18:40:02', '::1'),
(428, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-12 19:51:23', '::1'),
(429, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-16 10:11:24', '::1'),
(430, 1, 'Cierre de sesión', 'Sesión cerrada por inactividad', 'Sistema', '2025-08-16 10:19:52', '::1'),
(431, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-16 10:19:57', '::1'),
(432, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-16 10:50:55', '::1'),
(433, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-16 11:00:09', '::1'),
(434, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-16 11:06:52', '::1'),
(435, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-16 11:07:27', '::1'),
(436, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-16 11:15:00', '::1'),
(437, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-16 11:15:34', '::1'),
(438, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:17:23', '::1'),
(439, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:17:23', '::1'),
(440, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:17:23', '::1'),
(441, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:17:23', '::1'),
(442, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:17:23', '::1'),
(443, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:17:23', '::1'),
(444, 1, 'seleccionar_registro_principal', 'Registro principal grupo 1 seleccionado', 'uso_combustible', '2025-08-16 11:17:24', '::1'),
(445, 1, 'seleccionar_registro_principal', 'Registro principal grupo 1 seleccionado', 'uso_combustible', '2025-08-16 11:17:26', '::1'),
(446, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:17:29', '::1'),
(447, 1, 'seleccionar_registro_principal', 'Registro principal grupo 1 seleccionado', 'uso_combustible', '2025-08-16 11:17:31', '::1'),
(448, 1, 'exportar_seleccionados_interfaz', 'Iniciando exportación de 1 registros seleccionados desde interfaz', 'uso_combustible', '2025-08-16 11:17:34', '::1'),
(449, 1, 'exportar_excel_interfaz', 'Exportación Excel desde interfaz - 1 registros seleccionados, Fecha:  a ', 'uso_combustible', '2025-08-16 11:17:34', '::1'),
(450, 1, 'deseleccionar_registro_principal', 'Registro principal grupo 1 deseleccionado', 'uso_combustible', '2025-08-16 11:17:58', '::1'),
(451, 1, 'seleccionar_registro_principal', 'Registro principal grupo 2 seleccionado', 'uso_combustible', '2025-08-16 11:17:59', '::1'),
(452, 1, 'exportar_seleccionados_interfaz', 'Iniciando exportación de 1 registros seleccionados desde interfaz', 'uso_combustible', '2025-08-16 11:18:01', '::1'),
(453, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:18:01', '::1'),
(454, 1, 'seleccionar_registro_principal', 'Registro principal grupo 4 seleccionado', 'uso_combustible', '2025-08-16 11:18:18', '::1'),
(455, 1, 'exportar_seleccionados_interfaz', 'Iniciando exportación de 3 registros seleccionados desde interfaz', 'uso_combustible', '2025-08-16 11:18:22', '::1'),
(456, 1, 'exportar_excel_interfaz', 'Exportación Excel desde interfaz - 3 registros seleccionados, Fecha:  a ', 'uso_combustible', '2025-08-16 11:18:22', '::1'),
(457, 1, 'crear', 'Registro de combustible creado - Conductor: Juan Caceres, Chapa: AAOB TOYOTA RACTIS, Recorridos: 2', 'uso_combustible', '2025-08-16 11:20:23', '::1'),
(458, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:20:26', '::1'),
(459, 1, 'seleccionar_registro_principal', 'Registro principal grupo 1 seleccionado', 'uso_combustible', '2025-08-16 11:20:29', '::1'),
(460, 1, 'exportar_seleccionados_interfaz', 'Iniciando exportación de 2 registros seleccionados desde interfaz', 'uso_combustible', '2025-08-16 11:20:33', '::1'),
(461, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:20:33', '::1'),
(462, 1, 'exportar_excel_interfaz', 'Exportación Excel desde interfaz - 2 registros seleccionados, Fecha:  a ', 'uso_combustible', '2025-08-16 11:20:45', '::1'),
(463, 1, 'exportar_excel_interfaz', 'Exportación Excel desde interfaz - 2 registros seleccionados, Fecha:  a ', 'uso_combustible', '2025-08-16 11:20:50', '::1'),
(464, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:20:54', '::1'),
(465, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:21:02', '::1'),
(466, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:21:05', '::1'),
(467, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:23:18', '::1'),
(468, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:23:20', '::1'),
(469, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:23:20', '::1'),
(470, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:23:20', '::1'),
(471, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:23:31', '::1'),
(472, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-16 11:23:54', '::1'),
(473, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:23:57', '::1'),
(474, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:24:01', '::1'),
(475, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:26:38', '::1'),
(476, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:26:44', '::1'),
(477, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:26:48', '::1'),
(478, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:26:53', '::1'),
(479, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-16 11:28:56', '::1'),
(480, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:28:59', '::1'),
(481, 1, 'seleccionar_registro_principal', 'Registro principal grupo 1 seleccionado', 'uso_combustible', '2025-08-16 11:29:01', '::1'),
(482, 1, 'deseleccionar_registro_principal', 'Registro principal grupo 1 deseleccionado', 'uso_combustible', '2025-08-16 11:29:02', '::1'),
(483, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:29:07', '::1'),
(484, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:29:11', '::1'),
(485, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:29:11', '::1'),
(486, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:29:11', '::1'),
(487, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:29:12', '::1'),
(488, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:29:16', '::1'),
(489, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:49:14', '::1'),
(490, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:49:41', '::1'),
(491, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:49:42', '::1'),
(492, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:49:42', '::1'),
(493, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:49:42', '::1'),
(494, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:49:42', '::1'),
(495, 1, 'cargar_ver_registros', 'Página de ver registros cargada exitosamente', 'uso_combustible', '2025-08-16 11:49:48', '::1'),
(496, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-19 16:01:20', '::1'),
(497, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-22 09:09:25', '::1'),
(498, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-22 09:18:00', '::1'),
(499, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-22 09:20:47', '::1'),
(500, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-22 09:33:36', '::1'),
(501, 1, 'login', 'Inicio de sesión exitoso - Usuario: jucaceres', 'autenticacion', '2025-08-22 09:39:50', '::1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reporte_cierres`
--

CREATE TABLE `reporte_cierres` (
  `id` int NOT NULL,
  `tecnico_id` int NOT NULL,
  `mes` int NOT NULL,
  `anio` int NOT NULL,
  `cantidad_cierres` int DEFAULT '0',
  `estado` enum('normal','bajo','muy_bajo','justificado') COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `justificacion` enum('N','R','V','P') COLLATE utf8mb4_unicode_ci DEFAULT 'N',
  `comentario_medida` text COLLATE utf8mb4_unicode_ci,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reporte_cierres`
--

INSERT INTO `reporte_cierres` (`id`, `tecnico_id`, `mes`, `anio`, `cantidad_cierres`, `estado`, `justificacion`, `comentario_medida`, `fecha_registro`) VALUES
(89, 20, 1, 2025, 54, 'normal', 'N', '', '2025-05-04 21:07:44'),
(90, 20, 2, 2025, 17, 'bajo', 'N', 'Retroalimentación ', '2025-05-04 21:08:27'),
(91, 20, 3, 2025, 32, 'bajo', 'N', 'Retroiluminación ', '2025-05-04 21:08:51'),
(92, 20, 4, 2025, 46, 'normal', 'N', '', '2025-05-04 21:09:07'),
(93, 19, 1, 2025, 63, 'normal', 'N', '', '2025-05-04 21:11:00'),
(94, 19, 3, 2025, 0, 'normal', 'V', 'Vacaciones 30 días ', '2025-05-04 21:11:58'),
(95, 19, 4, 2025, 27, 'bajo', 'N', '', '2025-05-04 21:12:18'),
(96, 23, 1, 2025, 95, 'normal', 'N', 'Retroalimentación ', '2025-05-04 21:13:05'),
(97, 23, 2, 2025, 108, 'normal', 'N', '', '2025-05-04 21:13:33'),
(98, 23, 3, 2025, 18, 'normal', 'V', 'Vacaciones 2 semanas ', '2025-05-04 21:14:09'),
(99, 23, 4, 2025, 31, 'bajo', 'N', '', '2025-05-04 21:14:20'),
(100, 24, 1, 2025, 61, 'normal', 'N', '', '2025-05-04 21:15:02'),
(101, 24, 2, 2025, 47, 'normal', 'N', '', '2025-05-04 21:15:11'),
(102, 24, 3, 2025, 58, 'normal', 'N', '', '2025-05-04 21:15:21'),
(103, 24, 4, 2025, 47, 'normal', 'N', '', '2025-05-04 21:15:31'),
(104, 25, 1, 2025, 46, 'normal', 'N', '', '2025-05-04 21:16:10'),
(105, 25, 2, 2025, 50, 'normal', 'N', '', '2025-05-04 21:16:20'),
(106, 25, 3, 2025, 53, 'normal', 'N', '', '2025-05-04 21:16:30'),
(107, 25, 4, 2025, 48, 'normal', 'N', '', '2025-05-04 21:16:39'),
(108, 26, 1, 2025, 50, 'normal', 'N', '', '2025-05-04 21:17:48'),
(109, 26, 2, 2025, 55, 'normal', 'N', '', '2025-05-04 21:18:00'),
(110, 26, 3, 2025, 52, 'normal', 'N', '', '2025-05-04 21:18:11'),
(111, 26, 4, 2025, 22, 'bajo', 'N', '', '2025-05-04 21:18:32'),
(112, 27, 2, 2025, 50, 'normal', 'N', '', '2025-05-04 21:19:12'),
(113, 27, 3, 2025, 48, 'normal', 'N', '', '2025-05-04 21:19:22'),
(114, 27, 4, 2025, 41, 'bajo', 'N', '', '2025-05-04 21:19:31'),
(115, 36, 1, 2025, 62, 'normal', 'N', '', '2025-05-04 21:20:03'),
(116, 36, 2, 2025, 58, 'normal', 'N', '', '2025-05-04 21:20:14'),
(117, 36, 3, 2025, 59, 'normal', 'N', '', '2025-05-04 21:20:25'),
(118, 36, 4, 2025, 53, 'normal', 'N', '', '2025-05-04 21:20:34'),
(119, 29, 2, 2025, 41, 'bajo', 'N', '', '2025-05-04 21:21:27'),
(120, 29, 3, 2025, 64, 'normal', 'N', '', '2025-05-04 21:21:44'),
(121, 29, 4, 2025, 50, 'normal', 'N', '', '2025-05-04 21:21:53'),
(122, 30, 1, 2025, 46, 'normal', 'N', '', '2025-05-04 21:22:36'),
(123, 30, 2, 2025, 48, 'normal', 'N', '', '2025-05-04 21:22:47'),
(124, 30, 3, 2025, 41, 'bajo', 'N', '', '2025-05-04 21:22:55'),
(125, 30, 4, 2025, 48, 'normal', 'N', '', '2025-05-04 21:23:05'),
(126, 32, 1, 2025, 39, 'normal', 'V', '12 días de vacaciones', '2025-05-04 21:25:47'),
(127, 32, 2, 2025, 56, 'normal', 'N', '', '2025-05-04 21:26:10'),
(128, 32, 3, 2025, 44, 'bajo', 'N', '', '2025-05-04 21:26:22'),
(129, 32, 4, 2025, 48, 'normal', 'N', '', '2025-05-04 21:26:30'),
(130, 33, 1, 2025, 57, 'normal', 'N', '', '2025-05-04 21:27:08'),
(131, 33, 2, 2025, 49, 'normal', 'N', '', '2025-05-04 21:27:20'),
(132, 33, 3, 2025, 54, 'normal', 'N', '', '2025-05-04 21:27:34'),
(133, 33, 4, 2025, 50, 'normal', 'N', '', '2025-05-04 21:27:43'),
(134, 35, 2, 2025, 29, 'bajo', 'N', '', '2025-05-04 21:28:51'),
(135, 35, 3, 2025, 31, 'bajo', 'N', '', '2025-05-04 21:29:02'),
(136, 35, 4, 2025, 28, 'bajo', 'N', '', '2025-05-04 21:29:10'),
(137, 28, 1, 2025, 40, 'normal', 'P', 'Usuario nuevo', '2025-05-04 21:29:56'),
(138, 28, 2, 2025, 20, 'bajo', 'N', '', '2025-05-04 21:30:06'),
(139, 28, 3, 2025, 49, 'normal', 'N', '', '2025-05-04 21:30:17'),
(140, 28, 4, 2025, 49, 'normal', 'N', '', '2025-05-04 21:30:26'),
(141, 21, 1, 2025, 1, 'normal', 'R', 'Reposo por accidente laboral ', '2025-05-08 16:41:25'),
(142, 21, 2, 2025, 1, 'normal', 'R', 'Accidente Laboral', '2025-05-08 17:43:24'),
(143, 45, 1, 2025, 0, 'normal', 'P', 'tecnico ', '2025-05-08 17:43:57'),
(144, 21, 3, 2025, 51, 'normal', 'N', '', '2025-05-09 14:05:13'),
(145, 21, 4, 2025, 21, 'bajo', 'N', '', '2025-05-09 14:09:44'),
(146, 37, 2, 2025, 21, 'bajo', 'N', '', '2025-05-09 14:12:59'),
(147, 37, 1, 2025, 61, 'normal', 'N', '', '2025-05-09 14:13:24'),
(148, 37, 3, 2025, 22, 'bajo', 'N', '', '2025-05-09 14:14:03'),
(149, 34, 2, 2025, 80, 'normal', 'N', '', '2025-05-09 14:15:48'),
(150, 34, 1, 2025, 31, 'bajo', 'N', '', '2025-05-09 14:17:32'),
(151, 31, 2, 2025, 20, 'bajo', 'N', '', '2025-05-09 14:22:32'),
(152, 34, 3, 2025, 42, 'bajo', 'N', '', '2025-05-10 23:40:02'),
(153, 19, 2, 2025, 22, 'bajo', 'N', '', '2025-05-10 23:43:09'),
(154, 39, 1, 2025, 40, 'bajo', 'N', '', '2025-05-10 23:53:59'),
(155, 39, 2, 2025, 40, 'bajo', 'N', '', '2025-05-11 00:01:33'),
(156, 29, 1, 2025, 46, 'normal', 'N', '', '2025-05-11 00:04:13'),
(157, 22, 1, 2025, 20, 'bajo', 'N', '', '2025-05-11 12:12:59');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesiones_activas`
--

CREATE TABLE `sesiones_activas` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_ultima_actividad` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sesiones_activas`
--

INSERT INTO `sesiones_activas` (`id`, `user_id`, `token`, `fecha_inicio`, `fecha_ultima_actividad`, `ip_address`) VALUES
(118, 1, NULL, '2025-07-30 12:52:34', '2025-07-30 12:52:34', NULL),
(119, 1, NULL, '2025-07-30 13:35:23', '2025-07-30 13:35:23', NULL),
(120, 1, NULL, '2025-07-30 13:35:30', '2025-07-30 13:35:30', NULL),
(121, 1, NULL, '2025-08-01 12:06:15', '2025-08-01 12:06:15', NULL),
(122, 1, NULL, '2025-08-01 13:37:22', '2025-08-01 13:37:22', NULL),
(123, 1, NULL, '2025-08-01 14:55:00', '2025-08-01 14:55:00', NULL),
(124, 1, NULL, '2025-08-02 09:20:38', '2025-08-02 09:20:38', NULL),
(125, 1, NULL, '2025-08-05 13:17:43', '2025-08-05 13:17:43', NULL),
(126, 1, NULL, '2025-08-05 19:02:59', '2025-08-05 19:02:59', NULL),
(127, 1, NULL, '2025-08-06 09:21:59', '2025-08-06 09:21:59', NULL),
(128, 1, NULL, '2025-08-06 10:07:37', '2025-08-06 10:07:37', NULL),
(129, 1, NULL, '2025-08-06 12:06:01', '2025-08-06 12:06:01', NULL),
(130, 1, NULL, '2025-08-06 18:39:00', '2025-08-06 18:39:00', NULL),
(131, 1, NULL, '2025-08-06 19:13:25', '2025-08-06 19:13:25', NULL),
(132, 1, NULL, '2025-08-06 19:13:31', '2025-08-06 19:13:31', NULL),
(133, 1, NULL, '2025-08-08 09:03:45', '2025-08-08 09:03:45', NULL),
(134, 1, NULL, '2025-08-08 10:55:38', '2025-08-08 10:55:38', NULL),
(135, 1, NULL, '2025-08-08 11:24:24', '2025-08-08 11:24:24', NULL),
(136, 1, NULL, '2025-08-08 11:49:47', '2025-08-08 11:49:47', NULL),
(137, 1, NULL, '2025-08-08 18:34:22', '2025-08-08 18:34:22', NULL),
(138, 1, NULL, '2025-08-08 19:22:55', '2025-08-08 19:22:55', NULL),
(139, 1, NULL, '2025-08-12 11:32:43', '2025-08-12 11:32:43', NULL),
(140, 1, NULL, '2025-08-12 18:39:14', '2025-08-12 18:39:14', NULL),
(141, 1, NULL, '2025-08-12 19:51:22', '2025-08-12 19:51:22', NULL),
(142, 1, NULL, '2025-08-16 10:11:24', '2025-08-16 10:11:24', NULL),
(143, 1, NULL, '2025-08-16 10:19:57', '2025-08-16 10:19:57', NULL),
(144, 1, NULL, '2025-08-16 10:50:55', '2025-08-16 10:50:55', NULL),
(145, 1, NULL, '2025-08-16 11:00:09', '2025-08-16 11:00:09', NULL),
(146, 1, NULL, '2025-08-16 11:06:52', '2025-08-16 11:06:52', NULL),
(147, 1, NULL, '2025-08-16 11:07:27', '2025-08-16 11:07:27', NULL),
(148, 1, NULL, '2025-08-16 11:15:00', '2025-08-16 11:15:00', NULL),
(149, 1, NULL, '2025-08-16 11:15:34', '2025-08-16 11:15:34', NULL),
(150, 1, NULL, '2025-08-16 11:23:54', '2025-08-16 11:23:54', NULL),
(151, 1, NULL, '2025-08-16 11:28:56', '2025-08-16 11:28:56', NULL),
(152, 1, NULL, '2025-08-19 16:01:20', '2025-08-19 16:01:20', NULL),
(153, 1, NULL, '2025-08-22 09:09:25', '2025-08-22 09:09:25', NULL),
(154, 1, NULL, '2025-08-22 09:18:00', '2025-08-22 09:18:00', NULL),
(155, 1, NULL, '2025-08-22 09:20:47', '2025-08-22 09:20:47', NULL),
(156, 1, NULL, '2025-08-22 09:33:36', '2025-08-22 09:33:36', NULL),
(157, 1, NULL, '2025-08-22 09:39:50', '2025-08-22 09:39:50', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursales`
--

CREATE TABLE `sucursales` (
  `id` int NOT NULL,
  `segmento` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cebe` bigint DEFAULT NULL,
  `local` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `m2_neto` int NOT NULL,
  `localidad` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sucursales`
--

INSERT INTO `sucursales` (`id`, `segmento`, `cebe`, `local`, `m2_neto`, `localidad`, `created_at`, `updated_at`) VALUES
(1, 'STOCK', 1010010000, 'ST MALL EXCELSIOR', 1400, 'ASUNCION', '2025-07-23 19:19:15', '2025-07-23 19:19:15'),
(2, 'Stock', 1010020000, 'STOCK MCAL LOPEZ', 800, 'FERNANDO DE LA MORA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(3, 'Stock', 1010040000, 'STOCK RCA ARGENTINA', 1369, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(4, 'Stock', 1010050000, 'STOCK SAN LORENZO', 2300, 'SAN LORENZO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(5, 'Stock', 1010060000, 'STOCK BARRIO OBRERO', 1100, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(6, 'Stock', 1010070000, 'STOCK SACRAMENTO', 2200, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(7, 'Stock', 1010090000, 'STOCK PJC', 800, 'PJC', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(8, 'Stock', 1010100000, 'STOCK CDE', 2300, 'CDE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(9, 'Stock', 1010110000, 'STOCK CNEL. OVIEDO', 1700, 'CNEL. OVIEDO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(10, 'Stock', 1010120000, 'STOCK LAMBARE', 1200, 'LAMBARE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(11, 'Stock', 1010130000, 'STOCK BRASILIA', 1400, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(12, 'Stock', 1010140000, 'STOCK CAAGUAZU', 1700, 'CAAGUAZU', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(13, 'Stock', 1010150000, 'STOCK ITAUGUA', 1539, 'ITAUGUA ', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(14, 'Stock', 1010790000, 'STOCK ITAUGUA 2', 760, 'ITAUGUA ', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(15, 'Stock', 1010160000, 'STOCK DOÑA BERTA', 1100, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(16, 'Stock', 1010170000, 'STOCK MRA', 1700, 'M.R.A', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(17, 'Stock', 1010180000, 'STOCK ACCESO SUR', 1600, 'VILLA ELISA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(18, 'Stock', 1010190000, 'STOCK ITA', 1800, 'ITA ', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(19, 'Stock', 1010200000, 'STOCK CAPIATA RUTA2', 1700, 'CAPIATA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(20, 'Stock', 1010210000, 'STOCK LAMBARE CARRE', 1700, 'LAMBARE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(21, 'Stock', 1010240000, 'STOCK CAPIATA', 1600, 'CAPIATA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(22, 'Stock', 1010250000, 'STOCK VILLA ELISA', 1800, 'VILLA ELISA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(23, 'Stock', 1010260000, 'STOCK PTE. FRANCO', 1600, 'PDTE. FRANCO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(24, 'Stock', 1010270000, 'STOCK MINGA GUAZU', 1600, 'MINGA GUAZU', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(25, 'Stock', 1010280000, 'STOCK SAN ANTONIO', 1700, 'SAN ANTONIO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(26, 'Stock', 1010290000, 'STOCK CIUDAD NUEVA', 1000, 'CDE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(27, 'Stock', 1010310000, 'STOCK HIPER SLORENZ', 2000, 'SAN LORENZO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(28, 'Stock', 1010320000, 'STOCK LIMPIO', 1300, 'LIMPIO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(29, 'Stock', 1010330000, 'DELIMARK DELIMARK', 2000, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(30, 'Stock', 1010340000, 'STOCK AVELINO MARTI', 800, 'SAN LORENZO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(31, 'Stock', 1010350000, 'STOCK BRITEZ BORGES', 800, 'LUQUE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(32, 'Stock', 1010360000, 'STOCK DON BOSCO', 1000, 'CDE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(33, 'Stock', 1010370000, 'STOCK DEFEN CHACO', 800, 'LAMBARE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(34, 'Stock', 1010380000, 'STOCK HERNANDARIAS', 800, 'HERNANDARIAS', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(35, 'Stock', 1010390000, 'STOCK BOQUERON', 800, 'SAN ANTONIO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(36, 'Stock', 1010400000, 'STOCK YPANE', 800, 'YPANE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(37, 'Stock', 1010410000, 'STOCK CALLEI', 800, 'SAN LORENZO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(38, 'Stock', 1010440000, 'STOCK MRA 2', 1700, 'M.R.A', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(39, 'Stock', 1010450000, 'STOCK SANLO CENTRO', 800, 'SAN LORENZO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(40, 'Stock', 1010460000, 'STOCK UNICOMPRA', 800, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(41, 'Stock', 1010470000, 'STOCK PARAGUARI', 800, 'PARAGUARI', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(42, 'Stock', 1010480000, 'STOCK CNEL BOGADO', 800, 'CNEL. BOGADO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(43, 'Stock', 1010490000, 'STOCK ARTIGAS', 1000, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(44, 'Stock', 1010510000, 'STOCK PIQUETE', 800, 'LIMPIO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(45, 'Stock', 1010520000, 'STOCK J.A.SALDIVAR', 800, 'J.A.SALDIVAR', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(46, 'Stock', 1010530000, 'STOCK SAN IGNACIO', 800, 'SAN IGNACIO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(47, 'Stock', 1010550000, 'STOCK VILLA HAYES', 800, 'VILLA HAYES ', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(48, 'Stock', 1010570000, 'STOCK BRASILIA 2', 700, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(49, 'Stock', 1010580000, 'STOCK SANTANI', 800, 'SANTANI', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(50, 'Stock', 1010600000, 'STOCK PERONI', 600, 'LUQUE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(51, 'Stock', 1010630000, 'STOCK J.A.SALDIVAR 2', 800, 'J.A.SALDIVAR', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(52, 'Stock', 1010640000, 'STOCK GUARAMBARE', 600, 'GUARAMBARE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(53, 'Stock', 1010650000, 'STOCK ORTIZ GUERRERO', 800, 'SAN LORENZO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(54, 'Stock', 1010660000, 'STOCK DE LA VICTORIA', 600, 'SAN LORENZO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(55, 'Stock', 1010710000, 'STOCK PALMA LOMA', 600, 'LUQUE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(56, 'Stock', 1010720000, 'STOCK FDO.MORA SUR', 700, 'FERNANDO DE LA MORA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(57, 'Stock', 1010740000, 'STOCK BASÍLICA', 990, 'CAACUPE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(58, 'Stock', 1010750000, 'STOCK CAACUPÉ II', 750, 'CAACUPE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(59, 'Stock', 1010760000, 'STOCK KM8 ACARAY', 600, 'CDE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(60, 'Stock', 1010770000, 'STOCK CARAPEGUA 1', 570, 'CARAPEGUA ', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(61, 'Stock', 1010780000, 'STOCK CARAPEGUA 2', 541, 'CARAPEGUA ', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(62, 'Stock', 1010790000, 'STOCK ITAUGUA 2', 800, 'ITAUGUA ', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(63, 'S6', 1020010000, 'S6 PORTAL', 2400, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(64, 'S6', 1020020000, 'S6 FDO DE LA MORA', 1000, 'FERNANDO DE LA MORA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(65, 'S6', 1020030000, 'S6 HIPERSEIS', 3700, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(66, 'S6', 1020040000, 'S6 SAN LORENZO', 2000, 'SAN LORENZO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(67, 'S6', 1020050000, 'S6 LAMBARE', 1700, 'LAMBARE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(68, 'S6', 1020060000, 'S6 ENCARNACION', 2800, 'ENCARNACION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(69, 'S6', 1020070000, 'S6 MBURUCUYA', 3000, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(70, 'S6', 1020080000, 'S6 LOS LAURELES', 2800, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(71, 'S6', 1020090000, 'S6 TOTAL', 2200, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(72, 'S6', 1020100000, 'S6 MUNDIMARK', 3100, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(73, 'S6', 1020110000, 'S6 LA NEGRITA', 2400, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(74, 'S6', 1020120000, 'S6 GRAN UNION', 800, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(75, 'S6', 1020130000, 'S6 DENIS ROA', 1600, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(76, 'S6', 1020140000, 'S6 VILLARRICA', 1800, 'VILLARRICA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(77, 'S6', 1020150000, 'S6 ENCARNACION 2', 2500, 'ENCARNACION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(78, 'S6', 1020160000, 'S6 CDE SUPERCARRETER', 2200, 'PDTE. FRANCO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(79, 'S6', 1020170000, 'S6 AREGUA', 1600, 'AREGUA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(80, 'S6', 1020180000, 'S6 LUQUE', 1700, 'LUQUE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(81, 'S6', 1020190000, 'S6 PASEO LA GALERIA', 3000, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(82, 'S6', 1020200000, 'S6 CAMBACUA', 2500, 'FERNANDO DE LA MORA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(83, 'S6', 1020210000, 'S6 3 DE FEBRERO CDE', 2500, 'CDE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(84, 'S6', 1020220000, 'S6 VILLETA', 1600, 'VILLETA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(85, 'S6', 1020240000, 'S6 VILLAMORRA', 1600, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(86, 'S6', 1020250000, 'S6 SAN BERNARDINO', 800, 'SAN BERNARDINO', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(87, 'S6', 1020260000, 'S6 ÑEMBY', 1500, 'ÑEMBY', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(88, 'S6', 1020270000, 'S6 LUQUE LAURELTY', 1800, 'LUQUE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(89, 'S6', 1020290000, 'S6 ESPAÑA', 1700, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(90, 'S6', 1020310000, 'S6 JAPÓN', 1800, 'ASUNCION', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(91, 'S6', 1020320000, 'S6 CAPIATA', 800, 'CAPIATA', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(92, 'S6', 1020330000, 'S6 MADERO', 800, 'LUQUE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(93, 'S6', 1020370000, 'S6 CLUB SIRIO', 600, 'LAMBARE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(94, 'S6', 1020380000, 'S6 CAACUPÉ', 2200, 'CAACUPE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(95, 'S6', 1020400000, 'S6 PARANÁ CTRY (CDE)', 800, 'CDE', '2025-07-23 19:23:39', '2025-07-23 19:23:39'),
(96, 'STOCK EXPRESS', 1040210000, 'ST EXP.PATRICIO ESC.', 200, 'LAMBARE', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(97, 'STOCK EXPRESS', 1040240000, 'ST EXP.TOBATI', 200, 'LAMBARE', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(98, 'STOCK EXPRESS', 1040280000, 'ST EXP. CNEL OVIEDO', 200, 'CNEL. OVIEDO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(99, 'STOCK EXPRESS', 1040340000, 'ST EXP. PALMA LOMA', 200, 'LUQUE', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(100, 'STOCK EXPRESS', 1040370000, 'ST EXP. PAI ÑU', 200, 'ÑEMBY', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(101, 'STOCK EXPRESS', 1040390000, 'ST EXP. TTE.MOLAS', 200, 'SAN LORENZO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(102, 'STOCK EXPRESS', 1040410000, 'ST EXP. CAMPO JORDAN', 200, 'SAN LORENZO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(103, 'STOCK EXPRESS', 1040430000, 'ST EXP. 10 DE JULIO', 200, 'FERNANDO DE LA MORA', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(104, 'STOCK EXPRESS', 1040450000, 'ST EXP.BRITEZ BORGES', 200, 'LUQUE', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(105, 'STOCK EXPRESS', 1040460000, 'ST EXP.PANCHIT.LOPEZ', 200, 'CAPIATA', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(106, 'STOCK EXPRESS', 1040490000, 'ST EXP. BOQUERON', 200, 'SAN LORENZO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(107, 'STOCK EXPRESS', 1040520000, 'ST EXP. PITIANTUTA', 200, 'FERNANDO DE LA MORA', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(108, 'STOCK EXPRESS', 1040590000, 'ST EXP. LAS MERCEDES', 200, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(109, 'STOCK EXPRESS', 1040600000, 'ST EXP.AVDA.PARAGUAY', 200, 'ÑEMBY', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(110, 'STOCK EXPRESS', 1040630000, 'ST EXP. GUATAMBU', 200, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(111, 'STOCK EXPRESS', 1040670000, 'ST EXP. PIQUETE', 200, 'LIMPIO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(112, 'STOCK EXPRESS', 1041110000, 'ST EXP. PRATT GILL', 200, 'SAN LORENZO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(113, 'S6  EXPRESS', 1050240000, 'S6 EXP.KUBITCHET', 200, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(114, 'S6  EXPRESS', 1050250000, 'S6 EXP. ACUARELA', 200, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(115, 'STOCK EXPRESS', 1040200000, 'ST EXP. ZEBALLOS CUE', 250, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(116, 'STOCK EXPRESS', 1040220000, 'ST EXP.PRIMER PDTE.', 250, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(117, 'STOCK EXPRESS', 1040300000, 'ST EXP. CABALLERO', 250, 'M.R.A', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(118, 'STOCK EXPRESS', 1040310000, 'ST EXP. PERU', 250, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(119, 'STOCK EXPRESS', 1040350000, 'ST EXP.GRAL SANTOS 2', 250, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(120, 'STOCK EXPRESS', 1040470000, 'ST EXP.DE LA VICTORI', 250, 'SAN LORENZO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(121, 'STOCK EXPRESS', 1040480000, 'ST EXP. MONTOYA', 250, 'SAN LORENZO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(122, 'STOCK EXPRESS', 1040510000, 'ST EXP. AMERICO PICC', 250, 'VILLA ELISA', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(123, 'STOCK EXPRESS', 1040560000, 'ST EXP. BRASILIA', 250, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(124, 'STOCK EXPRESS', 1040610000, 'ST EXP. CAMPO VIA', 250, 'SAN LORENZO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(125, 'S6  EXPRESS', 1050140000, 'S6 EXP. SHOP. OVIEDO', 250, 'CNEL. OVIEDO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(126, 'STOCK EXPRESS', 1040320000, 'ST EXP. SACRAMENTO', 300, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(127, 'STOCK EXPRESS', 1040330000, 'ST EXP. SAJONIA 2', 300, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(128, 'STOCK EXPRESS', 1040360000, 'ST EXP. ACOSTA ÑU', 300, 'SAN LORENZO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(129, 'STOCK EXPRESS', 1041040000, 'ST EXP.LAS RESIDENT.', 300, 'LUQUE', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(130, 'STOCK EXPRESS', 1041100000, 'ST EXP. FERNANDO', 300, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(131, 'S6  EXPRESS', 1050190000, 'S6 EXP. MOIETY', 300, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(132, 'STOCK EXPRESS', 1040230000, 'ST EXP.SAJONIA 1', 350, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(133, 'STOCK EXPRESS', 1040530000, 'ST EXP.VILLA HAYES 1', 350, 'VILLA HAYES', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(134, 'STOCK EXPRESS', 1041030000, 'ST EXP. MOLAS LOPEZ', 350, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(135, 'STOCK EXPRESS', 1041050000, 'ST EXP. AVENIDA', 350, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(136, 'STOCK EXPRESS', 1041060000, 'ST EXP. SAN LORENZO', 350, 'SAN LORENZO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(137, 'S6  EXPRESS', 1050180000, 'S6 EXP. SAN MARTIN', 350, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(138, 'S6  EXPRESS', 1050220000, 'S6 EXP.SAN BERNARD.2', 350, 'SAN BERNARDINO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(139, 'STOCK EXPRESS', 1040580000, 'ST EXP. SLDO. OVELAR', 400, 'FERNANDO DE LA MORA', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(140, 'STOCK EXPRESS', 1041070000, 'ST EXP. 21 PROYECT.', 400, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(141, 'S6  EXPRESS', 1050120000, 'S6 EXP. SAN BERNARD.', 400, 'SAN BERNARDINO', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(142, 'S6  EXPRESS', 1050130000, 'S6 EXP.MOLAS LÓPEZ 1', 400, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(143, 'S6  EXPRESS', 1050160000, 'S6 EXP. PRIMER PDTE.', 400, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(144, 'S6  EXPRESS', 1050230000, 'S6 EXP.AVDA.YACHT', 400, 'LAMBARE', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(145, 'S6  EXPRESS', 1050210000, 'S6 EXP. BOQUERON', 450, 'ASUNCION', '2025-07-23 19:26:02', '2025-07-23 19:26:02'),
(148, 'PROVEEDOR', 0, 'PROVEEDOR', 1200, 'SIN LOCALIDAD DEFINIDA', '2025-07-25 17:23:16', '2025-07-25 18:03:29'),
(150, 'DEPOSITO', 5010020000, 'DEP HENMY', 32000, 'CAPIATA R2', '2025-08-01 17:37:26', '2025-08-01 17:37:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `uso_combustible`
--

CREATE TABLE `uso_combustible` (
  `id` int NOT NULL,
  `nombre_conductor` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha` date NOT NULL,
  `vehiculo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `documento` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `numero_baucher` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `litros_cargados` int NOT NULL,
  `tipo_vehiculo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `chapa` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `registro_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tarjeta` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0000',
  `fecha_carga` date NOT NULL DEFAULT (curdate()),
  `hora_carga` time NOT NULL DEFAULT (curtime()),
  `foto_voucher` longtext COLLATE utf8mb4_unicode_ci,
  `foto_voucher_ruta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_recorrido` enum('abierto','cerrado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierto',
  `fecha_cierre` timestamp NULL DEFAULT NULL,
  `cerrado_por` int DEFAULT NULL,
  `reabierto_por` int DEFAULT NULL,
  `fecha_reapertura` timestamp NULL DEFAULT NULL,
  `motivo_reapertura` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `uso_combustible`
--

INSERT INTO `uso_combustible` (`id`, `nombre_conductor`, `user_id`, `usuario_id`, `fecha`, `vehiculo`, `documento`, `numero_baucher`, `litros_cargados`, `tipo_vehiculo`, `chapa`, `observaciones`, `fecha_registro`, `fecha_modificacion`, `registro_id`, `tarjeta`, `fecha_carga`, `hora_carga`, `foto_voucher`, `foto_voucher_ruta`, `estado_recorrido`, `fecha_cierre`, `cerrado_por`, `reabierto_por`, `fecha_reapertura`, `motivo_reapertura`) VALUES
(55, 'Juan Caceres', '1', '1', '2025-07-30', NULL, 'Recorrido', '989895', 35, 'particular', 'AAOB', NULL, '2025-07-30 17:01:09', '2025-08-08 14:27:08', NULL, '3232', '2025-07-30', '17:00:00', NULL, 'voucher_1754484157_68934dbde42d5.png', 'cerrado', '2025-08-08 14:27:08', 1, NULL, NULL, NULL),
(56, 'Juan Caceres', '1', '1', '2025-08-01', NULL, 'Recorrido', '989895', 35, 'particular', 'AAOB TOYOTA RACTIS', NULL, '2025-08-01 16:38:47', '2025-08-08 14:27:02', NULL, '3232', '2025-08-01', '12:00:00', NULL, 'voucher_1754483995_68934d1b3c7b8.png', 'cerrado', '2025-08-08 14:27:02', 1, NULL, NULL, NULL),
(57, 'Juan Caceres', '1', '1', '2025-08-02', NULL, 'Recorrido', '004682198572', 18, 'particular', 'AAOB TOYOTA RACTIS', NULL, '2025-08-02 12:29:13', '2025-08-08 14:43:45', NULL, '9299', '2025-07-31', '19:01:00', NULL, 'voucher_1754484286_68934e3e4037b.png', 'cerrado', '2025-08-08 14:43:45', 1, 1, '2025-08-08 14:27:44', 'dsd'),
(58, 'Juan Caceres', '1', '1', '2025-08-06', NULL, 'Recorrido', '004682198572', 30, 'particular', 'AAOB TOYOTA RACTIS', NULL, '2025-08-06 12:45:29', '2025-08-08 15:18:06', NULL, '3232', '2025-08-06', '12:45:00', NULL, 'voucher_1754484345_68934e79171ee.png', 'abierto', '2025-08-08 14:56:14', 1, 1, '2025-08-08 15:18:06', 'gfg'),
(59, 'Juan Caceres', '1', '1', '2025-08-08', NULL, 'Recorrido', '004682198572', 25, 'particular', 'AAOB TOYOTA RACTIS', NULL, '2025-08-08 14:29:03', '2025-08-08 15:20:50', NULL, '9299', '2025-08-08', '14:28:00', NULL, 'voucher_1754663343_689609afd8a8d.jpg', 'cerrado', '2025-08-08 15:20:50', 1, 1, '2025-08-08 15:08:55', 'gdf'),
(60, 'Juan Caceres', '1', '1', '2025-08-08', NULL, 'Ruteo', '004686869025', 30, 'movil_retail', 'AAOB TOYOTA RACTIS', NULL, '2025-08-08 21:45:36', '2025-08-08 21:46:20', NULL, '4858', '2025-08-08', '21:44:00', NULL, 'voucher_1754689536_689670007a861.png', 'cerrado', '2025-08-08 21:46:20', 1, NULL, NULL, NULL),
(61, 'Richard Villar', '26', '26', '2025-08-02', NULL, 'Recorrido', '4684298984', 35, 'particular', 'BUH 527', NULL, '2025-08-02 15:56:19', '2025-08-02 15:56:19', NULL, '1289', '2025-08-01', '14:40:00', NULL, 'voucher_1754139379_688e0af346a94.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(62, 'Mauro Carballo', '2', '2', '2025-08-02', NULL, 'Recorrido', '004683627521', 12, 'particular', 'BGN525', NULL, '2025-08-02 15:57:41', '2025-08-02 15:57:41', NULL, '1153', '2025-08-01', '10:54:00', NULL, 'voucher_1754139461_688e0b4539be8.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(63, 'Richard Romero ', '33', '33', '2025-08-01', NULL, 'Ruteo', '004686268806', 47, 'movil_retail', 'AAMX801', NULL, '2025-08-04 15:28:10', '2025-08-04 15:28:10', NULL, '7749', '2025-08-02', '09:28:00', NULL, 'voucher_1754310490_6890a75aceb3a.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(64, 'Pedro Gomez ', '36', '36', '2025-08-02', NULL, 'Recorrido', '004686869025', 10, 'particular', 'HGV656', NULL, '2025-08-04 15:36:26', '2025-08-06 16:23:06', NULL, '0917', '2025-08-02', '12:40:00', NULL, 'voucher_1754310986_6890a94a0f92a.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(65, 'Santiago Barreto', '20', '20', '2025-08-01', NULL, 'Recorrido', '004683246903', 30, 'particular', 'AAGG660', NULL, '2025-08-04 15:51:31', '2025-08-04 15:51:31', NULL, '1131', '2025-08-01', '08:37:00', NULL, 'voucher_1754311891_6890acd39b8b2.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(66, 'Héctor Petruccelli', '19', '19', '2025-08-05', NULL, 'Recorrido', '004690463281', 30, 'particular', 'HDH018', NULL, '2025-08-05 14:28:16', '2025-08-05 14:28:16', NULL, '0609', '2025-08-04', '06:34:00', NULL, 'voucher_1754393296_6891ead0bc454.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(67, 'Santiago Barreto', '20', '20', '2025-08-05', NULL, 'Recorrido', '004683246903', 30, 'particular', 'AAGG660', NULL, '2025-08-05 19:13:11', '2025-08-05 19:13:11', NULL, '1131', '2025-08-01', '08:37:00', NULL, NULL, 'abierto', NULL, NULL, NULL, NULL, NULL),
(68, 'Julio Barrios', '16', '16', '2025-08-05', NULL, 'Recorrido', '004688307212', 40, 'particular', 'OAU381', NULL, '2025-08-05 21:21:06', '2025-08-06 18:08:29', NULL, '9892', '2025-08-02', '21:50:00', NULL, 'voucher_1754492909_68936fed4f0b4.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(69, 'Héctor Petruccelli', '19', '19', '2025-08-06', NULL, 'Ruteo', '004691401240', 40, 'movil_retail', 'AAMX786', NULL, '2025-08-06 15:43:31', '2025-08-07 21:51:02', NULL, '7733', '2025-08-04', '13:54:00', NULL, 'voucher_1754592662_6894f596a6a46.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(70, 'Santiago Barreto', '20', '20', '2025-08-06', NULL, 'Recorrido', '004696551842', 20, 'particular', 'AAGG660', NULL, '2025-08-07 00:24:30', '2025-08-07 00:24:30', NULL, '1131', '2025-08-06', '16:16:00', NULL, 'voucher_1754515470_6893c80ed0a36.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(71, 'Santiago Barreto', '20', '20', '2025-08-06', NULL, 'Ruteo', '004696332208', 45, 'movil_retail', 'AAAA972', NULL, '2025-08-08 15:50:40', '2025-08-08 15:50:40', NULL, '7036', '2025-08-06', '14:40:00', NULL, 'voucher_1754657440_6895f2a0634e2.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(72, 'Juan Santander', '25', '25', '2025-07-22', NULL, 'Ruteo', '004662282326', 40, 'movil_retail', 'AAAA972', NULL, '2025-08-08 15:53:58', '2025-08-08 15:53:58', NULL, '7036', '2025-07-22', '16:40:00', NULL, 'voucher_1754657638_6895f3668de90.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(73, 'Juan Santander', '25', '25', '2025-07-25', NULL, 'Ruteo', '004667471536', 38, 'movil_retail', 'AAAA972', NULL, '2025-08-08 16:00:17', '2025-08-08 16:00:17', NULL, '7036', '2025-07-25', '23:21:00', NULL, 'voucher_1754658017_6895f4e1b8bef.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(74, 'Héctor Petruccelli', '25', '25', '2025-08-01', NULL, 'Ruteo', '004683986636', 45, 'movil_retail', 'AAAA972', NULL, '2025-08-08 16:05:00', '2025-08-08 16:05:00', NULL, '7036', '2025-08-01', '12:52:00', NULL, 'voucher_1754658300_6895f5fc08440.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(75, 'Matías Núñez ', '29', '29', '2025-08-08', NULL, 'Recorrido', '004695412894', 8, 'particular', 'AACU', NULL, '2025-08-08 16:11:42', '2025-08-08 16:11:42', NULL, '1451', '2025-08-06', '08:17:00', NULL, 'voucher_1754658702_6895f78e45f46.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(76, 'Talia Alcaraz', '45', '45', '2025-08-11', NULL, '4835971', '004689850729', 10, 'particular', 'AAKM785', NULL, '2025-08-11 14:31:26', '2025-08-11 14:35:50', NULL, '1201', '2025-08-03', '18:13:00', NULL, 'voucher_1754911886_6899d48e15564.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(77, 'Talia Alcaraz', '45', '45', '2025-08-11', NULL, '4835971', '004704994504', 9, 'particular', 'AAKM785', NULL, '2025-08-11 15:21:56', '2025-08-11 15:21:56', NULL, '1201', '2025-08-10', '12:51:00', NULL, 'voucher_1754914916_6899e064534b4.jpg', 'abierto', NULL, NULL, NULL, NULL, NULL),
(78, 'Pedro Gomez ', '36', '36', '2025-08-09', NULL, 'Recorrido', '004703652761', 10, 'particular', 'HGV656', NULL, '2025-08-11 22:22:20', '2025-08-12 14:43:19', NULL, '0917', '2025-08-09', '17:50:00', NULL, 'voucher_1754940140_689a42ec00b75.jpg', 'cerrado', NULL, NULL, NULL, NULL, NULL),
(79, 'Juan Caceres', '1', '1', '2025-08-16', NULL, 'Recorrido', '004682198572', 30, 'particular', 'AAOB TOYOTA RACTIS', NULL, '2025-08-16 14:20:23', '2025-08-16 14:49:47', NULL, '3232', '2025-08-16', '14:19:00', NULL, 'voucher_1755354023_68a093a70d765.jpg', 'abierto', '2025-08-16 14:29:14', 1, 1, '2025-08-16 14:49:47', 'vc');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `uso_combustible_recorridos`
--

CREATE TABLE `uso_combustible_recorridos` (
  `id` int NOT NULL,
  `uso_combustible_id` int NOT NULL,
  `orden_secuencial` int NOT NULL DEFAULT '1',
  `origen` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destino` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `km_sucursales` decimal(10,2) DEFAULT NULL COMMENT 'Kilómetros aproximados entre sucursales',
  `comentarios_sector` text COLLATE utf8mb4_unicode_ci,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `uso_combustible_recorridos`
--

INSERT INTO `uso_combustible_recorridos` (`id`, `uso_combustible_id`, `orden_secuencial`, `origen`, `destino`, `km_sucursales`, `comentarios_sector`, `fecha_registro`, `fecha_modificacion`) VALUES
(78, 56, 1, 'S6 GRAN UNION', 'DELIMARK DELIMARK', NULL, NULL, '2025-08-06 12:39:55', '2025-08-06 12:39:55'),
(79, 56, 1, 'DELIMARK DELIMARK', 'S6 DENIS ROA', NULL, NULL, '2025-08-06 12:39:55', '2025-08-06 12:39:55'),
(80, 56, 1, 'S6 DENIS ROA', 'S6 MADERO', NULL, NULL, '2025-08-06 12:39:55', '2025-08-06 12:39:55'),
(81, 56, 1, 'S6 MADERO', 'STOCK VILLA ELISA', NULL, NULL, '2025-08-06 12:39:55', '2025-08-06 12:39:55'),
(89, 55, 1, 'STOCK RCA ARGENTINA', 'STOCK ACCESO SUR', NULL, NULL, '2025-08-06 12:43:03', '2025-08-06 12:43:03'),
(90, 55, 1, 'STOCK ACCESO SUR', 'STOCK AVELINO MARTI', NULL, NULL, '2025-08-06 12:43:03', '2025-08-06 12:43:03'),
(91, 55, 1, 'STOCK AVELINO MARTI', 'STOCK RCA ARGENTINA', NULL, NULL, '2025-08-06 12:43:03', '2025-08-06 12:43:03'),
(93, 57, 1, 'DELIMARK DELIMARK', 'S6 3 DE FEBRERO CDE', NULL, NULL, '2025-08-06 12:44:57', '2025-08-06 12:44:57'),
(96, 58, 1, 'DEP HENMY', 'S6 3 DE FEBRERO CDE', 40.00, NULL, '2025-08-06 13:11:18', '2025-08-06 13:11:18'),
(107, 59, 1, 'S6 3 DE FEBRERO CDE', 'S6 3 DE FEBRERO CDE', 23.00, '', '2025-08-08 14:39:35', '2025-08-08 14:39:35'),
(108, 59, 1, 'S6 CAACUPÉ', 'S6 EXP. BOQUERON', 23.00, '', '2025-08-08 14:39:35', '2025-08-08 14:39:35'),
(109, 59, 1, 'S6 EXP. PRIMER PDTE.', 'S6 ÑEMBY', 232.00, '', '2025-08-08 14:39:35', '2025-08-08 14:39:35'),
(110, 59, 1, 'S6 ÑEMBY', 'DELIMARK DELIMARK', 30.00, '', '2025-08-08 14:39:35', '2025-08-08 14:39:35'),
(111, 59, 1, 'DELIMARK DELIMARK', 'DEP HENMY', 66.00, '', '2025-08-08 14:39:35', '2025-08-08 14:39:35'),
(114, 60, 1, 'S6 DENIS ROA', 'DELIMARK DELIMARK', 4.00, '', '2025-08-08 21:46:10', '2025-08-08 21:46:10'),
(115, 60, 1, 'DELIMARK DELIMARK', 'S6 EXP. BOQUERON', 6.00, '', '2025-08-08 21:46:10', '2025-08-08 21:46:10'),
(116, 60, 1, 'S6 EXP. BOQUERON', 'S6 ESPAÑA', 4.00, '', '2025-08-08 21:46:10', '2025-08-08 21:46:10'),
(117, 79, 1, 'DELIMARK DELIMARK', 'DEP HENMY', 5.00, NULL, '2025-08-16 14:20:23', '2025-08-16 14:20:23'),
(118, 79, 2, 'DEP HENMY', 'S6 ENCARNACION 2', 200.00, NULL, '2025-08-16 14:20:23', '2025-08-16 14:20:23');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('administrador','tecnico','supervisor','analista') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tecnico',
  `codigo_tecnico` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `rol`, `codigo_tecnico`, `estado`) VALUES
(1, 'jucaceres', '$2y$10$24vEvldevqhJzTHAf9hV3.WGPcsmOF8iYx.d4QwZEwKIbAW8II0ka', 'Juan Caceres', 'administrador', NULL, 'activo'),
(2, 'macarballo', '$2y$10$LlOL2CHeTAY48S5fGuTZIeGjXYCjSj4nUdFh68YqFV3KaX2O025Ga', 'Mauro Carballo', 'tecnico', NULL, 'activo'),
(4, 'vaguero', '$2y$10$Xi3rHhfFm6O8NXOiJZloeeUz2Ro.1uGz1e6BlCDF/qISS4mK5ngU6', 'Victor Aguero', 'tecnico', NULL, 'activo'),
(16, 'jubarrios', '$2y$10$qvmmQnqW.axC12VXavEgQO6jYfXD6j7icWCC.FC75i4QDKCHKM44.', 'Julio Barrios', 'administrador', NULL, 'activo'),
(19, 'hpetruccelli', '$2y$10$ffN9G0DC1JPOPL/LjxUzpOD1RuR9/mkrk27DEZsnSFD.zac7IzjZ2', 'Héctor Petruccelli', 'tecnico', '105', 'activo'),
(20, 'sbarreto', '$2y$10$yP7vP6LgBt8a2/ORACc8eeDVL/uhzpqmSxdnRFzVzeYryPxOvkyK2', 'Santiago Barreto', 'tecnico', '266', 'activo'),
(21, 'cdelvalle', '$2y$10$bZtgakpwxdgHDJUwAhC/7uGAtC2OUt.K8OKRnXJ1kWLUzHbpxXrTi', 'Cristóbal Delvalle', 'tecnico', '105', 'inactivo'),
(22, 'gcardenas', '$2y$10$oNmWJsoseO.7u/QxUoBDROKaPOj9RX3Auu7DEQZo7DwE0p8asN4Iq', 'Gustavo Cárdenas ', 'tecnico', '288', 'activo'),
(23, 'jolopez', '$2y$10$to6.Fwt57vrj9qeCXktqkeH9T4EM30WyzYVzN6fkzm62cKXf7opOq', 'José Lopez ', 'tecnico', '92', 'activo'),
(24, 'jubenitez', '$2y$10$tZtQG2aoudEzyVR9l9yrveghBTmhGPcay9YBnp76.qYpt4PdlL56y', 'Juan Benítez', 'tecnico', '240', 'activo'),
(25, 'jsantander', '$2y$10$k/7vWVYg1f7h7Vndb1JW7.TNEel1P9pwZb92cOGU0RPZAkaFo5yFm', 'Juan Santander', 'tecnico', '98', 'activo'),
(26, 'rvillar', '$2y$10$gu2jTugA5vR4ltbe36CfcOt6XcVnxlGdybQdG7g2mPmzdIiO6f4ry', 'Richard Villar', 'tecnico', '96', 'activo'),
(27, 'jogarcia', '$2y$10$terRIT3A5jhCNiwQpTBNfem5xpOKpHpGejTyYgYCb3edoxa8vXxO2', 'Jonathan Garcia', 'tecnico', '261', 'activo'),
(28, 'pgimenez', '$2y$10$9xyh57YmSZl7.ovqWZFYV.Vaf864yti0fHhucygL72N0YV4o485UO', 'Pedro Giménez', 'tecnico', '366', 'activo'),
(29, 'matonunez', '$2y$10$wWYhzfoa8EwAVjvOLVHKL.H6HhicJQNb75FuLboXwnHIe2TE03mQK', 'Matías Núñez ', 'tecnico', '85', 'activo'),
(30, 'msanabria', '$2y$10$kaEy.HlSvob337zx1LapTO0NqaeT3W1tqdwcZeuc/7A7q3iBfaJzq', 'Matías Sanabria ', 'tecnico', '286', 'activo'),
(31, 'frportillo', '$2y$10$.2Gzc//017qqwKv2oBOesurTy./eo2nyyRpm0T9ecmjGsVUxd.dGi', 'Franco Portillo', 'tecnico', '330', 'activo'),
(32, 'ngaona', '$2y$10$cImGBZKG.cxcEWzeRL6GJO20u3Eqh5D2b8XFXbWStczrvBzvJLQA2', 'Néstor Gaona ', 'tecnico', '355', 'inactivo'),
(33, 'rromero', '$2y$10$ByAqWC6AwphtEAd2EkYZyeQv5bKSPm37isSIoTHD0KcGcURF6hRTy', 'Richard Romero ', 'tecnico', '359', 'activo'),
(34, 'edcañete', '$2y$10$Xdye.U5VYm8xTgCzfx72W.WgPvn8yFNusIrjPmU/L42aMOfwE1nde', 'Edelio Cañete ', 'tecnico', '32', 'inactivo'),
(35, 'jobogado', '$2y$10$0jdxToXaRwWmNiFJOn2aku3.9ddFjmy75tYvK6S8CuxiHY21DcoyK', 'Jonathan Bogado ', 'tecnico', '360', 'activo'),
(36, 'pgomez', '$2y$10$hKmOnbeRIiBdyxt1mwhvquAbX8h8kJotHAAgF7xXjZHFQE5arPub.', 'Pedro Gomez ', 'tecnico', '270', 'activo'),
(37, 'dgauto', '$2y$10$9uTZJPgKd9NJ/tPPT2dIwu/I42V5MUIhj84q9sKjKG4YUuVQiXOwq', 'David Gauto ', 'tecnico', '365', 'activo'),
(39, 'gucandia', '$2y$10$YgF9hj/aMdzF9SDlQCUsLuEaE2SwIwX8NzAe9hkewyEbHmjjkHylq', 'Gustavo Candia', 'tecnico', '568', 'activo'),
(40, 'rodominguez', '$2y$10$GKEryNknxSISuxic6indbOmpGRH3z5vu4Xkf2UD4NcQx94WVsKgWO', 'Romina Domínguez', 'administrador', NULL, 'activo'),
(41, 'oarce', '$2y$10$p.A2rwPbGxskl9iXCOo48eX0KYbmEwdA9YcGQ8Fls3vKPLxE6O.6q', 'Osvaldo Arce', 'administrador', NULL, 'activo'),
(42, 'aramos', '$2y$10$plU918husvqkMmXdg4Rq8OsPEAUiTlCD0NSI3/2IA0v//U8cbKtFa', 'Alejandro Ramos ', 'administrador', NULL, 'activo'),
(43, 'mvera', '$2y$10$xwPZUE4dzhVcOQJYZ4FlT.gaRR3sxDv7520k9flcsCMQgIIFHvTzi', 'Miguel Vera', 'tecnico', NULL, 'activo'),
(45, 'talcaraz', '$2y$10$SrvfeIBrKSPmFiXGm2JjH.4fLBQlS.zKqiModd8hjn67YjbLLjJJ6', 'Jonathan Alcaraz', 'tecnico', '1', 'activo'),
(46, 'jcaceres', '$2y$10$tnbpYMlkf5wUH2piyEkeduqTh3bSj1y8ui3ujsKc02btGx4bV.OEm', 'Juan Caceres', 'analista', NULL, 'activo'),
(47, 'prueba', '$2y$10$G6owZqLfEyin4cWNFLAAtu07ZqqNqr7.Y3VaQQWRjFc9czi6FMSWe', 'prueba', 'analista', NULL, 'activo'),
(48, 'asanabria', '$2y$10$OJMYEYpd6vmh8csu1UxdWOtiQ2qRUu2I5b5q0u0Y..OFHan3hYj92', 'Alicia Sanabria', 'analista', NULL, 'activo');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `acuse_recibo`
--
ALTER TABLE `acuse_recibo`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tecnico_id` (`tecnico_id`);

--
-- Indices de la tabla `distancias_cache`
--
ALTER TABLE `distancias_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_route` (`lat_origen`,`lon_origen`,`lat_destino`,`lon_destino`);

--
-- Indices de la tabla `fotos_informe_tecnico`
--
ALTER TABLE `fotos_informe_tecnico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `informe_id` (`informe_id`);

--
-- Indices de la tabla `informe_tecnico`
--
ALTER TABLE `informe_tecnico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_tecnico` (`tecnico_id`);

--
-- Indices de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patrimonio_ups` (`patrimonio_ups`);

--
-- Indices de la tabla `mantenimiento_ups`
--
ALTER TABLE `mantenimiento_ups`
  ADD PRIMARY KEY (`patrimonio`);

--
-- Indices de la tabla `reclamos_zonas`
--
ALTER TABLE `reclamos_zonas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `zona_mes_anio` (`zona`,`mes`,`anio`),
  ADD UNIQUE KEY `unique_registro` (`zona`,`mes`,`anio`);

--
-- Indices de la tabla `registro_actividades`
--
ALTER TABLE `registro_actividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `reporte_cierres`
--
ALTER TABLE `reporte_cierres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_tecnico_mes_anio` (`tecnico_id`,`mes`,`anio`);

--
-- Indices de la tabla `sesiones_activas`
--
ALTER TABLE `sesiones_activas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`user_id`);

--
-- Indices de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_segmento` (`segmento`),
  ADD KEY `idx_localidad` (`localidad`);

--
-- Indices de la tabla `uso_combustible`
--
ALTER TABLE `uso_combustible`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uso_combustible_fecha` (`fecha`),
  ADD KEY `idx_uso_combustible_user_id` (`user_id`),
  ADD KEY `idx_uso_combustible_estado` (`estado_recorrido`),
  ADD KEY `idx_uso_combustible_fecha_cierre` (`fecha_cierre`),
  ADD KEY `fk_cerrado_por` (`cerrado_por`),
  ADD KEY `fk_reabierto_por` (`reabierto_por`);

--
-- Indices de la tabla `uso_combustible_recorridos`
--
ALTER TABLE `uso_combustible_recorridos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recorridos_uso_combustible_id` (`uso_combustible_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `acuse_recibo`
--
ALTER TABLE `acuse_recibo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `distancias_cache`
--
ALTER TABLE `distancias_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fotos_informe_tecnico`
--
ALTER TABLE `fotos_informe_tecnico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `informe_tecnico`
--
ALTER TABLE `informe_tecnico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de la tabla `reclamos_zonas`
--
ALTER TABLE `reclamos_zonas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT de la tabla `registro_actividades`
--
ALTER TABLE `registro_actividades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=502;

--
-- AUTO_INCREMENT de la tabla `reporte_cierres`
--
ALTER TABLE `reporte_cierres`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT de la tabla `sesiones_activas`
--
ALTER TABLE `sesiones_activas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT de la tabla `sucursales`
--
ALTER TABLE `sucursales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT de la tabla `uso_combustible`
--
ALTER TABLE `uso_combustible`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT de la tabla `uso_combustible_recorridos`
--
ALTER TABLE `uso_combustible_recorridos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `acuse_recibo`
--
ALTER TABLE `acuse_recibo`
  ADD CONSTRAINT `acuse_recibo_ibfk_1` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `fotos_informe_tecnico`
--
ALTER TABLE `fotos_informe_tecnico`
  ADD CONSTRAINT `fotos_informe_tecnico_ibfk_1` FOREIGN KEY (`informe_id`) REFERENCES `informe_tecnico` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `informe_tecnico`
--
ALTER TABLE `informe_tecnico`
  ADD CONSTRAINT `fk_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  ADD CONSTRAINT `fk_patrimonio` FOREIGN KEY (`patrimonio_ups`) REFERENCES `mantenimiento_ups` (`patrimonio`);

--
-- Filtros para la tabla `registro_actividades`
--
ALTER TABLE `registro_actividades`
  ADD CONSTRAINT `registro_actividades_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `reporte_cierres`
--
ALTER TABLE `reporte_cierres`
  ADD CONSTRAINT `reporte_cierres_ibfk_1` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `sesiones_activas`
--
ALTER TABLE `sesiones_activas`
  ADD CONSTRAINT `sesiones_activas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `uso_combustible`
--
ALTER TABLE `uso_combustible`
  ADD CONSTRAINT `fk_cerrado_por` FOREIGN KEY (`cerrado_por`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_reabierto_por` FOREIGN KEY (`reabierto_por`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `uso_combustible_recorridos`
--
ALTER TABLE `uso_combustible_recorridos`
  ADD CONSTRAINT `fk_recorridos_combustible` FOREIGN KEY (`uso_combustible_id`) REFERENCES `uso_combustible` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
