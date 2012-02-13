<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>Social Cache Settings</h2>

	<form method="post">
		<table class="form-table">
			<tbody>
				<?php foreach($settings as $id=>$setting): ?>
					<?php if($setting['type'] == 'text'): ?>
					<tr valign="top">
						<th scope="row">
							<label for="<?= $id; ?>"><?= $setting['label']; ?></label>
						</th>
						<td>
							<input name="<?= $id; ?>" type="text" id="<?= $id; ?>" value="<?= $setting['value']; ?>" class="regular-text">
						</td>
					</tr>
					<?php endif; ?>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
	</form>
</div>