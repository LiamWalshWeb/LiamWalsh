<?php

class Plugin_protect extends Plugin
{
    public function password_form()
    {
        // fetch parameters
        $return  = $this->fetch('return', filter_input(INPUT_GET, 'return', FILTER_SANITIZE_STRING), null, false, false);
        $attr    = $this->fetchParam('attr', false);
        
        // set up data to be parsed into content
        $data = array(
            'error' => $this->flash->get('error', ''),
            'field_name' => 'password'
        );

        // determine form attributes
        $attr_string = '';
        if ($attr) {
            $attributes_array = Helper::explodeOptions($attr, true);

            foreach ($attributes_array as $key => $value) {
                $attr_string .= ' ' . $key . '="' . $value . '"';
            }
        }
        
        // build the form
        $html  = '<form action="' . Path::tidy(Config::getSiteRoot() . '/TRIGGER/protect/password') . '" method="post"' . $attr_string . '>';
        $html .= '<input type="hidden" name="return" value="' . $return . '">';
        $html .= '<input type="hidden" name="token" value="' . $this->tokens->create() . '">';
        $html .= Parse::template($this->content, $data);
        $html .= '</form>';
        
        // return the HTML
        return $html;
    }
    
    
    public function messages()
    {
        return Parse::template($this->content, array(
            'error' => $this->flash->get('error', null),
            'success' => $this->flash->get('success', null)
        ));
    }
}