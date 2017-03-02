<?php

namespace common\modules\api\v1\controllers\frontend;

use yii\rest\ActiveController;
use common\modules\api\v1\models\game\Game;

class GameController extends ActiveController
{
    public function init()
    {
        $this->modelClass = Game::class;

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
