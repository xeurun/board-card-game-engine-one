<?php

namespace common\modules\api\v1;

class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        if (\Yii::$app->id === 'app-console') {
            $this->controllerNamespace = 'common\modules\api\v1\controllers\console';
        } else {
            $this->controllerNamespace = 'common\modules\api\v1\controllers';
        }
        parent::init();
    }
}