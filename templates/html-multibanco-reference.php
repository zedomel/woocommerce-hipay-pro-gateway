<?php
/**
 * Multibanco reference info
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-booking/html-multibanco-reference.php.
 *
 * HOWEVER, on occasion HiPay Professional will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package WC_HiPay_Pro/Templates
 * @version 1.1.1
 */

defined( 'ABSPATH' ) || exit;

do_action( 'wc_hipay_pro_before_mb_reference_table', $order_id, $draft ); 

?>

<table cellpadding="6" cellspacing="2" style="width: 390px; height: 55px; margin: 10px 2px;border: 1px solid #ddd">
	<tr>
		<td style="background-color: #ccc;color:#313131;text-align:center;" colspan="3"><?php echo wp_kses_post( $description );?></td>
	</tr>
	<?php if( !empty( $draft ) ) : ?>
		<tr>
			<td rowspan="4" style="width:200px;padding: 0px 5px 0px 5px;vertical-align: middle;"><img src="<?php echo esc_url( WC_HiPay_Pro()->plugin_url() . '/images/mb_payment.jpg' );?>" style="margin-bottom: 0px; margin-right: 0px;"/></td>
			<td style="width:100px;"><?php _e('ENTITY:', 'wc-hipay-pro' );?></td>
			<td style="font-weight:bold;width:245px;"><?php echo esc_html( $draft[ 'entity' ] );?></td>
	</tr>	
		<tr>
			<td><?php _e('REFERENCE:', 'wc-hipay-pro');?></td>
			<td style="font-weight:bold;"><?php echo esc_html( $draft[ 'reference' ] ); ?></td>
		</tr>
		<tr>
			<td><?php _e('AMOUNT:', 'wc-hipay-pro'); ?></td>
			<td style="font-weight:bold;"><?php echo $order_total;?></td>
		</tr>		
		<tr>
			<td><?php _e('EXPIRE:', 'wc-hipay-pro'); ?></td>
			<td style="font-weight:bold;"><?php echo date_i18n( 'Y-m-d', $draft[ 'expire_date' ] ) ;?></td>
		</tr>
	<?php else: ?>
		<tr>
    	<td rowspan="4" style="width:200px;padding: 0px 5px 0px 5px;vertical-align: middle;"><img src="<?php echo esc_url( WC_HiPay_Pro()->plugin_url() . '/images/mb_payment.jpg' );?>" style="margin-bottom: 0px; margin-right: 0px;"/></td>
    	<td style="width:70%;"><?php _e( 'The Multibanco reference will be sent to your email address. Thank you.',  'wc-hipay-pro');?></td>    
  	</tr>
	<?php endif; ?>
</table>

<?php do_action( 'wc_hipay_pro_after_mb_reference_table', $order_id, $draft ); ?>
