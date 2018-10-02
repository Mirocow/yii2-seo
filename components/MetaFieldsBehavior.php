<?php

namespace mirocow\seo\components;

use mirocow\seo\models\Meta;
use mirocow\seo\Module;
use Yii;
use yii\base\Behavior;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\validators\Validator;

/**
 * Behavior to work with SEO meta options
 *
 * @package nevmerzhitsky\seomodule
 * @property ActiveRecord $owner
 */
class MetaFieldsBehavior extends Behavior
{
    public static $_controllersActions = [];

    public $stopNames = [];

    public $metaField;

    public $userCanEdit = false;

    public $controllerClassName;

    public $maxTitleLength = 200;

    public $maxDescLength = 200;

    public $maxKeysLength = 200;

    /**
     * @return array
     */
    public function events () {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $fields = Module::getMetaFields();

        return [
          //[$fields, 'string', 'max' => 200],
          [$fields, 'safe'],
        ];
    }

    /**
     * @return mixed
     */
    public function getSeoUrl()
    {
        if($this->isProduceFunc(Meta::KEY_URL)) {
            return $this->owner->getSeoUrl();
        }
    }

    /**
     * @return mixed
     */
    public function setSeoUrl($value)
    {
        if($this->isProduceFunc(Meta::KEY_URL)) {
            return $this->owner->{Meta::KEY_URL} = $value;
        }
    }

    /**
     * @param null $lang
     * @return mixed
     */
    public function getSeoTitle($lang = null)
    {
        return $this->setSeoField(Meta::KEY_TITLE, $lang);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setSeoTitle($value)
    {
        if($this->isProduceFunc(Meta::KEY_TITLE)) {
            return $this->owner->{Meta::KEY_TITLE} = $value;
        }
    }

    /**
     * @param null $lang
     * @return mixed
     */
    public function getSeoDescription($lang = null)
    {
        return $this->setSeoField(Meta::KEY_DESCRIPTION, $lang);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setSeoDescription($value)
    {
        if($this->isProduceFunc(Meta::KEY_DESCRIPTION)) {
            return $this->owner->{Meta::KEY_DESCRIPTION} = $value;
        }
    }

    /**
     * @param null $lang
     * @return mixed
     */
    public function getSeoKeywords($lang = null)
    {
        return $this->setSeoField(Meta::KEY_KEYWORDS, $lang);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setSeoKeywords($value)
    {
        if($this->isProduceFunc(Meta::KEY_KEYWORDS)) {
            return $this->owner->{Meta::KEY_KEYWORDS} = $value;
        }
    }

    /**
     * @param null $lang
     * @return bool|mixed
     */
    public function getSeoH1($lang = null)
    {
        return $this->setSeoField(Meta::KEY_H1, $lang);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setSeoH1($value)
    {
        if($this->isProduceFunc(Meta::KEY_H1)) {
            return $this->owner->{Meta::KEY_H1} = $value;
        }
    }

    /**
     * @param null $lang
     * @return bool|mixed
     */
    public function getSeoH2($lang = null)
    {
        return $this->setSeoField(Meta::KEY_H2, $lang);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setSeoH2($value)
    {
        if($this->isProduceFunc(Meta::KEY_H2)) {
            return $this->owner->{Meta::KEY_H2} = $value;
        }
    }

    /**
     * @param null $lang
     * @return bool|mixed
     */
    public function getSeoH3($lang = null)
    {
        return $this->setSeoField(Meta::KEY_H3, $lang);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setSeoH3($value)
    {
        if($this->isProduceFunc(Meta::KEY_H3)) {
            return $this->owner->{Meta::KEY_H3} = $value;
        }
    }

    /**
     * @param $fieldName
     * @param null $lang
     * @return bool
     */
    private function setSeoField($fieldName, $lang = null)
    {
        if($this->isProduceFunc($fieldName)) {
            $cacheUrlName = $this->getSeoUrl();
            $metas = Yii::$app->getModule('seo')->getMetaData($cacheUrlName, Yii::$app->language);
            if(empty($metas[$fieldName])){
                return false;
            }
            return $metas[$fieldName];
        }
    }

    /**
     * @param $name
     * @return bool
     */
    private function isProduceFunc($name)
    {
        if (method_exists($this->owner, 'get' . $name) || property_exists($this->owner, $name)) {
            return true;
        }
    }

    /**
     * @param \yii\base\Component $owner
     */
    public function attach ($owner)
    {
        parent::attach($owner);

        // if the current user can see and edit SEO-data model
        if (is_callable($this->userCanEdit)) {
            $this->userCanEdit = call_user_func($this->userCanEdit, $owner);
        }

        // Determine the controller and add it actions to the seo url stop list
        if (!empty($this->seoUrl) && !empty($this->controllerClassName)) {
            if (isset(static::$_controllersActions[$this->controllerClassName])) {
                // Obtain the previously defined controller actions
                $buffer = static::$_controllersActions[$this->controllerClassName];
            } else {
                // Get all actions of controller
                $reflection = new \ReflectionClass($this->controllerClassName);
                $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
                $controller = $reflection->newInstance(Yii::$app->getUniqueId(), null);
                // Add all reusable controller actions
                $buffer = array_keys($controller->actions());
                // Loop through all the main controller actions
                foreach ($methods as $method) {
                    /* @var $method \ReflectionMethod */
                    $name = $method->getName();
                    if ($name !== 'actions' && substr($name, 0, 6) == 'action') {
                        $action = substr($name, 6, strlen($name));
                        $action[0] = strtolower($action[0]);
                        $buffer[] = $action;
                    }
                }

                // Save controller actions for later use
                static::$_controllersActions[$this->controllerClassName] = $buffer;
            }

            // Merge controller actions with actions from config behavior
            $this->stopNames = array_unique(array_merge($this->stopNames, $buffer));
        }

        $this->addRule($owner, 'url', 'safe');

        foreach (Module::getMetaFields() as $key) {
            foreach ($this->rules() as $rule) {
                $attributes = array_shift($rule);
                $validator = array_shift($rule);
                foreach ($attributes as $attribute) {
                    $this->addRule($owner, $attribute, $validator, $rule);
                }
            }
        }
    }

    /**
     * @param ActiveRecord $model
     * @param $attributes
     * @param $validator
     * @param array $options
     * @return $model
     */
    public function addRule($model, $attributes, $validator, $options = [])
    {
        $validators = $model->getValidators();
        $validators->append(Validator::createValidator($validator, $model, (array) $attributes, $options));
        return $model;
    }

    public function afterSave ()
    {
        if(Yii::$app->request->isConsoleRequest) return;

        if(Yii::$app->request->isPost) {

            /** @var ActiveRecord $model */
            $model = $this->owner;
            $cacheUrlName = $this->getCacheUrlName();
            Yii::$app->getCache()->delete($cacheUrlName);

            Meta::deleteAll(['key' => $cacheUrlName]);
            foreach (Module::getMetaFields() as $key) {
                foreach (Yii::$app->getModule('seo')->languages as $language) {
                    $values        = $model->{$key};
                    if(!empty($values[$language])) {
                        $meta          = new Meta;
                        $meta->key     = $cacheUrlName;
                        $meta->name    = $key;
                        $meta->lang    = $language;
                        $meta->content = $values[$language];
                        $meta->save(false);
                    }
                }
            }

        }

    }

    /**
     * @return string
     */
    public function getCacheUrlName()
    {
        $cacheUrlName = $this->owner->getSeoUrl();
        return ltrim($cacheUrlName, '/');
    }

}