<?php

/**
 * This is the model class for table "sys_static".
 *
 * The followings are the available columns in table 'sys_static':
 * @property string $id
 * @property string $code
 * @property string $value
 * @property string $label
 * @property integer $priority
 * @property integer $add_time
 * @property integer $status
 */
class SysStatic extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'sys_static';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('priority, add_time, status', 'numerical', 'integerOnly'=>true),
			array('code', 'length', 'max'=>64),
			array('value', 'length', 'max'=>128),
			array('label', 'length', 'max'=>256),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, code, value, label, priority, add_time, status', 'safe', 'on'=>'search'),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => '编号',
			'code' => '类别代号',
			'value' => '实际值',
			'label' => '显示值',
			'priority' => '优先级',
			'add_time' => '建立日期',
			'status' => '状态(是否有效,0:无效,1:有效) ',
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
		$criteria->compare('code',$this->code,true);
		$criteria->compare('value',$this->value,true);
		$criteria->compare('label',$this->label,true);
		$criteria->compare('priority',$this->priority);
		$criteria->compare('add_time',$this->add_time);
		$criteria->compare('status',$this->status);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return SysStatic the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
