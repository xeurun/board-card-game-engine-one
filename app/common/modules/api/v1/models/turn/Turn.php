<?php

namespace common\modules\api\v1\models\turn;

class Turn extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{turn}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['game_id', 'card_number'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'game_id'       => 'game id',
            'card_number'   => 'Card number',
        ];
    }
}
