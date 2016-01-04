<?php
/**
 * Payment Amount entry meta value
 *
 * @file payment_amount.php
 * @package GravityView
 * @subpackage templates\fields
 */

$entry = GravityView_View::getInstance()->getCurrentEntry();

$value = rgar( $entry, 'payment_amount' );

echo GFCommon::to_money( $value, rgar( $entry, 'currency' ) );
