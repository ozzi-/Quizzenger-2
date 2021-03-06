<?php

namespace quizzenger\model {
	use \quizzenger\logging\Log as log;
	use \SqlHelper as SqlHelper;

	/**
	 * 	@author Simon Zingg
	 *	The GameModel provides data which is used for games.
	 *	Each method which modifies the database (INSERT, UPDATE, DELETE) checks if input parameters are set.
	 */
	class GameModel {
		private $mysqli;

		public function __construct(SqlHelper $mysqli) {
			$this->mysqli = $mysqli;
		}

		/**
		 * Adds a new game to a given quiz.
		 * @precondition Please check if current user has permission to generate a game for this quiz.
		 * @param $quiz_id Quiz Id
		 * @param $name Gamename
		 * @param $duration Gameduration as string in Format HH:MM:SS
		 * @return Returns new gamesession_id if successful, else null
		*/
		public function getNewGameSessionId($quiz_id, $name, $duration){
			if(isset($quiz_id, $name, $duration)){
				log::info('Getting New Game Session for Quiz-ID :'.$quiz_id);
				return $this->mysqli->s_insert("INSERT INTO gamesession (name, quiz_id, duration) VALUES (?, ?, ?)",array('s','i','s'),array($name, $quiz_id, $duration));
			}
			else{
				return null;
			}
		}

		/**
		 * Removes a game
		 * @precondition Please check if current user has permission to remove this game.
		 * @param $game_id Game Id
		 * @return Returns true if successful, else null
		 */
		public function removeGame($game_id){
			if(isset($game_id) && $this->userIDhasPermissionOnGameId($_SESSION['user_id'], $game_id)){
				log::info('Removing Game with ID :'.$game_id);
				$this->mysqli->s_query('DELETE FROM gamesession WHERE id = ?',['i'], [$game_id]);
				return true;
			}
			else{
				log::warning('Unauthorized try to remove game id :'.$game_id);
				return false;
			}
		}

		/**
		 * Gets the information which normally should be stored in the $_SESSION. This methode can be used to restore gamesessions for users
		 * @param $user_id User Id
		 * @param $game_id Game Id
		 * @return Returns an array with the 'gamecounter' and the 'gamequestions'
		*/
		public function getSessionData($user_id, $game_id){
			$result = $this->mysqli->s_query('SELECT COUNT(id) AS gamecounter FROM `questionperformance` '
				.' WHERE gamesession_id = ? AND user_id = ?', ['i','i'], [$game_id, $user_id]);
			$gamecounter = $this->mysqli->getSingleResult($result)['gamecounter'];


			$result = $this->mysqli->s_query('SELECT q.question_id FROM gamesession g, quiztoquestion q '
				.' WHERE g.quiz_id = q.quiz_id AND g.id = ?', ['i'], [$game_id]);
			$gamequestions = [];
			while ( $row = $result->fetch_assoc () ) {
				array_push($gamequestions, $row['question_id']);
			}

			return [ 'gamecounter' => $gamecounter, 'gamequestions' => $gamequestions ];
		}


		/**
		 * Starts the Game
		 * This method checks if user has permission on this game
		 * The starttime can only once be set
		 * @param $game_id
		 * @return Returns old value of starttime on success, else returns false if user is unauthorized
		 */
		public function startGame($game_id){
			if(isset($game_id) && $this->userIDhasPermissionOnGameId($_SESSION ['user_id'], $game_id)){
				log::info('Start Game with ID :'.$game_id);
				$oldValue = $this->mysqli->s_query('SELECT starttime FROM gamesession WHERE id = ?',['i'],[$game_id]);
				$update = $this->mysqli->s_query('UPDATE gamesession SET starttime = '
						.' CASE WHEN starttime IS NULL THEN CURRENT_TIMESTAMP ELSE starttime END'
						.' WHERE id = ?',['i'],[$game_id]);

				return $this->mysqli->getSingleResult($oldValue)['starttime'];
			}
			else{
				log::warning('Unauthorized try to start game id :'.$game_id);
				return false;
			}
		}

