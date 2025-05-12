<?php
/**
 * Timber initialization and configuration
 */

Timber\Timber::init();

Timber::$dirname = ['templates'];

/**
 * By default, Timber does NOT autoescape values. Want to enable Twig's autoescape?
 * No prob! Just set this value to true
 */
Timber::$autoescape = false; 