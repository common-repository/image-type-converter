<?php
namespace GPLSCOre\GPLS_PLUGIN_WICOR;
use Spatie\ImageOptimizer\DummyLogger;

defined( 'ABSPATH' ) || exit;

/**
 * Optimizer Logger Class.
 * 
 */
class OptimizerLogger extends DummyLogger {

    /**
     * Debug mode.
     * 
     * @var bool
     */
    private static $debug = false;


    /**
     * Info log.
     * 
     * @param mixed $message
     * @param array $context
     * @return void
     */
    public function info( $message, array $context = array() ): void {
        if ( self::$debug ) {
            error_log( 'Image Optimizer: } Error ' . $message . ' | Context : ' .implode( ', ', $context ) );
        }
    }

}