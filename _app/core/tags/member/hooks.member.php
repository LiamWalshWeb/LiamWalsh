<?php
class Hooks_member extends Hooks
{
  public function member__login()
  {
    $username = Request::post('username');
    $password = Request::post('password');
    $return   = Request::post('return');

    if (Statamic_Auth::login($username, $password)) {
      Session::setFlash('success', 'Success');
    } else {
      Session::setFlash('error', 'Failure');
    }

    URL::redirect(URL::assemble(Config::getSiteRoot(), $return));
  }

  public function member__logout()
  {
    $return = Request::get('return', Config::getSiteRoot());
    Statamic_Auth::logout();

    URL::redirect(URL::assemble(Config::getSiteRoot(), $return));
  }

}
