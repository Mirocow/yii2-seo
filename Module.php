<?php

namespace mirocow\seo;

use mirocow\seo\helpers\UrlHelper;
use mirocow\seo\models\Meta;
use Yii;
use yii\base\BootstrapInterface;
use yii\helpers\ArrayHelper;
use yii\web\Application;
use yii\web\View;

/**
 * Created by PhpStorm.
 * User: mirocow
 * Date: 29.10.2017
 * Time: 16:08
 */
class Module extends \yii\base\Module implements BootstrapInterface
{

    const NO_REDIRECT = 0;
    const FROM_WWW = 1;
    const FROM_WITHOUT_WWW = 2;

    public $backendMode = true;

    public $basePath = '@mirocow/seo/admin';

    /**
     * @var array
     */
    public $controllerMap = [
        'meta' => [
            'class' => 'mirocow\seo\admin\controllers\MetaController',
        ],
    ];

    /**
     * @var int
     */
    public $cacheExpire = 86400;

    /**
     * @var int type of redirect from WWW or without WWW
     */
    public $redirectWWW = self::NO_REDIRECT;

    /**
     * @var bool if true redirect from url with trailing slash
     */
    public $redirectTrailingSlash = false;

    /**
     * @var int
     */
    public $redirectStausCode = 301;

    /**
     * @var array
     */
    public $include = [];

    /**
     * @var array
     */
    private $_models = [];

    /**
     * @var array
     */
    private $metas = [];

    public function init()
    {
        parent::init();

        if (($app = Yii::$app) instanceof \yii\web\Application AND $this->backendMode) {
            $this->setModule('admin', [
                'class' => 'mirocow\seo\admin\Module',
                'controllerMap' => $this->controllerMap,
                'basePath' => $this->basePath,
            ]);
        }

        if(!Yii::$app->request->isConsoleRequest) {

            $url = Yii::$app->getRequest()->hostInfo . \Yii::$app->request->url;

            $cacheUrlName = UrlHelper::clean($url);

            $this->loadMetaData($cacheUrlName);

        }

    }

    public function loadMetaData($cacheUrlName)
    {
        $cacheExpire = $this->cacheExpire;

        $cacheKey = 'seo_' . md5($cacheUrlName);

        if(YII_DEBUG){
            Yii::$app->cache->delete($cacheKey);
        }

        $this->metas = Yii::$app->cache->get($cacheKey);

        if ($this->metas === false) {
            $rows = Meta::find()->where(['REGEXP', 'key' , '^' . preg_quote($cacheUrlName) . '$'])->asArray()->all();
            foreach ($rows as $row){
                $this->metas[$row['name']] = $row['content'];
            }
            if ($this->metas) {
                Yii::$app->cache->set($cacheKey, $this->metas, $cacheExpire);
            }
        }
    }

    /**
     * Returns an array of meta-fields.
     *
     * @param null $key
     *
     * @return array|mixed
     */
    public static function getMetaFields($key = null, $returnOnlyKeys = true)
    {
        $fields = [
            Meta::KEY_TITLE => Yii::t('app', 'Title'),
            Meta::KEY_DESCRIPTION => Yii::t('app', 'Description'),
            Meta::KEY_KEYWORDS => Yii::t('app', 'Keywords'),
            Meta::KEY_H1 => Yii::t('app', 'H1'),
            Meta::KEY_H2 => Yii::t('app', 'H2'),
            Meta::KEY_H3 => Yii::t('app', 'H3'),
            Meta::KEY_CONTENT => Yii::t('app', 'Content'),
            Meta::KEY_BEFORE => Yii::t('app', 'Before'),
            Meta::KEY_AFTER => Yii::t('app', 'After'),
            Meta::KEY_CUSTOM_CONTENT_1 => Yii::t('app', 'Custom content 1'),
            Meta::KEY_CUSTOM_CONTENT_2 => Yii::t('app', 'Custom content 2'),
            Meta::KEY_CUSTOM_CONTENT_3 => Yii::t('app', 'Custom content 3'),
        ];

        return isset($fields[$key])? $fields[$key]: ($returnOnlyKeys? array_keys($fields): $fields);
    }

