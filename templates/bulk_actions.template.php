
<div style="margin-top: 10px">
	<div style="width: auto; float: left">With selected persons: &nbsp;</div>
	<div style="width: auto; float: left">
		<select id="bulk-action-chooser">
			<option>-- Choose Action --</option>
	<?php
	if ($GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
		if ((array_get($_REQUEST, 'view') == 'groups') && (!empty($_REQUEST['groupid']) || !empty($_REQUEST['person_groupid']))) {
			?>
			<option value="remove-from-this-group">Remove from this group</option>
			<?php
		}
		?>
			<option value="add-to-existing-group">Add to an existing group</option>
			<option value="add-to-new-group">Add to new group</option>
			<?php
	}
	if ($GLOBALS['user_system']->havePerm(PERM_EDITNOTE)) {
		?>
		<option value="add-note">Add note</option>
		<?php
	}
	?>
		<option value="email">Send email</option>
	<?php
	$enable_sms = $GLOBALS['user_system']->havePerm(PERM_SENDSMS) && defined('SMS_HTTP_URL') && constant('SMS_HTTP_URL') && defined('SMS_HTTP_POST_TEMPLATE') && constant('SMS_HTTP_POST_TEMPLATE');
	if ($enable_sms) {
		?>
		<option value="smshttp">Send SMS</option>
		<?php
	}
	?>
		<option value="envelopes">Print envelopes</option>
		<option value="csv">Get CSV</option>
	<?php
	if (version_compare(PHP_VERSION, '5.2', '>=')) {
		?>
		<option value="mail-merge">Mail merge a document</option>
		<?php
	}
	require_once 'db_objects/action_plan.class.php';
	$plan_chooser = Action_Plan::getMultiChooser('planid', Array());
	if ($plan_chooser) {
		?>
		<option value="execute-plan">Execute an action plan</option>
		<?php
	}
	?>
	</select>
	<?php
	if ($GLOBALS['user_system']->havePerm(PERM_EDITPERSON)) {
		?>
		<div class="bulk-action" id="remove-from-this-group" style="text-align: right">
			<input type="submit" value="Go" onclick="this.form.action = '<?php echo BASE_URL; ?>?view=_edit_group&action=remove_members&groupid=<?php echo array_get($_REQUEST, 'groupid', array_get($_REQUEST, 'person_groupid')); ?>'" />
		</div>

		<div class="bulk-action" id="add-to-existing-group">
			<select name="groupid">
				<?php
				foreach ($GLOBALS['system']->getDBObjectData('person_group', Array(), 'OR', 'name') as $id => $details) {
					if (isset($groups[$id])) continue;
					?>
					<option value="<?php echo $id; ?>"><?php echo $details['name']; ?></option>
					<?php
				}
				?>
			</select>
			<input type="submit" value="Go" onclick="this.form.target = ''; this.form.action = '<?php echo BASE_URL; ?>?view=_edit_group&action=add_members'" />
		</div>

		<div class="bulk-action" id="add-to-new-group">
			<table>
				<tr>
					<td>New group name: </td>
					<td>
						<?php
						$GLOBALS['system']->includeDBClass('person_group');
						$g = new Person_Group();
						$g->printFieldInterface('name'); 
						?>
					</td>
				</tr>
				<tr>
					<td>Category:</td>
					<td><?php $g->printFieldInterface('categoryid'); ?></td>
				</tr>
				<tr>
					<td></td>
					<td>
						<input type="hidden" name="new_person_group_submitted" value="1" />
						<input type="submit" value="Go" onclick="this.form.target = ''; this.form.action = '<?php echo BASE_URL; ?>?view=groups__add'" />
					</td>
				</tr>
			</table>
		</div>
		<?php
	}
	if ($GLOBALS['user_system']->havePerm(PERM_EDITNOTE)) {
		?>
		<div class="bulk-action" id="add-note">
			<input type="hidden" name="new_note_submitted" value="1" />
			<?php 
			$GLOBALS['system']->includeDBClass('person_note');
			$note = new Person_Note();
			$note->printForm();
			?>
			<input type="submit" value="Go" onclick="this.form.target = ''; this.form.action = '<?php echo BASE_URL; ?>?view=_add_note_to_person'" />
		</div>
		<?php
	}
	if (version_compare(PHP_VERSION, '5.2', '>=')) {
		?>
		<div class="bulk-action" id="mail-merge">
			Source Document: <input class="compulsory" type="file" name="source_document" />
			<p class="smallprint">(Must be in <a href="http://en.wikipedia.org/wiki/OpenDocument">ODT</a> format. </p> 
			<p>Merge the document<br /><input class="compulsory" type="radio" name="merge_type" value="person" id="merge_type_person" checked="checked" /><label for="merge_type_person">for each person selected above</label><a class="smallprint" href="<?php echo BASE_URL; ?>/resources/sample_mail_merge.odt">(sample file)</a><br />
<input type="radio" name="merge_type" value="family" id="merge_type_family" /><label for="merge_type_family">for each family that has a member selected above</label><a class="smallprint" href="<?php echo BASE_URL; ?>/resources/sample_mail_merge_family.odt">(sample file)</a></p>
			<input type="submit" value="Go" onclick="this.form.target = ''; this.form.enctype = 'multipart/form-data'; this.form.action = '<?php echo BASE_URL; ?>?call=odf_merge'" />
		</div>
		<?php
	}
	?>
		<div class="bulk-action" id="email">
			<p>Send an email to<br />
			<input class="compulsory" type="radio" name="email_type" value="person" id="email_type_person" checked="checked" /><label for="email_type_person">the selected persons</label><br />
			<input type="radio" name="email_type" value="family" id="email_type_family" /><label for="email_type_family">the adults in the selected persons' families</label></p>
			<p><input type="checkbox" name="method" value="public" id="method-public" /><label for="method-public" class="smallprint">Allow recipients to see each other's email addresses</label></p>
			<input type="submit" value="Go" onclick="this.form.target = 'emailframe'; this.form.action = '<?php echo BASE_URL; ?>?call=email'" />
			<iframe name="emailframe" style="width: 0; height: 0; border-width: 0px"></iframe>
		</div>
	<?php
	if ($enable_sms) {
		?>
		<div class="bulk-action" id="smshttp">
			<input class="compulsory" type="radio" name="sms_type" value="person" id="sms_type_person" checked="checked" /><label for="sms_type_person">the selected persons</label><br />
			<input type="radio" name="sms_type" value="family" id="sms_type_family" /><label for="sms_type_family">the adults in the selected persons' families</label></p>
			Message: <br /><textarea name="message" rows="5" cols="30" maxlength="<?php echo SMS_MAX_LENGTH; ?>"></textarea>
			<input type="submit" value="Send" onclick="this.form.action = '<?php echo BASE_URL; ?>?view=_send_sms_http'" />
		</div>
		<?php
	}
	?>
		<div class="bulk-action" id="csv">
			<p>Get a CSV file of <br />
			<input class="compulsory" type="radio" name="merge_type" value="person" id="merge_type_person" checked="checked" /><label for="merge_type_person">the selected persons</label><br />
			<input type="radio" name="merge_type" value="family" id="merge_type_family" /><label for="merge_type_family">the families the selected persons belong to</label></p>
			<input type="submit" value="Go" onclick="this.form.target = ''; this.form.enctype = 'multipart/form-data'; this.form.action = '<?php echo BASE_URL; ?>?call=csv'" />
		</div>

		<div class="bulk-action" id="envelopes">
			<p>Print envelopes addressed to <br />
			<input class="compulsory" type="radio" name="addressee" value="person" id="addressee_person" checked="checked" /><label for="addressee_person">the selected persons themselves, grouped by family (eg "John, Joanne & James Smith")</label><br />
			<input type="radio" name="addressee" value="family" id="addressee_family" /><label for="addressee_family">the families the selected persons belong to (eg "Jones Family")</label><br />
			<input type="radio" name="addressee" value="adults" id="addressee_adults" /><label for="addressee_adults">adult members of the selected persons' families (eg "Bert and Marjorie Citizen")</label></p>
			<input type="submit" value="Go" onclick="targetFormToEnvelopeWindow(this.form); this.form.action = '<?php echo BASE_URL; ?>?call=envelopes'" />
		</div>
	<?php
	if ($plan_chooser) {
		?>
		<div class="bulk-action" id="execute-plan">
		<?php echo $plan_chooser; ?>
		<p>Reference date for plans: <?php print_widget('plan_reference_date', Array('type' => 'date'), NULL); ?>
		&nbsp;
		<input type="submit" value="Go" onclick="this.form.action = '<?php echo BASE_URL; ?>?view=_execute_plans'" /></p>
		</div>
		<?php
	}
	?>
</div>
</div>
