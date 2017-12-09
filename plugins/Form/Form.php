<?php
/**
 * The Machine
 *
 * PHP version 5
 *
 * @category  Plugin
 * @package   Machine
 * @author    Paolo Savoldi <paooolino@gmail.com>
 * @copyright 2017 Paolo Savoldi
 * @license   https://github.com/paooolino/Machine/blob/master/LICENSE 
 *            (Apache License 2.0)
 * @link      https://github.com/paooolino/Machine
 */
namespace Machine\Plugin;

/**
 * Form class
 *
 * A Form manager for the Machine.
 *
 * @category Plugin
 * @package  Machine
 * @author   Paolo Savoldi <paooolino@gmail.com>
 * @license  https://github.com/paooolino/Machine/blob/master/LICENSE 
 *           (Apache License 2.0)
 * @link     https://github.com/paooolino/Machine
 */
class Form
{
  private $machine;
  private $forms;
  
  private $formrow_template = '
    <div class="formRow type{{CLASS_TYPE}}">
      <div class="formLabel">
        {{LABEL}}
      </div>
      <div class="formField">
        {{FIELD}}
      </div>
      <div class="closing"></div>
    </div>
  ';
  private $form_template = '
    <div class="formContainer">
      <form method="post" action="{{FORMACTION}}" enctype="multipart/form-data">
        {{FORMROWS}}
        <button type="submit">{{SUBMITLABEL}}</button>
      </form>
    </div>
  ';
  
  private $field_templates = [
    "text" => '<input id="{{UNIQUE_ID}}" type="text" value="{{VALUE}}" {{ATTRIBUTES}} />',
    "image" => '<input id="{{UNIQUE_ID}}" type="file" data-value="{{VALUE}}" {{ATTRIBUTES}} />',
    "content" => '',
    "email" => '<input id="{{UNIQUE_ID}}" type="email" value="{{VALUE}}" {{ATTRIBUTES}} />',  
    "select" => '<select id="{{UNIQUE_ID}}" value="{{VALUE}}" {{ATTRIBUTES}}>{{OPTS}}</select>',  
    "password" => '<input id="{{UNIQUE_ID}}" type="password" {{ATTRIBUTES}} />',  
    "checkbox" => '<label><input id="{{UNIQUE_ID}}" type="checkbox" {{ATTRIBUTES}} /> {{LABEL}}</label>',     
    "hidden" => '<input type="hidden" value="{{VALUE}}" {{ATTRIBUTES}} />',
    "textarea" => '<textarea id="{{UNIQUE_ID}}" {{ATTRIBUTES}}>{{VALUE}}</textarea>',
    // for radio buttons, value is an attribute!
    "radio" => '<label><input id="{{UNIQUE_ID}}" name="{{NAME}}" type="radio" {{ATTRIBUTES}}" checked="{{CHECKED}}"> {{LABEL}}</label>'
  ];
  
  private $_values;
  
  private $_currentForm;
  
  /**
   * Form plugin constructor.
   *
   * The user should not use it directly, as this is called by the Machine.
   *
   * @param Machine $machine the Machine instance.
   */
  function __construct($machine) 
  {
    $this->machine = $machine;
    $this->_values = [];
    $this->_currentForm = "";
  }
  
  /**
   * Add a form, given a name and some options.
   *
   * An example
   * <code>
	 *	$Form->addForm("myForm", [
   *    "action" => "/register/",
   *    "submitlabel" => "Invia",
   *    "fields" => [
   *      ["email", "text", ["name" => "email"]],
   *      ["password", "password", ["name" => "password"]]
   *    ]
   *  ]);
   * </code>
   *
   * @param string $name
   * @param array  $opts
   *
   * @return void
   */
  public function addForm($name, $opts) 
  {
    $this->forms[$name] = $opts;
  }
  
  public function setFieldTemplate($fieldtype, $newtemplate)
  {
    $this->field_templates[$fieldtype] = $newtemplate;
  }
  
  /**
   *  Set the values for a form.
   */
  public function setValues($formname, $values)
  {
    $this->_values[$formname] = $values;
  }
  
