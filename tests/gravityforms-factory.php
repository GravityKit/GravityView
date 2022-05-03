<?php

class GF_UnitTest_Factory
{
    /**
     * @var GF_UnitTest_Factory_For_Form
     */
    public $form;

    /**
     * @var GF_UnitTest_Factory_For_Entry
     */
    public $entry;

    public $form_filename;

    public function __construct($form_filename = '')
    {
        $this->form = new GF_UnitTest_Factory_For_Form($this);
        $this->entry = new GF_UnitTest_Factory_For_Entry($this);
    }
}

class GF_UnitTest_Factory_For_Form extends GF_UnitTest_Factory_For_Thing
{
    public function __construct($factory = null)
    {
        parent::__construct($factory);

        $filename = $this->factory->form_filename;
        if (empty($filename)) {
            $filename = 'standard.json';
        }

        $form_json = file_get_contents(dirname(__FILE__).'/data/forms/'.$filename);
        $forms = json_decode($form_json, true);
        $form = $forms[0];

        $this->default_generation_definitions = $form;
    }

    public function load_form($filename)
    {
    }

    public function create_object($form)
    {
        return GFAPI::add_form($form);
    }

    public function update_object($form_id, $form)
    {
        $form['id'] = $form_id;

        return GFAPI::update_form($form);
    }

    public function get_object_by_id($form_id)
    {
        GFFormsModel::flush_current_forms();

        return GFAPI::get_form($form_id);
    }

    public function get_form_by_id($form_id)
    {
        return $this->get_object_by_id($form_id);
    }
}

class GF_UnitTest_Factory_For_Entry extends GF_UnitTest_Factory_For_Thing
{
    public function __construct($factory = null)
    {
        parent::__construct($factory);
        $this->default_generation_definitions = [];
    }

    public function generate_random_entry_object($form)
    {
        $fields = $form['fields'];
        foreach ($fields as $field) {
            /* @var GF_Field $field */
            $type = GFFormsModel::get_input_type($field);
            if (in_array($type, ['html', 'page', 'section'])) {
                continue;
            }
            $inputs = $field->get_entry_inputs();
            if (is_array($inputs)) {
                foreach ($inputs as $index => $input) {
                    $entry[(string) $input['id']] = isset($field['choices']) && is_array($field['choices']) ? $field['choices'][$index]['value'] : $this->_get_random_value($field);
                }
            } else {
                $entry[(string) $field['id']] = $this->_get_random_value($field);
            }
        }

        return $entry;
    }

    public function create_many_random($count, $form_id)
    {
        $form = $this->factory->form->get_form_by_id($form_id);

        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $entry = $this->generate_random_entry_object($form);
            $entry['form_id'] = $form_id;
            $results[] = $this->create($entry);
        }

        return $results;
    }

    public function create_object($entry)
    {
        return GFAPI::add_entry($entry);
    }

    public function update_object($entry_id, $entry)
    {
        $entry['id'] = $entry_id;

        return GFAPI::update_entry($entry);
    }

    public function get_object_by_id($entry_id)
    {
        return GFAPI::get_entry($entry_id);
    }

    public function get_entry_by_id($entry_id)
    {
        return $this->get_object_by_id($entry_id);
    }

    private function _get_random_value($field)
    {
        $type = GFFormsModel::get_input_type($field);
        switch ($type) {
            case 'number':
                $value = rand(0, 10);
                break;
            case 'date':
                $value = date('Y-m-d', strtotime('+'.mt_rand(0, 30).' days'));
                break;
            case 'time':
                $ampm = ['am', 'pm'];
                $value = sprintf('%02d:%02d %s', rand(1, 12), rand(1, 60), $ampm[array_rand($ampm)]);
                break;
            case 'list':
                $value = serialize(['testvalue'.uniqid(), 'testvalue'.uniqid(), 'testvalue'.uniqid()]);
                break;
            case 'website':
                $value = 'http://website'.uniqid().'.com';
                break;
            case 'phone':
                $value = sprintf('(%03d)%03d-%04d', rand(1, 999), rand(1, 999), rand(1, 9999));
                break;
            default:
                $value = 'testvalue'.uniqid();
        }

        return $value;
    }
}

