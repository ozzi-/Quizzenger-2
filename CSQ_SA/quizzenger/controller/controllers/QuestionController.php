<?php
namespace quizzenger\controller\controllers {
	use \quizzenger\model\ModelCollection as ModelCollection;
	use \quizzenger\view\View as View;

	class QuestionController{
		private $view;
		private $request;
		private $questionID;
		private $question;

		public function __construct($view) {
			$this->view = $view;
			$this->request = array_merge ( $_GET, $_POST );
		}

		public function render(){
			$this->questionID= intval($this->request ['id']);

			$this->loadQuestionInfoView();
			$this->loadMainView();
			$this->loadQuizsessionView();

			return $this->view->loadTemplate();
		}

		private function loadQuestionInfoView(){
			//viewQuestionInfo
			$viewQuestionInfo= new View();
			$viewQuestionInfo->setTemplate('questioninfo');

			$question = ModelCollection::questionModel()->getQuestion ( $this->questionID );
			$this->question = $question;
			$viewQuestionInfo->assign( 'question', $question );

			$questionHistory = ModelCollection::questionModel()->getHistoryForQuestionByID($this->questionID);
			$viewQuestionInfo->assign( 'questionhistory', $questionHistory );

			$author = ModelCollection::userModel()->getUsernameByID ( $question ['user_id'] );
			$viewQuestionInfo->assign ( 'author', $author );
			$viewQuestionInfo->assign ( 'user_id',$question ['user_id']);

			$tags = ModelCollection::tagModel()->getAllTagsByQuestionID ( $this->questionID );
			$viewQuestionInfo->assign ( 'tags', $tags );

			$this->view->assign( 'questioninfo', $viewQuestionInfo->loadTemplate());
		}

		private function loadMainView(){
			//innerView
			$this->view->setTemplate ( 'question' );

			$this->view->assign ( 'questionID', $this->questionID );
			$question = $this->question;
			$this->view->assign ( 'question', $question );
			$categoryName = ModelCollection::categoryModel()->getNameByID ( $question ['category_id'] );
			$this->view->assign ( 'category', $categoryName );

			$answers = ModelCollection::answerModel()->getAnswersByQuestionID ( $this->questionID );
			//randomize array
			mt_srand(time());
			$order = array_map(create_function('$val', 'return mt_rand();'), range(1, count($answers)));
			$_SESSION['questionorder'][$this->questionID] = $order;
			array_multisort($order, $answers);
			$this->view->assign ( 'answers', $answers );

			$alreadyReported= ModelCollection::reportModel()->checkIfUserAlreadyDoneReport("question", $this->questionID , $_SESSION ['user_id']);
			$this->view->assign ('alreadyreported',$alreadyReported);

			//set message
			if(isset($this->request['questionReport']) && $GLOBALS ['loggedin']){
				$this->view->assign ('message', mes_sent_report);
				if(isset($this->request['questionreportDescription'])){
					ModelCollection::reportModel()->addReport("question", $question['id'], $this->request['questionreportDescription'], $_SESSION['user_id'], $question['category_id']);
				} else {
					ModelCollection::reportModel()->addReport("question", $question['id'], NULL, $_SESSION['user_id'], $question['category_id']);
				}
			}

			//set message
			if(isset($this->request['ratingReport']) && $GLOBALS ['loggedin']){
				$this->view->assign ('message', mes_sent_report);
				if(isset($this->request['ratingreportDescription'])){
					ModelCollection::reportModel()->addReport("rating", $question['id'], $this->request['ratingreportDescription'], $_SESSION['user_id'], $question['category_id']);
				} else {
					ModelCollection::reportModel()->addReport("rating", $question['id'], NULL, $_SESSION['user_id'], $question['category_id']);
				}
			}
		}

		private function loadQuizsessionView(){
			//case user makes quizsession
			if (isset ( $this->request ['session_id'] )) {

				$session_id = $this->request ['session_id'];
				$questionCount= count ( $_SESSION ['questions'. $session_id] );
				$currentCounter= $_SESSION ['counter'. $session_id];
				$progress = round ( 100 * ($currentCounter / $questionCount) );
				$sessionString = "&amp;session_id=" . $session_id . "";
				$this->view->assign ( 'progress', $progress );
				$this->view->assign ( 'questioncount', $questionCount );
				$this->view->assign ( 'currentcounter', $currentCounter );
				$weight= ModelCollection::quizModel()->getWeightOfQuestionInQuiz($this->questionID, $_SESSION['quiz_id'. $session_id] );
				$this->view->assign ( 'weight', $weight);
			} else {
				$sessionString = "";
			}
			$this->view->assign ( 'session_id', $sessionString );

			$linkToSolution = '?view=solution&id='.$this->questionID.$sessionString;
			$this->view->assign ( 'linkToSolution', $linkToSolution);
		}


	} // class QuestionController
} // namespace quizzenger\controller\controllers

?>
