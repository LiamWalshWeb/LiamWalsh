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
      <span class="muted"><?php echo Localization::fetch('site_logs')?></span>
    </div>
  </div>


  <div class="section">
    <div class="section-header content">

      <?php if ($enabled && !$logs_writable): ?>
        <p class="alert">
          Your log file (<?php echo $path; ?>) is not writable, and thus, logs cannot be written.
        </p>
      <?php endif; ?>

      <p>
        This site <strong><?php echo ($enabled) ? "is" : "is not"; ?></strong> logging
        <?php if ($enabled): ?>
          messages, <strong><?php echo $log_level; ?></strong>-level messages or&nbsp;worse.
        <?php else: ?>
          messages. To turn on logging, set <code>_log_enabled</code> to <code>true</code> in <code>_config/settings.yaml</code>.
        <?php endif; ?>
      </p>
    </div>

    <div class="content">
    <?php if ($logs_exist): ?>
      <form method="get" id="date-submit" class="log-controls">
        <p>
          <span>
            Showing
            <select id="filter-chooser" name="filter">
              <option value="">all messages</option>
              <optgroup label="----------------">
                <option value="debug"<?php echo ($filter === "debug") ? ' selected="selected"' : ''?>>only debug messages</option>
                <option value="info"<?php echo ($filter === "info") ? ' selected="selected"' : ''?>>only info messages</option>
                <option value="warn"<?php echo ($filter === "warn") ? ' selected="selected"' : ''?>>only warn messages</option>
                <option value="error"<?php echo ($filter === "error") ? ' selected="selected"' : ''?>>only error messages</option>
                <option value="fatal"<?php echo ($filter === "fatal") ? ' selected="selected"' : ''?>>only fatal messages</option>
              </optgroup>
              <optgroup label="----------------">
                <option value="info+"<?php echo ($filter === "info+") ? ' selected="selected"' : ''?>>info messages or worse</option>
                <option value="warn+"<?php echo ($filter === "warn+") ? ' selected="selected"' : ''?>>warn messages or worse</option>
                <option value="error+"<?php echo ($filter === "error+") ? ' selected="selected"' : ''?>>error messages or worse</option>
              </optgroup>
            </select>
          </span>
          <span>
            that happened on
            <select id="date-chooser" name="date">
              <?php
              foreach ($logs as $date => $info) {
                $selected = ($date == $load_date) ? ' selected="selected"' : ''?>
                ?>
                <option value="<?php echo $date; ?>"<?php echo $selected; ?>><?php echo $info['date']; ?></option>
                <?php
              }
              ?>
            </select>
            <input type="submit" value="Go" />
          </span>
        </p>
      </form>
    </div>
  </div>

    <?php if ($records_exist): ?>
    <div class="section">
      <table class="table-log log-sortable log">
        <thead>
          <tr>
            <th class="level">Level</th>
            <th class="when">When</th>
            <th class="what" colspan="3">What Caused This</th>
            <th>Page</th>
            <th>Message</th>
          </tr>
        </thead>

        <tbody>
        <?php foreach ($log as $row): ?>
          <tr class="level-<?php echo strtolower($row[0]); ?>">
            <th class="level-<?php echo strtolower($row[0]); ?>" title="By <?php echo $row[5]; ?>"><?php echo ucwords(strtolower($row[0])); ?></th>
            <td class="when" data-fulldate="<?php echo strtotime($row[1]); ?>"><?php echo Date::format($time_format, $row[1]); ?></td>
            <td class="what"><?php echo $row[2]; ?></td>
            <td class="colon">:</td>
            <td class="specifically"><?php echo $row[3]; ?></td>
            <td><a href="<?php echo $row[4]; ?>"><?php echo $row[4]; ?></a></td>
            <td><?php echo Markdown($row[7]); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <ul class="small-screen-log log">
      <?php foreach ($log as $row): ?>
        <li class="level-<?php echo strtolower($row[0]); ?>">
          <strong class="level level-<?php echo strtolower($row[0]); ?>"><?php echo ucwords(strtolower($row[0])); ?></strong>

          <?php echo Markdown($row[7]); ?>

          <h6>
            <?php echo Date::format($time_format, $row[1]); ?> ·
            <?php echo $row[2]; ?>: <?php echo $row[3]; ?> ·
            <a href="<?php echo $row[4]; ?>"><?php echo $row[4]; ?></a>
          </h6>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
      <?php if (trim($filter)): ?>
        <p>This log file exists, but contains no messages with this filter.</p>
      <?php else: ?>
        <p>This log file exists, but there are no messages.</p>
      <?php endif; ?>
    <?php endif; ?>
  <?php else: ?>
    <p>No messages have been logged yet.</p>
  <?php endif; ?>
</div>
</div></div>