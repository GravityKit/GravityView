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
<div class="<?php gv_container_class('gv-table-container gv-table-multiple-container'); ?>">
<table class="gv-table-view">
	<thead>
		<?php gravityview_header(); ?>
		<tr>
			<?php

				// Make sure this wasn't overridden by search
				$this->setTemplatePartSlug('table');

				$this->renderZone( 'columns', array(
					'markup' => '<th id="{{ field_id }}" class="{{class}}" style="{{width:style}}">{{label}}</th>',
					'hide_empty' => false, // Always show <th>
				));

			?>
		</tr>
	</thead>

