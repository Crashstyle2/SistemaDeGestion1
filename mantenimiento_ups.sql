-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost:3306
-- Tiempo de generación: 29-04-2025 a las 18:55:42
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
  `firma_digital` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tecnico_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `informe_tecnico`
--

INSERT INTO `informe_tecnico` (`id`, `local`, `sector`, `equipo_asistido`, `orden_trabajo`, `patrimonio`, `jefe_turno`, `observaciones`, `firma_digital`, `fecha_creacion`, `tecnico_id`) VALUES
(14, 'S6 MBURUCUYA', 'CODIFICACION', 'PC DE ADMINISTRACION ', '10000', '124621', 'Juan Caceres', 'fgfg', '', '2025-04-29 12:29:35', 1),
(15, 'S6 MBURUCUYA', 'CODIFICACION', 'PC DE ADMINISTRACION ', '10000', '124621', 'Juan Caceres', 'fdfd', '', '2025-04-29 12:47:49', 1),
(17, 'S6 MBURUCUYA', 'CODIFICACION', 'PC DE ADMINISTRACION ', '10000', '124621', 'Juan Caceres', 'trrre', '', '2025-04-29 13:19:46', 1),
(20, 'S6 MBURUCUYA', 'CODIFICACION', 'PC DE ADMINISTRACION ', '10000', '124621', 'Juan Caceres', 'dsds', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAABVAAAADICAYAAAAOc8rBAAAAAXNSR0IArs4c6QAAIABJREFUeF7t3Qu0vWVdJ/BvXAQETNBQJkREilFHXRg6WmRq5KVGZfISas1Sw5pJbY2XWakjiFzSjGRGXNY0os1oiJdcZDqMlqsplRiki2l4F/MCJIIhyEUR5v01z17tTv/zP3u/Z59z3r3fz7vWZu+z93t5ns/z/s/hfM9z+Z7YCBAgQIAAAQIECBAgQIAAAQIECBAgQGCXAt/DhQABAgQIECBAgAABAgQIECBAgAABAgR2LSBAdWcQIECAAAECBAgQIECAAAECBAgQIEBgHQEBqluDAAECBAgQIECAAAECBAgQIECAAAECAlT3AAECBAgQIECAAAECBAgQIECAAAECBOYT0AN1Pi97EyBAgAABAgQIECBAgAABAgQIECAwIgEB6ogaW1UJECBAgAABAgQIECBAgAABAgQIEJhPQIA6n5e9CRAgQIAAAQIECBAgQIAAAQIECBAYkYAAdUSNraoECBAgQIAAAQIECBAgQIAAAQIECMwnIECdz8veBAgQIECAAAECBAgQIECAAAECBAiMSECAOqLGVlUCBAgQIECAAAECBAgQIECAAAECBOYTEKDO52VvAgQIECBAgAABAgQIECBAgAABAgRGJCBAHVFjqyoBAgQIECBAgAABAgQIECBAgAABAvMJCFDn87I3AQIECBAgQIAAAQIECBAgQIAAAQIjEhCgjqixVZUAAQIECBAgQIAAAQIECBAgQIAAgfkEBKjzedmbAAECBAgQIECAAAECBAgQIECAAIERCQhQR9TYqkqAAAECBAgQIECAAAECBAgQIECAwHwCAtT5vOxNgAABAgQIECBAgAABAgQIECBAgMCIBASoI2psVSVAgAABAgQIECBAgAABAgQIECBAYD4BAep8XvYmQIAAAQIECBAgQIAAAQIECBAgQGBEAgLUETW2qhIgQIAAAQIECBAgQIAAAQIECBAgMJ+AAHU+L3sTIECAAAECBAgQIECAAAECBAgQIDAiAQHqiBpbVQkQIECAAAECBAgQIECAAAECBAgQmE9AgDqfl70JECBAgAABAgQIECBAgAABAgQIEBiRgAB1RI2tqgQIECBAgAABAgQIECBAgAABAgQIzCcgQJ3Py94ECBAgQIAAAQIECBAgQIAAAQIECIxIQIA6osZWVQIECBAgQIAAAQIECBAgQIAAAQIE5hMQoM7nZW8CE4F9k+yf5I5rHvX+AWveu0uShyR5b5JLknwqyQ0oCRAgQIAAAQIECBAgQIAAAQIEhi8gQB1+Gynh/AIHrhNurg07+35dwelmt68k+WSSy1qgWq/r8bXNntjxBAgQIECAAAECBAgQIECAAAECixMQoC7O0pkWK3Bkkl9L8tUke++ip+euws87L7YIG57t75PcOMNjzyQ/mOSWJFWveyep93a1faOFqpNAtZ6rx+oXk9y+YYnsQIAAAQIECBAgQIAAAQIECBAgsFABAepCOZ1sAQI/3QWGJyZ5yibO9a1dhJr13k0zhJ3TgejaYybnvX4TZatDKxD+gST3WfM4ugXFuzp9levTrZfqdLj62STf2WR5HE6AAAECBAgQIECAAAECBAgQILCOgADVrTEEgce00PTJbf7QKlPNEfqZNm/o12cIPifh5s1DqFDPMtS/xyNaqPovk9w3ST3fL8l6vWu/m+Tzzaou++IWtPYsgsMIECBAgAABAgQIECBAgAABAgSmBQSo7oedEKj77mFd0Pe0brj6U5Mc0gpRvSz/IMnbklyY5Ns7UbiBXrOM1vZYra8PW1PeCpJ/NcnrLFQ10JZULAIECBAgQIAAAQIECBAgQGCpBASoS9VcS1/YB7bQtIbo37PVpoaff6CFphckqQDQNrvAAS1Y/ZEkz01yVDu05lI9S5A6O6Q9CRAgQIAAAQIECBAgQIAAAQK7EhCgui+2WuBeSZ7RgtMakl7bbUk+1C2sdF43PP2dSSrssy1G4KFJXpnk0e1017Qg9fV6pC4G2FkIECBAgAABAgQIECBAgACBcQkIUMfV3ttV27u3wLSG6D946qKXtp6m5ye5YrsKM9LrVJB6WpKfaPW/NsnFrZfqF0dqotoECBAgQIAAAQIECBAgQIAAgbkFBKhzkzlgHYGDktQiUBWa/lgX3u3R9vtUC02rt+nn6G27QAWppyaphbpqe1eSp2x7KVyQAAECBAgQIECAAAECBAgQILCkAgLUJW24gRR7/yRPaKHpY5Ps3cr1pW71+OplWotB/dVAyjr2YvyvJI9L8sIkZ48dQ/0JECBAgAABAgQIECBAgAABArMKCFBnlbLfROAO3VDwCkurp2mFp3dsH1zd5jOt0PQjSW5HNiiBP0tSvVEf3uafHVThFIYAAQIECBAgQIAAAQIECBAgMFQBAepQW2ZY5arh+I9ooemTktRw/dq+meSCthjUHyX57rCKrTRTAhVw3zXJoUmuIkOAAAECBAgQIECAAAECBAgQIDCbgAB1Nqex7vWQJE9P8tQWvJXDzd3CUO9rw/Pfm+SWseIsUb3vlOS61lb7LlG5FZUAAQIECBAgQIAAAQIECBAgsOMCAtQdb4LBFeA+SZ7Repse2Up3a5IPttD03UmuH1ypFWh3Ascm+Wibj/YYVAQIECBAgAABAgQIECBAgAABArMLCFBnt1rlPe/ZAtOa1/QBraI1h+lFbXj+O5J8fZUBVrxu1a7ntTlqqzexjQABAgQIECBAgAABAgQIECBAYEYBAeqMUCu42yFJfqYFpw+bqt9ftZ6mtRjUl1ew3mOs0slJTkvyqiQvGyOAOhMgQIAAAQIECBAgQIAAAQIE+goIUPvKLedxByZ5cgtNH5Vkz1aNzyY5P8lbuzlPP7OcVVPq3Qj8zyQ/l+TZSd5MigABAgQIECBAgAABAgQIECBAYHYBAersVsu6Zy0a9Pi2GNTjul6I+7SKfDXJ21tv00uXtXLKPZPAnyV5aJKHJ/nQTEfYiQABAgQIECBAgAABAgQIECBA4B8EBKireSPs1c1f+ujW0/SEJAe0al6b5F0tNP3TJLetZvXVao3A1UnumuTQJFfRIUCAAAECBAgQIECAAAECBAgQmF1AgDq71dD3rLb80RaaPiXJXVqBb0jynraI0AeSfGfoFVG+hQrcKcl1SW5JUr2RbQQIECBAgAABAgQIECBAgAABAnMICFDnwBrorg9qw/NrQajDWhm/neTC1tO0wtObBlp2xdp6gWOTfDRJLQ52zNZfzhUIECBAgAABAgQIECBAgAABAqslIEBdzvb8wRaaPq3rWVqva6vh+H/cQtMapl+9Dm0EavGoWkTq3UmehIMAAQIECBAgQIAAAQIECBAgQGA+AQHqfF47ufe/aKHp09f0JLy4habnJ/naThbQtQcp8PFu3tt/1S0i9b+T1CJiNgIECBAgQIAAAQIECBAgQIAAgTkEBKhzYO3ArgcnqaH51dP0uKlFvz7RQtPfTfK3O1Aul1wOged1c+Ge0+a9fUzrobwcJVdKAgQIECBAgAABAgQIECBAgMBABASoA2mIqWIckOQ3khye5LFT71+e5O1Jqqfpx4ZXbCUamMAD29yne7cQ/h0DK5/iECBAgAABAgQIECBAgAABAgSWQkCAOqxmelFXnFOTVIha29Wtp2kFpxcNq6hKM2CBA5P8dZIjkpyb5KQBl1XRCBAgQIAAAQIECBAgQIAAAQKDFhCgDqN5frEFp3dvxbkySYWpbxtG8ZRiyQQuSPLEJJcl+aEkNy9Z+RWXAAECBAgQIECAAAECBAgQIDAYAQHqzjVF2Z+Y5LQkR7ViXJLkZUk+uHPFcuUlF3h+ktcluTFJDeP/3JLXR/EJECBAgAABAgQIECBAgAABAjsqIEDdGf7HJzkjyQPa5WtRqJOTVM9BG4G+AuY97SvnOAIECBAgQIAAAQIECBAgQIDAOgIC1O29NY5LcnaSY9tlv5DkFUnOS3Lb9hbF1VZMwLynK9agqkOAAAECBAgQIECAAAECBAgMQ0CAuj3tcEyS1yQ5vl3uiiSnJ3ljklu3pwiusuIC5j1d8QZWPQIECBAgQIAAAQIECBAgQGBnBASoW+t+dJJXJzmhXeaa9vU5SW7Z2ks7+4gEzHs6osZWVQIECBAgQIAAAQIECBAgQGB7BQSoW+N9eJvj9BndIlF7JLk+yWuTnJXkhq25pLOOVMC8pyNteNUmQIAAAQIECBAgQIAAAQIEtkdAgLpY50O6052a5KQkeye5OckbutdnJrl2sZdyNgIx76mbgAABAgQIECBAgAABAgQIECCwxQIC1MUAH9yFpS9J8rwk+7V5Td/UwtQrF3MJZyHwzwTMe+qmIECAAAECBAgQIECAAAECBAhssYAAdXPAByR5YTdE/0XdEP07JbktyfldgPryJJdv7tSOJrBbAfOeukEIECBAgAABAgQIECBAgAABAtsgIEDth7xPkud2C0G9NMld2yne076+rN8pHUVgZgHzns5MZUcCBAgQIECAAAECBAgQIECAwOYEBKjz+e2V5FldT9NTkhzWDv1wkhckuXS+U9mbQC8B8572YnMQAQIECBAgQIAAAQIECBAgQKCfgAB1NrdyOjHJaUmOaodckuRlST442ynsRWAhAuY9XQijkxAgQIAAAQIECBAgQIAAAQIEZhMQoG7s9PgkZyR5QNv1E0lOTlJBlo3AdgqY93Q7tV2LAAECBAgQIECAAAECBAgQIJBEgLr+bXBckrOTHNt2+UKSVyQ5ry0W5QYisJ0C5j3dTm3XIkCAAAECBAgQIECAAAECBAg0AQHqP78VjknymiTHt4+uSHJ6N+fpG5Pc6s4hsAMC90jyF23BsnOTnLQDZXBJAgQIECBAgAABAgQIECBAgMAoBQSo/9jsRyd5dZIT2lvXtK/PSXLLKO8OlR6CwEOTXJjkzkmuSnJkkpuGUDBlIECAAAECBAgQIECAAAECBAiMQUCAmhze5jh9RrdI1B5Jrk/y2iRnJblhDDeBOg5SoP5t/kqSM9t9WaHpo5JcPMjSKhQBAgQIECBAgAABAgQIECBAYEUFxhygHtK16altOPTeSW5O8oYWWF27ou2tWsshcNck70jyyFbc9yWpgP+65Si+UhIgQIAAAQIECBAgQIAAAQIEVkdgjAHqwV1Y+pIkz0uyX5vX9E0tTL1ydZpWTZZUoELTtyW5W5s64oUt2F/S6ig2AQIECBAgQIAAAQIECBAgQGC5BcYUoB6QpMKoF3VD9O+U5LYk53cB6suTXL7czaj0KyCwZ5JXdqHpS9uQ/c8neWKSv1mBuqkCAQIECBAgQIAAAQIECBAgQGBpBcYQoO6T5LktmKqh0bW9p3192dK2nIKvksChSd6dpBaMqq2G7z8ryY2rVEl1IUCAAAECBAgQIECAAAECBAgso8AqB6h7tRDqlCSHtcb5cDeP5AuSXLqMjaXMKynwU0nekuSgJN9qYf//WMmaqhQBAgQIECBAgAABAgQIECBAYAkFVjFArTqd2A2DPi3JUa1NLknysiQfXMI2UuTVFKiFy16T5D+26tVQ/RqyX0P3bQQIECBAgAABAgQIECBAgAABAgMRWLUA9fFJzuiGQz+g+X4iyclJLhiIt2IQKIEj2j35wMbxWy1IvQUPAQIECBAgQIAAAQIECBAgQIDAsARWJUA9LsnZSY5tvF9I8ook57XFooalrjRjFjihDdmvRc1u6Hqc/pyAf8y3g7oTIECAAAECBAgQIECAAAECQxdY9gD1mDYM+vgGfUWS07s5T9+Y5Nah4yvfqAT2TfK6JM9ptf7zJE9O8sVRKagsAQIECBAgQIAAAQIECBAgQGDJBJY1QD06yauTVG++2q5pX5+TxDDoJbsJR1Dcul9/P0k9357ktUleIuQfQcurIgECBAgQIECAAAECBAgQILD0AssWoB7e5jh9RrdI1B5Jrm9h1FltOPTSN4gKrJzAz7eep3dM8o22wNkHVq6WKkSAAAECBAgQIECAAAECBAgQWFGBZQlQD+n8T01yUpJavfzmJG/oXp+Z5NoVbRvVWm6BmuP03CRPbdX4cHt95XJXS+kJECBAgAABAgQIECBAgAABAuMSGHqAenAb6vy8JPu1Ic9vamGqIGpc9+oy1faBbWGoI9oiZmckeaUFzZapCZWVAAECBAgQIECAAAECBAgQIPD/BYYaoB6U5M1JanGo/Vtjnd8FqC/vVi3/vMYjMGCBX+7u2V9Pcockf9cWiqrepzYCBAgQIECAAAECBAgQIECAAIElFBhigHpskj9Mcufm+Xvdcw3f/8QS+iryeARqmoma27R6n9ZWr2uu3q+Ph0BNCRAgQIAAAQIECBAgQIAAAQKrJzC0APUFbVGokr4pydPa6uWrJ69GqyJw/yS/lORZSfZpw/T/c5JXr0oF1YMAAQIECBAgQIAAAQIECBAgMGaBoQSoByZ5a5IntMa4MMnPWiBqzLfmoOteQemJSf59koe2kt6apOblfU6S9w+69ApHgAABAgQIECBAgAABAgQIECAws8AQAtRjktQw/Xsl+W6b51TvvZmb0I7bKPADLTR9ZpJa4Ky2y5P8tyTnGq6/jS3hUgQIECBAgAABAgQIECBAgACBbRLY6QD1PyQ5uw19virJk5JctE11dxkCswjsleSEFpw+qi28VkH/e5P8VuttevssJ7IPAQIECBAgQIAAAQIECBAgQIDA8gnsVIC6f5LfaSuUl9ofJXl6kquXj1CJV1TgHkl+IclJXVB691bHK5K8sfU4rdc2AgQIECBAgAABAgQIECBAgACBFRfYiQD1Pm1hqBoOfVvXo++VSU5Pohffit9sS1C9+vfwuCTVM/onk+zR7ssK+H8zyR90U0zUXKc2AgQIECBAgAABAgQIECBAgACBkQhsd4D67CSvT7Jf6236lCR/MhJr1RyuwCGtp2n1OL1nK+bXu9dvbsP0vzDcoisZAQIECBAgQIAAAQIECBAgQIDAVgpsV4BagWkNfa5h+rXVPKc132nNe2ojsFMCj2xzm/7bJHu3Qny4habvSnLLThXMdQkQIECAAAECBAgQIECAAAECBIYhsB0Bag3V//0kNXS/hum/qhu6f0qSWojHRmC7Be6c5JktOD26XfybSd6S5A1JLtvuArkeAQIECBAgQIAAAQIECBAgQIDAcAW2OkCtHqe/naQWjbo2yc+0BaO2U6RWUT83SfUs7DPPahkdt4njt7OuW3mtrXKYnLeC9mqr6vVZvT+/teDK3CvJI5I8eKq36Ze69/5PkouTfGfB11um01WQXI+Ptjle648b9aj5Xievd/rrPv92l6kNlHXzAgcm+a9JLk3y7XVOt1Xfx6Yvtx3X2LzWPz3DMpZ50Qbrne+OSX40yceS3KH7I/A+ax713r5J6mfMZ9eMXKifa59r/+/x8+bQ3q4mcx0CBAgQIECAAAECixfYqgC1fsF4XVvFvEp9SZKfTvLVxVdhwzN+qAWgG+5oBwIEBitQC84NKdDd6UB51uuX21i297XF38ZSX/VcLoE/TvKo5Sqy0hIgQIAAAQIECBAgMBHYigC1emHUkP37t4v8RpKX7GDPi1qk6uG7aPIKY6qXYz1ubj1KvpKkFg+qId2THpA15Lvq8vEkfz/iW2erHOq8P9QtLnbXJHU/VjBUvQ3r/UVu17Xh+Z8ZeW/TXZkemqQe9YeObyTZsz2qR/DkdT3v5Ndb8b1qkffXUM9V/5aG2KO4gt0HJPl0eyzC76gktVDh5HtJnfOGJDcm+Xz7GVS9Cb+//TGv3t+KbTuusehyD6nM1aPzbkmub6MF6vtOzVE9+f5Tn0+/N5m/etEmk/PV/yvUVv+fUD2b6zH5A0aNXKjP9+j+aHxw+3+EyfREVc56r0bf1DEv6kZAVMhvI0CAAAECBAgQIEBgCQUWHUo8Iclbk9RQygqsnpbkwh12qWHbZyX5civXPboFrQ5LUr8w7m6roeSXt1+8K3T7Yntdv4jXquzrDRHd4equzOUrQK0pIH4pyV+ss6DTRqFu3YcP6kLA09scpyuDM8KKVECxkwHuTgfIfa9fbjYCYxCooLL+8Fmh+fTzLK8rrK1Afe2+WxWyj6E91JEAAQIECBAgQIDASgksKkCtnhYVUj6/6fxlkhO63kA1x+RQt+oZUkFqBarVw/Qx7ReoQ7qw7d7dXHoH7abg1aureqtWmDp5VKharytsrR6sNgIECOy0QH2PH2KP4ru3qVWuTFKPrdqOaPNX1rQytV3V5rMe82iCrbJe1HnrD2NHJqkh7/VzdaMAtP5YayNAgAABAgQIECBAgMCWCiwiQD08yQXdsP1jWklf34aqLXsPze9NUgtAVJhav8zV8NB6rq8reN2dXQ2DrjC1er3eqZv37E/bL4JXtyHS13Rhcz38Er+lt7eTEyBA4B++V/9iNwz8zDakuoZcn5PklNZbEREBAgQIECBAgAABAgQIENitwGYD1Md1q3e/LUmFjTUEruafqxXUV32rHrcVptZ8r5OQtYLVetR7k95OGznUXGk17HASqO7uebJfhbBjXjF+I1OfEyBAYFcCd0nya+3nVP3sq56v/6n7Hv67uAgQIECAAAECBAgQIECAwO4E+gaoNRfhq7reOy9uJ68Flp7Y5gwdu3iZVg/VClhr/s1/035RL7OaNqAWOKlf5Ouxf0+sGtI4CVtr0avJ3G913ertWvO1fm0qnK19al44GwECBMYu8JBukaLfbN+fy+KiblGp57RF5sZuo/4ECBAgQIAAAQIECBAgsAuBPgFqrWD87iT1S2ht/73NfVqLLtnmE6jVg7+vhamTcLWeJwHr5Hk6eK3XNafhvFu1TwWpFbzW8+R19WidDmOn9xG6zqtsfwIElkGgfvb9QvtDYM13fVs3V+oHuxD1qaZWWYbmU0YCBAgQIECAAAECBAhsr8C8AerxSd7eelJWL8j6BfS87S2yqyWpRTbWhqw1dcAjkvxdC1jr8wpnJz1eZ51WYBp4OnSdBK3V27UWgPnQAhfL2rfNofuCBZ7TjUKAAIGNBOr75K+2n2W174VJfnKjg3xOgAABAgQIECBAgAABAuMSmDVA3SPJaUle1hZP+mQbsv/ZcXEtdW0PmApTqxfrIS2ErYB1elqBzYaum0GqOWHf10L5dyS5fTMncywBAgRmFHhSkncmubFNwWKBvxnh7EaAAAECBAgQIECAAIExCMwSoFZvw/OT/FgDqR6nJyW5aQxAI6/jdOg6CVrvm+SxST6f5LoF+VTv2Ad300HU9BAHtnPWqtm/vaDzOw0BAgQ2EqjFpJ6e5MwkL99oZ58TIECAAAECBAgQIECAwHgENgpQKzStXjnVK7EC0+cnOXc8PGq6zQI1t+uPJzmxhRgV0toIECCwHQJHJfl0ku8kuWebDmU7rusaBAgQIECAAAECBAgQIDBwgfUC1Hq/euCc2g3dr+H7NVS/hjh+fOD1UTwCBAgQIDCvQPW2/+VuMalTuoWlqkf821pv1HnPY38CBAgQIECAAAECBAgQWEGBXQWo1du0hunXglG1vSvJM5PUolE2AgQIECCwKgKT4PTFSQ5qlboiyeOS/PWqVFI9CBAgQIAAAQIECBAgQGBzAmsD1B9O8nttlfVagf2F3WJDb9jcJRxNgAABAgQGJVDBaU1J86K2mF4V7v3df2rUxcWDKqnCECBAgAABAgQIECBAgMCOC0wHqL+S5IzusVeSy9uQ/b/c8RIqAAECBAgQWIyA4HQxjs5CgAABAgQIECBAgACBUQlUgFq/UH44yQNbzd+T5N8tcIX1UYGqLAECBAgMTkBwOrgmUSACBAgQIECAAAECBAgsj0AFqO9M8uRW5Bqyf/byFF9JCRAgQIDAugKCUzcHAQIECBAgQIAAAQIECGxaoALUn03yliRXJTl002d0AgIECBAgsLMCgtOd9Xd1AgQIECBAgAABAgQIrJRABah7JvlGkgOTPCiJeU9XqolVhgABAqMROKRbFOqcblqa45Mc3Gr9h0lOsTjUaO4BFSVAgAABAgQIECBAgMDCBSaLSJ2b5NlJXp3kpQu/ihMSIECAAIHFCzwkyb9O8rD2fOTUJd7fvT5VcLp4dGckQIAAAQIECBAgQIDA2AQmAepju18+L+xC1C8luefYENSXAAECBAYvcESShyY5tgWmP7ymxDcn+XiS/ZKcnOSCwddIAQkQIECAAAECBAgQIEBgKQQmAaph/EvRXApJgACBUQjcMUn1Lq3AtB7Vy/Tua2r+mST/t/UwvTjJx7rg9Luj0FFJAgQIECBAgAABAgQIENhWgUmAWhc1jH9b6V2MAAECBJLUz6H7TAWlFZjer83PPQGqebovaWFphaYfSfJNegQIECBAgAABAgQIECBAYDsEpgNUw/i3Q9w1CBAgMG6Bg5LU8PtJ79IHJ/neKZJb21D86lU66WH66XGTqT0BAgQIECBAgAABAgQI7KTAdIA6PYz/R5JctJMFc20CBAgQWHqBvZIcs6Z36b3X1Oqra4bifzRJzWdqI0CAAAECBAgQIECAAAECgxCYDlCrQBWa1mrG30ryqiT/pb0eRGEVggABAgQGLVCLENZ8pZPepRWe7jtV4puS/PnUUPzqZfqVQddI4QgQIECAAAECBAgQIEBg9AJrA9TjkpzXzYd6jyZzXZJfF6SO/j4BQIAAgbUC0ws9TUJTCz25TwgQIECAAAECBAgQIEBg5QTWBqiTCh6b5MyuR+qj2xvXJDkryTl6pK7cPaBCBAgQ2Eigz0JPNaKh/ghnI0CAAAECBAgQIECAAAECSy2wXoAqSF3qZlV4AgQIzC1whySHJ6lh+PX8yCT1M+JubdGn/decsYbiX9qG49frj899RQcQIECAAAECBAgQIECAAIElENgoQBWkLkEjKiIBAgRmEDhoTUA6CUonz4fu5hxXJ/nQ1GJPl1joaQZxuxAgQIAAAQIECBAgQIDASgjMGqBOKlvz3J2R5Pj2xrWmblSJAAAKOUlEQVSt99E7W++jv/FL9UrcFypBgMDyCRy2TkBavUlrXus7b1Cl7yT52yRfSnJlkprP9E+S/E6SLy8fhxITIECAAAECBAgQIECAAIHFCMwboE4HqScn+aldFOMLSSpI/USST7bXlwlWF9NgzkKAwCgFaiX7tT1G6+sKRiePfTaQqflIKyCdhKTTz/W6QlMbAQIECBAgQIAAAQIECBAgsEagb4A6Oc0Tk5ye5Kok905y5G6EK1itILXmyROsuhUJECDwjwJ32UVAOh2Ofv8MWFe03qNrg9HqUVrvWdBpBkS7ECBAgAABAgQIECBAgACBtQKbDVDXnq96Sd0nyf3a477t+V5J9tgF/21JLm+9VKvXagWs9VwB682aiwABAisiML0402RI/XRAevAG9ZweXr9eQFr72AgQIECAAAECBAgQIECAAIEFCyw6QF2veILVBTec0xEgMBiBWp1+EpBOh6LTr/fboLSG1w+mORWEAAECBAgQIECAAAECBAj8U4HtClAFq+48AgSWVeCQFpCuF47W+xtthtdvJORzAgQIECBAgAABAgQIECAwUIGdDlB3F6xOhv/XlAD3T1JfbzTHas3F+t0kFyX5WpJbZnh8exf7TN67daDtplgECCxGoKYW2dWQ+klYWivbf98Gl1o7vH5XizQZXr+Y9nIWAgQIECBAgAABAgQIECCw7QJDDVB3F6zW/KqTcHXyvLtgdTOoFaCuDWF3FbjuLqjdXUA7fdza/dY7ThCzmRZ17CIE6vvGnu1RAeRGr+vzWfabZZ/NnuvHk3wryV5Tq9cfsAGK4fWLuGucgwABAgQIECBAgAABAgQILKnAsgWoGwWrT0rysCSfSnJjkn1287jDBp/XsRXWDG2rAHV3wevks90FsvVZBdHvGVrlRlCeo5PU42NToeIsweEs+2w2XJy+xu7OtSrfNya3m+H1I/iHp4oECBAgQIAAAQIECBAgQKCvwKoFIX0d1juuQqTdhbD12SxB7Kz7zXKuIYa6i3Z3vmEL3N6myqjpMm5b5/XuPlvvmK0+16FJ7pXkku4PLe9P8qUkNdxer+5h329KR4AAAQIECBAgQIAAAQIEdlRAgLqj/L0uvohQt+Z3fHgLkL7eqxQO6itQIV49PpJkMmfvvIHi9P47EVRWgGojQIAAAQIECBAgQIAAAQIECIxCQIA6imZWSQIECBAgQIAAAQIECBAgQIAAAQIE+ggIUPuoOYYAAQIECBAgQIAAAQIECBAgQIAAgVEICFBH0cwqSYAAAQIECBAgQIAAAQIECBAgQIBAHwEBah81xxAgQIAAAQIECBAgQIAAAQIECBAgMAoBAeoomlklCRAgQIAAAQIECBAgQIAAAQIECBDoIyBA7aPmGAIECBAgQIAAAQIECBAgQIAAAQIERiEgQB1FM6skAQIECBAgQIAAAQIECBAgQIAAAQJ9BASofdQcQ4AAAQIECBAgQIAAAQIECBAgQIDAKAQEqKNoZpUkQIAAAQIECBAgQIAAAQIECBAgQKCPgAC1j5pjCBAgQIAAAQIECBAgQIAAAQIECBAYhYAAdRTNrJIECBAgQIAAAQIECBAgQIAAAQIECPQREKD2UXMMAQIECBAgQIAAAQIECBAgQIAAAQKjEBCgjqKZVZIAAQIECBAgQIAAAQIECBAgQIAAgT4CAtQ+ao4hQIAAAQIECBAgQIAAAQIECBAgQGAUAgLUUTSzShIgQIAAAQIECBAgQIAAAQIECBAg0EdAgNpHzTEECBAgQIAAAQIECBAgQIAAAQIECIxCQIA6imZWSQIECBAgQIAAAQIECBAgQIAAAQIE+ggIUPuoOYYAAQIECBAgQIAAAQIECBAgQIAAgVEICFBH0cwqSYAAAQIECBAgQIAAAQIECBAgQIBAHwEBah81xxAgQIAAAQIECBAgQIAAAQIECBAgMAoBAeoomlklCRAgQIAAAQIECBAgQIAAAQIECBDoIyBA7aPmGAIECBAgQIAAAQIECBAgQIAAAQIERiEgQB1FM6skAQIECBAgQIAAAQIECBAgQIAAAQJ9BASofdQcQ4AAAQIECBAgQIAAAQIECBAgQIDAKAQEqKNoZpUkQIAAAQIECBAgQIAAAQIECBAgQKCPgAC1j5pjCBAgQIAAAQIECBAgQIAAAQIECBAYhYAAdRTNrJIECBAgQIAAAQIECBAgQIAAAQIECPQREKD2UXMMAQIECBAgQIAAAQIECBAgQIAAAQKjEBCgjqKZVZIAAQIECBAgQIAAAQIECBAgQIAAgT4CAtQ+ao4hQIAAAQIECBAgQIAAAQIECBAgQGAUAgLUUTSzShIgQIAAAQIECBAgQIAAAQIECBAg0EdAgNpHzTEECBAgQIAAAQIECBAgQIAAAQIECIxCQIA6imZWSQIECBAgQIAAAQIECBAgQIAAAQIE+ggIUPuoOYYAAQIECBAgQIAAAQIECBAgQIAAgVEICFBH0cwqSYAAAQIECBAgQIAAAQIECBAgQIBAHwEBah81xxAgQIAAAQIECBAgQIAAAQIECBAgMAoBAeoomlklCRAgQIAAAQIECBAgQIAAAQIECBDoIyBA7aPmGAIECBAgQIAAAQIECBAgQIAAAQIERiEgQB1FM6skAQIECBAgQIAAAQIECBAgQIAAAQJ9BASofdQcQ4AAAQIECBAgQIAAAQIECBAgQIDAKAQEqKNoZpUkQIAAAQIECBAgQIAAAQIECBAgQKCPgAC1j5pjCBAgQIAAAQIECBAgQIAAAQIECBAYhYAAdRTNrJIECBAgQIAAAQIECBAgQIAAAQIECPQREKD2UXMMAQIECBAgQIAAAQIECBAgQIAAAQKjEBCgjqKZVZIAAQIECBAgQIAAAQIECBAgQIAAgT4CAtQ+ao4hQIAAAQIECBAgQIAAAQIECBAgQGAUAgLUUTSzShIgQIAAAQIECBAgQIAAAQIECBAg0EdAgNpHzTEECBAgQIAAAQIECBAgQIAAAQIECIxCQIA6imZWSQIECBAgQIAAAQIECBAgQIAAAQIE+ggIUPuoOYYAAQIECBAgQIAAAQIECBAgQIAAgVEICFBH0cwqSYAAAQIECBAgQIAAAQIECBAgQIBAHwEBah81xxAgQIAAAQIECBAgQIAAAQIECBAgMAoBAeoomlklCRAgQIAAAQIECBAgQIAAAQIECBDoIyBA7aPmGAIECBAgQIAAAQIECBAgQIAAAQIERiEgQB1FM6skAQIECBAgQIAAAQIECBAgQIAAAQJ9BASofdQcQ4AAAQIECBAgQIAAAQIECBAgQIDAKAQEqKNoZpUkQIAAAQIECBAgQIAAAQIECBAgQKCPgAC1j5pjCBAgQIAAAQIECBAgQIAAAQIECBAYhYAAdRTNrJIECBAgQIAAAQIECBAgQIAAAQIECPQREKD2UXMMAQIECBAgQIAAAQIECBAgQIAAAQKjEBCgjqKZVZIAAQIECBAgQIAAAQIECBAgQIAAgT4C/w/IKXcUX6LD8wAAAABJRU5ErkJggg==', '2025-04-29 18:47:27', 4),
(21, 'S6 MBURUCUYA', 'CODIFICACION', 'PC DE ADMINISTRACION ', '10000', '124621', 'Juan Caceres', 'ds', 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAABVAAAADICAYAAAAOc8rBAAAAAXNSR0IArs4c6QAAIABJREFUeF7t3XuwdlddH/Cv5EJCAoEQQrjLrYJcJHIJqDFEAgFTIBIQRGi5VNuOdGrVGasztHamU0cqYm2cjiB4g0q5JISgUAtEwHCJgJZoQC4moULCLQRyISSBdP/qeprtmXOSs99zzj57P/uzZ555znuyn73W+qwn54/v/NZa3xEXAQIECBAgQIAAAQIECBAgQIAAAQIECGwq8B1cCBAgQIAAAQIECBAgQIAAAQIECBAgQGBzAQGqbwYBAgQIECBAgAABAgQIECBAgAABAgS2EBCg+moQIECAAAECBAgQIECAAAECBAgQIEBAgOo7QIAAAQIECBAgQIAAAQIECBAgQIAAgWECKlCHebmbAAECBAgQIECAAAECBAgQIECAAIEFCQhQFzTZhkqAAAECBAgQIECAAAECBAgQIECAwDABAeowL3cTIECAAAECBAgQIECAAAECBAgQILAgAQHqgibbUAkQIECAAAECBAgQIECAAAECBAgQGCYgQB3m5W4CBAgQIECAAAECBAgQIECAAAECBBYkIEBd0GQbKgECBAgQIECAAAECBAgQIECAAAECwwQEqMO83E2AAAECBAgQIECAAAECBAgQIECAwIIEBKgLmmxDJUCAAAECBAgQIECAAAECBAgQIEBgmIAAdZiXuwkQIECAAAECBAgQIECAAAECBAgQWJCAAHVBk22oBAgQIECAAAECBAgQIECAAAECBAgMExCgDvNyNwECBAgQIECAAAECBAgQIECAAAECCxIQoC5osg2VAAECBAgQIECAAAECBAgQIECAAIFhAgLUYV7uJkCAAAECBAgQIECAAAECBAgQIEBgQQIC1AVNtqESIECAAAECBAgQIECAAAECBAgQIDBMQIA6zMvdBAgQIECAAAECBAgQIECAAAECBAgsSECAuqDJNlQCBAgQIECAAAECBAgQIECAAAECBIYJCFCHebmbAAECBAgQIECAAAECBAgQIECAAIEFCQhQFzTZhkqAAAECBAgQIECAAAECBAgQIECAwDABAeowL3cTIECAAAECBAgQIECAAAECBAgQILAgAQHqgibbUAkQIECAAAECBAgQIECAAAECBAgQGCYgQB3m5W4CBAgQIECAAAECBAgQIECAAAECBBYkIEBd0GQbKgECBAgQIECAAAECBAgQIECAAAECwwQEqMO83E2AAAECBAgQIECAAAECBAgQIECAwIIEBKgLmmxDJUCAAAECBAgQIECAAAECBAgQIEBgmIAAdZiXuwkQIECAAAECBAgQIECAAAECBAgQWJCAAHVBk22oBAgQIECAAAECBAgQIECAAAECBAgMExCgDvNyNwECBAgQIECAAAECBAgQIECAAAECCxIQoC5osg2VAAECBAgQIECAAAECBAgQIECAAIFhAgLUYV7uJkCAAAECBAgQIECAAAECBAgQIEBgQQIC1AVNtqESIECAAAECBAgQIECAAAECBAgQIDBMQIA6zMvdBAgQIECAAAECBAgQIECAAAECBAgsSECAuqDJNlQCBAgQIECAAAECBAgQIECAAAECBIYJCFCHebmbAAECBAgQIECAAAECBAgQIECAAIEFCQhQFzTZhkqAAAECBAgQIECAAAECBAgQIECAwDABAeowL3cTIECAAAECBAgQIECAAAECBAgQILAgAQHqgibbUAkQIECAAAECBAgQIECAAAECBAgQGCYgQB3m5W4CBAgQIECAAAECBAgQIECAAAECBBYkIEBd0GQbKgECBAgQIECAAAECBAgQIECAAAECwwQEqMO83E2AAAECBAgQIECAAAECBAgQIECAwIIEBKgLmmxDJUCAAAECBAgQIECAAAECBAgQIEBgmIAAdZiXuwkQIECAAAECBAgQIECAAAECBAgQWJCAAHVBk22oBAgQIECAAAECBAgQIECAAAECBAgMExCgDvNyNwECBAgQIECAAAECBAgQIECAAAECCxIQoC5osg2VAAECBAgQIECAAAECBAgQIECAAIFhAgLUYV7uJkCAAAECBAgQIECAAAECBAgQIEBgQQIC1AVNtqESIECAAAECBAgQIECAAAECBAgQIDBMQIA6zMvdBAgQIECAAAECBAgQIECAAAECBAgsSECAuqDJNlQCBAgQIECAAAECBAgQIECAAAECBIYJCFCHebmbAAECBAgQIECAAAECBAgQIECAAIEFCQhQFzTZhkqAAAECBAgQIECAAAECBAgQIECAwDABAeowL3cTIECAAAECBAgQIECAAAECBAgQILAgAQHqgibbUAkQIECAAAECBAgQIECAAAECBAgQGCYgQB3m5W4CBAgQIECAAAEC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AQIECBAgQGDpAgLUpX8D9nb8907ytm7p/MOS3JTkZUl+MUmdeD/0ul2S01poWu9HtAdc3dqo5fm1t2qFqC4CBAgQIECAAAECBAgQIECAAAECuyIgQN0VRg/ZROCUVgl6VJKrkjy7VYcOwTq6u/mXkjygVZyuPlvPe2uSN7T3Ic90LwECBAgQIECAAAECBAgQIECmrqDrAAAIEElEQVSAAIFtCwhQt03lxm0K1HeqQs+XJqmfP94qRy++lc8fnuTR7XVCV036mCT36X3myt4hUH+0zb64jQABAgQIECBAgAABAgQIECBAgMCOBASoO+Lz4Q0CtRfpB5M8uP3+9UlevMmy+oOSPLyFpBWUfm+S72mB6+qR1yS5KMlhben/a2kTIECAAAECBAgQIECAAAECBAgQGFtAgDq2+Hq3944kp7Y9Tv91kjPbcO/XC0tXgWlVnK6uG5NcmOSC3qvC0wPZK3W9hY2OAAECBAgQIECAAAECBAgQIEBgVAEB6qjca9/YK5L8dAtDz0nyyCSPS3LHDSP/TAtK/7y9fzjJN9dexwAJECBAgAABAgQIECBAgAABAgRmJyBAnd2UTbrD5yV5/IYeXpHk/d0+qBWWfqgt8f/apEehcwQIECBAgAABAgQIECBAgAABAgSagADVV2E3BU7rqk1/Pcl1SV7V7W361iSX7GYDnkWAAAECBAgQIECAAAECBAgQIEBgTAEB6pja2iJAgAABAgQIECBAgAABAgQIECBAYFYCAtRZTZfOEiBAgAABAgQIECBAgAABAgQIECAwpoAAdUxtbREgQIAAAQIECBAgQIAAAQIECBAgMCsBAeqspktnCRAgQIAAAQIECBAgQIAAAQIECBAYU0CAOqa2tggQIECAAAECBAgQIECAAAECBAgQmJWAAHVW06WzBAgQIECAAAECBAgQIECAAAECBAiMKSBAHVNbWwQIECBAgAABAgQIECBAgAABAgQIzEpAgDqr6dJZAgQIECBAgAABAgQIECBAgAABAgTGFBCgjqmtLQIECBAgQIAAAQIECBAgQIAAAQIEZiUgQJ3VdOksAQIECBAgQIAAAQIECBAgQIAAAQJjCghQx9TWFgECBAgQIECAAAECBAgQIECAAAECsxIQoM5qunSWAAECBAgQIECAAAECBAgQIECAAIExBQSoY2priwABAgQIECBAgAABAgQIECBAgACBWQkIUGc1XTpLgAABAgQIECBAgAABAgQIECBAgMCYAgLUMbW1RYAAAQIECBAgQIAAAQIECBAgQIDArAQEqLOaLp0lQIAAAQIECBAgQIAAAQIECBAgQGBMAQHqmNraIkCAAAECBAgQIECAAAECBAgQIEBgVgIC1FlNl84SIECAAAECBAgQIECAAAECBAgQIDCmgAB1TG1tESBAgAABAgQIECBAgAABAgQIECAwKwEB6qymS2cJECBAgAABAgQIECBAgAABAgQIEBhTQIA6pra2CBAgQIAAAQIECBAgQIAAAQIECBCYlYAAdVbTpbMECBAgQIAAAQIECBAgQIAAAQIECIwpIEAdU1tbBAgQIECAAAECBAgQIECAAAECBAjMSkCAOqvp0lkCBAgQIECAAAECBAgQIECAAAECBMYUEKCOqa0tAgQIECBAgAABAgQIECBAgAABAgRmJSBAndV06SwBAgQIECBAgAABAgQIECBAgAABAmMKCFDH1NYWAQIECBAgQIAAAQIECBAgQIAAAQKzEhCgzmq6dJYAAQIECBAgQIAAAQIECBAgQIAAgTEFBKhjamuLAAECBAgQIECAAAECBAgQIECAAIFZCQhQZzVdOkuAAAECBAgQIECAAAECBAgQIECAwJgCAtQxtbVFgAABAgQIECBAgAABAgQIECBAgMCsBASos5ounSVAgAABAgQIECBAgAABAgQIECBAYEwBAeqY2toiQIAAAQIECBAgQIAAAQIECBAgQGBWAgLUWU2XzhIgQIAAAQIECBAgQIAAAQIECBAgMKaAAHVMbW0RIECAAAECBAgQIECAAAECBAgQIDArAQHqrKZLZwkQIECAAAECBAgQIECAAAECBAgQGFNAgDqmtrYIECBAgAABAgQIECBAgAABAgQIEJiVgAB1VtOlswQIECBAgAABAgQIECBAgAABAgQIjCkgQB1TW1sECBAgQIAAAQIECBAgQIAAAQIECMxKQIA6q+nSWQIECBAgQIAAAQIECBAgQIAAAQIExhQQoI6prS0CBAgQIECAAAECBAgQIECAAAECBGYlIECd1XTpLAECBAgQIECAAAECBAgQIECAAAECYwoIUMfU1hYBAgQIECBAgAABAgQIECBAgAABArMSEKDOarp0lgABAgQIECBAgAABAgQIECBAgACBMQUEqGNqa4sAAQIECBAgQIAAAQIECBAgQIAAgVkJCFBnNV06S4AAAQIECBAgQIAAAQIECBAgQIDAmAIC1DG1tUWAAAECBAgQIECAAAECBAgQIECAwKwEBKizmi6dJUCAAAECBAgQIECAAAECBAgQIEBgTAEB6pja2iJAgAABAgQIECBAgAABAgQIECBAYFYCAtRZTZfOEiBAgAABAgQIECBAgAABAgQIECAwpoAAdUxtbREgQIAAAQIECBAgQIAAAQIECBAgMCsBAeqspktnCRAgQIAAAQIECBAgQIAAAQIECBAYU0CAOqa2tggQIECAAAECBAgQIECAAAECBAgQmJWAAHVW06WzBAgQIECAAAECBAgQIECAAAECBAiMKSBAHVNbWwQIECBAgAABAgQIECBAgAABAgQIzEpAgDqr6dJZAgQIECBAgAABAgQIECBAgAABAgTGFBCgjqmtLQIECBAgQIAAAQIECBAgQIAAAQIEZiUgQJ3VdOksAQIECBAgQIAAAQIECBAgQIAAAQJjCghQx9TWFgECBAgQIECAAAECBAgQIECAAAECsxL4v5+8uvYxwO7nAAAAAElFTkSuQmCC', '2025-04-29 18:55:00', 4);

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
(17, '124621', '2025-04-29', 'dsd', 'Realizado', 'Victor Aguero');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mantenimiento_ups`
--

CREATE TABLE `mantenimiento_ups` (
  `patrimonio` varchar(50) NOT NULL,
  `cadena` varchar(100) DEFAULT NULL,
  `sucursal` varchar(100) DEFAULT NULL,
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
('124621', 'Stock', 'AVELINO MARTINEZ', 'APC', ' 12V-5Ah ', 64, '20KVA', '2025-04-29', '2027-04-29', NULL, 'Realizado', NULL, '2025-04-29 15:44:18');

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
(1, 1, 'Cierre de sesión', 'Cierre automático por inactividad', 'Sistema', '2025-04-29 09:04:44', '::1'),
(2, 1, 'Cierre de sesión', 'Cierre automático por inactividad', 'Sistema', '2025-04-29 09:19:25', '::1'),
(3, 1, 'Informe Técnico', 'Creación', 'Se creó un nuevo informe técnico', '2025-04-29 09:27:50', '::1'),
(4, 1, 'Informe Técnico', 'Creación', 'Se creó un nuevo informe técnico', '2025-04-29 09:28:14', '::1'),
(5, 1, 'Informe Técnico', 'Creación', 'Se creó un nuevo informe técnico', '2025-04-29 09:29:35', '::1'),
(6, 1, 'Informe Técnico', 'Creación', 'Se creó un nuevo informe técnico', '2025-04-29 09:47:49', '::1'),
(7, 1, 'Cierre de sesión', 'Cierre automático por inactividad', 'Sistema', '2025-04-29 10:00:03', '::1'),
(8, 1, 'Informe Técnico', 'Creación', 'Se creó un nuevo informe técnico', '2025-04-29 10:01:13', '::1'),
(9, 1, 'Informe Técnico', 'Creación', 'Se creó un nuevo informe técnico', '2025-04-29 10:19:46', '::1'),
(10, 1, 'Informe Técnico', 'Creación', 'Se creó un nuevo informe técnico', '2025-04-29 10:21:25', '::1'),
(11, 1, 'Cierre de sesión', 'Cierre automático por inactividad', 'Sistema', '2025-04-29 10:35:05', '::1'),
(12, 16, 'Informe Técnico', 'Creación', 'Se creó un nuevo informe técnico', '2025-04-29 14:29:56', '::1'),
(13, 1, 'Creación', 'Se creó nuevo registro UPS con patrimonio: 124621', 'Mantenimiento UPS', '2025-04-29 15:44:18', '::1'),
(14, 4, 'Creación', 'Se creó un nuevo informe técnico', 'Informe Técnico', '2025-04-29 15:47:27', '::1'),
(15, 4, 'Realizar Mantenimiento', 'Se realizó mantenimiento al UPS con patrimonio: 124621', 'Mantenimiento UPS', '2025-04-29 15:54:43', '::1'),
(16, 4, 'Creación', 'Se creó un nuevo informe técnico', 'Informe Técnico', '2025-04-29 15:55:00', '::1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('administrador','tecnico') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tecnico'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `nombre`, `rol`) VALUES
(1, 'jucaceres', 'Macross0', 'Juan Caceres', 'administrador'),
(2, 'macarballo', 'Temporal1', 'Mauro Carballo', 'tecnico'),
(4, 'vaguero', 'Temporal1', 'Victor Aguero', 'tecnico'),
(16, 'jubarrios', 'Temporal1', 'Julio Barrios', 'tecnico'),
(18, 'fegonzalez', '$2y$10$klZOKTQUVcA18EWqiJE2Hus3niNojfNrhuSKbXfgZWgBLLiLPjnW.', 'Felipe Gonzáles', 'tecnico');

--
-- Índices para tablas volcadas
--

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
-- Indices de la tabla `registro_actividades`
--
ALTER TABLE `registro_actividades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

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
-- AUTO_INCREMENT de la tabla `informe_tecnico`
--
ALTER TABLE `informe_tecnico`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de la tabla `mantenimientos`
--
ALTER TABLE `mantenimientos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `registro_actividades`
--
ALTER TABLE `registro_actividades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Restricciones para tablas volcadas
--

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
