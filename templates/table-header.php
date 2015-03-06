<?php
/**
 * Display above the entries loop when using a table layout
 *
 * @package GravityView
 * @subpackage GravityView/templates
 *
 * @global GravityView_View $this
 */
?>
<?php gravityview_before(); ?>
<div class="<?php gv_container_class('gv-table-container'); ?>">
<table class="gv-table-view">
	<thead>
		<?php gravityview_header(); ?>
		<tr>
			<?php

				// Make sure this wasn't overridden by search
				$this->setTemplatePartSlug('table');

				$this->renderZone( 'columns', array(
					'markup' => '<th class="{{class}}">{{label}}</th>',
					'hide_empty' => false, // Always show <th>
				));

			?>
		</tr>
	</thead>