		/**
		 * Sets the endtime of a game
		 * This method checks if user has permission on this game
		 * The endtime can only be set once and is not bigger than the calculated endtime (starttime+duration)
		 * @param $game_id
		 * @return Returns old value of endtime on success, else returns false
		 */
		public function setGameend($game_id){
			if(isset($game_id) && $this->isGameMember($_SESSION ['user_id'], $game_id)){
				log::info('Stop Game with ID :'.$game_id);
				$oldValue = $this->mysqli->s_query('SELECT endtime FROM gamesession WHERE id = ?',['i'],[$game_id]);
				$update = $this->mysqli->s_query('UPDATE gamesession SET endtime = '
						.' CASE WHEN endtime IS NULL THEN LEAST(CURRENT_TIMESTAMP, ADDTIME(starttime, duration)) ELSE endtime END'
						.' WHERE id = ?',['i'],[$game_id]);

				return $this->mysqli->getSingleResult($oldValue)['endtime'];
			}
			else{
				log::warning('Unauthorized try to setGameend id :'.$game_id);
				return false;
			}
		}

		/**
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

		/**
		 * Gets all members of a game
		 */
		public function getGameMembersByGameId($game_id){
			$result = $this->mysqli->s_query("SELECT g.user_id, u.username as member FROM gamemember g, user u WHERE g.gamesession_id = ? AND g.user_id = u.id",array('i'),array($game_id));
			return $this->mysqli->getQueryResultArray($result);
		}

		/**
		 * Tells weather the user is a member of the game.
		 */
		public function isGameMember($user_id, $game_id){
			$result = $this->mysqli->s_query("SELECT * FROM gamemember WHERE gamesession_id = ? AND user_id = ?",['i','i'],[$game_id, $user_id]);
			return $result->num_rows > 0;
		}

		/**
		 * Gets game info. For more information about the columns consult the query
		 */
		public function getGameInfoByGameId($game_id){
			$result = $this->mysqli->s_query("SELECT g.id as game_id, g.name as gamename, created_on, "
					." starttime, endtime, duration, ADDTIME(starttime, duration) as calcEndtime, quiz_id, "
					."user_id as owner_id, q.name as quizname, created as quiz_created_on FROM gamesession g, quiz q "
					."WHERE g.id = ? AND g.quiz_id = q.id",['i'],[$game_id]);
			return $this->mysqli->getSingleResult($result);
		}

		/**
		 * Gets all games where a user is game host
		 */
		public function getHostedGamesByUser($user_id){
			$result = $this->mysqli->s_query('SELECT g.id, g.name, session.members, g.starttime, g.duration FROM gamesession g '.
					'JOIN quiz q ON g.quiz_id = q.id '.
					'LEFT JOIN (SELECT gamesession_id, COUNT(user_id) AS members FROM gamemember '.
					'GROUP BY gamesession_id) AS session ON g.id = session.gamesession_id '.
					'WHERE q.user_id = ?',['i'],[$user_id]);
			return $this->mysqli->getQueryResultArray($result);
		}

		/**
		 * Gets all games where a user is game member
		 */
		public function getParticipatedGamesByUser($user_id){
			$result = $this->mysqli->s_query('SELECT g.id, g.name, session.members, g.starttime, g.duration FROM gamesession g '.
					'LEFT JOIN (SELECT gamesession_id, COUNT(user_id) AS members FROM gamemember '.
					'GROUP BY gamesession_id) AS session ON g.id = session.gamesession_id '.
					'WHERE g.id IN (SELECT gamesession_id FROM gamemember WHERE user_id = ?) ',['i'],[$user_id]);
			return $this->mysqli->getQueryResultArray($result);
		}

