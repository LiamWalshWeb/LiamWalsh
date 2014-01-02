<div id="subnav">
  <ul>
    <?php if (CP_Helper::show_page('security', true)): ?>
    <li><a href="<?php echo $app->urlFor("security"); ?>"<?php if ($route === "security"):?> class="active"<?php endif ?>><?php echo Localization::fetch('security_status')?></a></li>
    <?php endif ?>

    <?php if (CP_Helper::show_page('logs', true)): ?>
    <li><a href="<?php echo $app->urlFor("logs"); ?>"<?php if ($route === "logs"):?> class="active"<?php endif ?>><?php echo Localization::fetch('logs')?></a></li>
    <?php endif ?>
  </ul>
</div>

<div class="container">

  <div id="status-bar" class="web">
    <div class="status-block">
      <span class="muted"><?php echo Localization::fetch('security_status')?></span>
    </div>
  </div>

  <h2 class="web">System Files Security Check</h2>
  <div class="section">
    <?php if (isset($system_checks) && is_array($system_checks) && count($system_checks) > 0): ?>
    <table class="simple-table sortable table-security">
      <thead>
        <tr>
          <th>Folder/File</th>
          <th>Action Required</th>
          <th class="align-right">Secure Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($system_checks as $asset => $data): ?>
        <?php extract($data); ?>
        <tr>
          <td><?php print $asset ?></td>
          <td><?php if ($status_code === 200): ?><?php echo $message?><?php else:?><span class="subtle">None</span><?php endif ?></td>
          <td class="align-right"><?php if ($status_code !== 200): ?>Secure <span class="ss-icon">checkclipboard</span> <?php else: ?>Unsecure <span class="ss-icon">warning</span> <?php endif ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>

  <h2 class="web">User Accounts Security Check</h2>
  <div class="section">
    <table class="simple-table table-security">
      <thead>
        <tr>
          <th>User</th>
          <th>Action Required</th>
          <th class="align-right">Password&nbsp;Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $username => $user): ?>
        <tr>
          <td class="title <?php print $status ?>"><a href="member?name=<?php print $username ?>"><?php print $username ?></a></td>
          <td><?php if ($status == 'warning'): ?><em>Encrypt password.</em><?php else:?><span class="subtle">None</span><?php endif ?></td>
          <td class="align-right">
            <?php if ($user->is_password_encrypted()): ?>
              Encrypted <span class="ss-icon">checkclipboard</span>
            <?php else: ?>
              Unencrypted <span class="ss-icon">warning</span>
            <?php endif ?>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
    <?php else: ?>

    <p>You'll need to enable cURL on your server to automatically test your security.</p>

    <?php endif; ?>
  </div>
</div>