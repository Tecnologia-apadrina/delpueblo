<div class="wrap">
	<h2><?php _e( 'Create new scheduled job', 'yith-woocommerce-quick-export' ); ?></h2>

	<div class="exportation-job-settings">
		<form id="manual-export" method="post">
			<table class="form-table">
				<input type="hidden" name="ywqe_new_job" value="1">

				<tbody>

				<tr valign="top" class="">
					<th scope="row" class="titledesc"><?php _e( 'Choose data to export', 'yith-woocommerce-quick-export' ); ?></th>
					<td class="forminp forminp-checkbox">
						<fieldset>
							<legend class="screen-reader-text">
								<span><?php _e( 'Choose data to export', 'yith-woocommerce-quick-export' ); ?></span>
							</legend>
							<label for="ywqe_export_orders">
								<input name="ywqe_export_orders" id="ywqe_export_orders" type="checkbox" value="1"
								       checked="checked" class="export-items"><?php _e( 'Orders', 'yith-woocommerce-quick-export' ); ?>
							</label>
						</fieldset>
						<fieldset class="">
							<legend class="screen-reader-text">
								<span><?php _e( 'Choose data to export', 'yith-woocommerce-quick-export' ); ?></span>
							</legend>
							<label for="ywqe_export_customers">
								<input name="ywqe_export_customers" id="ywqe_export_customers" type="checkbox" value="1"
								       checked="checked" class="export-items"><?php _e( 'Customers', 'yith-woocommerce-quick-export' ); ?>
							</label></fieldset>
						<fieldset class="">
							<legend class="screen-reader-text">
								<span><?php _e( 'Choose data to export', 'yith-woocommerce-quick-export' ); ?></span>
							</legend>
							<label for="ywqe_export_coupons">
								<input name="ywqe_export_coupons" id="ywqe_export_coupons" type="checkbox" value="1"
								       checked="checked" class="export-items"><?php _e( 'Coupons', 'yith-woocommerce-quick-export' ); ?>
							</label>
						</fieldset>
                        <?php
                        //Integration with YITH Gift Cards
                        if ( function_exists( 'YITH_YWGC' ) ){
                        ?>
                            <fieldset class="">
                                <legend class="screen-reader-text">
                                   <span><?php _e( 'Choose data to export', 'yith-woocommerce-quick-export' ); ?></span>
                                </legend>
                                <label for="ywqe_export_gift_cards">
                                    <input name="ywqe_export_gift_cards" id="ywqe_export_gift_cards" type="checkbox" value="1"
                                       checked="checked" class="export-items"><?php _e( 'Gift Cards', 'yith-woocommerce-quick-export' ); ?>
                                </label>
                            </fieldset>
                        <?php } ?>
					</td>
				</tr>
				<tr valign="top" class="">
					<th scope="row" class="titledesc"><?php _e( 'Order status', 'yith-woocommerce-quick-export' ); ?></th>
					<td class="forminp forminp-checkbox">
						<?php
						$statuses = wc_get_order_statuses();
						foreach ( $statuses as $status => $status_name ): ?>
							<fieldset><label>
									<input name="ywqe_export_order_status[]" type="checkbox" value="<?php echo $status; ?>"
									       checked="checked"><?php echo $status_name; ?>
								</label></fieldset>
						<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>

				<tr valign="top" class="">
					<th scope="row" class="titledesc"><?php _e( 'Scheduled exportation', 'yith-woocommerce-quick-export' ); ?></th>
					<td class="forminp forminp-checkbox">
						<label for="ywqe_schedule_exportation">
							<input name="ywqe_schedule_exportation" id="ywqe_schedule_exportation" type="checkbox"
							       value="1"><?php _e( 'Start exportation at specific date/time', 'yith-woocommerce-quick-export' ); ?>
						</label>


					</td>
				</tr>

				<tr valign="top" class="manual-exportation">
					<th scope="row" class="titledesc">
						<label for="ywqe_export_start_date"><?php _e( 'From', 'yith-woocommerce-quick-export' ); ?></label>
					</th>
					<td class="forminp forminp-text">
						<input type="text" class="date-picker" name="ywqe_export_start_date"
						       id="ywqe_export_start_date" placeholder="dd-mm-yyyy"
						       maxlength="10" value="" pattern="(0[1-9]|1[0-9]|2[0-9]|3[01])-(0[1-9]|1[012])-[0-9]{4}">
						<span
							class="description"><?php _e( 'Choose the starting date. Leave blank to start from the first item found.', 'yith-woocommerce-quick-export' ); ?></span>
					</td>
				</tr>

				<tr valign="top" class="manual-exportation">
					<th scope="row" class="titledesc">
						<label for="ywqe_export_end_date"><?php _e( 'To', 'yith-woocommerce-quick-export' ); ?></label>
					</th>
					<td class="forminp forminp-text">
						<input type="text" class="date-picker" name="ywqe_export_end_date"
						       id="ywqe_export_end_date" placeholder="dd-mm-yyyy"
						       maxlength="10" value="" pattern="(0[1-9]|1[0-9]|2[0-9]|3[01])-(0[1-9]|1[012])-[0-9]{4}">

						<span
							class="description"><?php _e( 'Choose the ending date. Leave blank to end with the last item found.', 'yith-woocommerce-quick-export' ); ?></span>
					</td>
				</tr>

				<tr valign="top" class="scheduled-exportation">
					<th scope="row" class="titledesc">
						<label for="ywqe_export_title"><?php _e( 'Exportation job name', 'yith-woocommerce-quick-export' ); ?></label>
					</th>
					<td class="forminp forminp-text">
						<input type="text" name="ywqe_export_title"
						       id="ywqe_export_title" value="Exportation job name" size="50">
						<span
							class="description"><?php _e( 'Set a univocal name for this exportation job', 'yith-woocommerce-quick-export' ); ?></span>
					</td>

				</tr>

				<tr valign="top" class="scheduled-exportation">
					<th scope="row" class="titledesc">
						<label for="ywqe_export_on_date"><?php _e( 'Export on', 'yith-woocommerce-quick-export' ); ?></label>
					</th>
					<td class="forminp forminp-text">
						<input type="text" class="date-picker" name="ywqe_export_on_date"
						       id="ywqe_export_on_date" placeholder="dd-mm-yyyy"
						       maxlength="10" size="10" value=""
						       pattern="(0[1-9]|1[0-9]|2[0-9]|3[01])-(0[1-9]|1[012])-[0-9]{4}">
						<input type="text" name="ywqe_export_on_time" placeholder="hh:mm"
						       id="ywqe_export_on_time"
						       maxlength="5" size="5" value="20:00" pattern="(0[1-9]|1[0-9]|2[012]):(0[1-9]|[0-5][0-9])">
						<span
							class="description"><?php _e( 'Choose when the exportation should start.', 'yith-woocommerce-quick-export' ); ?></span>
					</td>

				</tr>

				<tr valign="top" class="scheduled-exportation">
					<th scope="row" class="titledesc"><?php _e( 'Recurrence', 'yith-woocommerce-quick-export' ); ?></th>
					<td class="forminp forminp-checkbox">
						<input name="ywqe_recurrence_type" id="ywqe_recurrence_type_none" type="radio" value="none" checked="checked" class="recurrence">
                        <label for="ywqe_recurrence_type_none" style="margin-right: 15px"><?php _e( 'None', 'yith-woocommerce-quick-export' ); ?></label>

						<input name="ywqe_recurrence_type" id="ywqe_recurrence_type_daily" type="radio" value="daily" class="recurrence">
                        <label for="ywqe_recurrence_type_daily" style="margin-right: 15px"><?php _e( 'Daily', 'yith-woocommerce-quick-export' ); ?></label>

						<input name="ywqe_recurrence_type" id="ywqe_recurrence_type_weekly" type="radio" value="weekly" class="recurrence">
                        <label for="ywqe_recurrence_type_weekly" style="margin-right: 15px"><?php _e( 'Weekly', 'yith-woocommerce-quick-export' ); ?></label>

						<input name="ywqe_recurrence_type" id="ywqe_recurrence_type_monthly" type="radio" value="monthly" class="recurrence">
                        <label for="ywqe_recurrence_type_monthly" style="margin-right: 15px"><?php _e( 'Monthly (30 days)', 'yith-woocommerce-quick-export' ); ?></label>
					</td>
				</tr>

				<tr valign="top">
					<th scope="row">
					</th>
					<td class="forminp forminp-color plugin-option">
						<input type="submit" value="<?php _e( 'Confirm', 'yith-woocommerce-quick-export' ); ?>" id="start-now"
						       class="button-primary" name="submit">
						<a href="<?php echo esc_url( remove_query_arg( 'create-job' ) ); ?>"
						   class="button"><?php _e( 'Cancel', 'yith-woocommerce-quick-export' ); ?></a>

					</td>
				</tr>

				</tbody>
			</table>
		</form>
	</div>


