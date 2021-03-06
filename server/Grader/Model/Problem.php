<?php

namespace Grader\Model;

class Problem extends Model{
	protected $hidden = array(
		'created_at', 'updated_at', 'deleted_at',
		'input_lang', 'input',
		'output_lang', 'output',
		'comparator'
	);
	protected $fillable = array(
		'name', 'description', 'point', 'creator',
		'graders', 'input_lang', 'output_lang',
		'comparator'
	);
	public function test(){
		return $this->belongsTo('Grader\Model\Test');
	}
	public function getGradersAttribute($value){
		return json_decode($value);
	}
	public function setGradersAttribute($value){
		if(isset($value['invalid'])){
			unset($value['invalid']);
		}
		$this->attributes['graders'] = json_encode($value);
	}
}