<?php
namespace Grader\Web;

use Symfony\Component\HttpFoundation\Request;

class TaskAPI extends Base{
	public function connect(\Silex\Application $app){
		$this->app = $app;
		$controllers = $app['controllers_factory'];
		$controllers->post('/result', array($this, 'save'));
		return $controllers;
	}
	public function save(Request $req){
		if(!$req->request->has('id')){
			$this->error('No task ID', 400);
		}
		if($req->request->get('key') != $this->app['key']){
			$this->error('Invalid key '.$req->request->get('key'), 403);
		}
		$result = $req->request->has('result_id') ? $req->request->get('result_id') : null;
		if(!empty($result)){
			$result = \Grader\Model\Result::find($req->request->get('result_id'));
			if(!$result){
				$this->error('No result by that ID', 404);
			}
			if($req->request->has('correct')){
				$result['state'] = 2;
				$result['correct'] = $req->request->get('correct');
				$result['result'] = $req->request->get('result');
				if($req->request->get('error') != ''){
					$result['error'] = $req->request->get('error');
				}else{
					$result['error'] = $req->request->get('compile');
				}
			}else{
				$result['state'] = 1;
			}
			$result->save();

			/*if(!$req->request->has('correct')){
				ignore_user_abort(true);
				set_time_limit(10);
				// calculate levenshtein distance to all submissions in same language
				$submissions = \Grader\Model\Result::where('lang', '=', $result['lang'])
					->where('user_id', '!=', $result['user_id'])
					->where('problem_id', '=', $result['problem_id'])
					->where('state', '=', 2)
					->where('correct', '=', 1)
					->groupBy('user_id');
				$minSub = array(0, 0);
				foreach($submissions->get() as $sub){
					set_time_limit(10);
					if($minSub[0] === 0 || ($lv=$this->levenshtein($result['code'], $sub['code'])) < $minSub[1]){
						if(!isset($lv)){
							// the or condition was not ran
							$lv=$this->levenshtein($result['code'], $sub['code']);
						}
						$minSub = array($sub['id'], $lv);
					}
				}
				if($minSub[0] > 0){
					$result['closest'] = $minSub[1];
					$result['closest_id'] = $minSub[0];
					$result->save();
				}
			}*/
		}else{
			$result = \Grader\Model\TaskResult::create($req->request->all());
			$result->save();
		}

		return $this->json(array(
			'success' => true
		));
	}

	private function levenshtein($s1, $s2){
		$sLeft = (strlen($s1) > strlen($s2)) ? $s1 : $s2; 
		$sRight = (strlen($s1) > strlen($s2)) ? $s2 : $s1; 
		$nLeftLength = strlen($sLeft); 
		$nRightLength = strlen($sRight); 
		if ($nLeftLength == 0) 
			return $nRightLength; 
		else if ($nRightLength == 0) 
			return $nLeftLength; 
		else if ($sLeft === $sRight) 
			return 0; 
		else if (($nLeftLength < $nRightLength) && (strpos($sRight, $sLeft) !== FALSE)) 
			return $nRightLength - $nLeftLength; 
		else if (($nRightLength < $nLeftLength) && (strpos($sLeft, $sRight) !== FALSE)) 
			return $nLeftLength - $nRightLength; 
		else { 
			$nsDistance = range(1, $nRightLength + 1); 
			for ($nLeftPos = 1; $nLeftPos <= $nLeftLength; ++$nLeftPos) 
			{ 
				$cLeft = $sLeft[$nLeftPos - 1]; 
				$nDiagonal = $nLeftPos - 1; 
				$nsDistance[0] = $nLeftPos; 
				for ($nRightPos = 1; $nRightPos <= $nRightLength; ++$nRightPos) 
				{ 
					$cRight = $sRight[$nRightPos - 1]; 
					$nCost = ($cRight == $cLeft) ? 0 : 1; 
					$nNewDiagonal = $nsDistance[$nRightPos]; 
					$nsDistance[$nRightPos] = 
						min($nsDistance[$nRightPos] + 1, 
								$nsDistance[$nRightPos - 1] + 1, 
								$nDiagonal + $nCost); 
					$nDiagonal = $nNewDiagonal; 
				} 
			} 
			return $nsDistance[$nRightLength]; 
		} 
	}
}
