<?php
/**
 * Display multiple entries as a list
 *
 * @package GravityView
 */

if((int)$this->__get('total_entries') === 0) {

	echo gv_no_results();

	return;
}

foreach( $this->entries as $entry ) :
?>

	<div id="gv_list_<?php echo $entry['id']; ?>" class="gv-list-view">

		<div class="gv-list-view-title">

			<?php if( !empty(  $this->fields['directory_list-title'] ) ):
				$i = 0;
				foreach( $this->fields['directory_list-title'] as $field ) :

					if( $i == 0 ): ?>
						<h3 class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></h3>
					<?php else: ?>
						<p class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></p>
					<?php endif;
					$i++; ?>
				<?php endforeach; ?>
			<?php endif; ?>

		</div>

		<div class="gv-list-view-content">

			<div class="gv-list-view-content-description">
				<?php if( !empty(  $this->fields['directory_list-description'] ) ):
					foreach( $this->fields['directory_list-description'] as $field ) : ?>
						<p class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></p>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div class="gv-list-view-content-image">
				<?php if( !empty(  $this->fields['directory_list-image'] ) ):
					foreach( $this->fields['directory_list-image'] as $field ) : ?>
						<?php echo gv_value( $entry, $field ); ?>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<div class="gv-list-view-content-attributes">
				<?php if( !empty(  $this->fields['directory_list-content-attributes'] ) ):
					foreach( $this->fields['directory_list-content-attributes'] as $field ) : ?>
						<p class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></p>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

		</div>

		<div class="gv-grid gv-list-view-footer">
			<?php if( !empty(  $this->fields['directory_list-footer-left'] ) ): ?>
				<div class="gv-grid-col-1-2">
					<?php foreach( $this->fields['directory_list-footer-left'] as $field ) : ?>
						<div class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if( !empty(  $this->fields['directory_list-footer-right'] ) ): ?>
				<div class="gv-grid-col-1-2">
					<?php foreach( $this->fields['directory_list-footer-right'] as $field ) : ?>
						<div class="<?php echo gv_class( $field ); ?>"><?php echo esc_html( gv_label( $field ) ); ?><?php echo gv_value( $entry, $field ); ?></div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			</div>
		</div>

	</div>

<?php endforeach; ?>
