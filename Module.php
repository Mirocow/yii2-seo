<?php

namespace mirocow\seo;

use mirocow\seo\models\Meta;
use Yii;
use yii\base\BootstrapInterface;
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

    public $basePath = 'mirocow\seo\admin\views';

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
     * @var array
     */
    public $include = [];

    /**
     * @var array
     */
    private $_models = [];

    public function init()
    {
        parent::init();

        if (($app = Yii::$app) instanceof \yii\web\Application AND $this->backendMode) {
            $this->setModule('admin', [
                'class' => 'mirocow\seo\admin\Module',
                'controllerMap' => $this->controllerMap,
            ]);
        }
    }

    /**
     * Returns an array of meta-fields.
     * @param null $key
     * @return array|mixed
     */
    public static function getMetaFields($key = null)
    {
        $fields = [
          Meta::KEY_TITLE,
          Meta::KEY_DESCRIPTION,
          Meta::KEY_KEYWORDS,
        ];

        return isset($fields[$key]) ? $fields[$key] : $fields;
    }

    /**
     * @return array
     */
    public static function keyToName($key)
    {
        $labels = [
          Meta::KEY_TITLE => 'Title',
          Meta::KEY_DESCRIPTION => 'Description',
          Meta::KEY_KEYWORDS => 'Keywords'
        ];

        return isset($labels[$key])? $labels[$key]: 'Uncknow';
    }

    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        if (is_string($this->include)) {
            $this->include = explode(',', $this->include);
        }

        $app->on(
          Application::EVENT_BEFORE_REQUEST,
          function () use ($app) {
              if ($app->getModule('seo')->redirectWWW != self::NO_REDIRECT) {
                  self::redirectWWW();
              }
              if ($app->getModule('seo')->redirectTrailingSlash == 1) {
                  self::redirectSlash();
              }

              $app->getView()->on(View::EVENT_BEGIN_PAGE, [self::class, 'registrationMeta'], $this->include);
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
     * Make redirect from url with trailing slash
     */
    public static function redirectSlash()
    {
        $redirUrl = preg_replace('#^(.*)/$#', '$1', Yii::$app->request->url);
        if (!empty($redirUrl) && $redirUrl !== Yii::$app->request->url) {
            Yii::$app->response->redirect($redirUrl, 301);
            Yii::$app->end();
        }
    }

    /**
     *
     */
    public static function registrationMeta()
    {
        if(Yii::$app->request->isConsoleRequest){
            return;
        }

        if (Yii::$app->request->isAjax === true) {
            return;
        }

        $cacheExpire = Yii::$app->getModule('seo')->cacheExpire;
        $cacheUrlName = ltrim(\Yii::$app->request->url, '/');

        $metas = Yii::$app->getCache()->get($cacheUrlName);
        if ($metas === false) {
            $rows = Meta::find()->asArray()->all();
            foreach ($rows as $row) {
                if (preg_match('~' . preg_quote($row['key']) . '~', $cacheUrlName, $matches)) {
                    $metas[ $row['name'] ] = $row;
                }
            }
            if($metas) {
                Yii::$app->getCache()->set(
                  $cacheUrlName,
                  $metas,
                  $cacheExpire
                );
            }
        }

        if($metas) {
            foreach ($metas as $meta) {
                switch ($meta['name']) {
                    case Meta::KEY_TITLE:
                        Yii::$app->controller->getView()->title = $meta['content'];
                        break;
                    case Meta::KEY_DESCRIPTION:
                    case Meta::KEY_KEYWORDS:
                        Yii::$app->controller->getView()->registerMetaTag([
                          'name' => self::keyToName($meta['name']),
                          'content' => $meta['content'],
                        ], $meta['name']);
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

}