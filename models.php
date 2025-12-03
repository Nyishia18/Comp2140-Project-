<?php
/**
 * Models loader: keeps backward compatibility by including config and all model classes.
 */

require_once __DIR__ . '/config.php';

// Load model classes
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Customer.php';
require_once __DIR__ . '/models/Item.php';
require_once __DIR__ . '/models/ShoppingCart.php';
require_once __DIR__ . '/models/PaymentService.php';
require_once __DIR__ . '/models/Order.php';
