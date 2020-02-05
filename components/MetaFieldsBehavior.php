<?php

namespace mirocow\seo\components;

use mirocow\seo\models\Meta;
use mirocow\seo\Module;
use Yii;
use yii\base\Behavior;
use yii\base\Model;
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
    public $scenario = Model::SCENARIO_DEFAULT;

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
     * @return mixed
     */
    public function getSeoTitle()
    {
        return $this->setSeoField(Meta::KEY_TITLE);
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
     * @return mixed
     */
    public function getSeoDescription()
    {
        return $this->setSeoField(Meta::KEY_DESCRIPTION);
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
     * @return mixed
     */
    public function getSeoKeywords()
    {
        return $this->setSeoField(Meta::KEY_KEYWORDS);
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
     * @return bool|mixed
     */
    public function getSeoH1()
    {
        return $this->setSeoField(Meta::KEY_H1);
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
     * @return bool|mixed
     */
    public function getSeoH2()
    {
        return $this->setSeoField(Meta::KEY_H2);
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
     * @return bool|mixed
     */
    public function getSeoH3()
    {
        return $this->setSeoField(Meta::KEY_H3);
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
     * @return mixed
     */
    public function getSeoContent()
    {
        return $this->setSeoField(Meta::KEY_CONTENT);
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
     * @return bool
     */
    private function setSeoField($fieldName)
    {
        $metas = Yii::$app->getModule('seo')->getMetaData();
        if(empty($metas[$fieldName])){
            return false;
        }

        return $metas[$fieldName];
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

            if($this->userCanEdit && $owner->scenario == $this->scenario) {

                $data = [];
                $values = Yii::$app->request->post($owner->formName());
                $cacheUrlName = $this->owner->getSeoUrl();

                foreach ($this->fields as $name) {
                    $getter = 'get' . ucfirst($name);
                    if (method_exists($owner, $getter)) {
                        if (!empty($values[$name] && $owner->{$name} <> $values[$name])) {
                            $data[] = [
                                'key' => $cacheUrlName,
                                'name' => $name,
                                'content' => $values[$name],
                            ];
                        }
                    }
                }

                if($data) {
                    $cacheKey = 'seo_' . md5($cacheUrlName);
                    Yii::$app->cache->delete($cacheKey);
                    Meta::deleteAll(['key' => $cacheUrlName]);
                    foreach ($data as $item) {
                        $meta = new Meta;
                        $meta->key = $item['key'];
                        $meta->name = $item['name'];
                        $meta->content = $item['content'];
                        $meta->save();
                    }
                }
            }

        }
    }

}