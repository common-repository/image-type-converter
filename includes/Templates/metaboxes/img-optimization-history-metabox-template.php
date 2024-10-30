<?php
use GPLSCorePro\GPLS_PLUGIN_WICOR\ImageConverter;
defined( 'ABSPATH' ) || exit;

$plugin_info = $args['plugin_info'];
$core        = $args['core'];
$_post       = $args['post'];
$_post_id    = $args['id'];
$metabox     = $args['metabox'];
?>
<div class="<?php echo esc_attr( $plugin_info['classes_prefix'] . '-img-optimization-history-container' ); ?>" style="margin-top:10px;overflow-y: scroll;overflow-x:hidden;max-height: 800px;">
<?php $metabox->optimization_history_table( $_post_id ); ?>
</div>