abstract class GF_UnitTest_Factory_For_Thing
{
    public $default_generation_definitions;
    /**
     * @var GF_UnitTest_Factory
     */
    public $factory;

    /**
     * Creates a new factory, which will create objects of a specific Thing.
     *
     * @param object $factory                        Global factory that can be used to create other objects on the system
     * @param array  $default_generation_definitions Defines what default values should the properties of the object have. The default values
     *                                               can be generators -- an object with next() method. There are some default generators: {@link WP_UnitTest_Generator_Sequence},
     *                                               {@link WP_UnitTest_Generator_Locale_Name}, {@link WP_UnitTest_Factory_Callback_After_Create}.
     */
    public function __construct($factory, $default_generation_definitions = [])
    {
        $this->factory = $factory;
        $this->default_generation_definitions = $default_generation_definitions;
    }

    abstract public function create_object($args);

    abstract public function update_object($object, $fields);

    public function create($args = [], $generation_definitions = null)
    {
        if (is_null($generation_definitions)) {
            $generation_definitions = $this->default_generation_definitions;
        }

        $generated_args = $this->generate_args($args, $generation_definitions, $callbacks);
        $created = $this->create_object($generated_args);
        if (!$created || is_wp_error($created)) {
            return $created;
        }

        if ($callbacks) {
            $updated_fields = $this->apply_callbacks($callbacks, $created);
            $save_result = $this->update_object($created, $updated_fields);
            if (!$save_result || is_wp_error($save_result)) {
                return $save_result;
            }
        }

        return $created;
    }

    public function create_and_get($args = [], $generation_definitions = null)
    {
        $object_id = $this->create($args, $generation_definitions);

        return $this->get_object_by_id($object_id);
    }

    abstract public function get_object_by_id($object_id);

    public function create_many($count, $args = [], $generation_definitions = null)
    {
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $results[] = $this->create($args, $generation_definitions);
        }

        return $results;
    }

    public function generate_args($args = [], $generation_definitions = null, &$callbacks = null)
    {
        $callbacks = [];
        if (is_null($generation_definitions)) {
            $generation_definitions = $this->default_generation_definitions;
        }

        foreach (array_keys($generation_definitions) as $field_name) {
            if (!isset($args[$field_name])) {
                $generator = $generation_definitions[$field_name];
                if (is_scalar($generator)) {
                    $args[$field_name] = $generator;
                } elseif (is_object($generator) && method_exists($generator, 'call')) {
                    $callbacks[$field_name] = $generator;
                } elseif (is_object($generator)) {
                    $args[$field_name] = $generator->next();
                } else {
                    $args[$field_name] = $generator;
                }
            }
        }

        return $args;
    }

    public function apply_callbacks($callbacks, $created)
    {
        $updated_fields = [];
        foreach ($callbacks as $field_name => $generator) {
            $updated_fields[$field_name] = $generator->call($created);
        }

        return $updated_fields;
    }

    public function callback($function)
    {
        return new WP_UnitTest_Factory_Callback_After_Create($function);
    }

    public function addslashes_deep($value)
    {
        if (is_array($value)) {
            $value = array_map([$this, 'addslashes_deep'], $value);
        } elseif (is_object($value)) {
            $vars = get_object_vars($value);
            foreach ($vars as $key => $data) {
                $value->{$key} = $this->addslashes_deep($data);
            }
        } elseif (is_string($value)) {
            $value = addslashes($value);
        }

        return $value;
    }
}

class GF_UnitTest_Generator_Sequence
{
    public $next;
    public $template_string;

    public function __construct($template_string = '%s', $start = 1)
    {
        $this->next = $start;
        $this->template_string = $template_string;
    }

    public function next()
    {
        $generated = sprintf($this->template_string, $this->next);
        $this->next++;

        return $generated;
    }
}

class GF_UnitTest_Factory_Callback_After_Create
{
    public $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    public function call($object)
    {
        return call_user_func($this->callback, $object);
    }
}
