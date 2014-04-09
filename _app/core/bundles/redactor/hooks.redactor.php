<?php

use Intervention\Image\Image;

class Hooks_redactor extends Hooks
{

    public function control_panel__add_to_head()
    {
        if (URL::getCurrent(false) == '/publish') {
            return $this->css->link(array('redactor.css', 'override.css'));
        }
    }

    public function control_panel__add_to_foot()
    {
        if (URL::getCurrent(false) == '/publish') {

            $html = $this->js->link(array('fullscreen.js', 'redactor.min.js'));

            $options = $this->getConfig();

            # Load image browser folder
            if (class_exists('Fieldtype_redactor') && method_exists('Fieldtype_redactor', 'get_field_settings')) {

                $field_settings = Fieldtype_redactor::get_field_settings();
                $field_config = array_get($field_settings, 'field_config', $field_settings);

                if (array_get($field_config, 'image_dir', false)) {
                    $image_path = Path::tidy($field_config['image_dir'].'/');

                    $resize_options = array_get($field_config, 'resize', array());

                    if (count($resize_options)) {
                        $resize_options['resize'] = true;
                    }

                    $resize_options_string = http_build_query($resize_options);

                    $options['imageGetJson'] = Config::getSiteRoot()."TRIGGER/redactor/fetch_images?path={$image_path}";
                    $options['imageUpload'] = Config::getSiteRoot()."TRIGGER/redactor/upload?path={$image_path}&{$resize_options_string}";
                }

                if (array_get($field_config, 'file_dir', false)) {
                    $file_path = Path::tidy($field_config['file_dir'].'/');

                    $options['fileUpload'] = Config::getSiteRoot()."TRIGGER/redactor/upload?path={$file_path}";
                }

                if (isset($field_config['image_dir_append_slug'])) {
                    $options['uploadFields'] = array(
                        'subfolder' => '#publish-slug'
                    );
                }
            }


            $redactor_options = json_encode($options, true);

            $html .= "<script>

                var redactor_options = $redactor_options;

                $(document).ready(
                  function() {
                    $.extend(redactor_options, {'imageUploadErrorCallback': callback});
                    $('.redactor-container textarea').redactor(redactor_options);
                  }

                );


                $('body').on('addRow', '.grid', function() {
                  $.extend(redactor_options, {'imageUploadErrorCallback': callback});
                  $('.redactor-container textarea').redactor(redactor_options);
                });

                function callback(obj, json) {
                  alert(json.error);
                }
              </script>";

            return $html;
        }
    }


    public function redactor__upload()
    {
        if (!Auth::getCurrentMember()) {
            exit("Invalid Request");
        }

        $path = Request::get('path');

        if (isset($path)) {

            $dir = Path::tidy(ltrim($path, '/').'/');

            if (isset($_POST['subfolder'])) {
                $dir .= $_POST['subfolder'] . '/';
            }

            Folder::make($dir);

            $_FILES['file']['type'] = strtolower($_FILES['file']['type']);

            if ($_FILES['file']['type'] == 'image/png'
            || $_FILES['file']['type'] == 'image/jpg'
            || $_FILES['file']['type'] == 'image/gif'
            || $_FILES['file']['type'] == 'image/jpeg') {

                $file_info = pathinfo($_FILES['file']['name']);

                // pull out the filename bits
                $filename = $file_info['filename'];
                $ext = $file_info['extension'];

                // build filename
                $file = $dir.$filename.'.'.$ext;

                // check for dupes
                if (File::exists($file)) {
                    $file = BASE_PATH . '/' . $dir.$filename.'-'.date('YmdHis').'.'.$ext;
                }

                if ( ! Folder::isWritable($dir)) {
                    Log::error('Upload failed. Directory "' . $dir . '" is not writable.', 'redactor');
                    echo json_encode(array('error' => "Redactor: Upload directory not writable."));
                    die();
                }

                if (Request::get('resize', false)) {

                    $image = Image::make($_FILES['file']['tmp_name']);

                    $width   = Request::get('width', null);
                    $height  = Request::get('height', null);
                    $ratio   = Request::get('ratio', true);
                    $upsize  = Request::get('upsize', false);
                    $quality = Request::get('quality', '75');

                    $image->resize($width, $height, $ratio, $upsize)
                          ->save($file, $quality);

                } else {
                    copy($_FILES['file']['tmp_name'], $file);
                }

                $return = array('filelink' => Path::toAsset($file));

                echo stripslashes(json_encode($return));
            } else {
                echo json_encode(array('error' => "Redactor: Could not find directory: '$dir'"));
            }

        } else {
            echo json_encode(array('error' => "Redactor: Upload directory not set."));
        }

    }

    public function redactor__fetch_images()
    {
        if (!Auth::getCurrentMember()) {
            exit("Invalid Request");
        }

        $dir = Path::tidy(ltrim(Request::get('path'), '/').'/');
        $image_list = glob($dir."*.{jpg,jpeg,gif,png}", GLOB_BRACE);

        $images = array();
        if (count($image_list) > 0) {
            foreach ($image_list as $image) {
                $images[] = array(
                    'thumb' => Config::getSiteRoot().$image,
                    'image' => Config::getSiteRoot().$image
                );
            }
        }


        echo json_encode($images);
    }
}