		/**
		 * Gets all question details for a game. Columns are questiontext, answeredTotal, answeredCorrect, answeredWrong, weight
		 */
		public function getQuestionDetailsByGame($game_id){
			$result = $this->mysqli->s_query('SELECT q.questiontext, COUNT(qp.question_id) AS answeredTotal, '
					.' SUM(CASE WHEN questionCorrect = 100 THEN 1 ELSE 0 END) AS answeredCorrect, '
					.' SUM(CASE WHEN questionCorrect = 0 THEN 1 ELSE 0 END) answeredWrong, weight'
					.' FROM questionperformance qp '
					.' RIGHT JOIN ('
						.' SELECT gamesession.id, weight, question_id'
						.' FROM gamesession, quiztoquestion'
						.' WHERE gamesession.quiz_id = quiztoquestion.quiz_id AND gamesession.id = ?) AS qtq'
					.' ON qtq.question_id = qp.question_id AND qtq.id = qp.gamesession_id'
					.' JOIN question q ON q.id = qtq.question_id'
					.' WHERE qtq.id = ?'
					.' GROUP BY qtq.question_id', ['i','i'], [$game_id, $game_id]);
			return $this->mysqli->getQueryResultArray($result);
		}

		/**
		 * Gets all open games.
		 */
		public function getOpenGames(){
			$result = $this->mysqli->query('SELECT g.id, g.name, u.username, session.members, g.duration FROM gamesession g '.
					'JOIN quiz q ON g.quiz_id = q.id '.
					'JOIN user u ON q.user_id = u.id '.
					'LEFT JOIN (SELECT gamesession_id, COUNT(user_id) AS members FROM gamemember '.
					'GROUP BY gamesession_id) AS session ON g.id = session.gamesession_id '.
					'WHERE g.starttime IS NULL');
			return $this->mysqli->getQueryResultArray($result);
		}

		/**
		 * Gets all active games by user id
		 */
		public function getActiveGamesByUser($user_id){
			$result = $this->mysqli->s_query('SELECT g.id, g.name, u.username, session.members, '.
					'g.duration, g.starttime, ADDTIME(g.starttime, g.duration) as calcEndtime FROM gamesession g '.
					'JOIN quiz q ON g.quiz_id = q.id '.
					'JOIN user u ON q.user_id = u.id '.
					'JOIN gamemember m ON g.id = m.gamesession_id '.
					'LEFT JOIN (SELECT gamesession_id, COUNT(user_id) AS members FROM gamemember '.
					'GROUP BY gamesession_id) AS session ON g.id = session.gamesession_id '.
					'WHERE m.user_id = ? AND g.starttime IS NOT NULL AND g.endtime IS NULL',['i'],[$user_id]);
			return $this->mysqli->getQueryResultArray($result);
		}

		/**
		 * User join a game.
		 * @return Returns 0 if successful. Returns null when no input parameters are passed.
		 */
		public function userJoinGame($user_id, $game_id){
			if(! isset($user_id, $game_id)) return null;
			log::info('User joins game ID:'.$game_id);
			return $this->mysqli->s_insert('INSERT IGNORE INTO gamemember (gamesession_id, user_id) VALUES (?, ?)',['i','i'],[$game_id, $user_id]);
		}

		/**
		 * User leave a game
		* @return Always returns false, because query didn't get any results when delete
		*/
		public function userLeaveGame($user_id, $game_id){
			if(! isset($user_id, $game_id)) return false;
			log::info('User leaves game ID:'.$game_id);
			return $this->mysqli->s_query("DELETE FROM gamemember WHERE gamesession_id=? AND user_id=?",['i','i'],[$game_id, $user_id]);
		}

		/**
		 * @return Returns true when starttime, otherwise false
		 */
		public function gameHasStarted($game_id){
			$result = $this->mysqli->s_query("SELECT starttime FROM gamesession WHERE id=?",['i'],[$game_id]);
			$resultArray = $this->mysqli->getQueryResultArray($result);
			if($result->num_rows > 0){
				return isset($resultArray[0]['starttime']);
			}
			else return false;
		}

