<?php

/**
 * Interfaz tope gama que obliga a implementar metodos.
 */
class Wizard
{


    /**
     * To be overwritten.
     *
     * @return void
     */
    public function run()
    {
    }


    /**
     * To be overwritten.
     *
     * @return void
     */
    public function load()
    {
    }


    /**
     * Print input using functions html lib.
     *
     * @param array $data Input definition.
     *
     * @return string HTML code for desired input.
     */
    public function printInput(array $data)
    {
        if (is_array($data) === false) {
            return '';
        }

        switch ($data['type']) {
            case 'text':
            return html_print_input_text(
                $data['name'],
                $data['value'],
                $data['alt'] = '',
                $data['size'] = 50,
                $data['maxlength'] = 255,
                ((isset($data['return']) === true) ? $data['return'] : true),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['required']) === true) ? $data['required'] : false),
                ((isset($data['function']) === true) ? $data['function'] : ''),
                ((isset($data['class']) === true) ? $data['class'] : ''),
                ((isset($data['onChange']) === true) ? $data['onChange'] : ''),
                ((isset($data['autocomplete']) === true) ? $data['autocomplete'] : '')
            );

            case 'image':
            return html_print_input_image(
                $data['name'],
                $data['src'],
                $data['value'],
                ((isset($data['style']) === true) ? $data['style'] : ''),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['options']) === true) ? $data['options'] : false)
            );

            case 'text_extended':
            return html_print_input_text_extended(
                $data['name'],
                $data['value'],
                $data['id'],
                $data['alt'],
                $data['size'],
                $data['maxlength'],
                $data['disabled'],
                $data['script'],
                $data['attributes'],
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['password']) === true) ? $data['password'] : false),
                ((isset($data['function']) === true) ? $data['function'] : '')
            );

            case 'password':
            return html_print_input_password(
                $data['name'],
                $data['value'],
                ((isset($data['alt']) === true) ? $data['alt'] : ''),
                ((isset($data['size']) === true) ? $data['size'] : 50),
                ((isset($data['maxlength']) === true) ? $data['maxlength'] : 255),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['required']) === true) ? $data['required'] : false),
                ((isset($data['class']) === true) ? $data['class'] : '')
            );

            case 'text':
            return html_print_input_text(
                $data['name'],
                $data['value'],
                ((isset($data['alt']) === true) ? $data['alt'] : ''),
                ((isset($data['size']) === true) ? $data['size'] : 50),
                ((isset($data['maxlength']) === true) ? $data['maxlength'] : 255),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['required']) === true) ? $data['required'] : false),
                ((isset($data['function']) === true) ? $data['function'] : ''),
                ((isset($data['class']) === true) ? $data['class'] : ''),
                ((isset($data['onChange']) === true) ? $data['onChange'] : ''),
                ((isset($data['autocomplete']) === true) ? $data['autocomplete'] : '')
            );

            case 'image':
            return html_print_input_image(
                $data['name'],
                $data['src'],
                $data['value'],
                ((isset($data['style']) === true) ? $data['style'] : ''),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['options']) === true) ? $data['options'] : false)
            );

            case 'hidden':
            return html_print_input_hidden(
                $data['name'],
                $data['value'],
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['class']) === true) ? $data['class'] : false)
            );

            case 'hidden_extended':
            return html_print_input_hidden_extended(
                $data['name'],
                $data['value'],
                $data['id'],
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['class']) === true) ? $data['class'] : false)
            );

            case 'color':
            return html_print_input_color(
                $data['name'],
                $data['value'],
                ((isset($data['class']) === true) ? $data['class'] : false),
                ((isset($data['return']) === true) ? $data['return'] : false)
            );

            case 'file':
            return html_print_input_file(
                $data['name'],
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['options']) === true) ? $data['options'] : false)
            );

            default:
                // Ignore.
            break;
        }

        return '';
    }


    /**
     * Print a form.
     *
     * @param array $data Definition of target form to be printed.
     *
     * @return void
     */
    public function printForm(array $data)
    {
        $form = $data['form'];
        $inputs = $data['inputs'];
        $js = $data['js'];

        $output = '<form action="'.$form['action'].'" method="'.$form['method'];
        $output .= '" '.$form['extra'].'>';

        $ouput .= '<ul>';

        foreach ($inputs as $input) {
            $output .= '<li><label>'.$input['label'].'</label>';
            $output .= '<label>'.$this->printInput($input['var']).'</li>';
        }

        $output .= '</ul>';
        $output .= '</form>';
        $output .= $js;

        return $output;

    }


}
