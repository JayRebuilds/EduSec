<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014 - 2015
 * @package yii2-widgets
 * @subpackage yii2-widget-select2 
 * @version 1.0.1
 */

namespace kartik\select2;

use Yii;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\base\InvalidConfigException;

/**
 * Select2 widget is a Yii2 wrapper for the Select2 jQuery plugin. This
 * input widget is a jQuery based replacement for select boxes. It supports
 * searching, remote data sets, and infinite scrolling of results. The widget
 * is specially styled for Bootstrap 3.
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 * @see http://ivaynberg.github.com/select2/
 */
class Select2 extends \kartik\base\InputWidget
{

    const LARGE = 'lg';
    const MEDIUM = 'md';
    const SMALL = 'sm';

    /**
     * @var string the locale ID (e.g. 'fr', 'de') for the language to be used by the Select2 Widget.
     * If this property not set, then the current application language will be used.
     */
    public $language;
    
    /**
     * @var bool whether to hide the search control and render it as a simple select. Defaults to `false`.
     */
    public $hideSearch = false;

    /**
     * @var array addon to prepend or append to the Select2 widget
     * - prepend: array the prepend addon configuration
     *     - content: string the prepend addon content
     *     - asButton: boolean whether the addon is a button or button group. Defaults to false.
     * - append: array the append addon configuration
     *     - content: string the append addon content
     *     - asButton: boolean whether the addon is a button or button group. Defaults to false.
     * - groupOptions: array HTML options for the input group
     * - contentBefore: string content placed before addon
     * - contentAfter: string content placed after addon
     */
    public $addon = [];

    /**
     * @var string Size of the Select2 input, must be one of the
     * [[LARGE]], [[MEDIUM]] or [[SMALL]]. Defaults to [[MEDIUM]]
     */
    public $size = self::MEDIUM;

    /**
     * @var array $data the option data items. The array keys are option values, and the array values
     * are the corresponding option labels. The array can also be nested (i.e. some array values are arrays too).
     * For each sub-array, an option group will be generated whose label is the key associated with the sub-array.
     * If you have a list of data models, you may convert them into the format described above using
     * [[\yii\helpers\ArrayHelper::map()]].
     */
    public $data;

    /**
     * @var array the HTML attributes for the input tag. The following options are important:
     * - multiple: boolean whether multiple or single item should be selected. Defaults to false.
     * - placeholder: string placeholder for the select item.
     */
    public $options = [];

    /**
     * @var boolean indicator for displaying text inputs
     * instead of select fields
     */
    private $_hidden = false;

    /**
     * Initializes the widget
     *
     * @throw InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->_hidden = !empty($this->pluginOptions['data']) ||
            !empty($this->pluginOptions['query']) ||
            !empty($this->pluginOptions['ajax']) ||
            isset($this->pluginOptions['tags']);
        if (!isset($this->data) && !$this->_hidden) {
            throw new InvalidConfigException("No 'data' source found for Select2. Either the 'data' property must be set OR one of 'data', 'query', 'ajax', or 'tags' must be set within 'pluginOptions'.");
        }
        if ($this->hideSearch) {
            $css = ArrayHelper::getValue($this->pluginOptions, 'dropdownCssClass', '');
            $css .= ' kv-hide';
            $this->pluginOptions['dropdownCssClass'] = $css;
        }
        if (!empty($this->options['placeholder']) && !$this->_hidden &&
            (empty($this->options['multiple']) || $this->options['multiple'] == false)
        ) {
            $this->data = ["" => ""] + $this->data;
        }
        Html::addCssClass($this->options, 'form-control');
        Html::addCssStyle($this->options, 'width:100%', false);
        $this->registerAssets();
        $this->renderInput();
    }

    /**
     * Embeds the input group addon
     *
     * @param string $input
     *
     * @return string
     */
    protected function embedAddon($input)
    {
        if (empty($this->addon)) {
            return $input;
        }
        $prepend = ArrayHelper::getValue($this->addon, 'prepend', '');
        $append = ArrayHelper::getValue($this->addon, 'append', '');
        $group = ArrayHelper::getValue($this->addon, 'groupOptions', []);
        $size = isset($this->size) ? ' input-group-' . $this->size : '';
        if ($this->pluginLoading) {
            Html::addCssClass($group, 'kv-hide group-' . $this->options['id']);
        }
        if (is_array($prepend)) {
            $content = ArrayHelper::getValue($prepend, 'content', '');
            if (isset($prepend['asButton']) && $prepend['asButton'] == true) {
                $prepend = Html::tag('div', $content, ['class' => 'input-group-btn']);
            } else {
                $prepend = Html::tag('span', $content, ['class' => 'input-group-addon']);
            }
            Html::addCssClass($group, 'input-group' . $size . ' select2-bootstrap-prepend');
        }
        if (is_array($append)) {
            $content = ArrayHelper::getValue($append, 'content', '');
            if (isset($append['asButton']) && $append['asButton'] == true) {
                $append = Html::tag('div', $content, ['class' => 'input-group-btn']);
            } else {
                $append = Html::tag('span', $content, ['class' => 'input-group-addon']);
            }
            Html::addCssClass($group, 'input-group' . $size . ' select2-bootstrap-append');
        }
        $addonText = $prepend . $input . $append;
        $contentBefore = ArrayHelper::getValue($this->addon, 'contentBefore', '');
        $contentAfter = ArrayHelper::getValue($this->addon, 'contentAfter', '');
        return Html::tag('div', $contentBefore . $addonText . $contentAfter, $group);
    }

    /**
     * Renders the source Input for the Select2 plugin.
     * Graceful fallback to a normal HTML select dropdown
     * or text input - in case JQuery is not supported by
     * the browser
     */
    protected function renderInput()
    {
        $class = $this->pluginLoading ? 'kv-hide ' : '';
        if (empty($this->addon) && isset($this->size)) {
            $class .= 'input-' . $this->size;
        }
        if ($this->pluginLoading) {
            $this->_loadIndicator = '<div class="kv-plugin-loading loading-' . $this->options['id'] . '">&nbsp;</div>';
        }
        Html::addCssClass($this->options, $class);
        if ($this->_hidden) {
            $input = $this->getInput('textInput');
        } else {
            $input = $this->getInput('dropDownList', true);
        }
        echo $this->_loadIndicator . $this->embedAddon($input);
    }

    /**
     * Registers the asset bundle and locale
     */
    public function registerAssetBundle() {
        $view = $this->getView();
        Select2Asset::register($view)->addLanguage($this->language, 'select2_locale_', '/');
    }
    
    /**
     * Registers the needed assets
     */
    public function registerAssets()
    {
        $id = $this->options['id'];
        $this->registerAssetBundle();
        // set default width
        $this->pluginOptions['width'] = 'resolve';
        // validate bootstrap has-success & has-error states
        $this->pluginEvents += ['select2-open' => "function(){initSelect2DropStyle('{$id}')}"];
        
        // register plugin
        if ($this->pluginLoading) {
            $this->registerPlugin('select2', "jQuery('#{$id}')", "initSelect2Loading('{$id}')");
        } else {
            $this->registerPlugin('select2');
        }
        
    }
}