		/**
		 * Checks if user is permitted to modify the given game
		 * @return Returns true if permitted, else false
		 */
		public function userIDhasPermissionOnGameId($user_id, $game_id){
			$gameOwner = $this->getGameOwnerByGameId($game_id);
			if($gameOwner == null) return null;
			else return $gameOwner == $user_id;
		}

		/**
		 * Gets the game report sorted by rank.
		 * @return array with columns questionAnswered, questionAnsweredCorrect, totalQuestions, totalTimeInSec, timePerQuestion, user_id, username
		 */
		public function getGameReport($game_id){
			$result = $this->mysqli->s_query('SELECT  @rank:=@rank+1 AS rank, questionAnswered, questionAnsweredCorrect, totalQuestions, user_id, username,'
					.' totalTimeInSec, timePerQuestion, endtime, starttime, userEndtime FROM'
					.' (SELECT @rank := 0, questionAnswered, questionAnsweredCorrect, totalQuestions, user_id, username,'
					.' totalTimeInSec, totalTimeInSec/questionAnsweredCount AS timePerQuestion, endtime, starttime, userEndtime FROM'
					.' (SELECT questionAnswered, questionAnsweredCorrect, totalQuestions, user_id, username, questionAnsweredCount,'
					.' TIMESTAMPDIFF(SECOND,starttime,(CASE WHEN (endtime IS NOT NULL AND questionAnswered <> totalQuestions)'
						.' THEN endtime ELSE '
							.' (CASE WHEN (endtime IS NULL AND questionAnswered <> totalQuestions) THEN CURRENT_TIMESTAMP ELSE userEndtime END ) '
						.' END)) AS totalTimeInSec, starttime, userEndtime, endtime'
					.' FROM'
						.' (SELECT SUM(CASE WHEN weight IS NOT NULL THEN weight ELSE 0 END) AS questionAnswered,'
						.' SUM(CASE WHEN questionCorrect = 100 THEN weight ELSE 0 END) AS questionAnsweredCorrect,'
						.' total.totalQuestions, u.id as user_id, u.username, COUNT(q.gamesession_id) as questionAnsweredCount, '
						.' total.starttime, time.userEndtime, '
						.' (CASE WHEN total.endtime > total.calcEndtime THEN total.calcEndtime ELSE total.endtime END) as endtime FROM gamemember m'
						.' LEFT JOIN questionperformance q ON q.gamesession_id = m.gamesession_id AND q.user_id = m.user_id'
						.' LEFT JOIN user u ON u.id = m.user_id'
						.' LEFT JOIN ('
							.' SELECT gamesession.id, gamesession.quiz_id, gamesession.endtime, '
							.' gamesession.starttime, ADDTIME(starttime, duration) as calcEndtime, SUM(weight) AS totalQuestions '
							.' FROM gamesession, quiztoquestion'
							.' WHERE gamesession.quiz_id = quiztoquestion.quiz_id AND gamesession.id = ?) AS total'
						.' ON total.id = m.gamesession_id'
						.' LEFT JOIN quiztoquestion qq ON qq.quiz_id = total.quiz_id AND qq.question_id = q.question_id'
						.' LEFT JOIN ('
							.' SELECT user_id, MAX(timestamp) AS userEndtime'
							.' FROM questionperformance q, gamesession g'
							.' WHERE q.gamesession_id = g.id AND q.gamesession_id = ?'
							.' GROUP BY q.user_id) AS time'
						.' ON time.user_id = m.user_id'
						.' WHERE m.gamesession_id = ?'
						.' GROUP BY m.user_id) as subsubQuery)'
					.' as subQuery'
					.' ORDER BY questionAnsweredCorrect DESC, timePerQuestion ASC) as Query',['i','i','i'],[$game_id,$game_id,$game_id]);
			return $this->mysqli->getQueryResultArray($result);
		}

	} // class GameModel
} // namespace quizzenger\model

?>