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

    public $fields = [
        Meta::KEY_TITLE,
        Meta::KEY_DESCRIPTION,
        Meta::KEY_KEYWORDS,
    ];

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
     * @param null $lang
     * @return mixed
     */
    public function getSeoContent($lang = null)
    {
        return $this->setSeoField(Meta::KEY_CONTENT, $lang);
    }

    /**
     * @param $value
     * @return mixed
     */
    public function setSeoContent($value)
    {
        if($this->isProduceFunc(Meta::KEY_CONTENT)) {
            return $this->owner->{Meta::KEY_CONTENT} = $value;
        }
    }

    /**
     * @param $fieldName
     * @param null $lang
     * @return bool
     */
    private function setSeoField($fieldName, $lang = null)
    {
        $cacheUrlName = $this->getSeoUrl();
        $metas = Yii::$app->getModule('seo')->getMetaData($cacheUrlName, Yii::$app->language);
        if(empty($metas[$fieldName])){
            return false;
        }

        if(!$lang){
            $lang = Yii::$app->language;
        }

        return [$lang => $metas[$fieldName]];
    }

    /**
     * @param $name
     *
     * @return bool
     */
    private function isProduceFunc($name)
    {
        if (method_exists($this->owner, 'get' . $name) || property_exists($this->owner, $name)) {
            return true;
        }
    }

    /**
     * @param ActiveRecord $model
     * @param $attribute
     * @param $validator
     * @param array $options
     * @return $model
     */
    public function addRule($model, $attribute, $validator, $options = [])
    {
        $this->owner->validators[] = Validator::createValidator($validator, $model, $attribute);
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function afterSave ()
    {
        if(Yii::$app->request->isConsoleRequest) return;

        if(Yii::$app->request->isPost) {

            /** @var ActiveRecord $owner */
            $owner = $this->owner;
            $cacheUrlName = $this->owner->getSeoUrl();
            Yii::$app->cache->delete($cacheUrlName);

            Meta::deleteAll(['key' => $cacheUrlName]);
            $values = Yii::$app->request->post($owner->formName());
            foreach ($this->fields as $key) {
                foreach (Yii::$app->getModule('seo')->languages as $language) {
                    if(!empty($values[$key][$language])) {
                        $meta          = new Meta;
                        $meta->key     = $cacheUrlName;
                        $meta->name    = $key;
                        $meta->lang    = $language;
                        $meta->content = $values[$key][$language];
                        $meta->save();
                    }
                }
            }

        }

    }

}