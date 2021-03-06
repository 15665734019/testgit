<?php
namespace common\models;

use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord  implements \yii\web\IdentityInterface
{
    const SUPER_ID=10;
    public static function tableName()
    {
        return'{{%user}}';
    }
    public function rules()
    {
        return [
            ['username','checkName','skipOnEmpty'=>false],
            ['password','string','min'=>6,'tooShort'=>'密码长度不能小于6位','skipOnEmpty'=>false,'when'=>function($model){return ($model->isNewRecord || $model->password !='');}],
            ['status','in','range'=>[0,1],'message'=>'非法操作'],
            ['id','safe']
        ];
    }
    public function checkName($attribute,$params){
        //字母，数字 2-30
        if(!preg_match("/^[\w]{2,30}$/",$this->$attribute)){
            $this->addError($attribute,'用户名必须为2-3-的数字或字母');
        }else if(self::find()->where(['username'=>$this->$attribute])->andWhere(['!=','id',$this->id])->count()>0 ){
            $this->addError($attribute,'用户名已经被占用');
        }
    }
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => 1]);
    }
    public static function findIdentityByAccessToken($token,$type=null){
        return static::findOne(['accessToken'=>$token]);
    }

    public static function findByUsername($username){     //①
        return static::findOne(['username'=>$username]);
    }

    public function getId(){
        return $this->id;
    }

    public function getAuthkey(){
        return $this->auth_key;
    }

    public function validateAuthKey($authKey){
        return $this->auth_key === $authKey;
    }

    public function validatePassword($password){          //②
        return $this->password === md5($password);
    }


/*
 * beforeSave 在多个model save时，要慎重使用
 *     public function beforeSave($insert)
    {

        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->date = $this->login_date = time();
            }
            if ($insert ||$this->password != $this->getOldAttribute('password')) {
                if (empty($this->password)) {
                    unset($this->password);
                } else {
                    $this->password = md5($this->password);
                }
                $this->login_date = time();
            }
            return true;
        } else {
            return false;
        }
    }*/
    public static function deleteIn($selected){
        $data=[];
        foreach($selected as $select){
            if($select == self::SUPER_ID) continue;
            $data[]=(int)$select;
        }
        return self::deleteAll(['id'=>$data]);
    }
    //记录的保存
    public function updateUserStatus(){
        if ($this->isNewRecord) {
            $this->date = $this->login_date = time();
        }else {
                if (empty($this->password)) {
                    unset($this->password);
                } else {
                    $this->password = md5($this->password);
                }
                $this->login_date = time();
             }
        return $this->save();
    }
}