  /**
   * Renders the form, given the name.
   *
   * @param string $params
   *
   * @return string The html code to display the form.
   */
  public function Render($params) 
  {
    $formName = $params[0];
    $this->_currentForm = $formName;
    
    $opts = $this->forms[$formName];
    
    $html_rows = "";
    foreach ($opts["fields"] as $formField) {
      $value = "";
      if (isset($formField[2]) && isset($formField[2]["name"])) {
        $fieldname = $formField[2]["name"];
        if (isset($this->_values[$formName][$fieldname])) {
          $value = $this->_values[$formName][$fieldname];
        }
      }
      $html_rows .= $this->machine->populateTemplate(
        $this->formrow_template, [
          "LABEL" => $this->getFormLabel($formField),
          "FIELD" => $this->getFormField($formField, $value),
          "CLASS_TYPE" => $formField[1]
        ]
      );
    }
    
    $html = $this->machine->populateTemplate(
      $this->form_template, [
        "FORMACTION" => $opts["action"],
        "FORMROWS" => $html_rows,
        "SUBMITLABEL" => isset($opts["submitlabel"]) ? $opts["submitlabel"] : "submit"
      ]
    );
    
    return $html;
  }
  
  private function _getHtmlForOptions($opts) {
    $html = '';
    
    foreach ($opts as $opt) {
      if (gettype($opt) == "string") {
        $html .= '<option>' . $opt . '</option>';
      } else {
        $html .= '<option value="' . $opt[0] . '">' . $opt[1] . '</option>';
      }
    }
    
    return $html;
  }
  
  /**
   *  Get an unique id for the field.
   *  
   *  This is used to assign an id="" attribute to the DOM input element, thus 
   *  able to be referenced by a possible <label for="".
   *  The id should be deterministic (not trandomly generated). It is build
   *  joining the form name and the field name, plus the field "value" attribute
   *  in case of radio buttons.
   */
  private function _getUniqueId($formField)
  {
    $id = $this->_currentForm . $formField[2]["name"];
    $id .= isset($formField[2]["value"]) ? $formField[2]["value"] : "";
    return $id;
  }
  
  private function getFormLabel($formField) 
  {
    $field_type = $formField[1];
    switch ($field_type) {
      case "hidden":
        return "";
        break;
      case "checkbox";
      case "radio":
        return "";
        break;
      case "content":
        return $formField[0];
      default:
        return '<label for="' . $this->_getUniqueId($formField) . '">' . $formField[0] . '</label>';
    } 
  }
  
  private function getFormField($formField, $value) 
  {
    $field_type = $formField[1];
    switch ($field_type) {
      case "text";
      case "image";
      case "email";
      case "hidden";
      case "textarea";
      case "password":
        return $this->machine->populateTemplate(
          $this->field_templates[$field_type],
          [
            "VALUE" => $value,
            "UNIQUE_ID" => $this->_getUniqueId($formField),
            "ATTRIBUTES" => $this->_buildFieldAttributesString($formField[2])
          ]
        );
        break;
      case "content":
        return '';
      case "select":
        return $this->machine->populateTemplate(
          $this->field_templates[$field_type],
          [
            "VALUE" => $value,
            "UNIQUE_ID" => $this->_getUniqueId($formField),
            "ATTRIBUTES" => $this->_buildFieldAttributesString($formField[2]),
            "OPTS" =>  $this->_getHtmlForOptions($formField[2]["options"])
          ]
        );
        break;;
      case "checkbox":
        $arr_attributes = $formField[2];
        if ($value == 1 || $value == true || $value == "true") {
          $arr_attributes["checked"] = "checked";
        }
        return $this->machine->populateTemplate(
          $this->field_templates[$field_type],
          [
            "UNIQUE_ID" => $this->_getUniqueId($formField),
            "LABEL" => $formField[0],
            "ATTRIBUTES" => $this->_buildFieldAttributesString($arr_attributes)
          ]
        );
        break;
      case "radio":
        return $this->machine->populateTemplate(
          $this->field_templates[$field_type],
          [
            "VALUE" => $value,
            "UNIQUE_ID" => $this->_getUniqueId($formField),
            "LABEL" => $formField[0],
            "ATTRIBUTES" => $this->_buildFieldAttributesString($formField[2])
          ]
        );
        break;
    }
  }
  
  private function _buildFieldAttributesString($arr_attributes)
  {
    $allowed_attributes = ["name", "disabled", "checked"];
    $atts = [];
    foreach ($arr_attributes as $k => $v) {
      if (in_array($k, $allowed_attributes)) {
        $atts[] = $k . '="' . htmlentities($v) . '"';
      }
    }
    return implode(" ", $atts);
  }
}
