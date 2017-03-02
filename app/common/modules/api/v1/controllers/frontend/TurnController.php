<?php

namespace common\modules\api\v1\controllers\frontend;

use yii\rest\ActiveController;
use common\modules\api\v1\models\turn\Turn;

class TurnController extends ActiveController
{
    public function init()
    {
        $this->modelClass = Turn::class;

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [];
    }
}
