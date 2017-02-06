<div class="wrap">
	<h1>ACF Repeater Field Query<span style="font-size: 10px; padding-left: 12px;">- For Events -</span></h1>
	<section>
		<form method="post" action="options.php">
			<hr />
			<h3>General Settings</h3>
	        <?php
	            settings_fields('acf_rfq-settings-group');
	            do_settings_sections('acf_rfq-settings-group');
	        ?>
			<table class="form-table">
				<tbody>
					<tr>
						<th scope="row">
							<label for="acf_rfq_posttype">Post Type <span style="color: #c00; font-size: 10px; font-weight: normal;">(Require)</span></label>
						</th>
						<td>
							<?php
							// Get All post-type
								$args = array(
								   'public'   => true,
								   '_builtin' => false
								);
								$output = 'names'; // names or objects, note names is the default
								$operator = 'and'; // 'and' or 'or'
								$post_types = get_post_types($args, $output, $operator);
							// Add Default post "post"
								$addpost = array('post' => 'post');
								$post_types = array_merge($addpost, $post_types);
							?>
							<select id="acf_rfq_posttype" name="acf_rfq_posttype" style="width: 150px;">
								<?php foreach ($post_types as $post_type): ?>
									<?php $selected = (get_option('acf_rfq_posttype') == $post_type) ? "selected" : ""; ?>
									<option value="<?php echo $post_type; ?>" <?php echo $selected; ?>><?php echo $post_type; ?></option>
								<?php endforeach; ?>
                            </select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="acf_rfq_taxonomy">Taxonomy <span style="font-size: 11px; font-weight: normal;">(Optional)</span></label>
						</th>
						<td>
							<?php
								$args = array(
									'public'   => true,
									'_builtin' => false
								);
								$output = 'names'; // or objects
								$operator = 'and'; // 'and' or 'or'
								$taxonomies = get_taxonomies($args, $output, $operator);
							// Add Default category
								$addcat = array('category' => 'category');
								$taxonomies = array_merge($addcat, $taxonomies);
							?>
							<select id="acf_rfq_taxonomy" name="acf_rfq_taxonomy" style="width: 150px;">
								<option value="">Select...</option>
								<?php foreach ($taxonomies as $taxonomy): ?>
									<?php $selected = (get_option('acf_rfq_taxonomy') == $taxonomy) ? "selected" : ""; ?>
									<option value="<?php echo $taxonomy; ?>" <?php echo $selected; ?>><?php echo $taxonomy; ?></option>
								<?php endforeach; ?>
                            </select>
						</td>
					</tr>
				</tbody>
			</table>
			<hr />
			<h3>Field Settings<span style="font-size: 11px; font-weight: normal; margin-left: 10px;">(Field Name)</span></h3>
			<table id="field-settings" class="form-table">
				<thead>
					<tr>
						<th></th>
						<th>Field</th>
						<th>Field Type</th>
						<th>Return Format</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th scope="row">
							<label for="acf_rfq_dategroup">Repeater <span style="color: #c00; font-size: 10px; font-weight: normal;">(Require)</span></label>
						</th>
						<td>
							<input type="text" id="acf_rfq_dategroup" class="regular-text" name="acf_rfq_dategroup" value="<?php echo get_option('acf_rfq_dategroup'); ?>" style="width: 150px;">
						</td>
						<td>
							Repeater
						</td>
						<td>
							--
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="acf_rfq_datefield">Date <span style="color: #c00; font-size: 10px; font-weight: normal;">(Require)</span><span style="display: block; font-size: 11px; font-weight: normal; margin-left: 10px;">Using "Date Picker"</span></label>
						</th>
						<td>
							<input type="text" id="acf_rfq_datefield" class="regular-text" name="acf_rfq_datefield" value="<?php echo get_option('acf_rfq_datefield'); ?>" style="width: 150px;">
						</td>
						<td>
							Date Picker
						</td>
						<td>
							<code>Ymd</code>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="acf_rfq_datefield">StartTime<span style="font-size: 11px; font-weight: normal; margin-left: 10px;">(Optional)</span><span style="display: block; font-size: 11px; font-weight: normal; margin-left: 10px;">Using "Time Picker"</span></label>
						</th>
						<td>
							<input type="text" id="acf_rfq_datefield" class="regular-text" name="acf_rfq_starttimefield" value="<?php echo get_option('acf_rfq_starttimefield'); ?>" style="width: 150px;">
						</td>
						<td>
							Time Picker
						</td>
						<td>
							<code>H:i</code>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="acf_rfq_datefield">FinishTime<span style="font-size: 11px; font-weight: normal; margin-left: 10px;">(Optional)</span><span style="display: block; font-size: 11px; font-weight: normal; margin-left: 10px;">Using "Time Picker"</span></label>
						</th>
						<td>
							<input type="text" id="acf_rfq_datefield" class="regular-text" name="acf_rfq_finishtimefield" value="<?php echo get_option('acf_rfq_finishtimefield'); ?>" style="width: 150px;">
						</td>
						<td>
							Time Picker
						</td>
						<td>
							<code>H:i</code>
						</td>
					</tr>
				</tbody>
			</table>
			<hr>
			<div class="link-doc">
				<a href="https://github.com/sectsect/acf-repeater-field-query" target="_blank">
					<dl>
						<dt>
							<img src="https://github-sect.s3-ap-northeast-1.amazonaws.com/github.svg" width="22" height="auto">
						</dt>
					    <dd>
					        Document on Github
					    </dd>
					</dl>
				</a>
			</div>
			<?php submit_button(); ?>
		</form>
	</section>
</div>
