<?php

namespace Blog\Models;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

class User extends Entity implements
	AuthenticatableContract,
	AuthorizableContract,
	CanResetPasswordContract
{
	use Authenticatable, Authorizable, CanResetPassword, SoftDeletes;

	protected $table = 'users';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'mobile', 'sms_code'
	];

	protected $visible = [
		'mobile', 'name', 'username', 'icon_url', 'email'
	];

	const STATUS_NORMAL = 0;
	const STATUS_INACTIVATED = 1;
	const STATUS_DISABLED = 2;
	// 后续的非正常状态是4, 8, 16, 32...

	const STATUS = [
		self::STATUS_NORMAL => '正常',
		self::STATUS_INACTIVATED => '未激活',
		self::STATUS_DISABLED => '已禁用',
	];

	const USER_TYPE_PERSONAL = 1;
	const USER_TYPE_GROUP = 2;
	const USER_TYPE_ENTERPRISE = 4;

	const USER_TYPE_DESC = [
		self::USER_TYPE_PERSONAL => '个人用户',
		self::USER_TYPE_GROUP => '团体用户',
		self::USER_TYPE_ENTERPRISE => '企业用户'
	];

	const ROLE_NORMAL = 1;
	const ROLE_MA = 2;
	const ROLE_PA = 4;
	const ROLE_DOCTOR = 8;
	const ROLE_STAFF = 16;

	const ROLE_DESC = [
		self::ROLE_NORMAL => '普通用户',
		self::ROLE_MA => 'MA',
		self::ROLE_PA => 'PA',
		self::ROLE_DOCTOR => '医生',
		self::ROLE_STAFF => '公司员工'
	];

	const HSO_ADMIN = [
		self::ROLE_DOCTOR,
		self::ROLE_STAFF,
		self::ROLE_MA,
		self::ROLE_PA,
	];

	//有康管理员
	const YK_ADMIN = [
		self::ROLE_DOCTOR,
		self::ROLE_STAFF,
	];

	const VISIT_SERVICE = 1;
	const GLOBAL_SERVICE = 2;

	const SERVICE_DESC = [
		self::VISIT_SERVICE => '上门服务',
		self::GLOBAL_SERVICE => '全球转诊服务',
	];

	const PURCHASED = 1;
	const NOT_PURCHASED = 2;

	const PURCHASE_DESC = [
		self::PURCHASED => '已购买',
		self::NOT_PURCHASED => '未购买',
	];

	/**
	 * 获得健康档案
	 */
	public function healthRecords()
	{
		return $this->hasMany(HealthRecord::class, 'user_id', 'id');
	}

	public function emrs()
	{
		return $this->hasMany(EMR::class, 'user_id', 'id');
	}

	public function doctorStudios()
	{
		return $this->belongsToMany(DoctorStudio::class, 'doctor_studio_user_refs', 'user_id', 'doctor_studio_id')
			->withPivot(['role', 'qrcode_id'])->withTimestamps();
	}

	/**
	 * 获得患者信息
	 */
	public function userInfo()
	{
		return $this->hasOne(UserInfo::class, 'user_id', 'id');
	}

	/**
	 * 获得员工信息
	 */
	public function StaffInfo()
	{
		return $this->hasOne(StaffInfo::class, 'user_id', 'id');
	}


	public function doctorByUser()
	{
		return $this->belongsToMany(User::class, 'user_doctor_refs', 'user_id', 'doctor_id')
			->withPivot(['doctor_name', 'booking_time', 'type'])->withTimestamps();
	}

	/**
	 * 获得医生
	 */
	public function doctors()
	{
		return $this->hasOne(Doctor::class, 'user_id', 'id');
	}

	/**
	 * 获得医生信息
	 */
	public function doctorInfo()
	{
		return $this->hasOne(DoctorInfo::class, 'user_id', 'id');
	}

	/**
	 * 返回关注患者的医生
	 */
	public function followedDoctors()
	{
		return $this->belongsToMany(Doctor::class, 'user_doctor_refs', 'user_id', 'doctor_id')->withPivot('type')
			->where('type', Doctor::USER_DOCTOR_REF_FOLLOW);
	}


	/**
	 * 通过用户名和密码验证用户是否存在(用户名不能重复)
	 * @param $userName,$password
	 * @return  instance  如果存在，返回一个User类的实例
	 *          null 如果不存在，返回为空
	 */
	public static function getUser($userName, $password)
	{
		$user = User::where('username', $userName)
			->where('status', User::STATUS_NORMAL)
			->first();
		if(is_null($user))
		{
			return null;
		}
		if(Hash::check($password, $user->password))
		{
			return $user;
		}
		return null;
	}

	/**
	 * 通过验证ID来验证用户是否存在
	 * @return instance 如果存在，返回一个实例
	 *         null    如果不存在，返回空
	 */
	public static function verifyUser($id)
	{
		return User::where('id', $id)->where('status', User::STATUS_NORMAL)->first();
	}

	/**
	 * 通过ID来验证医生是否存在
	 * @return instance 如果存在，返回一个医生实体
	 *         null 如果不存在，返回空
	 */
	public static function verifyDoctor($id)
	{
		return User::where('id', $id)->where('status', User::STATUS_NORMAL)
			->where('role', User::ROLE_DOCTOR)->first();
	}

	/**
	 * 通过ID来验证患者是否存在
	 * @return instance 如果存在，返回一个实体
	 *         null 如果不存在，返回空
	 */
	public static function verifyPatient($id)
	{
		return User::where('id', $id)->where('status', User::STATUS_NORMAL)
			->where('role', User::ROLE_NORMAL)->first();
	}

	/**
	 * 通过ID来验证MA是否存在
	 * @return instance 如果存在，返回一个实体
	 *         null 如果不存在，返回空
	 */
	public static function verifyMA($id)
	{
		return User::where('id', $id)->where('status', User::STATUS_NORMAL)
			->where('role', User::ROLE_MA)->first();
	}

	/**
	 * 通过ID来验证PA是否存在
	 * @return instance 如果存在，返回一个实体
	 *         null 如果不存在，返回空
	 */
	public static function verifyPA($id)
	{
		return User::where('id', $id)->where('status', User::STATUS_NORMAL)
			->where('role', User::ROLE_PA)->first();
	}

	/**
	 * 获得聊天室组
	 * @return
	 */
	public function rongcloudConversationGroups()
	{
		return $this->belongsToMany(RongCloudConversationGroup::class, 'user_rongcloud_conversation_group_refs',
			'user_id', 'rongcloud_conversation_group_id')->withPivot(['rongcloud_conversation_group_type',
			'conversation_type', 'doctor_studio_id'])->withTimestamps();
	}

	/**
	 * 通过user id和患者id来获取所关注的患者的emr列表
	 * @return array 实体列表
	 *         integer 错误状态码
	 */
	public static function getPatients($type, $id, $patientId = null)
	{
		$user = null;
		$code = null;
		switch($type)
		{
			case User::ROLE_PA :
				$user = self::verifyPA($id);
				$code = Consts::PA_NOT_EXISTS;
				break;
			case User::ROLE_MA :
				$user = self::verifyMA($id);
				$code = Consts::MA_NOT_EXISTS;
				break;
			case User::ROLE_DOCTOR :
				$user = self::verifyDoctor($id);
				$code = Consts::DOCTOR_NOT_EXISTS;
				break;
		}

		if(is_null($user))
		{
			return $code;
		}
		if(!is_null($patientId))
		{
			if(is_null(self::verifyPatient($patientId)))//判断患者存不存在
			{
				return Consts::PATIENT_NOT_EXISTS;
			}
		}

		$doctor = Doctor::where('user_id', $user->id)->first();
		if(is_null($doctor))//说明系统数据库有错误
		{
			return Consts::ERROR;
		}

		$patient = null;
		if(is_null($patientId))
		{
			$patient = $doctor->followedPatients()->first();
		}
		else
		{
			$patient = $doctor->followedPatients()->where('id', $patientId)->first();
		}

		if(is_null($patient))//如果患者不存在，说明所给医生和患者之间并没有联系
		{
			return Consts::ERROR;
		}
		return $patient->emrs()->get();
	}

	public function doctorStudioQRCodeURL($doctorStudioId) {
		$qrcodeId = $this->doctorStudios()->where('doctor_studio_id', $doctorStudioId)->first()->pivot->qrcode_id;
		return QRCode::find($qrcodeId)->wechat_img_url;
	}

	public static function createWeChatUserWithRongCloudAccount($openid, $nickname, $headImgURL)
	{
		$user = self::createWithAutoId(['openid' => $openid, 'role' => User::ROLE_NORMAL,
			'status' =>User::STATUS_NORMAL, 'name' => $nickname, 'icon_url' => $headImgURL]);
		RongCloudAccount::createRecord($user->id, $nickname, $headImgURL);
		return $user;
	}

	public static function createNormalUserWithRongCloudAccount($userName, $password, $name='', $headImgURL=' ')
	{
		$user = self::createByUserNamePassword($userName, $password, User::ROLE_NORMAL, $name);
		RongCloudAccount::createRecord($user->id, $userName, $headImgURL);
		return $user;
	}

	public static function createByUserNamePassword($userName, $password, $role, $name='')
	{
		return self::createWithAutoId([
			'username' => $userName,
			'name' => $name,
			'password' => Hash::make($password),
			'role' => $role,
			'status' => User::STATUS_NORMAL,
			'api_token' => str_random(100)
		]);
	}

	public static function createStaffUserWithRongCloudAccount($userName, $password, $role, $name='', $headImgURL=' ')
	{
		if (!in_array($role, DoctorStudio::getStaffRoles())) {
			return null;
		}
		$user = self::createByUserNamePassword($userName, $password, $role, $name);
		RongCloudAccount::createRecord($user->id, $name, $headImgURL);
		UserInfo::createWithAutoId([
			'user_id' => $user->id,
			'name' => $userName,
			'user_type' => UserInfo::USER_TYPE_STAFF,
			'role' => User::ROLE_DESC[$role],
			'department' => UserInfo::DEPARTMENT_DESC[$role]
		]);
		return $user;
	}

	public static function createDoctorUserWithRongCloudAccount($userName, $password, $name='', $headImgURL=' ', $createDoctor=false)
	{
		$user = self::createByUserNamePassword($userName, $password, User::ROLE_DOCTOR, $name);
		RongCloudAccount::createRecord($user->id, $userName, $headImgURL);
		if ($createDoctor) {
			Doctor::createWithAutoId([
				'name' => $name,
				'user_id' => $user->id
			]);
		}
		return $user;
	}

	/**
	 * 通过患者ID来验证该患者是否已经购买了套餐
	 * @return boolean  如果已经购买，返回一个true
	 *          如果未购买，返回false
	 */
	public static function isPurchasedUser($id)
	{
		$user = User::where('id', $id)->where('status', User::STATUS_NORMAL)
			->where('role', User::ROLE_NORMAL)->first();
		if(!is_null($user) && !is_null($user->userInfo))
		{
			return true;
		}
		return false;
	}

	/**
	 * 判断是否需要向ElasticSearch中添加数据
	 */
	protected function notifyES(array $attributes = [])
	{
		var_dump("子类调用");
		var_dump($attributes);
		if(isset($attributes['name']))
		{
			var_dump('sssss');
			Event::fire(new ElasticSearchEvent(ElasticSearchEvent::TYPE_UPDATE, 'user', $this->id, $attributes));
		}
		return ;
	}

}