    /**
     * @return array
     */
    public static function keyToName($key)
    {
        $labels = self::getMetaFields(null, false);

        return isset($labels[$key]) ? $labels[$key] : 'Uncknow';
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if (Yii::$app->request->isConsoleRequest) {
            return;
        }

        if (Yii::$app->request->isAjax === true) {
            return;
        }

        if (is_string($this->include)) {
            $this->include = explode(',', $this->include);
        }

        $app->on(
            Application::EVENT_BEFORE_REQUEST,
            function () use ($app) {
                if ($app->getModule('seo')->redirectWWW != self::NO_REDIRECT) {
                    self::redirectWWW();
                }
                if ($app->getModule('seo')->redirectTrailingSlash == true) {
                    self::redirectPattern('#^(.*)/$#', $this->redirectStausCode);
                }
                $app->getView()->on(View::EVENT_BEFORE_RENDER, [self::class, 'registrationMeta'], $this->include);
            }
        );
    }

    /**
     * If redirectWWW config make 301 redirect to www or not www domain
     */
    public static function redirectWWW()
    {
        $type = Yii::$app->getModule('seo')->redirectWWW;
        if ($type != self::NO_REDIRECT) {
            $readirArr = [
                self::FROM_WITHOUT_WWW => function () {
                    if (preg_match('#^(http|https):\/\/www\.#i', Yii::$app->request->hostInfo) === 0) {
                        Yii::$app->response->redirect(
                            str_replace('://', '://www.', Yii::$app->request->absoluteUrl),
                            301
                        );
                        Yii::$app->end();
                    }
                },
                self::FROM_WWW => function () {
                    if (preg_match('#^(http|https):\/\/www\.#i', Yii::$app->request->hostInfo) === 1) {
                        Yii::$app->response->redirect(
                            str_replace('://www.', '://', Yii::$app->request->absoluteUrl),
                            301
                        );
                        Yii::$app->end();
                    }
                },
            ];
            $readirArr[$type]();
        }
    }

    /**
     * @param string $pattern
     * @param int $stausCode
     * @throws \yii\base\ExitException
     */
    public static function redirectPattern($pattern, $stausCode = 301)
    {
        $redirUrl = preg_replace($pattern, '$1', Yii::$app->request->url);
        if (!empty($redirUrl) && $redirUrl !== Yii::$app->request->url) {
            Yii::$app->response->redirect($redirUrl, $stausCode);
            Yii::$app->end();
        }
    }

    public static function registrationMeta()
    {
        $metas = Yii::$app->getModule('seo')->getMetaData();

        if ($metas) {
            foreach ($metas as $name => $meta) {
                switch ($name) {
                    case Meta::KEY_TITLE:
                        Yii::$app->controller->getView()->title = $meta;
                        break;
                    case Meta::KEY_DESCRIPTION:
                    case Meta::KEY_KEYWORDS:
                        Yii::$app->controller->getView()->registerMetaTag([
                            'name' => self::keyToName($name),
                            'content' => $meta,
                        ], $name);
                        break;
                    case Meta::KEY_H1:
                    case Meta::KEY_H2:
                    case Meta::KEY_H2:
                    case Meta::KEY_CONTENT:
                        Yii::$app->controller->getView()->blocks[$name] = $meta;
                        break;
                }
            }
        }
    }

    /**
     * @param string $route
     * @return array|bool
     * @throws \yii\base\InvalidConfigException
     */
    public function createController($route)
    {
        if (strpos($route, 'admin/') !== false) {
            return $this->getModule('admin')->createController(str_replace('admin/', '', $route));
        } else {
            return parent::createController($route);
        }
    }

    /**
     * @param $cacheUrlName
     * @return array|bool|mixed
     */
    public function getMetaData()
    {
        return $this->metas;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setMetaKey(string $key, string $value)
    {
        $this->metas[$key] = $value;
    }

}