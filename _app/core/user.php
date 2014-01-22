<?php
class statamic_user
{
    protected $data = array();
    protected $name = null;

    public function Statamic_User($data)
    {
        $this->data = $data;
    }

    public function set_name($name)
    {
        $this->name = $name;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function set_first_name($name)
    {
        $this->data['first_name'] = $name;
    }

    public function set_email($email)
    {
        $this->data['email'] = $email;
    }

    public function get_first_name()
    {
        return array_get($this->data, 'first_name', '');
    }

    public function get_full_name()
    {
        $names = array(
            'first_name' => $this->get_first_name(),
            'last_name'  => $this->get_last_name()
        );

        $full_name = trim(implode($names, ' '));

        // No name. Fall back to username.
        if ($full_name === "") {
            $full_name = $this->get_name();
        }

        return $full_name;
    }

    public function get_email()
    {
        return array_get($this->data, 'email', '');
    }

    public function get_gravatar($size = "26")
    {
        $gravatar = new \emberlabs\GravatarLib\Gravatar();
        $gravatar->setAvatarSize($size);
        $gravatar->setDefaultImage('http://f.cl.ly/items/3M1e2a0x423F2w312J1M/buck.jpg');

        return $gravatar->buildGravatarURL($this->get_email());
    }

    public function set_password($password, $hashed = false)
    {
        if ($hashed) {
            $this->data['password_hash']  = Password::hash($password);
            $this->data['password']       = '';
            
            if (isset($this->data['encrypted_password'])) {
                $this->data['encrypted_password'] = "";
            }
            
            if (isset($this->data['salt'])) {
                $this->data['salt'] = "";
            }
        } else {
            $this->data['password']       = $password;
            $this->data['password_hash']  = '';
        }
    }

    public function get_last_name()
    {
        if (isset($this->data['last_name'])) {
            return $this->data['last_name'];
        }

        return '';
    }

    public function get_password()
    {
        if (isset($this->data['password'])) {
            return $this->data['password'];
        }
    }

    public function set_last_name($name)
    {
        $this->data['last_name'] = $name;
    }

    public function get_biography()
    {
        if (isset($this->data['biography'])) {
            return $this->data['biography'];
        }

        return '';
    }

    public function set_biography_raw($biography)
    {
        $this->data['biography_raw'] = $biography;
        $this->data['biography']     = Content::transform($biography);
    }

    public function get_biography_raw()
    {
        if (isset($this->data['biography_raw'])) {
            return $this->data['biography_raw'];
        }

        return '';
    }

    public function set_roles($string)
    {
        $this->data['roles'] = explode(",", $string);
    }

    public function get_roles_list($delim = ', ')
    {
        if (isset($this->data['roles'])) {
            return implode($delim, $this->data['roles']);
        }

        return '';
    }

    public function correct_password($password)
    {
        if (isset($this->data['password']) && $this->data['password'] <> '') {
            return ($this->data['password'] == $password);
            
        } elseif (isset($this->data['password_hash']) && $this->data['password_hash'] <> '') {
            // check for new password
            return Password::check($password, $this->data['password_hash']);
            
        } elseif (isset($this->data['encrypted_password']) && $this->data['encrypted_password'] <> '') {
            // check for old password
            return $this->matches_old_password($password);
            
        }

        return false;
    }
    
    public function get_hashed_password()
    {
        if (isset($this->data['password']) && $this->data['password']) {
            return $this->data['password'];
        } elseif (isset($this->data['password_hash']) && $this->data['password_hash']) {
            return $this->data['password_hash'];
        }
        
        return '';
    }
    
    public function is_password_hashed()
    {
        return (isset($this->data['password_hash']) && $this->data['password_hash'] != "");
    }

    public function has_role($role)
    {
        if (isset($this->data['roles'])) {
            $roles = $this->data['roles'];

            if (in_array($role, $roles)) {
                return true;
            }
        }

        return false;
    }

    public function rename($name)
    {
        if (!statamic_user::is_valid_name($name)) {
            throw new Exception('cannot be "' . $name . '" because that is not an allowed name.');
        }
        
        $file     = BASE_PATH . "/_config/users/{$this->name}.yaml";
        $new_file = BASE_PATH . "/_config/users/{$name}.yaml";
        
        // check to see if file already exists
        if (File::exists($new_file)) {
            throw new Exception('cannot be renamed to "' . $name . '" because that name is already in use by another account.');
        }
        
        rename($file, $new_file);
    }

    public function save()
    {
        $user_account = (array)$this->data;
        $content      = $user_account['biography_raw'];

        unset($user_account['biography']);
        unset($user_account['biography_raw']);

        $file_path = "_config/users/{$this->name}.yaml";

        File::put($file_path, File::buildContent($user_account, $content));
    }

    public function delete()
    {
        $file = "_config/users/{$this->name}.yaml";
        unlink($file);
    }

    // STATIC FUNCTIONS
    // ------------------------------------------------------
    public static function load($username)
    {
        $meta_raw = "";
        if (File::exists("_config/users/{$username}.yaml")) {
            $meta_raw = file_get_contents("_config/users/{$username}.yaml");
        } else {
            return null;
        }

        if (Pattern::endsWith($meta_raw, "---")) {
            $meta_raw .= "\n"; # prevent parse failure
        }
        # Parse YAML Front Matter
        if (stripos($meta_raw, "---") === false) {
            $meta            = YAML::Parse($meta_raw);
            $meta['content'] = "";
        } else {

            list($yaml, $content) = preg_split("/---/", $meta_raw, 2, PREG_SPLIT_NO_EMPTY);
            $meta = YAML::Parse($yaml);
            $meta['username'] = $username;
            $meta['biography_raw'] = trim($content);
            $meta['biography'] = Content::transform($content);

            $u = new Statamic_User($meta);
            $u->set_name($username);

            return $u;
        }
    }

    public static function get_profile($username) {
        if (File::exists("_config/users/{$username}.yaml")) {
            $protected_fields = array_fill_keys(
                array('password', 'encrypted_password', 'salt', 'password_hash'),
                NULL);

            $profile_content = file_get_contents("_config/users/{$username}.yaml");
            $profile_data = Statamic::yamlize_content($profile_content, 'biography');
            $profile_data['username'] = $username;

            return array_diff_key($profile_data, $protected_fields);

        }

        return null;
    }
    
    public static function is_valid_name($name)
    {
        return preg_match('/^[a-z0-9\-_\.]+$/i', $name);
    }
    
    
    
    // convert old password hashes to new ones
    // ------------------------------------------------------------------------
    
    private function matches_old_password($password)
    {
        if (
            isset($this->data['encrypted_password']) &&
            $this->data['encrypted_password']
        ) {
            // old password exists, check against old system
            $salt = '';
            if (isset($this->data['salt'])) {
                $salt = $this->data['salt'];
            }
            
            // return true/false on if it matches
            return (sha1($password.$salt) == $this->data['encrypted_password']);
        }
        
        return false;
    }

}
