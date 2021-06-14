<?php

if (function_exists('ui_get_full_url') === false) {


    /**
     * Get public url.
     *
     * @param string $url Relative url.
     *
     * @return string Dependant url.
     */
    function ui_get_full_url(string $url='')
    {
        global $config;
        if (is_array($config) === true) {
            if (empty($config['homeurl']) === false) {
                return $config['homeurl'].'/'.$url;
            }

            if (empty($config['baseurl']) === false) {
                return $config['baseurl'].'/'.$url;
            }
        }

        return $url;
    }


}

if (function_exists('__') === false) {


    /**
     * Override for translation function if not available.
     *
     * @param string|null $str String to be translated.
     *
     * @return string
     */
    function __(?string $str)
    {
        if ($str !== null) {
            $ret = $str;
            try {
                $args = func_get_args();
                array_shift($args);
                $ret = vsprintf($str, $args);
            } catch (Exception $e) {
                return $str;
            }

            return $ret;
        }

        return '';
    }


}

if (function_exists('html_print_image') === false) {


    /**
     * Prints an image HTML element.
     *
     * @param string  $src            Image source filename.
     * @param boolean $return         Whether to return or print.
     * @param array   $options        Array with optional HTML options to set.
     *          At this moment, the following options are supported:
     *          align, border, hspace, ismap, vspace, style, title, height,
     *          longdesc, usemap, width, id, class, lang, xml:lang, onclick,
     *          ondblclick, onmousedown, onmouseup, onmouseover, onmousemove,
     *          onmouseout, onkeypress, onkeydown, onkeyup, pos_tree, alt.
     * @param boolean $return_src     Whether to return src field of image
     *          ('images/*.*') or complete html img tag ('<img src="..." alt="...">').
     * @param boolean $relative       Whether to use relative path to image or not
     *          (i.e. $relative= true : /pandora/<img_src>).
     * @param boolean $no_in_meta     Do not show on metaconsole folder at first. Go
     *          directly to the node.
     * @param boolean $isExternalLink Do not shearch for images in Pandora.
     *
     * @return string HTML code if return parameter is true.
     */
    function html_print_image(
        $src,
        $return=false,
        $options=false,
        $return_src=false,
        $relative=false,
        $no_in_meta=false,
        $isExternalLink=false
    ) {
        $attr = '';
        if (is_array($options) === true) {
            foreach ($options as $k => $v) {
                $attr = $k.'="'.$v.'" ';
            }
        }

        $output = '<img src="'.ui_get_full_url($src).'" '.$attr.'/>';
        if ($return === false) {
            echo $output;
        }

        return $output;
    }


}


if (function_exists('html_print_submit_button') === false) {


    /**
     * Render an submit input button element.
     *
     * The element will have an id like: "submit-$name"
     *
     * @param string  $label      Input label.
     * @param string  $name       Input name.
     * @param boolean $disabled   Whether to disable by default or not. Enabled by default.
     * @param array   $attributes Additional HTML attributes.
     * @param boolean $return     Whether to return an output string or echo now (optional, echo by default).
     *
     * @return string HTML code if return parameter is true.
     */
    function html_print_submit_button(
        $label='OK',
        $name='',
        $disabled=false,
        $attributes='',
        $return=false
    ) {
        if (!$name) {
            $name = 'unnamed';
        }

        if (is_array($attributes)) {
            $attr_array = $attributes;
            $attributes = '';
            foreach ($attr_array as $attribute => $value) {
                $attributes .= $attribute.'="'.$value.'" ';
            }
        }

        $output = '<input type="submit" id="submit-'.$name.'" name="'.$name.'" value="'.$label.'" '.$attributes;
        if ($disabled) {
            $output .= ' disabled="disabled"';
        }

        $output .= ' />';
        if (!$return) {
            echo $output;
        }

        return $output;
    }


}

if (function_exists('get_product_name') === false) {


    /**
     * Returns product name.
     *
     * @return string PRoduct name.
     */
    function get_product_name()
    {
        return 'UMC';
    }


}

// End.
