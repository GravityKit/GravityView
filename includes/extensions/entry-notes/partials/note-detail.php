<?php
/**
 * Display note details, in both editing and non-editing modes
 *
 * @since 1.17
 */
?>
<div itemscope itemtype="https://schema.org/Comment">
	<div class="gv-note-author-details" itemscope itemtype="https://schema.org/Person">
		<div class="gv-note-avatar" itemprop="image">{avatar}</div>
		<h6 class="gv-note-author" itemprop="name">{user_name}</h6>
	</div>
	<span class="gv-note-added-on" itemprop="dateCreated" datetime="{date_created}">{added_on}</span>
	<div class="gv-note-content" itemprop="comment">{value}</div>
</div>