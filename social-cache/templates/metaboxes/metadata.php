<div id="social-metadata-wrapper" class="social-cache_postbox default">
	<!-- type -->
	<div class="field" id="social-cache-message-type">
		<label class="field_label" for="social-cache-message-type">Type</label>
		<p class="instructions">Choose your social network.</p>
		<ul class="radio_list radio horizontal">
			<li>
				<label>
					<input type="radio" name="social-cache-message-type" value="facebook" <?= $fields['social-cache-message-type'] == 'facebook' ? 'checked="checked"' : '' ?>>Facebook
				</label>
			</li>
			<li>
				<label>
					<input type="radio" name="social-cache-message-type" value="twitter" <?= $fields['social-cache-message-type'] == 'twitter' ? 'checked="checked"' : '' ?>>Twitter
				</label>
			</li>
		</ul>
	</div>

	<!-- date published -->
	<div class="field">
		<label class="field_label" for="social-cache-date-published">Date Published (on Social Network)</label>
		<p class="instructions">Add in the following format for best results: YYYY-MM-DD HH:MM:SS</p>
		<input type="text" id="social-cache-date-published" class="text" name="social-cache-date-published" value="<?= $fields['social-cache-date-published']; ?>">
	</div>

	<!-- date published -->
	<div class="field">
		<label class="field_label" for="social-cache-json-cache">JSON Cache</label>
		<p class="instructions">For internal use, please do not fill out or edit.</p>
		<textarea id="social-cache-json-cache" rows="4" class="textarea" name="social-cache-json-cache"><?= $fields['social-cache-json-cache']; ?></textarea>
	</div>
</div>