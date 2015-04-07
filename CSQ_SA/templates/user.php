<?php
	$user = $this->_ ['user'];
	$questionCount = $this->_ ['questioncount'];
	$quizCount = $this->_ ['quizcount'];
	$userScore = $this->_ ['userscore'];
	$categoryscores = $this->_ ['categoryscores'];
	$moderatedCategories = $this->_ ['moderatedcategories'];
	$absolvedCount = $this->_ ['absolvedcount'];
	$achievementList = $this->_['achievementlist'];
	$rankList = $this->_['ranklist'];

	if (isset($this->_['message'])){
		echo '<div class="alert alert-info" role="alert"><a href="#" class="close" data-dismiss="alert">&times;</a>'.htmlspecialchars($this->_['message']).'</div>';
	}

	$ranks = [];
	$activeRanks = [];
	while($current = $rankList->fetch_object()):
		$ranks[] = $current;
		if($userScore >= $current->threshold){
			$activeRanks[$current->threshold] = $current;
		}
	endwhile;
	$userRankIndex = max(array_keys($activeRanks));
	$userRank = $activeRanks[$userRankIndex];
?>
<div class="panel panel-default">
	<div class="panel-heading"><strong><?php echo htmlspecialchars($user['username']);?></strong>
		<?php
			if(!$this->_ ['alreadyreported']){?>
			<button type="button" class="btn btn-link btn-xs pull-right" data-toggle="modal" data-target="#newUserReportDialog">Benutzer melden</button>
			<?php }
		?>
	</div>
		<div class="panel-body">
			<div class="row">
  				<div class="col-md-6">
					<h4><img alt="ribbon" src="<?=APP_PATH?>/templates/img/ribbon.png"> Punkte pro Kategorie</h4>
					<table style="width:100%">
						<?php
							foreach($categoryscores as $catScore){
								echo('<tr><td width="180px">'.htmlspecialchars($catScore['name'])."</td>");
								echo("<td>".'<span class="badge alert-success">'.htmlspecialchars($catScore['score'])."</span></td></tr>");
							}
						?>
					</table>
					<br><h4>Gesamtpunktezahl <?= ' <span class="badge alert-success">'.htmlspecialchars($userScore).'</span>'?></h4>
					<div class="btn-group hidden-lg hidden-md" >
						<h4 class="pull-left">Rang </h4>
						<div class="rank rank-position-neutral"  data-tooltip-title="<?php echo htmlspecialchars($userRank->name); ?>"
							data-tooltip-text="<?php echo htmlspecialchars("{$userRank->threshold} Punkte"); ?>">
							<div class="point point-rank clickable point-active">
								<img src="<?php echo RANK_PATH . "/{$userRank->image}." . RANK_IMAGE_EXTENSION; ?>"></img>
							</div>
						</div>
						<br>
					</div>
				</div>
  				<div class="col-md-6">
					<?php
					if($user['superuser']){
						echo('<h4 style="color:red"><img alt="superuser" src="'.htmlspecialchars(APP_PATH).'/templates/img/superuser.png"> Ist Superuser</h4><br>');
					}
					if($moderatedCategories!=null){
						echo('<h4><img alt="moderator" src="'.htmlspecialchars(APP_PATH).'/templates/img/moderator.png"> Moderator in folgenden Kategorien</h4>');
						foreach($moderatedCategories as $modCat){
  							echo(htmlspecialchars($modCat['name'])."<br>");
						}
						echo("<hr>");
					} ?>
					<a href="?view=questionlist&amp;user=<?php echo $user['id']?>">
						<h4>
							Alle Fragen des Users <span class="badge"><?php echo $questionCount; ?></span>
						</h4>
					</a>
					Anzahl beantworteter Fragen: <span class="badge"><?= $absolvedCount ?></span>
				</div>
			</div>
			<div class="scrollable">
				<div class="rankbar hidden-xs hidden-sm">
					<?php
					foreach($ranks as $current){
					?>
						<div class="rank" data-tooltip-title="<?php echo htmlspecialchars($current->name); ?>"
							data-tooltip-text="<?php echo htmlspecialchars("{$current->threshold} Punkte"); ?>">
							<div class="point point-rank clickable <?php echo $userScore >= $current->threshold ? 'point-active' : ''; ?>">
								<img src="<?php echo RANK_PATH . "/{$current->image}." . RANK_IMAGE_EXTENSION; ?>"></img>
							</div>
						</div>
					<?php } ?>
				</div>
			</div>
			<hr>
			<!--  -->
			<div id="achievements">
				<h4>Achievements</h4>
				<?php while($current = $achievementList->fetch_object()): ?>
					<div class="point point-achievement clickable <?php echo $current->achieved ? ' point-active' : ''; ?>"
						data-tooltip-title="<?php echo '★ ' . htmlspecialchars($current->name) . ' ★'; ?>"
						data-tooltip-text="<?php echo htmlspecialchars($current->description); ?>">
						<img src="<?php echo ACHIEVEMENT_PATH . "/{$current->image}." . ACHIEVEMENT_IMAGE_EXTENSION; ?>" />
					</div>
				<?php endwhile; ?>
			</div>
		</div>
	</div>
	<?php
		if(isset($_SESSION ['user_id']) && $user['id']== $_SESSION ['user_id'] && $GLOBALS['loggedin'] ){?>
			<div class="panel panel-default">
				<div class="panel-heading clickable">
					<a class="" data-toggle="collapse" data-target="#collapseChangePasswort" >
						<strong>Passwort ändern</strong>
					</a>
				</div>
				<div id="collapseChangePassword" class="panel-collapse collapse">
					<div class="panel-body">
						<strong>Email:</strong> <?php echo htmlspecialchars($user['email']);?><br>
						<br>
						<form class="change_password_form" action="./index.php?view=processChangepassword"  method="post" id="change_password_form" name="change_password_form">
			          		<div class="form-group">
			          			<input type="hidden" class="form-control" id="change_password_form_email" name="change_password_form_email" value="<?php echo $_SESSION['email']; // we need this so the browser pw manager knows which account we are changing password for, else he might ask?>" />
			          		</div>
			          		<div class="form-group">
			          			<input class="form-control" type="password" placeholder="Passwort" name="change_password_form_password" id="change_password_form_password"/><br>
			          		</div>
							<div class="form-group">
			          			<input class="form-control" placeholder="Passwort wiederholen" type="password" name="change_password_form_password_confirm" id="change_password_form_password_confirm" /><br>
			          		</div>
			           		<button class="btn btn btn-primary" value="ChangePW" type="submit" />
			           			Passwort ändern
			           		</button>
		              	</form>
			   		</div>
			   	</div>
		   	</div>
		<?php }?>

<form role="form" method="post">
	<input type="hidden" name="userReport" value="1">
	<div class="modal fade" id="newUserReportDialog" tabindex="-1" role="dialog"
		aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">
						<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
					</button>
					<h4 class="modal-title" id="myModalLabel">Benutzer melden</h4>
				</div>
				<div class="modal-body">
					<input type="text" autofocus="" placeholder="Begr&uuml;ndung der Meldung" name="userreportDescription" id="usernreport"
						class="form-control">
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
					<button type="submit" class="btn btn-primary">Senden</button>
				</div>
			</div>
		</div>
	</div>
</form>
