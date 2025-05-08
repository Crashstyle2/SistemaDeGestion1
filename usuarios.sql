-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 02-05-2025 a las 19:19:30
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
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('administrador','tecnico') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tecnico'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `rol`) VALUES
(1, 'jucaceres', '$2y$10$24vEvldevqhJzTHAf9hV3.WGPcsmOF8iYx.d4QwZEwKIbAW8II0ka', 'Juan Caceres', 'administrador'),
(2, 'macarballo', '$2y$10$1dtn71JHRhPcBaklCuR9Q.aDrd0rLaGNfRTKvcYmTai3WArgr37YC', 'Mauro Carballo', 'tecnico'),
(4, 'vaguero', '$2y$10$kktphmHDVr52UOT9y3A11uo/WSIPDtileP.2mqBpmFsaQQngITgje', 'Victor Aguero', 'tecnico'),
(16, 'jubarrios', '$2y$10$COK0YxDnYEy/eMC3tpj3lO2/Whwjt1FergoJrICVu5HoSjXos1j1u', 'Julio Barrios', 'administrador'),
(19, 'hpetruccelli', '$2y$10$ffN9G0DC1JPOPL/LjxUzpOD1RuR9/mkrk27DEZsnSFD.zac7IzjZ2', 'Héctor Petruccelli', 'tecnico'),
(20, 'sbarreto', '$2y$10$yP7vP6LgBt8a2/ORACc8eeDVL/uhzpqmSxdnRFzVzeYryPxOvkyK2', 'Santiago Barreto', 'tecnico'),
(21, 'cdelvalle', '$2y$10$bZtgakpwxdgHDJUwAhC/7uGAtC2OUt.K8OKRnXJ1kWLUzHbpxXrTi', 'Cristóbal Delvalle', 'tecnico'),
(22, 'gcardenas', '$2y$10$oNmWJsoseO.7u/QxUoBDROKaPOj9RX3Auu7DEQZo7DwE0p8asN4Iq', 'Gustavo Cárdenas ', 'tecnico'),
(23, 'jolopez', '$2y$10$to6.Fwt57vrj9qeCXktqkeH9T4EM30WyzYVzN6fkzm62cKXf7opOq', 'José Lopez ', 'tecnico'),
(24, 'jubenitez', '$2y$10$tZtQG2aoudEzyVR9l9yrveghBTmhGPcay9YBnp76.qYpt4PdlL56y', 'Juan Benítez', 'tecnico'),
(25, 'jsantander', '$2y$10$k/7vWVYg1f7h7Vndb1JW7.TNEel1P9pwZb92cOGU0RPZAkaFo5yFm', 'Juan Santander', 'tecnico'),
(26, 'rvillar', '$2y$10$gu2jTugA5vR4ltbe36CfcOt6XcVnxlGdybQdG7g2mPmzdIiO6f4ry', 'Richard Villar', 'tecnico'),
(27, 'jogarcia', '$2y$10$terRIT3A5jhCNiwQpTBNfem5xpOKpHpGejTyYgYCb3edoxa8vXxO2', 'Jonathan Garcia', 'tecnico'),
(28, 'pgimenez', '$2y$10$9xyh57YmSZl7.ovqWZFYV.Vaf864yti0fHhucygL72N0YV4o485UO', 'Pedro Giménez', 'tecnico'),
(29, 'matonunez', '$2y$10$wWYhzfoa8EwAVjvOLVHKL.H6HhicJQNb75FuLboXwnHIe2TE03mQK', 'Matías Núñez ', 'tecnico'),
(30, 'msanabria', '$2y$10$kaEy.HlSvob337zx1LapTO0NqaeT3W1tqdwcZeuc/7A7q3iBfaJzq', 'Matías Sanabria ', 'tecnico'),
(31, 'frportillo', '$2y$10$.2Gzc//017qqwKv2oBOesurTy./eo2nyyRpm0T9ecmjGsVUxd.dGi', 'Franco Portillo', 'tecnico'),
(32, 'ngaona', '$2y$10$cImGBZKG.cxcEWzeRL6GJO20u3Eqh5D2b8XFXbWStczrvBzvJLQA2', 'Néstor Gaona ', 'tecnico'),
(33, 'rromero', '$2y$10$ByAqWC6AwphtEAd2EkYZyeQv5bKSPm37isSIoTHD0KcGcURF6hRTy', 'Richard Romero ', 'tecnico'),
(34, 'edcañete', '$2y$10$Xdye.U5VYm8xTgCzfx72W.WgPvn8yFNusIrjPmU/L42aMOfwE1nde', 'Edelio Cañete ', 'tecnico'),
(35, 'jobogado', '$2y$10$0jdxToXaRwWmNiFJOn2aku3.9ddFjmy75tYvK6S8CuxiHY21DcoyK', 'Jonathan Bogado ', 'tecnico'),
(36, 'pgomez', '$2y$10$hKmOnbeRIiBdyxt1mwhvquAbX8h8kJotHAAgF7xXjZHFQE5arPub.', 'Pedro Gomez ', 'tecnico'),
(37, 'dgauto', '$2y$10$9uTZJPgKd9NJ/tPPT2dIwu/I42V5MUIhj84q9sKjKG4YUuVQiXOwq', 'David Gauto ', 'tecnico'),
(38, 'jcaceres', '$2y$10$IWHVQ2OOLKRn4UPMNuXTNub6WjtR5lZZ2HVlnEojkaDSOavRgLrOK', 'Usuario Demo', 'tecnico'),
(39, 'gucandia', '$2y$10$YgF9hj/aMdzF9SDlQCUsLuEaE2SwIwX8NzAe9hkewyEbHmjjkHylq', 'Gustavo Candia', 'tecnico'),
(40, 'rodominguez', '$2y$10$GKEryNknxSISuxic6indbOmpGRH3z5vu4Xkf2UD4NcQx94WVsKgWO', 'Romina Domínguez', 'administrador'),
(41, 'oarce', '$2y$10$p.A2rwPbGxskl9iXCOo48eX0KYbmEwdA9YcGQ8Fls3vKPLxE6O.6q', 'Osvaldo Arce', 'administrador'),
(42, 'aramos', '$2y$10$plU918husvqkMmXdg4Rq8OsPEAUiTlCD0NSI3/2IA0v//U8cbKtFa', 'Alejandro Ramos ', 'administrador'),
(43, 'mvera', '$2y$10$xwPZUE4dzhVcOQJYZ4FlT.gaRR3sxDv7520k9flcsCMQgIIFHvTzi', 'Miguel Vera', 'tecnico'),
(45, 'talcaraz', '$2y$10$SrvfeIBrKSPmFiXGm2JjH.4fLBQlS.zKqiModd8hjn67YjbLLjJJ6', 'Talia Alcaraz', 'tecnico');

--
-- Índices para tablas volcadas
--

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
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
