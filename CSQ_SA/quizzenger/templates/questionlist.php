<?php
	use \quizzenger\utilities\FormatUtility as FormatUtility;
?>
<?php if(isset($this->_['addedQuestion'])){?>
	<script>
		$(document).ready(function() {
		  var oTable = $('#tableQuestionList').dataTable();
		  oTable.fnPageChange( 'last' );
		} );
	</script>
<?php }?>

<?php if(isset($this->_['template']) && $this->_['template'] === 'questionlist'): ?>
<div class="panel panel-default">
	<div class="panel-heading">
		<b>Kategorie</b>
	</div>
<?php endif; ?>
<div class="panel-body">
	<form id="addToQuizForm" role="form" method="post" action="?view=questionlist">
		<input id="inputquizid" type="hidden" name="quiz_id" value="-1">
			<div class="table-responsive">
				<table class="table" id="tableQuestionList">
					<thead>
						<tr>
							<th>Frage</th>
							<th style="font-weight: normal;">Bewertung</th>
							<th style="font-weight: normal;">Schwierigkeit</th>
							<th style="font-weight: normal;">Durchführungen</th>
							<?php if($GLOBALS['loggedin']){ ?>
								<th style="font-weight: normal;" class="hidden-xs">Aktionen</th>
							<?php } ?>
						</tr>
					</thead>
					<tbody>
						<?php $i=-1; foreach ( $this->_ ['questions'] as $question ) {
								$i++;?>
							<tr<?php if(isset($this->_['addedQuestion']) && $this->_['addedQuestion']== $question['id'] ){
										echo(' class="success"');
									}?>>
								<td>
									<div id="questionTextSpan">
										<a href="?view=question&amp;id=<?php echo $question['id']; ?>" title="Frage anzeigen">
											<b><?= $out = strlen($question['questiontext']) > QUESTIONTEXT_CUTOFF_LENGTH ? htmlspecialchars(substr($question['questiontext'],0,QUESTIONTEXT_CUTOFF_LENGTH))." . . ." : htmlspecialchars($question['questiontext']); ?></b>
										</a>
										<?php if(strlen($question['questiontext']) > QUESTIONTEXT_CUTOFF_LENGTH){?>
											<span id="questionTextAddition">
											<?=htmlspecialchars($question['questiontext'])?>
										</span>
										<?php }?>
									</div>
									<?= $this->_['tags'][$i]?>
								</td>
								<td>
									<?php
										if(is_null($question['rating'])){
											echo("<span style='font-size:0;'>0</span>Keine Bewertung vorhanden");
										}else{
											echo(FormatUtility::formatNumber(($question['rating']), 1)." ".createStarsString($question['rating']));
										}
									?>
								</td>
								<td>
									<?= htmlspecialchars(createDifficultyString($question['difficulty'],$question['difficultycount']))?>
								</td>
								<td style="text-align: center">
									<?=htmlspecialchars($question['difficultycount'])?>
								</td>
								<?php if($GLOBALS['loggedin']){ ?>

										<td class="hidden-xs">

											<label> <input type="checkbox" name="addtoquiz[]" class="hidden-xs" value="<?php echo $question['id']; ?>"></label>&nbsp;

										<?php if(isset($this->_ ['myquestions']) && $this->_ ['myquestions']=="myquestions" ){?>
											<a class="remove-row" href="#" data-qid="<?php echo $question['id']; ?>" data-type="question" title="Löschen">
												<span class="glyphicon glyphicon-remove"></span>
											</a>
											&nbsp;
											<a href="?view=editquestion&amp;id=<?php echo $question['id']; ?>" title="Bearbeiten" >
												<span class="glyphicon glyphicon-edit"></span>
											</a>
										<?php } ?>
										</td>
								<?php
								}?>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>

				<hr class="hidden-xs">
				<?php if ($GLOBALS ['loggedin']): ?>
				<span class="table hidden-xs">
					<div class="dropdown pull-right">
						<button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown">
							Markierte Fragen zu folgendem Quiz hinzuf&uuml;gen <span class="caret"></span>
						</button>
						<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
							<?php foreach ( $this->_ ['quizzes'] as $quiz ) { ?>
							<li role="presentation">
								<a onclick="submitAddQuestionToQuiz(<?php echo $quiz['id']; ?>)" role="menuitem" tabindex="-1" href="javascript:void()">
									<?= htmlspecialchars($quiz['name']); ?>
								</a>
							</li><?php } ?>
							<li role="presentation" class="divider"></li>
							<li role="presentation">
								<a role="menuitem" tabindex="-1" data-toggle="modal" data-target="#newQuizDialog" href="javascript:void()">
									Neues Quiz
								</a>
							</li>
						</ul>
					</div>
				</span>
				<?php endif; ?>

		<?php if(isset($this->_ ['pointsearned'])):?>
			<div class="alert alertautoremove alert-success alert-dismissible centered" id="score-alert" role="alert">
				<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
				<strong><?=htmlspecialchars($this->_ ['pointsearned'])?></strong> Punkte!
			</div>
		<?php endif; ?>

		<div class="modal fade" id="newQuizDialog" tabindex="-1" role="dialog"
			aria-labelledby="myModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">
							<span aria-hidden="true">&times;</span><span class="sr-only">Close</span>
						</button>
						<h4 class="modal-title" id="myModalLabel">Neues Quiz erstellen</h4>
					</div>
					<div class="modal-body">
						<input type="text" autofocus="" required="required"
							placeholder="Quiz Name" name="quizname" id="quizname"
							class="form-control">
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Abbrechen</button>
						<button type="submit" class="btn btn-primary"
							formaction="./index.php?view=myquizzes">Speichern</button>
					</div>
				</div>
			</div>
		</div>
	</form>
	<div style="clear:both;margin-bottom:1em"></div>
	<div class="pull-right">
		<div>
			<a href="<?php echo APP_PATH . '/?view=questionexport&id=' . $_SESSION['user_id']; ?>">Fragen <u>exportieren</u> und herunterladen</a>
		</div>
		<div>
			<form action="<?php echo APP_PATH . '/?view=questionimport'; ?>" method="POST" enctype="multipart/form-data">
				<input id="question-import-upload" type="file" name="import" accept=".quizzenger" style="display:none">
				<input id="question-import-submit" type="submit" style="display:none">
				<a id="question-import-link" href="#">Fragen hochladen und <u>importieren</u></a>
			</form>
            <script>
				$(function() {
					$("#question-import-link").on('click', function(e) {
						e.preventDefault();
						$("#question-import-upload").trigger('click');
					});

					$("#question-import-upload").on('change', function() {
						$("#question-import-submit").trigger('click');
					});
				});
			</script>
		 </div>
	</div>
</div>
<?php if(isset($this->_['template']) && $this->_['template'] === 'questionlist'): ?>
</div>
<?php endif; ?>

<?php

function createDifficultyString($difficulty,$difficultycount){
	if($difficultycount<MIN_DIFFICULTY_COUNT_NEEDED_TO_SHOW){
		return "Noch unbekannt";
	}
	if($difficulty>=0 && $difficulty<=25){
		return "Sehr Schwer";
	}elseif($difficulty>25 && $difficulty<=50){
		return "Schwer";
	}elseif($difficulty>50 && $difficulty<=75){
		return "Moderat";
	}elseif($difficulty>75 && $difficulty<=100){
		return "Einfach";
	}
	return "Noch unbekannt";
}

function createStarsString($stars){
	$stars=round($stars);
	$maxStars=RATING_MAX_STARS;
	return str_repeat('<span class="glyphicon glyphicon-star hidden-xs"></span>',$stars).
		str_repeat('<span class="glyphicon glyphicon-star-empty hidden-xs"></span>',($maxStars-$stars));
}
?>
