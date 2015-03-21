<?php

namespace quizzenger\gamification\model {
	use \stdClass as stdClass;
	use \SplEnum as SplEnum;
	use \mysqli as mysqli;
	use \quizzenger\logging\Log as Log;
	use \SqlHelper as SqlHelper;
	use \QuizModel as QuizModel;

	/*	@author Simon Zingg
	 *	The GameModel provides data which is used for games.
	 */
	class GameModel {
		private $mysqli;
		private $quizModel;

		public function __construct(SqlHelper $mysqli, QuizModel $quizModel) {
			$this->mysqli = $mysqli;
			$this->quizModel = $quizModel;
		}
		
		/*
		 * Adds a new game to a given quiz.
		 * Checks if current user has permission to generate a game for this quiz.
		 * @param $quiz_id
		 * @return Returns new gamesession_id if successful, else null 
		*/
		public function getNewGameSessionId($quiz_id, $name){
			if(isset($quiz_id, $name) && $this->quizModel->userIDhasPermissionOnQuizId($quiz_id,$_SESSION ['user_id'])){
				log::info('Getting New Game Session for Quiz-ID :'.$quiz_id);
				return $this->mysqli->s_insert("INSERT INTO gamesession (name, quiz_id) VALUES (?, ?)",array('s','i'),array($name, $quiz_id));
			}
			else{
				log::warning('Unauthorized try to add new Gamesession for Quiz-ID :'.$question_id)
				return null;
			}
		}
		
		public function startGame($game_id){
			log::info('Start Game with ID :'.$game_id)
			$this->mysqli->s_query("UPDATE gamesession SET has_started=CURRENT_TIMESTAMP WHERE id=?",array('i'),array($game_id));
		}
		
		/*
		 * @return Returns username of the gameowner if successful, else null
		 */
		public function getGameOwnerByGameId($game_id){
			$result = $this->mysqli->s_query("SELECT user_id FROM gamesession g, quiz q WHERE g.quiz_id = q.id and g.id=?",array('i'),array($game_id));
			$resultArray = $this->mysqli->getQueryResultArray($result);
			if($result->num_rows > 0 && isset($resultArray[0]['user_id'])){
				return $resultArray[0]['user_id'];
			}
			else return null;
		}
		
		/*
		 * Gets all open games.
		 * @return Always returns false, because query didn't get any results when delete
		 */
		public function getOpenGames(){
			$result = $this->mysqli->query('SELECT g.name, u.username, session.members FROM gamesession g '.
					'JOIN quiz q ON g.quiz_id = q.id '.
					'JOIN user u ON q.user_id = u.id '.
					'LEFT JOIN (SELECT gamesession_id, count(user_id) AS members FROM gamemember '.
					'GROUP BY gamesession_id) AS session ON g.id = session.gamesession_id '.
					'WHERE g.has_started IS NULL');
			return $this->mysqli->getQueryResultArray($result);
		}
		/*
		Select g.name, u.username, session.members from gamesession g
		join quiz q on g.quiz_id = q.id
		join user u on q.user_id = u.id
		left join (select gamesession_id, count(user_id) as members from gamemember group by gamesession_id) as session  on g.id = session.gamesession_id
		where g.has_started is NULL;
		*/
		
		/*
		 * User join a game.
		 *  
		 * @return Returns 0 if successful else you will be redirected to the error page because entry already exists //TODO:
		 */
		public function userJoinGame($user_id, $game_id){
			log::info('User joins game ID:'.$game_id);
			return $this->mysqli->s_insert("INSERT INTO gamemember (gamesession_id, user_id) VALUES (?, ?)",array('i','i'),array($game_id, $user_id));
		}
		
		/*
		* @return Always returns false, because query didn't get any results when delete
		*/
		public function userLeaveGame($user_id, $game_id){
			log::info('User leaves game ID:'.$game_id);
			return $this->mysqli->s_query("DELETE FROM gamemember WHERE gamesession_id=? AND user_id=?",array('i','i'),array($game_id, $user_id));
		}
		/*
		 * Checks if user is permitted to modify the given game
		 * @return Returns true if permitted, else false
		 */
		public function userIDhasPermissionOnGameId($user_id, $game_id){
			$gameOwner = $this->getGameOwnerByGameId($game_id);
			if($gameOwner == null) return null;
			else return $gameOwner == $user_id;
		}
		
		
	} // class GameModel
} // namespace quizzenger\gamification\model

?>