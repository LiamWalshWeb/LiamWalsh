<div class="container">

  <div id="status-bar">
    <div class="status-block">
      <span class="muted"><?php echo $status_message ?></span>
      <span class="folder"><?php echo $full_name; ?></span>
    </div>
    <ul>
      <li>
        <a href="#" class="faux-submit">
          <span class="ss-icon">check</span>
          <?php echo Localization::fetch('save')?>
        </a>
      </li>
    </ul>
  </div>

  <form method="post" action="member?name=<?php print $name ?>" data-validate="parsley" class="primary-form" autocomplete="off">

    <input type="hidden" name="member[original_name]" value="<?php print $original_name ?>" />

    <?php if (isset($new)): ?>
      <input type="hidden" name="member[new]" value="1" />
    <?php endif ?>

    <div class="section content">

      <?php if (isset($errors) && (sizeof($errors) > 0)): ?>
      <div class="panel topo">
        <p><?php echo Localization::fetch('error_form_submission')?></p>
        <ul class="errors">
          <?php foreach ($errors as $field => $error): ?>
          <li><span class="field"><?php print $field ?></span> <span class="error"><?php print $error ?></span></li>
          <?php endforeach ?>
        </ul>
      </div>
      <?php endif ?>

      <div class="input-block input-text required">
        <label for="member-username"><?php echo Localization::fetch('username')?></label>
        <input type="text" id="member-username" name="member[name]" value="<?php print $name ?>" data-required="true" autocomplete="off" />
      </div>

      <div class="input-block input-text required">
        <label for="member-email"><?php echo Localization::fetch('email')?></label>
        <input type="email" name="member[yaml][email]" id="member-email" value="<?php print $email; ?>" data-required="true" autocomplete="off" />
      </div>

      <div class="input-block input-text">
        <label for="member-first-name"><?php echo Localization::fetch('first_name')?></label>
        <input type="text" name="member[yaml][first_name]" class="text title" id="gaa" value="<?php print $first_name; ?>" autocomplete="off" />
      </div>

      <div class="input-block input-text">
        <label for="member-last-name"><?php echo Localization::fetch('last_name')?></label>
        <input type="text" name="member[yaml][last_name]" id="member-last-name" value="<?php print $last_name; ?>" autocomplete="off" />
      </div>


      <div class="input-block" data-bind="visible: showChangePassword() !== true">
        <label for="member-password"><?php echo Localization::fetch('password')?></label>
        <div class="well">
          <a href="#" class="btn btn-small" data-bind="click: changePassword">Change Password</a>
        </div>
      </div>

      <div class="input-block input-text input-password" data-bind="visible: showChangePassword, css: {required: showChangePassword}">
        <label for="member-password"><?php echo Localization::fetch('password')?></label>
        <input type="password" name="member[yaml][password]" id="member-password" value="" autocomplete="off" data-bind="css: {required: showChangePassword}" />
      </div>

      <div class="input-block input-text input-password" data-bind="visible: showChangePassword, css: {required: showChangePassword}">
        <label for="member-password-confirmation"><?php echo Localization::fetch('password_confirmation')?></label>
        <input type="password" name="member[yaml][password_confirmation]" id="member-password-confirmation" value="" autocomplete="off" data-bind="css: {required: showChangePassword}" />
      </div>

      <div class="input-block input-checkbox input">
        <div class="checkbox-block">
          <input type="checkbox" name="member[yaml][roles]" id="member-roles" value="admin" <?php if ($roles) print "checked" ?> />
          <label for="member-roles"><?php echo Localization::fetch('admin')?></label>
        </div>
      </div>

      <div class="input-block input-textarea markitup">
        <label for="member-bio"><?php echo Localization::fetch('biography')?></label>
        <textarea name="member[biography]" id="member-bio"><?php print $biography; ?></textarea>
      </div>

    </div>

    <div id="publish-action" class="footer-controls push-down">
      <input type="submit" class="btn" value="<?php echo Localization::fetch('save')?>" id="publish-submit">
    </div>

  </form>
</div>

<script type="text/javascript">
  var viewModel = {
      showChangePassword: ko.observable(<?php if (isset($new)) {echo "true";} else {echo "false";} ?>),
      changePassword: function() {
        this.showChangePassword(true);
      }
  };
  ko.applyBindings(viewModel);
</script>