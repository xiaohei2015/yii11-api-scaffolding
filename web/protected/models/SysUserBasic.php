<?php

/**
 * This is the model class for table "sys_user_basic".
 *
 * The followings are the available columns in table 'sys_user_basic':
 * @property string $id
 * @property string $account
 * @property integer $user_type
 * @property string $password
 * @property string $salt
 * @property string $name
 * @property string $avatar
 * @property integer $sex
 * @property string $phone
 * @property string $email
 * @property integer $email_verify
 * @property integer $city
 * @property integer $add_time
 * @property integer $last_login
 * @property integer $status
 * @property integer $failed_count
 * @property integer $lock_expired_time
 */
class SysUserBasic extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'sys_user_basic';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_type, sex, email_verify, city, add_time, last_login, status, failed_count, lock_expired_time', 'numerical', 'integerOnly'=>true),
			array('account, password, name, email', 'length', 'max'=>64),
			array('salt', 'length', 'max'=>32),
			array('avatar', 'length', 'max'=>120),
			array('phone', 'length', 'max'=>20),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, account, user_type, password, salt, name, avatar, sex, phone, email, email_verify, city, add_time, last_login, status, failed_count, lock_expired_time', 'safe', 'on'=>'search'),

			array('email','email','message'=>"email格式错误" ),//
			array('phone', 'match', 'pattern'=>'/^1[0-9]{10}$/','message'=>"请输入正确的手机号"),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'SysUserHr'=>array(self::HAS_ONE, 'SysUserHr', array('user_id'=>'id')),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => '用户UID',
			'account' => '账号',
			'user_type' => '用户类型',
			'password' => '密码',
			'salt' => '密码盐',
			'name' => '昵称',
			'avatar' => '头像',
			'sex' => '性别(1:男,2:女)',
			'phone' => '手机',
			'email' => '邮箱',
			'email_verify' => '是否验证邮箱(0:未验证,1:已验证)',
			'city' => '所在城市',
			'add_time' => '创建时间',
			'last_login' => '最后登录时间',
			'status' => '状态(是否有效,0:无效,1:有效)',
			'failed_count' => '登录失败次数',
			'lock_expired_time' => '账号锁定到期时间',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id,true);
		$criteria->compare('account',$this->account,true);
		$criteria->compare('user_type',$this->user_type);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('salt',$this->salt,true);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('avatar',$this->avatar,true);
		$criteria->compare('sex',$this->sex);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('email_verify',$this->email_verify);
		$criteria->compare('city',$this->city);
		$criteria->compare('add_time',$this->add_time);
		$criteria->compare('last_login',$this->last_login);
		$criteria->compare('status',$this->status);
		$criteria->compare('failed_count',$this->failed_count);
		$criteria->compare('lock_expired_time',$this->lock_expired_time);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return SysUserBasic the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
