<?php

namespace Blog\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

abstract class Entity extends Model
{

	public $incrementing = false;

	protected $hidden = ['pivot', 'created_at', 'updated_at', 'deleted_at'];

	/**
	 * 带自增ID的create()新增模型
	 */
	public static function createWithAutoId(array $attributes = [])
	{
		$model = new static($attributes);
		$table_name = $model->table;

		$re = DB::selectFromWriteConnection("select nextval('{$table_name}') id");
		$model->id = $re[0]->id;
		$ok = $model->save();

		return $ok ? $model : null;
	}

	protected function asJson($value)
	{
		return json_encode($value, JSON_UNESCAPED_UNICODE);
	}

	public function fromJson($value, $asObject = false)
	{
		return json_decode($value, ! $asObject, $options=JSON_UNESCAPED_UNICODE);
	}

	/**
	 * 判断是否需要向ElasticSearch中添加数据
	 * @param array $attributes
	 * @param array $options
	 * @return mixed
	 */
	abstract protected function notifyES(array $attributes = []);

	/**
	 * 重写update方法，让其加上ES
	 */
	public function update(array $attributes = [], array $options = [])
	{
		var_dump("父类调用");
		$this->notifyES($attributes);
		return parent::update($attributes, $options);
	}

	/**
	 *重写delete方法，让其加上ES
	 */
	public function delete()
	{
		$this->notifyES();
		return parent::delete();
	}

	/**
	 * 找到对应的Elasticsearch中哪个type
	 */
	private function findType()
	{
//        if($this instanceof User)
//        {
//            return ElasticSearch::TYPE_USER;
//        }
//        if($this instanceof DoctorStudio)
//        {
//            return ElasticSearch::TYPE_STUDIO;
//        }
		return null;
	}
}
