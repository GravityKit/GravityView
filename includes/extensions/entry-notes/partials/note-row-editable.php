<?php
/**
 * Display a note row, with editing options
 *
 * @since 1.17
 */
?>
<tr class="{row_class}">
	<th class="check-column" scope="row">
		<input type="checkbox" value="{note_id}" name="note[]" />
	</th>
	<td class="entry-detail-note">
		{note_detail}
	</td>
</tr>