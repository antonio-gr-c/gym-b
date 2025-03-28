-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 28-03-2025 a las 05:44:14
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_medico`
--
CREATE DATABASE IF NOT EXISTS `sistema_medico` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `sistema_medico`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diagnosticos`
--

DROP TABLE IF EXISTS `diagnosticos`;
CREATE TABLE `diagnosticos` (
  `id_diagnostico` int(11) NOT NULL,
  `sintomas` text DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `id_paciente` int(11) DEFAULT NULL,
  `id_doctor` int(11) DEFAULT NULL,
  `folio` varchar(50) NOT NULL,
  `fecha` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `diagnosticos`
--

INSERT INTO `diagnosticos` (`id_diagnostico`, `sintomas`, `diagnostico`, `observaciones`, `id_paciente`, `id_doctor`, `folio`, `fecha`) VALUES
(1, 'Dolor de cabeza y fatiga', 'Hipertensión leve', 'Se recomienda seguimiento en 3 meses', 1, 1, 'ABC123', NULL),
(2, 'Dolor de cabeza y fatiga', 'Posible migraña', 'Requiere seguimiento en una semana', 2, 1, 'ABC123', NULL),
(3, 'nhgbfvdc', 'ngbfvd', 'gnbfvd', 3, 1, 'erwerw', NULL),
(4, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 4, 2, 'RX-2024001', NULL),
(5, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 5, 3, 'RX-2024002', NULL),
(6, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 6, 1, 'RX-2024003', NULL),
(7, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 7, 2, 'RX-2024004', NULL),
(8, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 8, 3, 'RX-2024005', NULL),
(9, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 9, 1, 'RX-2024006', NULL),
(10, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 10, 2, 'RX-2024007', NULL),
(11, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 11, 3, 'RX-2024008', NULL),
(12, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 12, 1, 'RX-2024009', NULL),
(13, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 13, 2, 'RX-2024010', NULL),
(14, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 14, 3, 'RX-2024011', NULL),
(15, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 15, 1, 'RX-2024012', NULL),
(16, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 16, 2, 'RX-2024013', NULL),
(17, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 17, 3, 'RX-2024014', NULL),
(18, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 18, 1, 'RX-2024015', NULL),
(19, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 19, 2, 'RX-2024016', NULL),
(20, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 20, 3, 'RX-2024017', NULL),
(21, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 21, 1, 'RX-2024018', NULL),
(22, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 22, 2, 'RX-2024019', NULL),
(23, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 23, 3, 'RX-2024020', NULL),
(24, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 24, 1, 'RX-2024021', NULL),
(25, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 25, 2, 'RX-2024022', NULL),
(26, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 26, 3, 'RX-2024023', NULL),
(27, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 27, 1, 'RX-2024024', NULL),
(28, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 28, 2, 'RX-2024025', NULL),
(29, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 29, 3, 'RX-2024026', NULL),
(30, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 30, 1, 'RX-2024027', NULL),
(31, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 31, 2, 'RX-2024028', NULL),
(32, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 32, 3, 'RX-2024029', NULL),
(33, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 33, 1, 'RX-2024030', NULL),
(34, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 34, 2, 'RX-2024031', NULL),
(35, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 35, 3, 'RX-2024032', NULL),
(36, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 36, 1, 'RX-2024033', NULL),
(37, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 37, 2, 'RX-2024034', NULL),
(38, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 38, 3, 'RX-2024035', NULL),
(39, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 39, 1, 'RX-2024036', NULL),
(40, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 40, 2, 'RX-2024037', NULL),
(41, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 41, 3, 'RX-2024038', NULL),
(42, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 42, 1, 'RX-2024039', NULL),
(43, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 43, 2, 'RX-2024040', NULL),
(44, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 44, 3, 'RX-2024041', NULL),
(45, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 45, 1, 'RX-2024042', NULL),
(46, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 46, 2, 'RX-2024043', NULL),
(47, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 47, 3, 'RX-2024044', NULL),
(48, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 48, 1, 'RX-2024045', NULL),
(49, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 49, 2, 'RX-2024046', NULL),
(50, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 50, 3, 'RX-2024047', NULL),
(51, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 51, 1, 'RX-2024048', NULL),
(52, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 52, 2, 'RX-2024049', NULL),
(53, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 53, 3, 'RX-2024050', NULL),
(54, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 54, 1, 'RX-2024051', NULL),
(55, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 55, 2, 'RX-2024052', NULL),
(56, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 56, 3, 'RX-2024053', NULL),
(57, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 57, 1, 'RX-2024054', NULL),
(58, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 58, 2, 'RX-2024055', NULL),
(59, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 59, 3, 'RX-2024056', NULL),
(60, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 60, 1, 'RX-2024057', NULL),
(61, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 61, 2, 'RX-2024058', NULL),
(62, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 62, 3, 'RX-2024059', NULL),
(63, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 63, 1, 'RX-2024060', NULL),
(64, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 64, 2, 'RX-2024061', NULL),
(65, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 65, 3, 'RX-2024062', NULL),
(66, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 66, 1, 'RX-2024063', NULL),
(67, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 67, 2, 'RX-2024064', NULL),
(68, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 68, 3, 'RX-2024065', NULL),
(69, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 69, 1, 'RX-2024066', NULL),
(70, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 70, 2, 'RX-2024067', NULL),
(71, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 71, 3, 'RX-2024068', NULL),
(72, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 72, 1, 'RX-2024069', NULL),
(73, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 73, 2, 'RX-2024070', NULL),
(74, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 74, 3, 'RX-2024071', NULL),
(75, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 75, 1, 'RX-2024072', NULL),
(76, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 76, 2, 'RX-2024073', NULL),
(77, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 77, 3, 'RX-2024074', NULL),
(78, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 78, 1, 'RX-2024075', NULL),
(79, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 79, 2, 'RX-2024076', NULL),
(80, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 80, 3, 'RX-2024077', NULL),
(81, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 81, 1, 'RX-2024078', NULL),
(82, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 82, 2, 'RX-2024079', NULL),
(83, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 83, 3, 'RX-2024080', NULL),
(84, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 84, 1, 'RX-2024081', NULL),
(85, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 85, 2, 'RX-2024082', NULL),
(86, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 86, 3, 'RX-2024083', NULL),
(87, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 87, 1, 'RX-2024084', NULL),
(88, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 88, 2, 'RX-2024085', NULL),
(89, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 89, 3, 'RX-2024086', NULL),
(90, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 90, 1, 'RX-2024087', NULL),
(91, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 91, 2, 'RX-2024088', NULL),
(92, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 92, 3, 'RX-2024089', NULL),
(93, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 93, 1, 'RX-2024090', NULL),
(94, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 94, 2, 'RX-2024091', NULL),
(95, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 95, 3, 'RX-2024092', NULL),
(96, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 96, 1, 'RX-2024093', NULL),
(97, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 97, 2, 'RX-2024094', NULL),
(98, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 98, 3, 'RX-2024095', NULL),
(99, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 99, 1, 'RX-2024096', NULL),
(100, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 100, 2, 'RX-2024097', NULL),
(101, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 101, 3, 'RX-2024098', NULL),
(102, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 102, 1, 'RX-2024099', NULL),
(103, 'Dolor de cabeza', 'Hipertensión', 'Control mensual recomendado', 103, 2, 'RX-2024100', NULL),
(0, 'BGRFEVD', 'BEFVDC', 'EFVDWCS', 104, 1, 'jmnhbgfvdc', NULL),
(0, 'RBFEV', 'BTERWE', 'BETVR', 105, 1, '12345', NULL),
(0, 'gg dfvs', 'fh gdfcsG DFS', 'bgrefvd', 106, 1, '987654321', NULL),
(0, 'nybtvrefdw', 'yhgtfrdews', 'yjhgtfrdews', 107, 1, '060503', '2025-03-27'),
(0, 'g dfvs', 'g dfvs', 'rh gbefvwd', 108, 1, 'hygtfds', '2025-03-29'),
(0, 'gbfvdcs', 'gnbfvd', 'bgfvdcs', 109, 1, 'prueba', '2003-02-20'),
(0, 'thgrfe', 'bgfvdc', 'bgfvdc', 110, 10, '99999', '2025-03-27'),
(0, 'bgfds', 'bfdvcsa', 'gfrdefsda', 111, 10, '1234321', '2025-03-27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `doctores`
--

DROP TABLE IF EXISTS `doctores`;
CREATE TABLE `doctores` (
  `id_doctor` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido_paterno` varchar(50) NOT NULL,
  `apellido_materno` varchar(50) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `id_jefe` int(11) DEFAULT NULL,
  `rango` varchar(15) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Volcado de datos para la tabla `doctores`
--

INSERT INTO `doctores` (`id_doctor`, `nombre`, `apellido_paterno`, `apellido_materno`, `usuario`, `contraseña`, `telefono`, `correo`, `id_jefe`, `rango`, `estado`) VALUES
(1, 'Alejandro', 'Ramírez', 'López', 'alejandrorl', '$2y$10$MdK7ep19RHll1BiM6wOwveUNgewTAI9dgRyLLmnQ60Azj0yX.bDVC', '5551112233', 'alejandro@example.com', NULL, 'jefe', 1),
(2, 'Mariana', 'Fernández', 'Soto', 'marianafs', '$2y$10$MdK7ep19RHll1BiM6wOwveUNgewTAI9dgRyLLmnQ60Azj0yX.bDVC', '5552223344', 'mariana@example.com', 1, 'trabajador', 1),
(3, 'Luis', 'González', 'Martínez', 'luisgm', '$2y$10$MdK7ep19RHll1BiM6wOwveUNgewTAI9dgRyLLmnQ60Azj0yX.bDVC', '5553334455', 'luis@example.com', 1, 'trabajador', 1),
(10, 'antonio', 'garcia', 'cruz', 'antoniogc', '$2y$10$vQP3pCA8u5eW4ywlop1Mr.Uczk7FaP6UirpxKbFqEATrHvvZ5/pxe', '9516577535', 'antoniogc984@gmail.com', 1, 'trabajador', 1),
(11, 'tadeo', 'martinez', 'quero', 'tadeomq', '$2y$10$VC7NRkzcV7PWtUBeP.tWaui50wwKAkmGeWj0NmBGkbxxJ15pnmhW.', '9516577535', 'antoniogc984@gmial.com', 1, 'trabajador', 1),
(12, 'estefania', 'vaconcelos', 'perez', 'estefaniavp', '$2y$10$ti.uzxgfNlZ/bEdUoRu8SuInjhfNJbYz2EI6f98BTlaAu2BrfiIAy', '9513009236', 'vasconcelosfany@gmail.com', 1, 'trabajador', 1);

--
-- Disparadores `doctores`
--
DROP TRIGGER IF EXISTS `before_insert_doctor`;
DELIMITER $$
CREATE TRIGGER `before_insert_doctor` BEFORE INSERT ON `doctores` FOR EACH ROW BEGIN
    -- Concatenar nombre + primera letra del apellido paterno + primera letra del apellido materno en minúsculas
    SET NEW.usuario = CONCAT(
        LOWER(NEW.nombre),
        LOWER(LEFT(NEW.apellido_paterno, 1)),
        LOWER(LEFT(NEW.apellido_materno, 1))
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historia_clinica`
--

DROP TABLE IF EXISTS `historia_clinica`;
CREATE TABLE `historia_clinica` (
  `id_historia_clinica` int(11) NOT NULL,
  `presion_arterial` varchar(20) DEFAULT NULL,
  `temperatura` decimal(4,1) DEFAULT NULL,
  `frecuencia_cardiaca` int(11) DEFAULT NULL,
  `saturacion_oxigeno` int(11) DEFAULT NULL,
  `enfermedades_previas` text DEFAULT NULL,
  `medicacion` text DEFAULT NULL,
  `alergias` text DEFAULT NULL,
  `id_paciente` int(11) DEFAULT NULL,
  `id_doctor` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historia_clinica`
--

INSERT INTO `historia_clinica` (`id_historia_clinica`, `presion_arterial`, `temperatura`, `frecuencia_cardiaca`, `saturacion_oxigeno`, `enfermedades_previas`, `medicacion`, `alergias`, `id_paciente`, `id_doctor`) VALUES
(1, '120/80', 36.5, 72, 98, 'Diabetes tipo 2', 'Metformina', 'Ninguna', 1, 1),
(2, '120/80', 36.5, 75, 98, 'Diabetes', 'Metformina', 'Ninguna', 2, 1),
(3, '34', 34.0, 223, 3232, 'no', 'no', 'no', 3, 1),
(4, '138/77', 38.2, 86, 99, 'Hipertensión', 'Losartán', 'Ninguna', 4, 2),
(5, '110/71', 35.4, 99, 98, 'Hipertensión', 'Losartán', 'Ninguna', 5, 3),
(6, '110/86', 36.0, 75, 90, 'Hipertensión', 'Losartán', 'Ninguna', 6, 1),
(7, '140/87', 35.7, 73, 98, 'Hipertensión', 'Losartán', 'Ninguna', 7, 2),
(8, '140/72', 37.7, 62, 96, 'Hipertensión', 'Losartán', 'Ninguna', 8, 3),
(9, '125/79', 39.0, 95, 96, 'Hipertensión', 'Losartán', 'Ninguna', 9, 1),
(10, '111/83', 35.8, 95, 97, 'Hipertensión', 'Losartán', 'Ninguna', 10, 2),
(11, '116/89', 36.7, 95, 97, 'Hipertensión', 'Losartán', 'Ninguna', 11, 3),
(12, '133/89', 38.5, 82, 92, 'Hipertensión', 'Losartán', 'Ninguna', 12, 1),
(13, '123/81', 38.3, 89, 100, 'Hipertensión', 'Losartán', 'Ninguna', 13, 2),
(14, '116/77', 35.9, 86, 99, 'Hipertensión', 'Losartán', 'Ninguna', 14, 3),
(15, '132/89', 35.0, 95, 94, 'Hipertensión', 'Losartán', 'Ninguna', 15, 1),
(16, '136/87', 39.0, 80, 98, 'Hipertensión', 'Losartán', 'Ninguna', 16, 2),
(17, '120/89', 36.3, 84, 99, 'Hipertensión', 'Losartán', 'Ninguna', 17, 3),
(18, '117/70', 39.4, 73, 100, 'Hipertensión', 'Losartán', 'Ninguna', 18, 1),
(19, '119/72', 38.1, 87, 94, 'Hipertensión', 'Losartán', 'Ninguna', 19, 2),
(20, '138/76', 38.2, 84, 100, 'Hipertensión', 'Losartán', 'Ninguna', 20, 3),
(21, '120/79', 37.1, 99, 91, 'Hipertensión', 'Losartán', 'Ninguna', 21, 1),
(22, '140/81', 38.5, 97, 94, 'Hipertensión', 'Losartán', 'Ninguna', 22, 2),
(23, '135/78', 38.3, 81, 94, 'Hipertensión', 'Losartán', 'Ninguna', 23, 3),
(24, '123/82', 38.9, 63, 94, 'Hipertensión', 'Losartán', 'Ninguna', 24, 1),
(25, '112/87', 35.3, 94, 99, 'Hipertensión', 'Losartán', 'Ninguna', 25, 2),
(26, '135/77', 37.8, 97, 97, 'Hipertensión', 'Losartán', 'Ninguna', 26, 3),
(27, '126/72', 39.9, 69, 94, 'Hipertensión', 'Losartán', 'Ninguna', 27, 1),
(28, '111/87', 36.9, 83, 95, 'Hipertensión', 'Losartán', 'Ninguna', 28, 2),
(29, '117/73', 35.7, 96, 95, 'Hipertensión', 'Losartán', 'Ninguna', 29, 3),
(30, '124/78', 38.5, 66, 92, 'Hipertensión', 'Losartán', 'Ninguna', 30, 1),
(31, '135/73', 39.4, 73, 94, 'Hipertensión', 'Losartán', 'Ninguna', 31, 2),
(32, '110/73', 35.7, 86, 90, 'Hipertensión', 'Losartán', 'Ninguna', 32, 3),
(33, '129/85', 38.5, 88, 98, 'Hipertensión', 'Losartán', 'Ninguna', 33, 1),
(34, '132/70', 36.4, 64, 96, 'Hipertensión', 'Losartán', 'Ninguna', 34, 2),
(35, '133/75', 39.9, 70, 99, 'Hipertensión', 'Losartán', 'Ninguna', 35, 3),
(36, '125/72', 39.6, 97, 95, 'Hipertensión', 'Losartán', 'Ninguna', 36, 1),
(37, '132/90', 36.5, 81, 93, 'Hipertensión', 'Losartán', 'Ninguna', 37, 2),
(38, '139/90', 39.7, 80, 93, 'Hipertensión', 'Losartán', 'Ninguna', 38, 3),
(39, '136/81', 39.2, 69, 95, 'Hipertensión', 'Losartán', 'Ninguna', 39, 1),
(40, '135/76', 35.1, 63, 96, 'Hipertensión', 'Losartán', 'Ninguna', 40, 2),
(41, '123/83', 38.4, 99, 97, 'Hipertensión', 'Losartán', 'Ninguna', 41, 3),
(42, '140/80', 35.7, 87, 96, 'Hipertensión', 'Losartán', 'Ninguna', 42, 1),
(43, '133/71', 36.0, 81, 94, 'Hipertensión', 'Losartán', 'Ninguna', 43, 2),
(44, '122/84', 38.8, 75, 99, 'Hipertensión', 'Losartán', 'Ninguna', 44, 3),
(45, '111/87', 39.9, 86, 94, 'Hipertensión', 'Losartán', 'Ninguna', 45, 1),
(46, '140/83', 37.2, 79, 98, 'Hipertensión', 'Losartán', 'Ninguna', 46, 2),
(47, '136/70', 35.8, 61, 95, 'Hipertensión', 'Losartán', 'Ninguna', 47, 3),
(48, '118/70', 36.1, 80, 97, 'Hipertensión', 'Losartán', 'Ninguna', 48, 1),
(49, '122/83', 39.4, 63, 100, 'Hipertensión', 'Losartán', 'Ninguna', 49, 2),
(50, '140/84', 38.4, 91, 91, 'Hipertensión', 'Losartán', 'Ninguna', 50, 3),
(51, '119/81', 38.8, 89, 93, 'Hipertensión', 'Losartán', 'Ninguna', 51, 1),
(52, '136/83', 35.7, 60, 92, 'Hipertensión', 'Losartán', 'Ninguna', 52, 2),
(53, '134/82', 36.3, 68, 96, 'Hipertensión', 'Losartán', 'Ninguna', 53, 3),
(54, '114/79', 36.3, 77, 94, 'Hipertensión', 'Losartán', 'Ninguna', 54, 1),
(55, '116/75', 36.7, 64, 92, 'Hipertensión', 'Losartán', 'Ninguna', 55, 2),
(56, '137/81', 35.6, 96, 96, 'Hipertensión', 'Losartán', 'Ninguna', 56, 3),
(57, '135/75', 36.2, 92, 91, 'Hipertensión', 'Losartán', 'Ninguna', 57, 1),
(58, '138/82', 35.9, 91, 94, 'Hipertensión', 'Losartán', 'Ninguna', 58, 2),
(59, '137/74', 37.4, 89, 93, 'Hipertensión', 'Losartán', 'Ninguna', 59, 3),
(60, '135/87', 39.6, 94, 96, 'Hipertensión', 'Losartán', 'Ninguna', 60, 1),
(61, '113/71', 39.2, 63, 96, 'Hipertensión', 'Losartán', 'Ninguna', 61, 2),
(62, '138/80', 37.5, 60, 100, 'Hipertensión', 'Losartán', 'Ninguna', 62, 3),
(63, '129/87', 36.9, 75, 96, 'Hipertensión', 'Losartán', 'Ninguna', 63, 1),
(64, '127/81', 37.5, 84, 96, 'Hipertensión', 'Losartán', 'Ninguna', 64, 2),
(65, '140/85', 35.0, 97, 98, 'Hipertensión', 'Losartán', 'Ninguna', 65, 3),
(66, '112/89', 35.6, 72, 94, 'Hipertensión', 'Losartán', 'Ninguna', 66, 1),
(67, '112/80', 36.2, 91, 100, 'Hipertensión', 'Losartán', 'Ninguna', 67, 2),
(68, '117/79', 35.0, 93, 94, 'Hipertensión', 'Losartán', 'Ninguna', 68, 3),
(69, '131/89', 39.4, 98, 100, 'Hipertensión', 'Losartán', 'Ninguna', 69, 1),
(70, '112/75', 37.0, 61, 90, 'Hipertensión', 'Losartán', 'Ninguna', 70, 2),
(71, '131/89', 38.6, 98, 92, 'Hipertensión', 'Losartán', 'Ninguna', 71, 3),
(72, '117/84', 37.8, 83, 100, 'Hipertensión', 'Losartán', 'Ninguna', 72, 1),
(73, '119/85', 39.8, 76, 91, 'Hipertensión', 'Losartán', 'Ninguna', 73, 2),
(74, '134/87', 37.3, 77, 97, 'Hipertensión', 'Losartán', 'Ninguna', 74, 3),
(75, '122/82', 39.1, 89, 92, 'Hipertensión', 'Losartán', 'Ninguna', 75, 1),
(76, '135/87', 36.6, 98, 92, 'Hipertensión', 'Losartán', 'Ninguna', 76, 2),
(77, '126/90', 36.7, 90, 95, 'Hipertensión', 'Losartán', 'Ninguna', 77, 3),
(78, '111/80', 37.7, 68, 98, 'Hipertensión', 'Losartán', 'Ninguna', 78, 1),
(79, '131/74', 36.2, 82, 92, 'Hipertensión', 'Losartán', 'Ninguna', 79, 2),
(80, '138/84', 37.3, 63, 91, 'Hipertensión', 'Losartán', 'Ninguna', 80, 3),
(81, '128/86', 35.3, 77, 96, 'Hipertensión', 'Losartán', 'Ninguna', 81, 1),
(82, '128/88', 36.3, 68, 94, 'Hipertensión', 'Losartán', 'Ninguna', 82, 2),
(83, '123/75', 35.2, 63, 95, 'Hipertensión', 'Losartán', 'Ninguna', 83, 3),
(84, '138/70', 37.4, 84, 93, 'Hipertensión', 'Losartán', 'Ninguna', 84, 1),
(85, '140/90', 37.2, 77, 95, 'Hipertensión', 'Losartán', 'Ninguna', 85, 2),
(86, '127/70', 36.1, 75, 93, 'Hipertensión', 'Losartán', 'Ninguna', 86, 3),
(87, '113/73', 39.3, 95, 90, 'Hipertensión', 'Losartán', 'Ninguna', 87, 1),
(88, '124/70', 35.9, 70, 94, 'Hipertensión', 'Losartán', 'Ninguna', 88, 2),
(89, '139/86', 36.2, 66, 99, 'Hipertensión', 'Losartán', 'Ninguna', 89, 3),
(90, '138/77', 39.9, 65, 94, 'Hipertensión', 'Losartán', 'Ninguna', 90, 1),
(91, '131/78', 36.1, 60, 91, 'Hipertensión', 'Losartán', 'Ninguna', 91, 2),
(92, '122/83', 36.4, 70, 91, 'Hipertensión', 'Losartán', 'Ninguna', 92, 3),
(93, '118/85', 37.7, 95, 95, 'Hipertensión', 'Losartán', 'Ninguna', 93, 1),
(94, '113/82', 39.3, 79, 90, 'Hipertensión', 'Losartán', 'Ninguna', 94, 2),
(95, '126/88', 35.7, 67, 96, 'Hipertensión', 'Losartán', 'Ninguna', 95, 3),
(96, '118/79', 37.3, 87, 94, 'Hipertensión', 'Losartán', 'Ninguna', 96, 1),
(97, '126/72', 37.4, 98, 100, 'Hipertensión', 'Losartán', 'Ninguna', 97, 2),
(98, '114/72', 39.5, 75, 91, 'Hipertensión', 'Losartán', 'Ninguna', 98, 3),
(99, '127/77', 38.8, 80, 94, 'Hipertensión', 'Losartán', 'Ninguna', 99, 1),
(100, '127/77', 38.8, 70, 90, 'Hipertensión', 'Losartán', 'Ninguna', 100, 2),
(101, '125/86', 37.5, 92, 100, 'Hipertensión', 'Losartán', 'Ninguna', 101, 3),
(102, '136/80', 35.1, 88, 96, 'Hipertensión', 'Losartán', 'Ninguna', 102, 1),
(103, '119/88', 36.3, 83, 98, 'Hipertensión', 'Losartán', 'Ninguna', 103, 2),
(104, 'GRBEFVQ', 563.0, 63, 651, 'GEFVD', 'REDRV', 'EBGFVDQ', 104, 1),
(105, '526', 465.0, 146, 64, 'TEGRWEF', 'BTREFD', 'GTREF', 105, 1),
(106, 'nyrtbrv', 6.0, 45, 465, 'tebfv', 'gbfvds', 'bgrfv', 106, 1),
(107, 'nyrtbrv', 6.0, 45, 465, 'tebfv', 'gbfvds', 'bgrfv', 107, 1),
(108, 'grbddvs', 55.0, 55, 55, 'bgfvs', 'g dfdvscs', 'bgefvd', 108, 1),
(109, '45', 999.9, 45, 4554, 'gtbrve', 'gbfvdcs', 'ghbfvdc', 109, 1),
(110, '45', 999.9, 45, 4554, 'gtbrve', 'gbfvdcs', 'ghbfvdc', 110, 10),
(111, '45', 999.9, 45, 4554, 'gtbrve', 'gbfvdcs', 'ghbfvdc', 111, 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pacientes`
--

DROP TABLE IF EXISTS `pacientes`;
CREATE TABLE `pacientes` (
  `id_paciente` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido_paterno` varchar(50) NOT NULL,
  `apellido_materno` varchar(50) DEFAULT NULL,
  `edad` int(11) NOT NULL,
  `genero` enum('Masculino','Femenino','Otro') NOT NULL,
  `pais` varchar(50) NOT NULL,
  `telefono` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `pacientes`
--

INSERT INTO `pacientes` (`id_paciente`, `nombre`, `apellido_paterno`, `apellido_materno`, `edad`, `genero`, `pais`, `telefono`) VALUES
(1, 'Juan', 'Pérez', 'Gómez', 35, 'Masculino', 'México', '5544332211'),
(2, 'Juan', 'Pérez', 'Gómez', 30, 'Masculino', 'México', '5512345678'),
(3, 'egweg', 'gre', 'Gracia', 34, 'Otro', 'México', '9516577535'),
(4, 'Javier', 'Pérez', 'Fernández', 58, 'Femenino', 'México', '5547122016'),
(5, 'Mónica', 'Martínez', 'López', 64, 'Masculino', 'México', '5576618824'),
(6, 'José', 'Fernández', 'Gómez', 56, 'Masculino', 'México', '5561356015'),
(7, 'Luis', 'Sánchez', 'López', 19, 'Femenino', 'México', '5590801917'),
(8, 'Lucía', 'Fernández', 'Rodríguez', 26, 'Femenino', 'México', '5590162262'),
(9, 'María', 'Ramírez', 'Gómez', 58, 'Masculino', 'México', '5565496421'),
(10, 'Mónica', 'López', 'López', 74, 'Femenino', 'México', '5570128533'),
(11, 'Ana', 'Gómez', 'Martínez', 38, 'Femenino', 'México', '5530741720'),
(12, 'Carlos', 'Gómez', 'Rodríguez', 64, 'Masculino', 'México', '5572787938'),
(13, 'Carlos', 'Rodríguez', 'Ramírez', 27, 'Masculino', 'México', '5554068948'),
(14, 'Fernando', 'López', 'Ramírez', 72, 'Masculino', 'México', '5525568968'),
(15, 'Ana', 'Pérez', 'Pérez', 69, 'Femenino', 'México', '5556939735'),
(16, 'María', 'Fernández', 'Gómez', 55, 'Masculino', 'México', '5531398809'),
(17, 'Luis', 'Martínez', 'Fernández', 50, 'Masculino', 'México', '5560017047'),
(18, 'José', 'Martínez', 'Ramírez', 57, 'Femenino', 'México', '5594540536'),
(19, 'Elena', 'Ramírez', 'Pérez', 34, 'Femenino', 'México', '5521150246'),
(20, 'José', 'Fernández', 'Martínez', 41, 'Masculino', 'México', '5529612553'),
(21, 'Elena', 'Gómez', 'Sánchez', 35, 'Masculino', 'México', '5526574321'),
(22, 'Carlos', 'Ramírez', 'López', 67, 'Masculino', 'México', '5524864629'),
(23, 'Elena', 'Sánchez', 'Pérez', 67, 'Femenino', 'México', '5540997771'),
(24, 'Javier', 'Sánchez', 'Gómez', 60, 'Masculino', 'México', '5517705610'),
(25, 'Javier', 'Rodríguez', 'López', 29, 'Masculino', 'México', '5540921971'),
(26, 'Mónica', 'López', 'Fernández', 76, 'Masculino', 'México', '5582567299'),
(27, 'Mónica', 'Sánchez', 'Martínez', 35, 'Masculino', 'México', '5580768129'),
(28, 'Mónica', 'López', 'Ramírez', 23, 'Masculino', 'México', '5540638521'),
(29, 'María', 'Gómez', 'Ramírez', 74, 'Masculino', 'México', '5523413236'),
(30, 'José', 'Ramírez', 'Ramírez', 31, 'Femenino', 'México', '5575072241'),
(31, 'Lucía', 'Ramírez', 'López', 79, 'Masculino', 'México', '5583009836'),
(32, 'Javier', 'Sánchez', 'Gómez', 63, 'Femenino', 'México', '5551742695'),
(33, 'Carlos', 'Gómez', 'Fernández', 37, 'Femenino', 'México', '5560669568'),
(34, 'Javier', 'López', 'Rodríguez', 74, 'Masculino', 'México', '5532193057'),
(35, 'Mónica', 'López', 'Ramírez', 38, 'Masculino', 'México', '5585148431'),
(36, 'Luis', 'Ramírez', 'López', 40, 'Masculino', 'México', '5549250086'),
(37, 'Lucía', 'Pérez', 'Rodríguez', 33, 'Masculino', 'México', '5561951669'),
(38, 'Luis', 'Pérez', 'Gómez', 54, 'Femenino', 'México', '5579185548'),
(39, 'Carlos', 'Gómez', 'Sánchez', 48, 'Masculino', 'México', '5512025796'),
(40, 'Fernando', 'Fernández', 'Fernández', 69, 'Masculino', 'México', '5531517300'),
(41, 'Fernando', 'Pérez', 'Fernández', 55, 'Masculino', 'México', '5581590144'),
(42, 'Ana', 'Gómez', 'Pérez', 54, 'Femenino', 'México', '5552417250'),
(43, 'Fernando', 'Gómez', 'López', 32, 'Femenino', 'México', '5597538690'),
(44, 'María', 'Pérez', 'Fernández', 55, 'Femenino', 'México', '5577140555'),
(45, 'Javier', 'Sánchez', 'Gómez', 56, 'Femenino', 'México', '5563126217'),
(46, 'Mónica', 'Gómez', 'Fernández', 59, 'Femenino', 'México', '5522062501'),
(47, 'Javier', 'Martínez', 'López', 71, 'Masculino', 'México', '5533920169'),
(48, 'María', 'Sánchez', 'Martínez', 49, 'Masculino', 'México', '5555289909'),
(49, 'Elena', 'Fernández', 'Fernández', 42, 'Masculino', 'México', '5535745599'),
(50, 'Fernando', 'López', 'Pérez', 71, 'Femenino', 'México', '5512715884'),
(51, 'Fernando', 'Ramírez', 'Sánchez', 22, 'Masculino', 'México', '5510648239'),
(52, 'Carlos', 'López', 'López', 75, 'Femenino', 'México', '5546824932'),
(53, 'Mónica', 'Rodríguez', 'Rodríguez', 51, 'Femenino', 'México', '5519856888'),
(54, 'Lucía', 'Fernández', 'Martínez', 19, 'Femenino', 'México', '5594194885'),
(55, 'María', 'Sánchez', 'Gómez', 77, 'Masculino', 'México', '5532587289'),
(56, 'Luis', 'Gómez', 'Rodríguez', 72, 'Femenino', 'México', '5579231349'),
(57, 'Luis', 'Pérez', 'Martínez', 55, 'Masculino', 'México', '5578609936'),
(58, 'Javier', 'Fernández', 'Pérez', 46, 'Masculino', 'México', '5542713689'),
(59, 'José', 'Fernández', 'Pérez', 36, 'Masculino', 'México', '5531103044'),
(60, 'José', 'Rodríguez', 'Fernández', 78, 'Femenino', 'México', '5541215716'),
(61, 'Ana', 'Rodríguez', 'López', 42, 'Femenino', 'México', '5510105327'),
(62, 'Ana', 'López', 'Sánchez', 61, 'Masculino', 'México', '5579297193'),
(63, 'Ana', 'Fernández', 'Fernández', 66, 'Femenino', 'México', '5539950467'),
(64, 'Mónica', 'Sánchez', 'Pérez', 30, 'Masculino', 'México', '5586052118'),
(65, 'José', 'Ramírez', 'Rodríguez', 55, 'Masculino', 'México', '5513297167'),
(66, 'María', 'Gómez', 'Martínez', 18, 'Femenino', 'México', '5533924312'),
(67, 'Carlos', 'Rodríguez', 'Sánchez', 19, 'Masculino', 'México', '5523525498'),
(68, 'Lucía', 'Fernández', 'Martínez', 40, 'Masculino', 'México', '5569459678'),
(69, 'Mónica', 'Rodríguez', 'Sánchez', 31, 'Masculino', 'México', '5589272180'),
(70, 'María', 'Gómez', 'Rodríguez', 28, 'Masculino', 'México', '5547275743'),
(71, 'Ana', 'López', 'Fernández', 44, 'Femenino', 'México', '5517443582'),
(72, 'Javier', 'Sánchez', 'Gómez', 43, 'Femenino', 'México', '5598284226'),
(73, 'Lucía', 'Rodríguez', 'Gómez', 34, 'Femenino', 'México', '5571127305'),
(74, 'José', 'Gómez', 'Pérez', 24, 'Femenino', 'México', '5595990653'),
(75, 'Ana', 'Fernández', 'Gómez', 52, 'Femenino', 'México', '5546446772'),
(76, 'Elena', 'Ramírez', 'Ramírez', 72, 'Femenino', 'México', '5594687029'),
(77, 'Carlos', 'Ramírez', 'Sánchez', 57, 'Femenino', 'México', '5550162802'),
(78, 'Carlos', 'Gómez', 'Ramírez', 80, 'Masculino', 'México', '5523711935'),
(79, 'María', 'Rodríguez', 'Fernández', 73, 'Femenino', 'México', '5581879133'),
(80, 'Luis', 'Fernández', 'Sánchez', 51, 'Masculino', 'México', '5550276896'),
(81, 'Javier', 'López', 'Ramírez', 51, 'Masculino', 'México', '5517944480'),
(82, 'José', 'López', 'Pérez', 49, 'Femenino', 'México', '5562091900'),
(83, 'José', 'Rodríguez', 'Rodríguez', 77, 'Femenino', 'México', '5520503062'),
(84, 'Fernando', 'Sánchez', 'Sánchez', 39, 'Femenino', 'México', '5561903792'),
(85, 'Javier', 'Sánchez', 'López', 20, 'Femenino', 'México', '5571009061'),
(86, 'Luis', 'López', 'López', 68, 'Femenino', 'México', '5530729274'),
(87, 'Elena', 'Sánchez', 'Pérez', 37, 'Masculino', 'México', '5550394881'),
(88, 'Ana', 'Martínez', 'Ramírez', 76, 'Masculino', 'México', '5574897457'),
(89, 'Fernando', 'Martínez', 'Ramírez', 20, 'Masculino', 'México', '5535572513'),
(90, 'Ana', 'Pérez', 'Pérez', 66, 'Femenino', 'México', '5542636384'),
(91, 'José', 'Fernández', 'Martínez', 77, 'Femenino', 'México', '5561655053'),
(92, 'Fernando', 'Sánchez', 'Pérez', 31, 'Masculino', 'México', '5514331026'),
(93, 'Fernando', 'Ramírez', 'Martínez', 32, 'Masculino', 'México', '5515788509'),
(94, 'José', 'Fernández', 'López', 44, 'Masculino', 'México', '5597499005'),
(95, 'Carlos', 'Pérez', 'Pérez', 48, 'Femenino', 'México', '5552343698'),
(96, 'Lucía', 'Sánchez', 'Pérez', 28, 'Masculino', 'México', '5550170521'),
(97, 'Ana', 'Fernández', 'López', 52, 'Masculino', 'México', '5539314289'),
(98, 'José', 'Martínez', 'Gómez', 72, 'Masculino', 'México', '5544678376'),
(99, 'Lucía', 'López', 'Fernández', 45, 'Femenino', 'México', '5564195881'),
(100, 'Fernando', 'Martínez', 'López', 48, 'Femenino', 'México', '5526793005'),
(101, 'Javier', 'Pérez', 'Pérez', 43, 'Masculino', 'México', '5531192259'),
(102, 'Luis', 'Ramírez', 'Fernández', 60, 'Femenino', 'México', '5583749797'),
(103, 'Carlos', 'Pérez', 'Martínez', 64, 'Femenino', 'México', '5544758195'),
(104, 'bgfvdcsx', 'g fdcs', 'GFVDCSQ', 65, 'Masculino', 'GRBEFVD', '651325312'),
(105, 'PRUEBA', 'TBEVR', 'BTERW', 78, 'Otro', 'COLOMBIA', '1595159515'),
(106, 'nyrbtevr', 'btrevwdc', 'terfw', 456, 'Femenino', 'rynbtefv', '45645846489'),
(107, 'nyrbtevr', 'btrevwdc', 'terfw', 456, 'Otro', 'rynbtefv', '45645846489'),
(108, 'juyhtgrf', 'gnbfvdcs', 'rnhgbefvd', 45, 'Otro', 'thgrfe', '4565455855'),
(109, 'FINAL', 'befrwef', 'gnrbfvdw', 22, 'Masculino', 'México', '9585658565'),
(110, 'FINAL', 'befrwef', 'gnrbfvdw', 22, 'Femenino', 'México', '9585658565'),
(111, 'FINAL', 'befrwef', 'gnrbfvdw', 22, 'Masculino', 'México', '9585658565');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `doctores`
--
ALTER TABLE `doctores`
  ADD PRIMARY KEY (`id_doctor`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `id_jefe` (`id_jefe`);

--
-- Indices de la tabla `historia_clinica`
--
ALTER TABLE `historia_clinica`
  ADD PRIMARY KEY (`id_historia_clinica`),
  ADD KEY `id_paciente` (`id_paciente`),
  ADD KEY `id_doctor` (`id_doctor`);

--
-- Indices de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  ADD PRIMARY KEY (`id_paciente`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `doctores`
--
ALTER TABLE `doctores`
  MODIFY `id_doctor` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `historia_clinica`
--
ALTER TABLE `historia_clinica`
  MODIFY `id_historia_clinica` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT de la tabla `pacientes`
--
ALTER TABLE `pacientes`
  MODIFY `id_paciente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `doctores`
--
ALTER TABLE `doctores`
  ADD CONSTRAINT `doctores_ibfk_1` FOREIGN KEY (`id_jefe`) REFERENCES `doctores` (`id_doctor`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `historia_clinica`
--
ALTER TABLE `historia_clinica`
  ADD CONSTRAINT `historia_clinica_ibfk_1` FOREIGN KEY (`id_paciente`) REFERENCES `pacientes` (`id_paciente`) ON DELETE CASCADE,
  ADD CONSTRAINT `historia_clinica_ibfk_2` FOREIGN KEY (`id_doctor`) REFERENCES `doctores` (`id_doctor`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
