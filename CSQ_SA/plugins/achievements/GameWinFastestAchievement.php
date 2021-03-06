<?php
	namespace quizzenger\plugins\achievements {
		use \SqlHelper as SqlHelper;
		use \quizzenger\logging\Log as Log;
		use \quizzenger\dispatching\UserEvent as UserEvent;
		use \quizzenger\achievements\IAchievement as IAchievement;
		use \quizzenger\model\ModelCollection as ModelCollection;

		class GameWinFastestAchievement implements IAchievement {

			private static function cmp($a, $b)
			{
				return ($a['totalTimeInSec'] < $b['totalTimeInSec']) ? -1 : (($a['totalTimeInSec'] > $b['totalTimeInSec']) ? 1 : 0);
			}

			public function grant(SqlHelper $database, UserEvent $event) {
				//Setup
				$memberCount = $event->get('member-count');
				$user = $event->user();

				$gamereport = ModelCollection::gameModel()->getGameReport($event->get('gameid'));

				//getWinners
				$winner = $gamereport[0]['user_id'];

				usort($gamereport, ['quizzenger\plugins\achievements\GameWinFastestAchievement', 'cmp']);
				$timeWinner = $gamereport[0]['user_id'];

				return $winner == $user && count($gamereport) >= $memberCount && $timeWinner == $user;
			}
		} // class GameWinAchievement
	} // namespace quizzenger\plugins\achievements
?>
