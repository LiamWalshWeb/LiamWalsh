<?php
class Plugin_member extends Plugin
{
  public function login()
  {
    $site_root = Config::getSiteRoot();

    $return = $this->fetchParam('return', $site_root);

    /*
    |--------------------------------------------------------------------------
    | Form HTML
    |--------------------------------------------------------------------------
    |
    | The login form writes a hidden field to the form to set the return url.
    | Form attributes are accepted as colon/piped options:
    | Example: attr="class:form|id:contact-form"
    |
    | Note: The content of the tag pair is inserted back into the template
    |
    */

    $attributes_string = '';

    if ($attr = $this->fetchParam('attr', false)) {
      $attributes_array = Helper::explodeOptions($attr, true);
      foreach ($attributes_array as $key => $value) {
        $attributes_string .= " {$key}='{$value}'";
      }
    }

    $html  = "<form method='post' action='" . Path::tidy($site_root . "TRIGGER/member/login") . "' {$attributes_string}>";
    $html .= "<input type='hidden' name='return' value='$return' />";
    $html .= $this->content;
    $html .= "</form>";

    return $html;

  }

  public function logout()
  {
    $return = $this->fetchParam('return', '/');

    return URL::assemble(Config::getSiteRoot(), "TRIGGER", 'member', "logout?return={$return}");
  }

  public function profile()
  {
    $current_user = Statamic_Auth::get_current_user() ? Statamic_Auth::get_current_user()->get_name() : false;

    $member = $this->fetchParam('member', $current_user);
    $profile_data = Statamic_user::get_profile($member);

    if ($profile_data) {
      return Parse::template($this->content, $profile_data);
    }
  }

  public function listing()
  {
    $role = $this->fetchParam('role', false);
    $limit       = $this->fetchParam('limit', null, 'is_numeric'); // defaults to none
    $offset      = $this->fetchParam('offset', 0, 'is_numeric'); // defaults to zero
    $sort_by     = $this->fetchParam('sort_by', 'title'); // defaults to date
    $sort_dir    = $this->fetchParam('sort_dir', 'desc'); // defaults to desc

    $members = Statamic_Auth::get_user_list(false);

    if (is_array($members) && count($members) > 0) {

      $members = array_slice($members, $offset, $limit, true);

    if ($sort_by == 'random') {
      shuffle($list);
    } elseif ($sort_by != 'title' || $sort_by != 'username') {
      # sort by any other field
      usort($members, function($a, $b) use ($sort_by) {
        if (isset($a[$sort_by]) && isset($b[$sort_by])) {
          return strcmp($b[$sort_by], $a[$sort_by]);
        }
      });
    }

    // default sort is asc
    if ($sort_dir == 'desc') {
      $members = array_reverse($members);
    }

      return Parse::tagLoop($this->content, $members);
    } else {
      return array('no_results' => true);
    }

  }
}
