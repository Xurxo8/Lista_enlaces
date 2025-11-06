<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

/* Tabla de enlaces individuales */
$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'enlace` (
  `id_enlace` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `url` VARCHAR(512) NOT NULL,
  `nueva_ventana` TINYINT(1) NOT NULL DEFAULT 0,
  `posicion` INT UNSIGNED DEFAULT 0,
  `personalizado` BOOLEAN NOT NULL DEFAULT FALSE,
  PRIMARY KEY (`id_enlace`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;';

/* Tabla principal de listas de enlaces */
$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'lista_enlaces` (
  `id_lista` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `hook` VARCHAR(64) DEFAULT NULL,
  `posicion` INT UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id_lista`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;';

/* Relación N:N entre listas y enlaces */
$sql[] = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'lista_enlace_relacion` (
  `id_lista` INT UNSIGNED NOT NULL,
  `id_enlace` INT UNSIGNED NOT NULL,
  `posicion` INT UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id_lista`, `id_enlace`),
  CONSTRAINT `fk_relacion_lista` FOREIGN KEY (`id_lista`)
    REFERENCES `'._DB_PREFIX_.'lista_enlaces` (`id_lista`) ON DELETE CASCADE,
  CONSTRAINT `fk_relacion_enlace` FOREIGN KEY (`id_enlace`)
    REFERENCES `'._DB_PREFIX_.'enlace` (`id_enlace`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;';

/* Ejecutar todas las sentencias SQL */
foreach ($sql as $query) {
  if (!Db::getInstance()->execute($query)) {
    return false;
  }
}

